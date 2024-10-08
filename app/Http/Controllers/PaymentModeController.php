<?php

namespace App\Http\Controllers;

use App\Models\PaymentMode;
use Illuminate\Http\Request;

class PaymentModeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $results = PaymentMode::select(
            'payment_modes.*',
        )
            ->orderBy('created_at', 'desc');

        if ($search) {
            $results = $results->where('name', 'like', "%$search%");
        }

        if ($request->has('limit')) {
            $results = $results->paginate($limit);
        } else {
            $results = $results->get();
        }
        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function createNew(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $paymentModeExists = PaymentMode::where('name', $request->name)
            ->exists();

        if ($paymentModeExists) {
            return response()->json(['message' => 'Payment Mode with this name already exists'], 422);
        }

        $paymentMode = new PaymentMode();
        $paymentMode->name = $request->name;

        $paymentMode->save();

        return response()->json('Payment Mode inserted successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $paymentModeId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $paymentModeExists = PaymentMode::where('name', $request->name)
            ->where('paymentModeId', '<>', $paymentModeId)
            ->exists();

        if ($paymentModeExists) {
            return response()->json(['message' => 'Payment mode with this name already exists'], 422);
        }

        $paymentMode = PaymentMode::find($paymentModeId);
        $paymentMode->name = $request->name;

        $paymentMode->update();

        return response()->json('Payment Mode Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $paymentModeId)
    {
        $paymentMode = PaymentMode::find($paymentModeId);
        if (!$paymentMode) {
            return response()->json(['message' => 'Payment mode not found'], 404);
        }

        $paymentMode->deleted_at = now();
        $paymentMode->save();
        return response()->json('Payment Mode Deleted Sucessfully');
    }
}
