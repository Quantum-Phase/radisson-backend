<?php

namespace App\Http\Controllers;

use App\Models\LedgerType;
use Illuminate\Http\Request;

class LedgerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $type = $request->type;

        $results = LedgerType::select(
            'ledgerTypeId',
            'name',
            'type',
        )
            ->orderBy('created_at', 'desc')
            ->when($type, function ($query) use ($type) {
                $query->where('type', "=", $type);
            });

        if ($request->has('limit')) {
            $results = $results->paginate($limit);
        } else {
            $results = $results->get();
        }

        return response()->json($results);
    }
}
