<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        return view('admin.categories.index', [
            'categories' => ExpenseCategory::orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        ExpenseCategory::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_categories,code'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, ExpenseCategory $category): RedirectResponse
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $category->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_categories,code,'.$category->id],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return back()->with('status', 'Category updated.');
    }
}
