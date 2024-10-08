<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $companyId = $request->companyId;

        $results = Department::select(
            'departments.*',
        )
            ->orderBy('created_at', 'desc');

        if ($companyId) {
            $results = $results->where('companyId', '=', $companyId);
        }

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
            'companyId' => 'required',
        ]);

        $departmentExists = Department::where('name', $request->name)
            ->where('companyId', $request->companyId)
            ->exists();

        if ($departmentExists) {
            return response()->json(['message' => 'Department with this name already exists'], 422);
        }

        $department = new Department();
        $department->name = $request->name;
        $department->companyId = $request->companyId;

        $department->save();

        return response()->json('Department inserted successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $departmentId)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'companyId' => 'required',
        ]);

        $companyExists = Department::where('name', $request->name)
            ->where('companyId', $request->companyId)
            ->where('departmentId', '<>', $departmentId)
            ->exists();

        if ($companyExists) {
            return response()->json(['message' => 'Department with this name already exists'], 422);
        }

        $department = Department::find($departmentId);
        $department->name = $request->name;
        $department->companyId = $request->companyId;

        $department->update();

        return response()->json('Department Updated Sucessfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $departmentId)
    {
        $department = Department::find($departmentId);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        if ($department->jobs()->exists()) {
            return response()->json(['message' => 'Department has been assigned to jobs, cannot be deleted'], 422);
        }
        $department->deleted_at = now();
        $department->save();
        return response()->json('Department Deleted Sucessfully');
    }
}
