<?php

namespace App\Http\Controllers;

use App\Models\UserFeeDetail;
use Illuminate\Http\Request;

class UserFeeDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $userId)
    {
        $limit = (int)$request->limit;

        $data = UserFeeDetail::where("userId", $userId)
            ->with(['batch' => function ($query) {
                $query->select('batchId', 'name', 'courseId'); // Select only these fields from batch table
            }, 'batch.course:courseId,name'])
            ->select('userFeeDetailId', 'amountToBePaid', 'remainingAmount', 'totalAmountPaid', 'batchId', 'refundAmount');

        if ($request->has('limit')) {
            $data = $data->paginate($limit);
        } else {
            $data = $data->get();
        }

        if (!$data) {
            return response()->json(['message' => 'User Fee detail not found'], 404);
        }

        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
