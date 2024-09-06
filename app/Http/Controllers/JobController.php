<?php

namespace App\Http\Controllers;

use App\Models\StudentWork;
use App\Models\Work;


use Illuminate\Http\Request;

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
        )
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%$search%");
            });

        if ($limit > 0) {
            $job = $job->paginate($limit);
        } else {
            $job = $job->get();
        }

        return response()->json($job);
    }

    public function insertJob(Request $request)
    {
        $insertJob = new Work();
        $insertJob->name = $request->name;
        $insertJob->start_date = $request->start_date;
        $insertJob->type = $request->type;
        $insertJob->save();

        //Insert Student in Job
        $StudentJob = new StudentWork;
        $StudentJob->userId = $request->input('studentId');
        $StudentJob->workId = $insertJob->workId;
        return response()->json('Internship/Job Inserted Sucessfully');
    }

    public function deleteJob(Request $request, $workId)
    {
        $jobs = Work::find($workId);
        $jobs->delete();
        return response()->json('Internship/Job Deleted Sucessfully');
    }

    public function update($workId)
    {
        $studentWork = StudentWork::with(['student.user', 'work'])
            ->findOrFail($workId);

        // Now you have access to student's name and other details:
        $studentName = $studentWork->student->user->name;
        $student = $studentWork->student;
        $work = $studentWork->work;

        // ... do something with the student's name, student, and work data
        return response()->json(['studentName' => $studentName, 'student' => $student, 'work' => $work]);
    }

    public function updateJob(Request $request, $workId)
    {
        $jobs = Work::find($workId);
        $jobs->name = $request->name;
        $jobs->start_date = $request->start_date;
        $jobs->type = $request->type;
        $jobs->update();
        return response()->json('Internship/Job Updated Sucessfully');
    }
}
