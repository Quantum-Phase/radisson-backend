<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\StudentBatch;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFeeDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class UserController extends Controller
{

    public function searchUser(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchuser = User::where('name', 'LIKE', '%' . $search . '%')
                ->orwhere('phoneNo', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchuser);
        }
    }

    public function showUser(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $blockId = $request->blockId;
        $excludedBatchId = $request->excludedBatchId;
        $duesPaidStudent = filter_var($request->input('duesPaidStudent'), FILTER_VALIDATE_BOOLEAN);

        // Convert comma-separated string to an array if necessary
        $roles = is_string($request->query('role')) ? explode(',', $request->query('role')) : [$request->query('role')];

        $results = User::select(
            'users.userId',
            'users.name',
            'users.email',
            'users.student_code',
            'users.phoneNo',
            'users.dob',
            'users.gender',
            'users.profileImg',
            'users.role',
            'users.blockId',
            'users.permanentAddress',
            'users.temporaryAddress',
            'users.emergencyContactNo',
            'users.startDate',
            'users.parents_name',
            'users.created_at'
        )
            ->orderBy('created_at', 'desc')
            ->when($roles, function ($query, $roles) {
                return $query->whereIn('users.role', $roles);
            })
            ->when($blockId, function ($query, $blockId) {
                return $query->where('users.blockId', $blockId);
            })
            // Add search filtering based on search query
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('users.name', 'like', "%$search%")
                        ->orWhere('users.email', 'like', "%$search%")
                        ->orWhere('users.phoneNo', 'like', "%$search%")
                        ->orWhere('users.role', 'like', "%$search%")
                        ->orWhere('users.student_code', 'like', "%$search%");
                });
            })
            // Exclude students from the specified batch
            ->when($excludedBatchId, function ($query, $excludedBatchId) {
                return $query->whereNotIn('users.userId', function ($subquery) use ($excludedBatchId) {
                    $subquery->select('userId')
                        ->from('student_batches')
                        ->where('batchId', $excludedBatchId)
                        ->where('deleted_at', null);
                });
            }) // Filter for students who have paid all dues if duesPaidStudent is true
            ->when($duesPaidStudent, function ($query) {
                return $query->whereDoesntHave('userFeeDetail', function ($subquery) {
                    $subquery->where('remainingAmount', '>', 0);
                });
            });

        if ($request->has('limit')) {
            $results = $results->paginate($limit);
        } else {
            $results = $results->get();
        }
        return response()->json($results);
    }

    public function insertUser(Request $request)
    {
        $request->validate([
            'start_date' => 'date_format:Y-m-d',
            'date' => 'date_format:Y-m-d',
            'email' => [
                Rule::unique('users', 'email')->whereNull('deleted_at'),
                $request->role !== 'student' ? 'required' : 'nullable',
            ],
            'phoneNo' => Rule::unique('users', 'phoneNo')->whereNull('deleted_at'),
        ]);

        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');
        $formattedDob = Carbon::parse($request->date)->format('Y-m-d');

        $insertUser = new User;
        $insertUser->name = $request->name;
        $insertUser->email = $request->email;
        $insertUser->password = Hash::make($request->password);
        $insertUser->role = $request->role;
        $insertUser->phoneNo = $request->phoneNo;
        $insertUser->dob = $formattedDob;
        $insertUser->gender = $request->gender;
        $insertUser->permanentAddress = $request->paddress;
        $insertUser->temporaryAddress = $request->taddress;
        // $insertUser->startDate = $formattedStartDate;
        $insertUser->emergencyContactNo = $request->econtact;
        $insertUser->parents_name = $request->parents_name;

        if ($request->hasFile('profileimg')) {
            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $insertUser->profileImg = 'profileImage/' . $imageName;
        }

        if ($insertUser->hasRole('accountant')) {
            $insertUser->blockId = $request->blockId;
        }

        $insertUser->save();

        if ($request->role == 'student') {

            $studentCode = 'STD-' . $insertUser->userId;

            // Update the student_code for the user
            $insertUser->student_code = $studentCode;
            $insertUser->save();
        }

        return response()->json('User Inserted Sucessfully');
    }

    public function deleteUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json('User not found', 404);
        }

        if ($user->role !== "student") {
            $payment = Payment::where('transaction_by', $userId)->first();
            if ($payment) {
                return response()->json(['message' => 'Cannot delete user'], 422);
            }
        }

        if ($user->hasRole('mentor')) {
            if ($user->mentorBatches->count() > 0) {
                return response()->json(['message' => 'Cannot delete mentor, they are assigned to batches'], 422);
            }
        }

        if ($user->hasRole('student')) {
            $job = $user->job;
            if ($job->count() > 0) {
                return response()->json(['message' => 'Cannot delete student, they are assigned to internship/job'], 422);
            }

            $batch = $user->batches;
            if ($batch->count() > 0) {
                return response()->json(['message' => 'Cannot delete student, they are assigned to batches'], 422);
            }

            $payment = Payment::where('payed_by', $userId)->first();

            if ($payment) {
                return response()->json(['message' => 'Cannot delete student, as they have made an payment.'], 422);
            }
        }

        $data = User::find($userId);
        $data->deleted_at = now();
        $data->save();

        if ($user->hasRole('student')) {
            StudentBatch::where('userId', $userId)->delete();
            UserFeeDetail::where('userId', $userId)->delete();
        }

        return response()->json('User Deleted Sucessfully');
    }

    public function singleUser($userId)
    {
        $data = User::with(['studentBatch.batch'])->find($userId);

        if (!$data) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $response = (object)[
            'userId' => $data->userId,
            'student_code' => $data->student_code,
            'name' => $data->name,
            'email' => $data->email,
            'phoneNo' => $data->phoneNo,
            'dob' => $data->dob,
            'gender' => $data->gender,
            'role' => $data->role,
            'profileImg' => $data->profileImg,
            'permanentAddress' => $data->permanentAddress,
            'temporaryAddress' => $data->temporaryAddress,
            'emergencyContactNo' => $data->emergencyContactNo,
            'parents_name' => $data->parents_name,
            'block' => $data->block()->first(),
            'created_at' => $data->created_at,

            'batch' => $data->studentBatch->first() ? [
                'batchId' => $data->studentBatch->first()->batchId,
                'name' => $data->studentBatch->first()->batch->name ?? 'N/A',
                'course' => $data->studentBatch->first() ? ($data->studentBatch->first()->batch ? $data->studentBatch->first()->batch->load('course')->course : null) : null,
                'start_date' => $data->studentBatch->first()->batch->start_date ?? null,
                'end_date' => $data->studentBatch->first()->batch->end_date ?? null,
                'time' => $data->studentBatch->first()->batch->time ?? null,
            ] : null,

            'internshipJob' => $data->job->first() ? [
                'jobId' =>  $data->job->first()->jobId,
                'paid_amount' =>  $data->job->first()->paid_amount,
                'start_date' =>  $data->job->first()->start_date,
                'type' =>  $data->job->first()->type,
                'company' => [
                    'companyId' =>  $data->job->first()->company->companyId,
                    'name' =>  $data->job->first()->company->name,
                ],
                'department' => [
                    'departmentId' =>  $data->job->first()->department->departmentId,
                    'name' =>  $data->job->first()->department->name,
                ],
            ] : null,

            'userFeeDetail' => $data->userFeeDetail->first() ? [
                'userFeeDetailId' => $data->userFeeDetail->first() ? $data->userFeeDetail->first()->userFeeDetailId : null,
                'amountToBePaid' => $data->userFeeDetail->first() ? $data->userFeeDetail->first()->amountToBePaid : null,
                'totalAmountPaid' => $data->userFeeDetail->first() ? $data->userFeeDetail->first()->totalAmountPaid : null,
                'remainingAmount' => $data->userFeeDetail->first() ? $data->userFeeDetail->first()->remainingAmount : null,
            ] : null,
        ];

        return response()->json($response);
    }

    public function updateUser(Request $request, $userId)
    {
        Log::info($request->all()); // Log the request data
        $request->validate([
            'name' => 'required|string',
            'email' => [
                Rule::unique('users', 'email')->ignore($userId, 'userId')->whereNull('deleted_at'),
            ],
            'phoneNo' => [
                Rule::unique('users', 'phoneNo')->ignore($userId, 'userId')->whereNull('deleted_at'),
            ],
        ]);

        $data = User::find($userId);
        $data->name = $request->name;
        $data->email = $request->email;
        $data->phoneNo = $request->phoneNo;
        $data->dob = $request->date;
        $data->gender = $request->gender;
        $data->permanentAddress = $request->paddress;
        $data->temporaryAddress = $request->taddress;
        $data->emergencyContactNo = $request->econtact;
        $data->parents_name = $request->parents_name;
        $data->startDate = $request->start_date;
        // $data->time = $request->time;

        if ($data->hasRole('accountant')) {
            $data->blockId = $request->blockId;
        }

        if ($request->hasFile('profileimg')) {

            if ($data->profileImg && file_exists(public_path($data->profileImg))) {
                unlink(public_path($data->profileImg));
            }


            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $data->profileImg = 'profileImage/' . $imageName;
        }

        $data->update();

        // $studentsBatch = StudentBatch::where('userId', $userId)->first();
        // if ($studentsBatch) {
        //     $studentsBatch->batchId = $request->batchId;
        //     $studentsBatch->update();
        // } else {

        //     $studentBatch = new StudentBatch;
        //     $studentBatch->batchId = $request->batchId;
        //     $studentBatch->userId = $userId;
        //     $studentBatch->save();
        // }

        return response()->json('User Updated Sucessfully');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $userId = $user->userId;

        $request->validate([
            'email' => [
                Rule::unique('users', 'email')->ignore($userId, 'userId')->whereNull('deleted_at'),
            ],
            'phoneNo' => [
                Rule::unique('users', 'phoneNo')->ignore($userId, 'userId')->whereNull('deleted_at'),
            ],
        ]);
        $userdata = User::with(['studentBatch.batch'])->find($user->userId);

        if (!$userdata) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phoneNo' => 'required|string',
            'gender' => 'required|string',
            'profileimg' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userdata->name = $request->name;
        $userdata->email = $request->email;
        $userdata->phoneNo = $request->phoneNo;
        $userdata->gender = $request->gender;

        if ($request->hasFile('profileimg')) {
            if ($userdata->profileImg && file_exists(public_path($userdata->profileImg))) {
                unlink(public_path($userdata->profileImg));
            }

            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $userdata->profileImg = 'profileImage/' . $imageName;
        }

        if ($userdata) {
            $userdata->save();
        }

        $response = (object)[
            'userId' => $userdata->userId,
            'name' => $userdata->name,
            'email' => $userdata->email,
            'phoneNo' => $userdata->phoneNo,
            'dob' => $userdata->dob,
            'gender' => $userdata->gender,
            'role' => $userdata->role,
            'profileImg' => $userdata->profileImg,
            'permanentAddress' => $userdata->permanentAddress,
            'temporaryAddress' => $userdata->temporaryAddress,
            'emergencyContactNo' => $userdata->emergencyContactNo,
            'parents_name' => $userdata->parents_name,
            'block' => $userdata->block()->first(),
        ];

        return response()->json($response);
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $userdata = User::with(['studentBatch.batch'])->find($user->userId);

        if (!$userdata) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'oldPassword' => 'required|string',
            'password' => 'required|string|min:8',
            'confirmPassword' => 'required|string|min:8',
        ]);

        if (!Hash::check($request->oldPassword, $userdata->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 422);
        }

        if ($request->password !== $request->confirmPassword) {
            return response()->json(['message' => 'New password and confirm password does not match.'], 422);
        }

        $userdata->password = Hash::make($request->password);
        $userdata->update();

        return response()->json(['message' => 'Password changed successfully']);
    }
}

/*
{
	data: [],
	pagination: { totalData: 10 }
}*/