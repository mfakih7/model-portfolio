<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioImageRequest;
use App\Models\PortfolioCategory;
use App\Models\PortfolioImage;
use App\Services\PortfolioImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioImageController extends Controller
{
    public function __construct(private PortfolioImageService $images) {}

    public function index(): View
    {
        $images = PortfolioImage::with('category')->orderBy('sort_order')->get();

        return view('admin.portfolio.index', compact('images'));
    }

    public function create(): View
    {
        $categories = PortfolioCategory::where('status', true)->orderBy('name')->get();

        return view('admin.portfolio.create', compact('categories'));
    }

    public function store(PortfolioImageRequest $request): RedirectResponse
    {
        $variants = $this->images->generate($request->file('image'));

        PortfolioImage::create([
            'portfolio_category_id' => $request->portfolio_category_id,
            'title' => $request->title,
            'description' => $request->description,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $request->input('sort_order', PortfolioImage::max('sort_order') + 1),
            ...$variants,
        ]);

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio image uploaded successfully.');
    }

    public function edit(PortfolioImage $portfolio): View
    {
        $categories = PortfolioCategory::orderBy('name')->get();

        return view('admin.portfolio.edit', ['image' => $portfolio, 'categories' => $categories]);
    }

    public function update(PortfolioImageRequest $request, PortfolioImage $portfolio): RedirectResponse
    {
        $data = [
            'portfolio_category_id' => $request->portfolio_category_id,
            'title' => $request->title,
            'description' => $request->description,
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $request->input('sort_order', $portfolio->sort_order),
        ];

        if ($request->hasFile('image')) {
            $this->images->deleteVariants($portfolio);
            $data = [...$data, ...$this->images->generate($request->file('image'))];
        }

        $portfolio->update($data);

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio image updated successfully.');
    }

    public function destroy(PortfolioImage $portfolio): RedirectResponse
    {
        $this->images->deleteVariants($portfolio);
        $portfolio->delete();

        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio image deleted.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:portfolio_images,id']);

        foreach ($request->order as $position => $id) {
            PortfolioImage::where('id', $id)->update(['sort_order' => $position]);
        }

        return back()->with('success', 'Image order updated.');
    }
}
