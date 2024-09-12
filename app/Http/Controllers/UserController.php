<?php

namespace App\Http\Controllers;

use App\Models\AccountantBlock;
use App\Models\Course;
use App\Models\CourseAssigned;
use App\Models\StudentBatch;
use App\Models\StudentCourse;
use App\Models\StudentWork;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;



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

            'profileimg' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'date_format:Y-m-d',
            'date' => 'date_format:Y-m-d',
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

        if ($request->role == 'accountant') {
            $userBlock = new AccountantBlock;
            $userBlock->userId = $insertUser->userId;
            $userBlock->blockId = $request->blockId;
            $userBlock->save();
        }
        return response()->json('User Inserted Sucessfully');
    }


    public function deleteUser($userId)
    {
        $data = User::find($userId);
        $data->delete();
        return response()->json('User Deleted Sucessfully');
    }

    public function update($userId)
    {
        $data = User::with(['studentBatch.batch', 'studentCourse.course', 'accountantBlock.block'])->find($userId);

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

            'batch' => $data->studentBatch->first() ? [
                'batchId' => $data->studentBatch->first()->batchId,
                'name' => $data->studentBatch->first()->batch->name ?? 'N/A',
            ] : null,

            'course' => $data->studentCourse->first() ? [
                'courseId' => $data->studentCourse->first()->courseId,
                'name' => $data->studentCourse->first()->course->name ?? 'N/A',
            ] : null,

            'block' => $data->accountantBlock->first() ? [
                'blockId' => $data->accountantBlock->first()->blockId,
                'name' => $data->accountantBlock->first()->block->name ?? 'N/A',
            ] : null,
        ];

        return response()->json($response);
    }



    public function updateUser(Request $request, $userId)
    {
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

        if ($request->hasFile('profileimg')) {
            $request->validate([
                'profileimg' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($data->profileimg && file_exists(public_path($data->profileimg))) {
                unlink(public_path($data->profileimg));
            }


            $file = $request->file('profileimg');
            $imageName = time() . '.' . $file->extension();
            $file->move(public_path('profileImage'), $imageName);

            $data->profileimg = 'profileImage/' . $imageName;
        }

        $data->update();

        // $studentCourse = StudentCourse::where('userId', $userId)->first();
        // if ($studentCourse) {
        //     $studentCourse->courseId = $request->courseId;
        //     $studentCourse->update();
        // } else {
        //     $studentCourse = new StudentCourse();
        //     $studentCourse->userId = $userId;
        //     $studentCourse->courseId = $request->courseId;
        //     $studentCourse->save();
        // }

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
        $AccountantBlock = AccountantBlock::where('userId', $userId)->first();
        if ($AccountantBlock) {
            $AccountantBlock->blockId = $request->blockId;
            $AccountantBlock->update();
        } else {
            $AccountantBlock = new AccountantBlock;
            $AccountantBlock->blockId = $request->blockId;
            $AccountantBlock->userId = $userId;
            $AccountantBlock->save();
        }

        return response()->json('User Updated Sucessfully');
    }
}


/*
{
	data: [],
	pagination: { totalData: 10 }
}*/