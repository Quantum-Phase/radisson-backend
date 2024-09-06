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
            'isDeleted'
        )
            ->with(['studentWork.user'])
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
                    'id' => $studentWork->user->userId,
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
        $dateString = $request->start_date;
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $dateString);
        $formattedDate = $date->format('Y-m-d H:i:s');

        $insertJob = new Work();
        $insertJob->name = $request->name;
        $insertJob->start_date = $formattedDate;
        $insertJob->type = $request->type;
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
        $jobs->delete();
        return response()->json('Internship/Job Deleted Sucessfully');
    }

    public function updatej($workId)
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
