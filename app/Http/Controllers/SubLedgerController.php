<?php

namespace App\Http\Controllers;

use App\Models\SubLedger;
use Illuminate\Http\Request;

class SubLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $ledgerId = $request->ledgerId;

        $results = SubLedger::select(
            'subLedgerId',
            'ledgerId',
            'name',
        )
            ->with(['ledger' => function ($query) {
                $query->select('ledgerId', 'name');
            }])
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%$search%");
                })
                    ->orWhereHas('ledger', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    });
            })
            ->when($ledgerId, function ($query) use ($ledgerId) {
                $query->where('ledgerId', $ledgerId);
            });

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
            'ledgerId' => [
                'required',
                'exists:ledgers,ledgerId',
            ],
        ]);

        $subLedgerExists = SubLedger::where('name', $request->name)
            ->exists();

        if ($subLedgerExists) {
            return response()->json(['message' => 'Sub Ledger with this name already exists'], 422);
        }

        $subLedger = new SubLedger();
        $subLedger->name = $request->name;
        $subLedger->ledgerId = $request->ledgerId;

        $subLedger->save();

        return response()->json('Sub Ledger inserted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function getSingle(string $subLedgerId)
    {
        $subLedger = SubLedger::find($subLedgerId);

        if (!$subLedger) {
            return response()->json(['message' => 'Sub Ledger not found'], 404);
        }
        return response()->json($subLedger);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(Request $request, string $subLedgerId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'ledgerId' => [
                'required',
                'exists:ledgers,ledgerId',
            ],
        ]);

        $subLedgerExists = SubLedger::where('name', $request->name)
            ->where('subLedgerId', '<>', $subLedgerId)
            ->exists();

        if ($subLedgerExists) {
            return response()->json(['message' => 'Sub Ledger with this name already exists'], 422);
        }

        $subLedger = SubLedger::find($subLedgerId);

        $subLedger->name = $request->name;
        $subLedger->ledgerId = $request->ledgerId;
        $subLedger->update();

        return response()->json('SubLedger Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $subLedgerId)
    {
        $subLedger = SubLedger::find($subLedgerId);
        if (!$subLedger) {
            return response()->json(['message' => 'Sub Ledger not found'], 404);
        }

        $subLedger->delete();

        return response()->json('Sub Ledger Deleted Successfully');
    }
}
