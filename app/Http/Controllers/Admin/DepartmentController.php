<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isDirector(), 403);

        return view('admin.departments.index', [
            'departments' => Department::orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        Department::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return back()->with('status', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        $department->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code,'.$department->id],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return back()->with('status', 'Department updated.');
    }
}
