<?php

namespace App\Http\Controllers;

use App\Models\Batch;



use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function searchBatch(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchbatch = Batch::where('name', 'LIKE', '%' . $search . '%')
                ->orwhere('phoneNo', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchbatch);
        }
    }

    public function showBatch(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $batch_data = Batch::select(
            'batches.batchId',
            'batches.name',
            'batches.isActive',
            'batches.isDeleted',
        );

        // Add search filtering based on search query
        if ($search) {
            $batch_data = $batch_data->where(function ($subquery) use ($search) {
                $subquery->where('batches.name', 'like', "%$search%");
            });
        }

        if ($request->has('limit')) {
            $batch_data = $batch_data->paginate($limit);
        } else {
            $batch_data = $batch_data->get();
        }

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
