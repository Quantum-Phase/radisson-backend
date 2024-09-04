<?php

namespace App\Http\Controllers;

use App\Models\Work;


use Illuminate\Http\Request;

class JobController extends Controller
{
    public function showJob(Request $request)
    {
        $job = Work::all()->paginate(5);
        return response()->json($job);
    }
    public function insertJob(Request $request)
    {
        $insertJob = new Work();
        $insertJob->name = $request->name;
        $insertJob->start_date = $request->start_date;
        $insertJob->type = $request->type;
        $insertJob->save();
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
        $jobs = Work::find($workId);
        return response()->json($jobs);
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
