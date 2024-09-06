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
            // Add search filtering based on search query
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('users.name', 'like', "%$search%")
                        ->orWhere('users.email', 'like', "%$search%");
                });
            });

        if ($request->has('limit')) {
            $results = $results->paginate($limit);
        } else {
            $results = $results->paginate(10);
        }

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

        // $username = $request->name;

        $insertUser = new User;
        $insertUser->name = $request->name;
        $insertUser->email = $request->email;
        $insertUser->password = Hash::make($request->password);
        // $insertUser->password = Hash::make($this->generatedpassword($username));
        $insertUser->role = $request->role;
        $insertUser->phoneNo = $request->phoneNo;
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

            // $studentWork = new StudentWork;
            // $studentWork->workId = $request->workId;
            // $studentWork->userId = $insertUser->userId;
            // $studentWork->save();
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
        $data = User::find($userId);
        // return view('user.updateUser', compact('data'));
        return response()->json($data);
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

        // $studentBatch=StudentBatch::find($userId);

        return response()->json('User Updated Sucessfully');
    }

    // public function generatepassword($username) {}
}


/*
{
	data: [],
	pagination: { totalData: 10 }
}*/