<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\StudentBatch;
use App\Models\UserFeeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $excludedBatchId = $request->excludedBatchId;

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
            })->when($excludedBatchId, function ($query, $excludedBatchId) {
                return $query->whereNotIn('batchId', [$excludedBatchId]);
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
            ->orderBy('created_at', 'desc')
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
                'refundRequestedAmount' => $userFeeDetails->sum('refundRequestedAmount') ?? null,
                'refundedAmount' => $userFeeDetails->sum('refundedAmount') ?? null,
            ];
        });

        return response()->json($students);
    }

    public function addStudentToBatch(Request $request)
    {
        $request->validate([
            'studentId' => 'required',
            'batchId' => 'required',
            'discountType' => 'nullable|in:percent,amount',
            'discountAmount' => 'required_if:discountType,amount',
            'discountPercent' => 'required_if:discountType,percent',
        ]);

        $batch = Batch::find($request->batchId);

        if (!$batch || $batch->end_date < Carbon::now()->format('Y-m-d')) {
            return response()->json(['message' => 'Batch has ended or not found, cannot add new student'], 400);
        }

        if (StudentBatch::where('userId', $request->studentId)->where('batchId', $request->batchId)->exists()) {
            return response()->json(['message' => 'Student has already been assigned to this batch'], 422);
        }

        DB::transaction(function () use ($request, $batch) {
            StudentBatch::create([
                'userId' => $request->studentId,
                'batchId' => $request->batchId,
                'discountType' => $request->discountType,
                'discountAmount' => (int) $request->discountAmount,
                'discountPercent' => (int) $request->discountPercent,
            ]);

            $course = $batch->course()->first();
            $amountToBePaid = $course->totalFee;

            if ($request->discountType === "percent") {
                $amountToBePaid -= (int) (($request->discountPercent / 100) * $course->totalFee);
            } elseif ($request->discountType === "amount") {
                $amountToBePaid -= (int) $request->discountAmount;
            }

            UserFeeDetail::create([
                'userId' => $request->studentId,
                'batchId' => $batch->batchId,
                'amountToBePaid' => $amountToBePaid,
                'remainingAmount' => $amountToBePaid,
                'refundAmount' => 0
            ]);
        });

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

    public function transferStudent(Request $request)
    {
        // Validate the request
        $request->validate([
            'studentId' => 'required|exists:users,userId', // Ensure the student exists
            'oldBatchId' => 'required|exists:batches,batchId', // Ensure the old batch exists
            'newBatchId' => 'required|exists:batches,batchId', // Ensure the new batch exists
        ]);

        // Find the old and new batches
        $oldBatch = Batch::find($request->oldBatchId);

        if (!$oldBatch) {
            return response()->json(['message' => 'Old batch not found'], 404);
        }

        $newBatch = Batch::find($request->newBatchId);

        if (!$newBatch) {
            return response()->json(['message' => 'New batch not found'], 404);
        }

        // Check if the student is already assigned to the new batch
        $alreadyAssignedToNewBatch = StudentBatch::where('userId', $request->studentId)
            ->where('batchId', $request->newBatchId)
            ->exists();

        if ($alreadyAssignedToNewBatch) {
            return response()->json(['message' => 'Student is already assigned to the new batch'], 400);
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Find the user fee details for the old batch
            $userFeeDetailForOldBatch = UserFeeDetail::where('userId', $request->studentId)
                ->where('batchId', $request->oldBatchId)
                ->first();

            if (!$userFeeDetailForOldBatch) {
                throw new \Exception('User fee details for the old batch not found');
            }

            if ($userFeeDetailForOldBatch->refundAmount > 0) {
                throw new \Exception('Please pay the refund amount before procceding');
            }

            // Create a new student batch relationship with the new batch
            StudentBatch::create([
                'userId' => $request->studentId,
                'batchId' => $request->newBatchId,
                'discountType' => $oldBatch->discountType,
                'discountAmount' => (int) $oldBatch->discountAmount,
                'discountPercent' => (int) $oldBatch->discountPercent,
                'isTransfered' => true
            ]);

            // Mark the old batch as deleted
            $oldStudentBatch = StudentBatch::where('userId', $request->studentId)->where('batchId', $request->oldBatchId)->first();

            $oldStudentBatch->deleted_at = now();
            $oldStudentBatch->save();

            // Find the course for the new batch
            $course = $newBatch->course()->first();
            if (!$course) {
                throw new \Exception('Course not found for the new batch');
            }

            // Calculate the total amount to be paid
            $amountToBePaid = $course->totalFee;

            // Calculate the remaining amount and refund amount
            $remainingAmount = $amountToBePaid - $userFeeDetailForOldBatch->totalAmountPaid;
            $refundRequestedAmount = 0;
            $refundedAmount = 0;

            if ($remainingAmount < 0) {
                $refundRequestedAmount = abs($remainingAmount); // Use absolute value for refund
                $remainingAmount = 0;
            }

            // Create a new user fee detail for the new batch
            UserFeeDetail::create([
                'userId' => $request->studentId,
                'batchId' => $request->newBatchId,
                'amountToBePaid' => $amountToBePaid,
                'totalAmountPaid' => $userFeeDetailForOldBatch->totalAmountPaid,
                'remainingAmount' => $remainingAmount,
                'refundRequestedAmount' => $refundRequestedAmount,
                'refundedAmount' => $refundedAmount,
            ]);

            $userFeeDetailForOldBatch->deleted_at = now();
            $userFeeDetailForOldBatch->save();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Student transferred successfully'], 200);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            Log::error('Error transferring student: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to transfer student: ' . $e->getMessage()], 500);
        }
    }

    public function refundRequest(Request $request)
    {
        $request->validate([
            'studentId' => 'required|exists:users,userId',
            'batchId' => 'required|exists:batches,batchId',
            'amount' => 'required|numeric|min:0',
        ]);

        $userFeeDetail = UserFeeDetail::where('userId', $request->studentId)->where('batchId', $request->batchId)->first();

        if (!$userFeeDetail) {
            return response()->json(['message' => 'User fee details not found'], 404);
        }

        if ($userFeeDetail->totalAmountPaid < $request->amount) {
            return response()->json(['message' => 'Amount cannot be greater than the total amount paid'], 400);
        }

        $userFeeDetail->refundRequestedAmount = $userFeeDetail->refundRequestedAmount + $request->amount;
        $userFeeDetail->save();

        return response()->json(['message' => 'Refund request submitted successfully'], 200);
    }
}
