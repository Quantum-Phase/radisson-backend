<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAssigned;
use App\Models\StudentBatch;
use App\Models\StudentCourse;
use App\Models\StudentWork;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;


class UserController extends Controller
{

    public function showUser(Request $request)
    {
        // $role = $request->query('role');

        // $results = User::select('users.userId', 'users.name', 'users.email', 'users.phoneNo', 'users.dob', 'users.gender', 'users.profileImg', 'users.role', 'users.permanentAddress', 'users.temporaryAddress', 'users.emergencyContactNo', 'users.startDate', 'student_batches.batchId', 'batches.name AS batchname')
        //     ->leftJoin('student_batches', 'users.userId', '=', 'student_batches.userId')
        //     ->leftJoin('batches', 'student_batches.batchId', '=', 'batches.batchId')
        //     ->when($role, function ($query, $role) {
        //         return $query->where('users.role', $role);
        //     })
        //     ->paginate(5);

        $role = $request->query('role');

        // Convert comma-separated string to an array if necessary
        $roles = is_string($role) ? explode(',', $role) : [$role];

        $results = User::select(
            'users.userId',
            'users.name',
            'users.email',
            'users.phoneNo',
            'users.dob',
            'users.gender',
            'users.profileImg',
            'users.role',
            'users.permanentAddress',
            'users.temporaryAddress',
            'users.emergencyContactNo',
            'users.startDate',
            'student_batches.batchId',
            'batches.name AS batchname',
        )
            ->leftJoin('student_batches', 'users.userId', '=', 'student_batches.userId')
            ->leftJoin('batches', 'student_batches.batchId', '=', 'batches.batchId')
            ->when($roles, function ($query, $roles) {
                return $query->whereIn('users.role', $roles);
            })
            ->paginate(5);



        // $results = User::select('users.userId', 'users.name', 'users.email', 'users.phoneNo', 'users.dob', 'users.gender', 'users.profileImg', 'users.role', 'users.permanentAddress', 'users.temporaryAddress', 'users.emergencyContactNo', 'users.startDate', 'student_batches.batchId', 'batches.name AS batchname')
        // ->leftJoin('student_batches', 'users.userId', '=', 'student_batches.userId')
        // ->leftJoin('batches', 'student_batches.batchId', '=', 'batches.batchId')
        // ->leftJoin('course_assigned', 'users.userId', '=', 'course_assigned.userId')
        // ->leftJoin('courses', 'course_assigned.courseId', '=', 'courses.courseId')
        // ->paginate(5);

        return response()->json($results);
    }

    public function insertUser(Request $request)
    {
        $request->validate([

            'profileimg' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $file = $request->file('profileimg');
        $imageName = time() . '.' . $file->extension();
        $file->move(public_path('profileImage'), $imageName);

        $insertUser = new User;
        $insertUser->name = $request->name;
        $insertUser->email = $request->email;
        $insertUser->password = Hash::make($request->password);
        $insertUser->role = $request->role;
        $insertUser->phoneNo = $request->phone;
        $insertUser->dob = $request->date;
        $insertUser->gender = $request->gender;
        $insertUser->permanentAddress = $request->paddress;
        $insertUser->temporaryAddress = $request->taddress;
        $insertUser->startDate = $request->startdate;
        $insertUser->profileimg = 'profileImage/' . $imageName;
        $insertUser->emergencyContactNo = $request->econtact;
        $insertUser->save();

        if ($request->role == 'student') {
            $studentBatch = new StudentBatch;
            $studentBatch->batchId = $request->batchId;
            $studentBatch->userId = $insertUser->userId;
            $studentBatch->save();

            $studentCourse = new StudentCourse();
            $studentCourse->userId = $insertUser->userId;
            $studentCourse->courseId = $request->courseId;
            $studentCourse->save();

            $studentWork = new StudentWork;
            $studentWork->workId = $request->workId;
            $studentWork->userId = $insertUser->userId;
            $studentWork->save();
        }
        return response()->json('User Inserted Sucessfully');
    }


    public function deleteUser($userId)
    {
        $data = User::find($userId);
        $data->delete();
        return response()->json('User Deleted Sucessfully');
    }

    // public function update($userId)
    // {
    //     // $data = User::find($userId);
    //     // return view('user.updateUser', compact('data'));
    //     return response()->json(User::find($userId));
    // }

    public function updateUser(Request $request, $userId)
    {
        $data = User::find($userId);
        $data->name = $request->name;
        $data->email = $request->email;
        // $data->password = $request->password;
        // $data->role = $request->role;
        $data->phoneNo = $request->phone;
        $data->dob = $request->date;
        $data->gender = $request->gender;
        $data->permanentAddress = $request->paddress;
        $data->temporaryAddress = $request->taddress;
        // $data->startDate = $request->startdate;
        // $data->profileimg = $request->role;
        $data->emergencyContactNo = $request->econtact;
        $data->update();

        // $studentBatch=StudentBatch::find($userId);

        return response()->json('User Updated Sucessfully');
    }
}


/*
{
	data: [],
	pagination: { totalData: 10 }
}*/