<?php

namespace App\Http\Controllers;

use App\Models\StudentBatch;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
            'users.permanentAddress',
            'users.temporaryAddress',
            'users.emergencyContactNo',
            'users.startDate',
            'users.parents_name',
            'student_batches.batchId',
            'batches.name AS batchname',
            // 'accountant_blocks.blockId',
            // 'blocks.name AS blockname',
        )
            ->leftJoin('student_batches', 'users.userId', '=', 'student_batches.userId')
            ->leftJoin('batches', 'student_batches.batchId', '=', 'batches.batchId')
            // ->leftJoin('accountant_blocks', 'users.userId', '=', 'accountant_blocks.userId')
            // ->leftJoin('blocks', 'accountant_blocks.blockId', '=', 'blocks.blockId')
            ->when($roles, function ($query, $roles) {
                return $query->whereIn('users.role', $roles);
            })
            // Add search filtering based on search query
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('users.name', 'like', "%$search%")
                        ->orWhere('users.email', 'like', "%$search%")
                        ->orWhere('users.phoneNo', 'like', "%$search%");
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
            'profileimg' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'date_format:Y-m-d',
            'date' => 'date_format:Y-m-d',
            'email' => 'unique:users,email,NULL,deleted_at',
            'phoneNo' => 'unique:users,phoneNo,NULL,deleted_at',
        ]);

        $file = $request->file('profileimg');
        $imageName = time() . '.' . $file->extension();
        $file->move(public_path('profileImage'), $imageName);

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
        $insertUser->profileimg = 'profileImage/' . $imageName;
        $insertUser->emergencyContactNo = $request->econtact;
        $insertUser->parents_name = $request->parents_name;

        if ($insertUser->hasRole('accountant')) {
            $insertUser->blockId = $request->blockId;
        }

        $insertUser->save();

        if ($request->role == 'student') {

            $studentCode = 'STD-' . $insertUser->userId;

            // Update the student_code for the user
            $insertUser->student_code = $studentCode;
            // $insertUser->time = $request->time;
            $insertUser->save();

            $studentBatch = new StudentBatch;
            $studentBatch->batchId = $request->batchId;
            $studentBatch->userId = $insertUser->userId;
            $studentBatch->save();
        }

        return response()->json('User Inserted Sucessfully');
    }

    public function deleteUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json('User not found', 404);
        }

        if ($user->hasRole('mentor')) {
            $mentorCourses = $user->mentorCourses;
            if ($mentorCourses->count() > 0) {
                return response()->json(['message' => 'Cannot delete mentor, they are assigned to courses'], 422);
            }
        }

        if ($user->hasRole('student')) {
            $studentWork = $user->studentWork;
            if ($studentWork->count() > 0) {
                return response()->json(['message' => 'Cannot delete student, they are assigned to internship/job'], 422);
            }
        }

        $data = User::find($userId);
        $data->deleted_at = now();
        $data->save();

        if ($user->hasRole('student')) {
            StudentBatch::where('userId', $userId)->delete(); // Add this line
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

            'batch' => $data->studentBatch->first() ? [
                'batchId' => $data->studentBatch->first()->batchId,
                'name' => $data->studentBatch->first()->batch->name ?? 'N/A',
                'course' => $data->studentBatch->first() ? ($data->studentBatch->first()->batch ? $data->studentBatch->first()->batch->load('course')->course : null) : null,
                'start_date' => $data->studentBatch->first()->batch->start_date ?? null,
                'end_date' => $data->studentBatch->first()->batch->end_date ?? null,
                'time' => $data->studentBatch->first()->batch->time ?? null,
            ] : null,

            'internshipJob' => $data->studentWork->first() ? [
                'workId' => $data->studentWork->first()->work ? $data->studentWork->first()->work->workId : null,
                'name' => $data->studentWork->first()->work ? $data->studentWork->first()->work->name ?? 'N/A' : null,
                'type' => $data->studentWork->first()->work ? $data->studentWork->first()->work->type ?? 'N/A' : null,
                'start_date' => $data->studentWork->first()->work ? $data->studentWork->first()->work->start_date ?? 'N/A' : null,
                'paid_amount' => $data->studentWork->first()->work ? $data->studentWork->first()->work->paid_amount ?? 'N/A' : null,
            ] : null,
        ];

        return response()->json($response);
    }

    public function updateUser(Request $request, $userId)
    {
        $request->validate([
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

            if ($data->profileimg && file_exists(public_path($data->profileimg))) {
                unlink(public_path($data->profileimg));
            }


            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $data->profileimg = 'profileImage/' . $imageName;
        }

        $data->update();

        $studentsBatch = StudentBatch::where('userId', $userId)->first();
        if ($studentsBatch) {
            $studentsBatch->batchId = $request->batchId;
            $studentsBatch->update();
        } else {

            $studentBatch = new StudentBatch;
            $studentBatch->batchId = $request->batchId;
            $studentBatch->userId = $userId;
            $studentBatch->save();
        }

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
            if ($userdata->profileimg && file_exists(public_path($userdata->profileimg))) {
                unlink(public_path($userdata->profileimg));
            }

            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $userdata->profileimg = 'profileImage/' . $imageName;
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