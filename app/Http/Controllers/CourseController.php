<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MentorCourse;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;


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
        $search = $request->search;

        // Query the Course model with related mentor and user data
        $course = Course::select(
            'courses.courseId',
            'courses.name',
            'courses.isActive',
            'courses.isDeleted',
            'courses.totalFee',
            'courses.duration_unit',
            'courses.duration'
        )
            ->with('mentorCourses.user') // Eager load mentorCourses and user relationships
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            });

        // Check if limit is provided and apply pagination or fetch all results
        if ($limit > 0) {
            $course = $course->paginate($limit);
        } else {
            $course = $course->get();
        }

        $course->transform(function ($course) {
            $mentor = $course->mentorCourses->map(function ($mentorCourse) {
                return [
                    'userId' => optional($mentorCourse->user)->userId,
                    'name' => optional($mentorCourse->user)->name,
                ];
            })->first();
        
            unset($course->mentorCourses);
            $course->mentor = $mentor;
            return $course;
        });

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

        //Insert Mentor in Course
        $mentorCourse = new MentorCourse;
        $mentorCourse->userId = $request->input('mentorId');
        $mentorCourse->courseId = $course->courseId;
        $mentorCourse->save();


        return response()->json('Course Inserted Sucessfully');
    }

    public function deleteCourse($courseId)
    {
        $course = Course::find($courseId);
        // Check if any users are assigned to the course through StudentCourse
        if ($course->users()->count() > 0) {
            return response()->json(['error' => 'Cannot delete course. It is assigned to one or more users.'], 400);
        }
        $course->delete();
        return response()->json('Course Deleted Sucessfully');
    }

    public function updatec($courseId)
    {
        $course = Course::find($courseId);
        return view('course.updateCourse', compact('course'));
    }

    public function updateCourse(Request $request, $courseId)
    {
        $course = Course::find($courseId);
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->update();

        // $mentorId = $request->mentorId;

        $mentorCourse = MentorCourse::where('courseId', $courseId)->first();

        if ($mentorCourse) {
            $mentorCourse->userId = $request->mentorId;
            $mentorCourse->update();
        } else {
            $mentorCourse = new MentorCourse;
            $mentorCourse->userId = $request->input('mentorId');
            $mentorCourse->courseId = $courseId;
            $mentorCourse->save();
        }
        return response()->json('Course Updated Sucessfully');
    }
}
