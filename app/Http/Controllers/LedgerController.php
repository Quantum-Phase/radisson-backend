<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\Payment;
use App\Models\SubLedger;

use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $type = $request->type;

        $results = Ledger::select(
            'ledgerId',
            'name',
            'ledgerTypeId',
            'isStudentFeeLedger',
            'isStudentRefundLedger',
        )
            ->with(['ledgerType' => function ($query) {
                $query->select('ledgerTypeId', 'name', 'type');
            }])
            ->orderBy('created_at', 'desc')
            ->when($type, function ($query) use ($type) {
                $query->whereHas('ledgerType', function ($query) use ($type) {
                    $query->where('type', $type);
                });
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%$search%")
                        ->orWhere('ledgerTypeId', 'like', "%$search%");
                });
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
            'ledgerTypeId' => 'required',
        ]);

        $ledgerExists = Ledger::where('name', $request->name)
            ->exists();

        if ($ledgerExists) {
            return response()->json(['message' => 'Ledger with this name already exists'], 422);
        }

        $company = new Ledger();
        $company->name = $request->name;
        $company->ledgerTypeId = $request->ledgerTypeId;

        $company->save();

        return response()->json('Ledger inserted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function getSingle(string $ledgerId)
    {
        $ledger = Ledger::find($ledgerId);

        if (!$ledger) {
            return response()->json(['message' => 'Ledger not found'], 404);
        }
        return response()->json($ledger);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $ledgerId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'ledgerTypeId' => 'required',
        ]);

        $ledgerExists = Ledger::where('name', $request->name)
            ->where('ledgerId', '<>', $ledgerId)
            ->exists();

        if ($ledgerExists) {
            return response()->json(['message' => 'Ledger with this name already exists'], 422);
        }

        $ledger = Ledger::find($ledgerId);

        if ($ledger->isRelatedToStudent) {
            return response()->json(['message' => 'Ledger is related to student, cannot be updated'], 422);
        }

        $ledger->name = $request->name;
        $ledger->ledgerTypeId = $request->ledgerTypeId;

        $ledger->update();

        return response()->json('Ledger Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $ledgerId)
    {
        $ledger = Ledger::find($ledgerId);
        if (!$ledger) {
            return response()->json(['message' => 'Ledger not found'], 404);
        }

        if ($ledger->isRelatedToStudent) {
            return response()->json(['message' => 'Ledger is related to student, cannot be deleted'], 422);
        }

        if ($ledger->payments()->exists()) {
            return response()->json(['message' => 'Ledger has been assigned to payments, cannot be deleted'], 422);
        }

        if ($ledger->subLedgers()->exists()) {
            return response()->json(['message' => 'Ledger has been assigned to subledgers, cannot be deleted'], 422);
        }

        $ledger->delete(); // or $ledger->forceDelete(); if you want to permanently delete

        return response()->json('Ledger Deleted Successfully');
    }

    // app/Http/Controllers/LedgerController.php

public function getReports(Request $request)
{
    $ledgerId = $request->ledgerId;
    $subLedgerId = $request->subLedgerId;

    $ledger = Ledger::find($ledgerId);
    $subLedger = SubLedger::find($subLedgerId);

    if (!$ledger || !$subLedger) {
        return response()->json(['message' => 'Ledger or sub-ledger not found'], 404);
    }

    $ledgerReport = $this->generateLedgerReport($ledger);
    $subLedgerReport = $this->generateSubLedgerReport($subLedger);

    return response()->json(['ledgerReport' => $ledgerReport, 'subLedgerReport' => $subLedgerReport]);
}

private function generateLedgerReport(Ledger $ledger)
{
    $report = [
        'ledgerId' => $ledger->ledgerId,
        'name' => $ledger->name,
        'ledgerTypeId' => $ledger->ledgerTypeId,
        'isStudentFeeLedger' => $ledger->isStudentFeeLedger,
        'isStudentRefundLedger' => $ledger->isStudentRefundLedger,
        'transactions' => $this->getLedgerTransactions($ledger),
    ];

    return $report;
}

private function generateSubLedgerReport(SubLedger $subLedger)
{
    $report = [
        'subLedgerId' => $subLedger->subLedgerId,
        'name' => $subLedger->name,
        'ledgerId' => $subLedger->ledgerId,
        'transactions' => $this->getSubLedgerTransactions($subLedger),
    ];

    return $report;
}

private function getLedgerTransactions(Ledger $ledger)
{
    $transactions = Payment::where('ledgerId', $ledger->ledgerId)
        ->with(['paymentMode', 'subLedger'])
        ->get();

    return $transactions;
}

private function getSubLedgerTransactions(SubLedger $subLedger)
{
    $transactions = Payment::where('subLedgerId', $subLedger->subLedgerId)
        ->with(['paymentMode', 'ledger'])
        ->get();

    return $transactions;
}
}
