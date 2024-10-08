<?php

namespace App\Http\Controllers;

use App\Models\Course;
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
        $todayStudentCount = User::whereIn('role', ['student'])->whereDate('created_at', today())->count();

        $internshipJobCount = Job::count();
        $todaysInternshipJobCount = Job::whereDate('created_at', today())->count();

        $totalCourseCount = Course::count();

        $totalCreditAmount = Payment::where('type', 'credit')->sum('amount');

        return response()->json([
            'staffCount' => $staffCount,
            'studentCount' => $studentCount,
            'todayStudentCount' => $todayStudentCount,
            'internshipJobCount' => $internshipJobCount,
            'todaysInternshipJobCount' => $todaysInternshipJobCount,
            'totalCourseCount' => $totalCourseCount,
            'totalCreditAmount' => $totalCreditAmount,
        ]);
    }
}
