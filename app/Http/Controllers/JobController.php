<?php

namespace App\Http\Controllers;

use App\Models\StudentWork;
use App\Models\Work;
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
            $searchjob = Work::where('name', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchjob);
        }
    }

    public function showJob(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $job = Work::select(
            'workId',
            'name',
            'start_date',
            'type',
            'isActive',
            'isDeleted',
            'paid_amount'
        )
            ->with(['studentWork.user'])
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            });

        if ($limit > 0) {
            $job = $job->paginate($limit);
        } else {
            $job = $job->get();
        }

        $job->transform(function ($work) {
            $work->student = $work->studentWork->map(function ($studentWork) {
                return [
                    'userId' => $studentWork->user->userId,
                    'name' => $studentWork->user->name,
                    // Add other student fields as needed
                ];
            })->first(); // Get the first student object
            unset($work->studentWork);
            return $work;
        });

        return response()->json($job);
    }

    public function insertJob(Request $request)
    {
        // $dateString = $request->start_date;
        // $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $dateString);
        // $formattedDate = $date->format('Y-m-d H:i:s');

        $messages = [
            'studentId.unique' => 'The student has already been assigned with job/internship.',
        ];

        $request->validate([
            'studentId' => Rule::unique('student_works', 'userId')->whereNull('deleted_at'),
        ], $messages);
        
        $formattedStartDate = Carbon::parse($request->start_date)->format('Y-m-d');

        $insertJob = new Work();
        $insertJob->name = $request->name;
        $insertJob->start_date = $formattedStartDate;
        $insertJob->type = $request->type;
        $insertJob->paid_amount = $request->paid_amount;
        $insertJob->save();

        //Insert Student in Job
        $StudentJob = new StudentWork;
        $StudentJob->userId = $request->input('studentId');
        $StudentJob->workId = $insertJob->workId;
        $StudentJob->save();
        return response()->json('Internship/Job Inserted Sucessfully');
    }

    public function deleteJob(Request $request, $workId)
    {
        $jobs = Work::find($workId);
        $jobs->deleted_at = now();
        $jobs->save();

        StudentWork::where('workId', $workId)->delete(); // Add this line

        return response()->json('Internship/Job Deleted Sucessfully');
    }

    public function updatej($workId)
    {
        $studentWork = StudentWork::with(['student.user', 'work'])
            ->findOrFail($workId);


        $studentName = $studentWork->student->user->name;
        $student = $studentWork->student;
        $work = $studentWork->work;


        return response()->json(['studentName' => $studentName, 'student' => $student, 'work' => $work]);
    }

    public function updateJob(Request $request, $workId)
    {
        $jobs = Work::find($workId);
        $jobs->name = $request->name;
        $jobs->start_date = $request->start_date;
        $jobs->type = $request->type;
        $jobs->paid_amount = $request->paid_amount;
        $jobs->update();

        $studentJob = StudentWork::where('workId', $workId)->first();
        if ($studentJob) {
            $studentJob->userId = $request->studentId;
            $studentJob->update();
        } else {
            $StudentJob = new StudentWork;
            $StudentJob->userId = $request->input('studentId');
            $StudentJob->workId = $workId;
            $StudentJob->save();
        }
        return response()->json('Internship/Job Updated Sucessfully');
    }
}
