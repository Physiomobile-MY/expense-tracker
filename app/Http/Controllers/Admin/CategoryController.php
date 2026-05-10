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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_categories,code'],
            'description' => ['nullable', 'string'],
            'keywords_text' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        ExpenseCategory::create($this->categoryPayload($validated));

        return back()->with('status', 'Category created.');
    }

    public function update(Request $request, ExpenseCategory $category): RedirectResponse
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_categories,code,'.$category->id],
            'description' => ['nullable', 'string'],
            'keywords_text' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $category->update($this->categoryPayload($validated));

        return back()->with('status', 'Category updated.');
    }

    private function categoryPayload(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'code' => str($validated['code'])->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString(),
            'description' => $validated['description'] ?? null,
            'keywords' => $this->parseKeywords($validated['keywords_text'] ?? ''),
            'status' => $validated['status'],
        ];
    }

    private function parseKeywords(?string $keywords): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $keywords))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique(fn (string $keyword): string => str($keyword)->lower()->toString())
            ->values()
            ->all();
    }
}
