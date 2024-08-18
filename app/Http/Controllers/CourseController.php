<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{

    public function showCourse()
    {
        $course = Course::select(
            'courses.courseId',
            'courses.name',
            'courses.isActive',
            'courses.isDeleted',
            'courses.totalFee',
            'courses.duration_unit',
            'courses.duration'
        )
            ->paginate(5);
        // return view('course.displaycourse', compact('course'));
        return response()->json($course);
    }

    public function insertCourse(Request $request)
    {
        $course = new Course;
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->save();
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
