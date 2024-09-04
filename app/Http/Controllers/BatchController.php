<?php

namespace App\Http\Controllers;

use App\Models\Batch;



use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function showBatch()
    {
        $batch_data = Batch::all()->paginate(5);
        return response()->json($batch_data);
    }

    public function insertBatch(Request $request)
    {
        $batch = new Batch;
        $batch->name = $request->name;
        $batch->save();
        return response()->json('Batch inserted sucessfully');
    }

    public function deleteBatch($batchId)
    {
        $batch = Batch::find($batchId);
        $batch->delete();
        return response()->json('Batch Deleted Sucessfully');
    }

    public function updateb($batchId)
    {
        $batch = Batch::find($batchId);
        return response()->json($batch);
    }

    public function updateBatch(Request $request, $batchId)
    {
        $batch = Batch::find($batchId);
        $batch->name = $request->name;
        $batch->update();
        return response()->json('Batch Updated Sucessfully');
    }
}
