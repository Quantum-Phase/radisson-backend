<?php

namespace App\Http\Controllers;

use App\Models\BalanceSheet;
use App\Models\Ledger;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $user = auth()->user();

        $balanceSheet = BalanceSheet::with([
            'transactionBy:userId,name',
            'assets' => function ($query) {
                $query->select('ledgerId', 'name');
            },
            'liabilities' => function ($query) {
                $query->select('ledgerId', 'name');
            },
            'block'
        ])
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('transactionBy', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('assets', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('liabilities', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->orderBy('created_at', 'desc');

        if ($user->role !== "superadmin") {
            $balanceSheet->where("transactionBy", $user->userId);
        }

        // Paginate if limit is provided, else get all
        if ($request->has('limit')) {
            $balanceSheet = $balanceSheet->paginate($limit);
        } else {
            $balanceSheet = $balanceSheet->get();
        }

        return response()->json($balanceSheet);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'name' => 'required|string',
            'blockId' => 'required|exists:blocks,blockId',
            'assetsId' => 'required|exists:ledgers,ledgerId',
            'liabilitiesId' => 'required|exists:ledgers,ledgerId',
        ]);

        $user = auth()->user();

        // If the amount is invalid, return an error
        if ($request->amount <= 0) {
            return response()->json(['error' => 'Amount must be greater than zero.'], 422);
        }

        DB::transaction(function () use ($request, $user) {
            // Create payments for both asset and liability
            $balanceSheet = new BalanceSheet();
            $balanceSheet->amount = $request->amount;
            $balanceSheet->name = $request->name;
            $balanceSheet->blockId = $request->blockId;
            $balanceSheet->assetsId = $request->assetsId;
            $balanceSheet->liabilitiesId = $request->liabilitiesId;
            $balanceSheet->remarks = $request->remarks;
            $balanceSheet->transactionBy = $user->userId;

            $balanceSheet->save();

            // Update asset ledger
            $assetLedger = Ledger::find($request->assetsId);
            $assetLedger->amount += $request->amount;
            $assetLedger->save();

            // Update liability ledger
            $liabilityLedger = Ledger::find($request->liabilitiesId);
            $liabilityLedger->amount += $request->amount;
            $liabilityLedger->save();
        });

        return response()->json(['message' => 'Balance sheet inserted successfully.']);
    }

    public function getFinancialOverview(Request $request)
    {
        $dateType = $request->dateType ? $request->dateType : "today";
        $user = auth()->user();

        // Initialize the payments query
        $balanceSheetQuery = BalanceSheet::query();

        // Apply date filtering based on dateType
        $today = Carbon::today();
        switch ($dateType) {
            case 'today':
                $balanceSheetQuery->whereDate('created_at', $today);
                break;
            case 'weekly':
                $startOfWeek = $today->copy()->startOfWeek(); // Default is Sunday
                $endOfWeek = $today->copy()->endOfWeek(); // Default is Saturday
                $balanceSheetQuery->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                break;
            case 'monthly':
                $balanceSheetQuery->whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year);
                break;
            case 'yearly':
                $balanceSheetQuery->whereYear('created_at', $today->year);
                break;
            default:
                // Optionally handle 'daily' or other cases if needed
                break;
        }

        if ($user->role !== "superadmin") {
            $balanceSheetQuery->where("transactionBy", $user->userId);
        }

        $response = $balanceSheetQuery->get();

        $grouped = [
            'assets' => [
                'type' => 'assets',
                'totalAmountByType' => 0,
                'ledger' => []
            ],
            'liabilities' => [
                'type' => 'liabilities',
                'totalAmountByType' => 0,
                'ledger' => []
            ]
        ];

        // Retrieve all ledgers for assets and liabilities
        $assetLedgers = Ledger::whereIn('ledgerId', $response->pluck('assetsId'))->get()->keyBy('ledgerId');
        $liabilityLedgers = Ledger::whereIn('ledgerId', $response->pluck('liabilitiesId'))->get()->keyBy('ledgerId');

        // Group by assetsId
        foreach ($response as $item) {
            // Grouping for assets
            $assetsKey = $item['assetsId'];
            $ledgerName = $assetLedgers[$assetsKey]->name ?? "Unknown Asset";

            if (!isset($grouped['assets']['ledger'][$assetsKey])) {
                $grouped['assets']['ledger'][$assetsKey] = [
                    'ledgerName' => $ledgerName,
                    'total' => 0,
                    'payments' => []
                ];
            }

            $grouped['assets']['ledger'][$assetsKey]['total'] += $item['amount'];
            $grouped['assets']['totalAmountByType'] += $item['amount'];
            $grouped['assets']['ledger'][$assetsKey]['payments'][] = [
                'id' => $item['balanceSheetId'],
                'name' => $item['name'],
                'amount' => $item['amount']
            ];
        }

        // Group by liabilitiesId
        foreach ($response as $item) {
            // Grouping for liabilities
            $liabilitiesKey = $item['liabilitiesId'];
            $ledgerName = $liabilityLedgers[$liabilitiesKey]->name ?? "Unknown Liability";

            if (!isset($grouped['liabilities']['ledger'][$liabilitiesKey])) {
                $grouped['liabilities']['ledger'][$liabilitiesKey] = [
                    'ledgerName' => $ledgerName,
                    'total' => 0,
                    'payments' => []
                ];
            }

            $grouped['liabilities']['ledger'][$liabilitiesKey]['total'] += $item['amount'];
            $grouped['liabilities']['totalAmountByType'] += $item['amount'];
            $grouped['liabilities']['ledger'][$liabilitiesKey]['payments'][] = [
                'id' => $item['balanceSheetId'],
                'name' => $item['name'],
                'amount' => $item['amount']
            ];
        }

        // Convert ledger arrays to simple arrays
        foreach ($grouped as &$group) {
            $group['ledger'] = array_values($group['ledger']);
        }

        // Convert grouped array to a simple indexed array
        $grouped = array_values($grouped);
        return response()->json($grouped);
    }
}
