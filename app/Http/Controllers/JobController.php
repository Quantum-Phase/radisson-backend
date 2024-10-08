<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Support\Carbon;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function searchJob(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchjob = Job::where('name', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchjob);
        }
    }

    public function showJob(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $companyId = $request->companyId;

        $job = Job::select(
            'jobId',
            'companyId',
            'departmentId',
            'studentId',
            'start_date',
            'type',
            'isActive',
            'isDeleted',
            'paid_amount'
        )
            ->with(['company' => function ($query) {
                $query->select('companyId', 'name');
            }])
            ->with(['department' => function ($query) {
                $query->select('departmentId', 'name');
            }])
            ->with(['student' => function ($query) {
                $query->select('userId', 'name');
            }])
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('companyId', "=", $companyId);
            })
            ->where(function ($query) use ($search) {
                $query->where('type', 'like', "%$search%")
                    ->orWhereHas('student', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('department', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('company', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    });
            })
            ->orderBy('created_at', 'desc');

        if ($limit > 0) {
            $job = $job->paginate($limit);
        } else {
            $job = $job->get();
        }

        return response()->json($job);
    }

    public function insertJob(Request $request)
    {
        $request->validate([
            'studentId' => [
                'required',
                Rule::unique('jobs', 'studentId')->whereNull('deleted_at'),
            ],
            'companyId' => 'required',
            'departmentId' => 'required',
            'paid_amount' => 'required',
            'start_date' => 'required',
            'type' => 'required'
        ]);

        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $insertJob = new Job();
        $insertJob->studentId = $request->studentId;
        $insertJob->companyId = $request->companyId;
        $insertJob->departmentId = $request->departmentId;
        $insertJob->start_date = $formattedStartDate;
        $insertJob->type = $request->type;
        $insertJob->paid_amount = $request->paid_amount;
        $insertJob->save();

        return response()->json('Internship/Job Inserted Sucessfully');
    }

    public function deleteJob(Request $request, $workId)
    {
        $jobs = Job::find($workId);
        $jobs->deleted_at = now();
        $jobs->save();

        return response()->json('Internship/Job Deleted Sucessfully');
    }

    public function getSingleJob($workId)
    {
        $job = Job::with([
            'department' => function ($query) {
                $query->select('departmentId', 'name');
            },
            'company' => function ($query) {
                $query->select('companyId', 'name');
            },
            'student' => function ($query) {
                $query->select('userId', 'name');
            }
        ])->find($workId);

        return response()->json($job);
    }

    public function updateJob(Request $request, $workId)
    {
        $request->validate([
            'studentId' => [
                'required',
                Rule::unique('jobs', 'studentId')->ignore($workId, 'jobId')->whereNull('deleted_at'),
            ],
            'companyId' => 'required',
            'departmentId' => 'required',
            'paid_amount' => 'required',
            'start_date' => 'required',
            'type' => 'required'
        ]);

        $jobs = Job::find($workId);
        $jobs->studentId = $request->studentId;
        $jobs->companyId = $request->companyId;
        $jobs->departmentId = $request->departmentId;
        $jobs->start_date = $request->start_date;
        $jobs->type = $request->type;
        $jobs->paid_amount = $request->paid_amount;
        $jobs->update();

        return response()->json('Internship/Job Updated Sucessfully');
    }
}
