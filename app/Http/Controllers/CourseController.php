<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MentorCourse;
use App\Models\User;
use Illuminate\Http\Request;


class CourseController extends Controller
{
    public function searchCourse(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchcourse = Course::where('name', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchcourse);
        }
    }

    public function showCourse(Request $request)
    {
        $limit = (int)$request->limit;
        if ($request->has($limit)) {
            $course = Course::select(
                'courses.courseId',
                'courses.name',
                'courses.isActive',
                'courses.isDeleted',
                'courses.totalFee',
                'courses.duration_unit',
                'courses.duration'
            );
            return response()->json($course);
        } else {
            $course = Course::select(
                'courses.courseId',
                'courses.name',
                'courses.isActive',
                'courses.isDeleted',
                'courses.totalFee',
                'courses.duration_unit',
                'courses.duration'
            )
                ->paginate(10);
            // return view('course.displaycourse', compact('course'));
            return response()->json($course);
        }
    }

    public function insertCourse(Request $request)
    {
        $course = new Course;
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->save();

        //Insert Mentor in Course
        $mentorCourse = new MentorCourse;
        $mentorCourse->userId = $request->input('mentorId');
        $mentorCourse->courseId = $course->courseId;

        return response()->json('Course Inserted Sucessfully');
    }

    public function deleteCourse($courseId)
    {
        $course = Course::find($courseId);
        $course->delete();
        return response()->json('Course Deleted Sucessfully');
    }

    // public function updatec($courseId)
    // {
    //     $course = Course::find($courseId);
    //     return view('course.updateCourse', compact('course'));
    // }

    public function updateCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->update();
        return response()->json('Course Updated Sucessfully');
    }
}
