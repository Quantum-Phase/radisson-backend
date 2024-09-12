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

        // Transform the collection to structure the mentor's user data
        $course->transform(function ($course) {
            // Assuming mentorCourses has a 'user' relationship
            $course->mentor = $course->mentorCourses->map(function ($mentorCourse) {
                return [
                    'userId' => $mentorCourse->user->userId,
                    'name' => $mentorCourse->user->name,
                    // Add other user fields as needed
                ];
            })->first(); // Get the first mentor's user object
            unset($course->mentorCourses); // Remove mentorCourses relationship to simplify the result
            return $course;
        });

        return response()->json($course);
    }

    public function insertCourse(Request $request)
    {


        $course = new Course;
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        // $course->start_date = $formattedStartDate;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        if ($request->dunit == 'months') {
            $course->end_date = Carbon::parse($request->start_date)->addMonths($request->duration)->format('Y-m-d');
        } else {
            $course->end_date = Carbon::parse($request->start_date)->addDays($request->duration)->format('Y-m-d');
        }
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
