<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;

        $results = Company::select(
            'company.companyId',
            'company.name',
            'company.address',
            'company.contactPerson',
            'company.contactNumber',
        )
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where(function ($subquery) use ($search) {
                    $subquery->where('company.name', 'like', "%$search%")
                        ->orWhere('company.address', 'like', "%$search%");
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
     *Creating a new resource.
     */
    public function createNew(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'address' => 'required|string|max:255',
            'contactPerson' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
        ]);

        $companyExists = Company::where('name', $request->name)
            ->where('address', $request->address)
            ->exists();

        if ($companyExists) {
            return response()->json(['message' => 'Company with this name and address already exists'], 422);
        }

        $company = new Company();
        $company->name = $request->name;
        $company->address = $request->address;
        $company->contactPerson = $request->contactPerson;
        $company->contactNumber = $request->contactNumber;

        $company->save();

        return response()->json('Company inserted successfully');
    }

    /**
     * Show the specific resource.
     */
    public function getSingle($companyId)
    {
        $company = Company::find($companyId);

        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        return response()->json($company);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(Request $request, string $companyId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'address' => 'required|string|max:255',
            'contactPerson' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:255',
        ]);

        $companyExists = Company::where('name', $request->name)
            ->where('address', $request->address)
            ->where('companyId', '<>', $companyId)
            ->exists();

        if ($companyExists) {
            return response()->json(['message' => 'Company with this name and address already exists'], 422);
        }

        $company = Company::find($companyId);
        $company->name = $request->name;
        $company->address = $request->address;
        $company->contactPerson = $request->contactPerson;
        $company->contactNumber = $request->contactNumber;

        $company->update();

        return response()->json('Company Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $companyId)
    {
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        if ($company->jobs()->exists()) {
            return response()->json(['message' => 'Company has been assigned to jobs, cannot be deleted'], 422);
        }

        // Delete related departments
        $company->departments()->delete();

        $company->delete(); // or $company->forceDelete(); if you want to permanently delete

        return response()->json('Company Deleted Successfully');
    }
}
