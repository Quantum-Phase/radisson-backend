<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\StudentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
            'batches.courseId',
        )
            ->with('course')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            });

        if ($request->has('limit')) {
            $batch_data = $batch_data->paginate($limit);
        } else {
            $batch_data = $batch_data->get();
        }

        return response()->json($batch_data);
    }

    public function insertBatch(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('batches', 'name')->whereNull('deleted_at'),
            ],        
            'courseId' => 'required|exists:courses,courseId',
            'start_date' => 'date_format:Y-m-d',
        ]);
        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $course = Course::where('courseId', $request->courseId)->first();

        $batch = new Batch;
        $batch->name = $request->name;
        $batch->start_date = $formattedStartDate;
        $batch->time = $request->time;
        $batch->courseId = $request->courseId;

        if ($course->duration_unit == 'months') {
            $batch->end_date = Carbon::parse($request->start_date)->addMonths($course->duration)->format('Y-m-d');
        } else {
            $batch->end_date = Carbon::parse($request->start_date)->addDays($course->duration)->format('Y-m-d');
        }

        $batch->save();

        return response()->json('Batch inserted successfully');
    }

    public function deleteBatch($batchId)
    {
        $batch = Batch::find($batchId);
        if ($batch->students()->count() > 0) {
            return response()->json(['message' => 'Cannot delete batch. It is assigned to one or more users.'], 400);
        }
        $batch->deleted_at = now();
        $batch->save();
        return response()->json('Batch Deleted Sucessfully');
    }

    public function singleBatch($batchId)
    {
        $batch_data = Batch::find($batchId);
        $batch_data->course = $batch_data->course()->first();

        $studentBatch = StudentBatch::where("batchId", $batchId);

        if($studentBatch->count() > 0) {
            $batch_data->disable_course = true;
        }

        return response()->json($batch_data);
    }

    public function updateBatch(Request $request, $batchId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('batches', 'name')->ignore($batchId, 'batchId')->whereNull('deleted_at'),
            ],
            'courseId' => 'required|exists:courses,courseId',
            'start_date' => 'date_format:Y-m-d',
        ]);
        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $course = Course::where('courseId', $request->courseId)->first();
        $batch = Batch::find($batchId);
        $batch->name = $request->name;
        $batch->start_date = $formattedStartDate;
        $batch->time = $request->time;
        $batch->courseId = $request->courseId;

        if ($course->duration_unit == 'months') {
            $batch->end_date = Carbon::parse($request->start_date)->addMonths($course->duration)->format('Y-m-d');
        } else {
            $batch->end_date = Carbon::parse($request->start_date)->addDays($course->duration)->format('Y-m-d');
        }
        $batch->update();

        return response()->json('Batch Updated Sucessfully');
    }

    public function studentsByBatch(Request $request, $batchId)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $students = StudentBatch::where('batchId', $batchId)
            ->with('user') // eager load the user relationship
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                });
            });

        if ($request->has('limit')) {
            $students = $students->paginate($limit);
        } else {
            $students = $students->get();
        }

        // Transform data to return student details
        $students->transform(function ($student) {
            return [
                'userId' => $student->user->userId ?? null,
                'name' => $student->user->name ?? null,
                'email' => $student->user->email ?? null,
                'phoneNo' => $student->user->phoneNo ?? null,
                'created_at' => $student->user->created_at ?? null
            ];
        });

        return response()->json($students);
    }
}
