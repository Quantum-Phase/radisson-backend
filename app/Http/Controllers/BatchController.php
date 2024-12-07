<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\StudentBatch;
use App\Models\UserFeeDetail;
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
        $studentId = $request->studentId;

        $batch_data = Batch::select(
            'batches.batchId',
            'batches.name',
            'batches.isActive',
            'batches.isDeleted',
            'batches.time',
            'batches.start_date',
            'batches.end_date',
            'batches.courseId',
            'batches.mentorId',
        )
            ->with('course')
            ->with(['mentor' => function ($query) {
                $query->select('userId', 'name');
            }])
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            })->when($studentId, function ($query, $studentId) {
                $query->whereHas('studentBatches', function ($query) use ($studentId) {
                    $query->where('userId', $studentId);
                });
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
        $batch->mentorId = $request->mentorId;

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
        if ($batch->studentBatches()->count() > 0) {
            return response()->json(['message' => 'Cannot delete batch. It is assigned to one or more users.'], 400);
        }
        $batch->deleted_at = now();
        $batch->save();
        return response()->json('Batch Deleted Sucessfully');
    }

    public function singleBatch($batchId)
    {
        $batch_data = Batch::with([
            'course' => function ($query) {
                $query->select('courseId', 'name', 'totalFee');
            },
            'mentor' => function ($query) {
                $query->select('userId', 'name');
            }
        ])->find($batchId);

        $studentBatch = StudentBatch::where("batchId", $batchId);

        if ($studentBatch->count() > 0) {
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
        $batch->mentorId = $request->mentorId;

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
        $feeStatus = $request->feeStatus;

        $students = StudentBatch::where('batchId', $batchId)
            ->with('user', 'user.userFeeDetail') // eager load the user relationship
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phoneNo', 'like', "%$search%");
                });
            });

        // Filter based on feeStatus
        if ($feeStatus === 'paid') {
            $students = $students->whereHas('user.userFeeDetail', function ($query) use ($batchId) {
                $query->where('batchId', $batchId)
                    ->where('remainingAmount', 0);
            });
        } elseif ($feeStatus === 'unpaid') {
            $students = $students->whereHas('user.userFeeDetail', function ($query) use ($batchId) {
                $query->where('batchId', $batchId)
                    ->where('remainingAmount', '>', 0);
            });
        }

        if ($request->has('limit')) {
            $students = $students->paginate($limit);
        } else {
            $students = $students->get();
        }

        // Transform data to return student details
        $students->transform(function ($student) use ($batchId) {
            $userFeeDetails = $student->user->userFeeDetail()->where('batchId', $batchId)->get();

            return [
                'userId' => $student->user->userId ?? null,
                'name' => $student->user->name ?? null,
                'email' => $student->user->email ?? null,
                'phoneNo' => $student->user->phoneNo ?? null,
                'created_at' => $student->user->created_at ?? null,
                'amountToBePaid' => $userFeeDetails->sum('amountToBePaid') ?? null,
                'totalAmountPaid' => $userFeeDetails->sum('totalAmountPaid') ?? null,
                'remainingAmount' => $userFeeDetails->sum('remainingAmount') ?? null,
            ];
        });

        return response()->json($students);
    }

    public function addStudentToBatch(Request $request)
    {
        $request->validate([
            'studentId' => 'required',
            'batchId' => 'required',
        ]);

        $batch = Batch::find($request->batchId);

        if ($batch->end_date < Carbon::now()->format('Y-m-d')) {
            return response()->json(['message' => 'Batch has ended, cannot add new student'], 400);
        }

        $studentBatchExists = StudentBatch::where('userId', $request->studentId)
            ->where('batchId', $request->batchId)
            ->exists();

        if ($studentBatchExists) {
            return response()->json(['message' => 'Student has already been assigned to this batch'], 422);
        }

        $studentBatch = new StudentBatch();
        $studentBatch->userId = $request->studentId;
        $studentBatch->batchId = $request->batchId;

        $studentBatch->save();

        $batch = Batch::find($request->batchId);
        $course = $batch->course()->first();

        $userFeeDetail = new UserFeeDetail();
        $userFeeDetail->userId = $request->studentId;
        $userFeeDetail->batchId = $batch->batchId;
        $userFeeDetail->amountToBePaid = $course->totalFee;
        $userFeeDetail->remainingAmount = $course->totalFee;
        $userFeeDetail->save();

        return response()->json('Student inserted to batch successfully');
    }

    public function deleteStudentFromBatch($batchId, $studentId)
    {
        $studentBatch = StudentBatch::where('userId', $studentId)->where('batchId', $batchId)->first();

        $studentBatch->deleted_at = now();
        $studentBatch->save();

        $userFeeDetail = UserFeeDetail::where('userId', $studentId)->where('batchId', $batchId)->first();
        $userFeeDetail->deleted_at = now();
        $userFeeDetail->save();

        return response()->json(['message' => 'Student removed from batch successfully'], 200);
    }
}
