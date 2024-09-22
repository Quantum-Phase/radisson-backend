<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a dashboard analytics.
     */
    public function getDashboardAnalytics()
    {
        $staffCount = User::whereIn('role', ['accountant', 'admin', 'mentor'])->count();
        $studentCount = User::whereIn('role', ['student'])->count();
        $internshipJobCount = Work::count();
        $totalCreditAmount = Payment::where('type', 'credit')->sum('amount');
        
        
        return response()->json([
            'staffCount' => $staffCount,
            'studentCount' => $studentCount,
            'internshipJobCount' => $internshipJobCount,
            'totalCreditAmount' => $totalCreditAmount,
        ]);
    }
}
