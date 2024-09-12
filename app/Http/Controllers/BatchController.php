<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchCourse;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BatchController extends Controller
{
    public function searchBatch(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchbatch = Batch::where('name', 'LIKE', '%' . $search . '%')
                ->orwhere('phoneNo', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchbatch);
        }
    }

    public function showBatch(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $batch_data = Batch::select(
            'batches.batchId',
            'batches.name',
            'batches.isActive',
            'batches.isDeleted',
            'batches.time',
            'batches.start_date',
            'batches.end_date',
            'batch_courses.courseId',
            'courses.name AS coursename'
        )
            ->leftJoin('batch_courses', 'batch_courses.batchId', '=', 'batches.batchId')
            ->leftJoin('courses', 'courses.courseId', '=', 'batch_courses.courseId');

        // Add search filtering based on search query
        if ($search) {
            $batch_data = $batch_data->where(function ($subquery) use ($search) {
                $subquery->where('batches.name', 'like', "%$search%");
            });
        }

        if ($request->has('limit')) {
            $batch_data = $batch_data->paginate($limit);
        } else {
            $batch_data = $batch_data->get();
        }
        // Transform data to return courses as objects, handling null values
        $batch_data->transform(function ($batch) {
            // Ensure batchCourses is not null before calling map
            $batch->courses = $batch->batchCourses ? $batch->batchCourses->map(function ($batchCourse) {
                return [
                    'courseId' => $batchCourse->course->courseId ?? null,
                    'name' => $batchCourse->course->name ?? null,
                    'duration' => $batchCourse->course->duration ?? null,

                ];
            }) : []; // If null, set courses to an empty array

            unset($batch->batchCourses);
            return $batch;
        });


        return response()->json($batch_data);
    }

    public function insertBatch(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'courseId' => 'required|exists:courses,courseId',
            'start_date' => 'date_format:Y-m-d',
        ]);
        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $course = Course::where('courseId', $request->courseId)->first();

        $batch = new Batch;
        $batch->name = $request->name;
        $batch->start_date = $formattedStartDate;
        $batch->time = $request->time;
        if ($course->dunit == 'months') {
            $batch->end_date = Carbon::parse($request->start_date)->addMonths($course->duration)->format('Y-m-d');
        } else {
            $batch->end_date = Carbon::parse($request->start_date)->addDays($course->duration)->format('Y-m-d');
        }

        $batch->save();

        $batchcourse = new BatchCourse;
        $batchcourse->courseId = $request->courseId;
        $batchcourse->batchId = $batch->batchId;
        $batchcourse->save();

        return response()->json('Batch inserted successfully');
    }


    public function deleteBatch($batchId)
    {
        $batch = Batch::find($batchId);
        $batch->delete();
        return response()->json('Batch Deleted Sucessfully');
    }

    public function updateb($batchId)
    {
        $batch = Batch::find($batchId);
        return response()->json($batch);
    }

    public function updateBatch(Request $request, $batchId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'courseId' => 'required|exists:courses,courseId',
            'start_date' => 'date_format:Y-m-d',
        ]);
        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $course = Course::where('courseId', $request->courseId)->first();
        $batch = Batch::find($batchId);
        $batch->name = $request->name;
        $batch->update();

        $batchcourse = BatchCourse::where('batchId', $batchId)->first();
        if ($batchcourse) {
            $batchcourse->courseId = $request->courseId;
            $batch->start_date = $formattedStartDate;
            $batch->time = $request->time;
            if ($course->dunit == 'months') {
                $batch->end_date = Carbon::parse($request->start_date)->addMonths($course->duration)->format('Y-m-d');
            } else {
                $batch->end_date = Carbon::parse($request->start_date)->addDays($course->duration)->format('Y-m-d');
            }
            $batchcourse->update();
        } else {
            $batchcourse = new BatchCourse;
            $batchcourse->courseId = $request->courseId;
            $batchcourse->batchId = $batch->batchId;
            $batchcourse->save();
        }
        return response()->json('Batch Updated Sucessfully');
    }
}
