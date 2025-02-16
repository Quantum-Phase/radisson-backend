<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

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

        $course = Course::select(
            'courses.courseId',
            'courses.name',
            'courses.isActive',
            'courses.isDeleted',
            'courses.totalFee',
            'courses.duration_unit',
            'courses.duration',
            'courses.totalHours'
        )
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            });

        // Check if limit is provided and apply pagination or fetch all results
        if ($limit > 0) {
            $course = $course->paginate($limit);
        } else {
            $course = $course->get();
        }

        return response()->json($course);
    }

    public function insertCourse(Request $request)
    {
        $request->validate([
            Rule::unique('courses', 'name')->whereNull('deleted_at'),

        ]);
        $course = new Course;
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->totalHours = $request->totalHours;
        $course->save();

        return response()->json('Course Inserted Sucessfully');
    }

    public function deleteCourse($courseId)
    {
        $batches = Batch::where('courseId', $courseId)->get();

        if ($batches->count() > 0) {
            return response()->json(['message' => 'Cannot delete course, they are assigned to batches'], 422);
        }

        $course = Course::find($courseId);
        $course->deleted_at = now();
        $course->save();

        return response()->json('Course Deleted Sucessfully');
    }

    public function updatec($courseId)
    {
        $course = Course::find($courseId);
        return view('course.updateCourse', compact('course'));
    }

    public function updateCourse(Request $request, $courseId)
    {
        $request->validate([
            'name' => [
                Rule::unique('courses', 'name')->ignore($courseId, 'courseId')->whereNull('deleted_at'),
            ],
        ]);

        $course = Course::find($courseId);
        $course->name = $request->name;
        $course->totalFee = $request->tfee;
        $course->duration_unit = $request->dunit;
        $course->duration = $request->duration;
        $course->totalHours = $request->totalHours;
        $course->update();

        return response()->json('Course Updated Sucessfully');
    }
}
