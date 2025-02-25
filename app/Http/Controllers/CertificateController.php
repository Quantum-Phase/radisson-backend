<?php

namespace App\Http\Controllers;

use App\Models\StudentBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Nilambar\NepaliDate\NepaliDate;

class CertificateController extends Controller
{
    public function generatePDF(Request $request)
    {
        try {
            ini_set('max_execution_time', 300); // Set to 5 minutes
            set_time_limit(300); // Alternative way to set timeout

            $request->validate([
                'userId' => 'required',
                'batchId' => 'required',
            ]);

            $userId = $request->userId;
            $batchId = $request->batchId;

            $studentBatch = StudentBatch::with(['user', 'batch.course'])->where('userId', $userId)->where('batchId', $batchId)->first();

            if (!$studentBatch) {
                return response()->json([
                    'message' => 'Student batch not found'
                ], 404);
            }

            // Pre-load images
            $images = [
                'certificate_bg' => base64_encode(file_get_contents(public_path('images/certificate-bg.svg'))),
                'gold_wheat' => base64_encode(file_get_contents(public_path('images/gold-wheat.svg'))),
                'radisson_logo' => base64_encode(file_get_contents(public_path('images/radisson-logo.svg'))),
                'watermark_logo' => public_path('images/watermark-logo.png'),
                'certificate_line' => base64_encode(file_get_contents(public_path('images/certificate-line.svg'))),
                'profile_bg' => base64_encode(file_get_contents(public_path('images/profile-bg.png'))),
                'default_profile' => base64_encode(file_get_contents(public_path('images/user-default.jpg'))),
                'signature' => base64_encode(file_get_contents(public_path('images/signature.svg'))),
                'seal' => base64_encode(file_get_contents(public_path('images/seal.svg')))
            ];

            // Create NepaliDate instance
            $nepaliDate = new NepaliDate();

            // Convert start date from Nepali to English
            $startDateParts = explode('-', $studentBatch->batch->start_date);
            if (count($startDateParts) !== 3) {
                throw new \Exception('Invalid start date format');
            }

            $englishStartDate = $nepaliDate->convertBsToAd(
                (int)$startDateParts[0],
                (int)$startDateParts[1],
                (int)$startDateParts[2]
            );
            $startDate = Carbon::create(
                $englishStartDate['year'],
                $englishStartDate['month'],
                $englishStartDate['day']
            );

            // Convert end date from Nepali to English
            $endDateParts = explode('-', $studentBatch->batch->end_date);
            $englishEndDate = $nepaliDate->convertBsToAd(
                (int)$endDateParts[0],
                (int)$endDateParts[1],
                (int)$endDateParts[2]
            );
            $endDate = Carbon::create(
                $englishEndDate['year'],
                $englishEndDate['month'],
                $englishEndDate['day']
            )->endOfDay();

            $data = [
                'studentName' => $studentBatch->user->name,
                'studentProfileImg' => $studentBatch->user->profileImg,
                'gender' => $studentBatch->user->gender,
                'courseName' => $studentBatch->batch->course->name,
                'trainingDuration' => $studentBatch->batch->course->duration . $studentBatch->batch->course->duration_unit . " (" . $studentBatch->batch->course->totalHours . "Hrs)",
                'trainingDate' => $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y'),
                'studentDateOfBirth' => Carbon::parse($studentBatch->user->dob)->format('d M Y'),
                'date' => now()->format('Y-m-d'),
                'images' => $images
            ];

            $pdf = PDF::loadView('pdf.certificate', $data)
                ->setOption("fontDir", storage_path('fonts/'))
                ->setOption("fontCache", storage_path('fonts/'))
                ->setOption("defaultFont", "lato");

            $pdf->setPaper('a4', 'portrait');

            $pdf->setOption('enable_php', true);
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->stream('certificate.pdf');
        } catch (\Exception $e) {
            Log::error('Certificate generation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
