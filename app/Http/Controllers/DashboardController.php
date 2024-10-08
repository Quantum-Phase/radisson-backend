<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Payment;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display a dashboard analytics.
     */
    public function getDashboardAnalytics()
    {
        $staffCount = User::whereIn('role', ['accountant', 'admin', 'mentor'])->count();
        $studentCount = User::whereIn('role', ['student'])->count();
        $internshipJobCount = Job::count();
        $totalCreditAmount = Payment::where('type', 'credit')->sum('amount');


        return response()->json([
            'staffCount' => $staffCount,
            'studentCount' => $studentCount,
            'internshipJobCount' => $internshipJobCount,
            'totalCreditAmount' => $totalCreditAmount,
        ]);
    }
}
