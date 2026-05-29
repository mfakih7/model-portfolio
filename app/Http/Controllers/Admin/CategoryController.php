<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\PortfolioCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = PortfolioCategory::withCount('images')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        PortfolioCategory::create([
            'name' => $request->name,
            'slug' => $request->slug ?: PortfolioCategory::generateSlug($request->name),
            'status' => $request->boolean('status', true),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(PortfolioCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, PortfolioCategory $category): RedirectResponse
    {
        $category->update([
            'name' => $request->name,
            'slug' => $request->slug ?: PortfolioCategory::generateSlug($request->name, $category->id),
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(PortfolioCategory $category): RedirectResponse
    {
        if ($category->images()->exists()) {
            return back()->with('error', 'Cannot delete category with existing images.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
