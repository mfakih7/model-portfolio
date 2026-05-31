<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PortfolioCategory;
use App\Models\PortfolioImage;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public const INITIAL_COUNT = 12;

    public const LOAD_MORE_COUNT = 9;

    public function index(?string $category = null)
    {
        $settings = SiteSetting::current();
        $categories = PortfolioCategory::where('status', true)->orderBy('name')->get();

        [$images, $total] = $this->queryImages($category);

        $activeCategory = $category
            ? $categories->firstWhere('slug', $category)
            : null;

        $hasMore = $total > $images->count();

        return view('public.portfolio', compact(
            'settings',
            'categories',
            'images',
            'activeCategory',
            'category',
            'total',
            'hasMore',
        ));
    }

    public function loadMore(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->input('offset', self::INITIAL_COUNT));
        $limit = min(self::LOAD_MORE_COUNT, max(1, (int) $request->input('limit', self::LOAD_MORE_COUNT)));
        $category = $request->input('category');

        [$images, $total] = $this->queryImages($category, $offset, $limit);

        $nextOffset = $offset + $images->count();

        return response()->json([
            'html' => view('partials.portfolio-grid-items', [
                'images' => $images,
                'offset' => $offset,
            ])->render(),
            'nextOffset' => $nextOffset,
            'hasMore' => $nextOffset < $total,
        ]);
    }

    /**
     * @return array{0:\Illuminate\Support\Collection<int, PortfolioImage>, 1:int}
     */
    private function queryImages(?string $category, ?int $offset = null, ?int $limit = null): array
    {
        $query = PortfolioImage::with('category')
            ->whereHas('category', fn ($q) => $q->where('status', true))
            ->orderBy('sort_order');

        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $total = (clone $query)->count();

        if ($offset !== null) {
            $query->skip($offset);
        }

        if ($limit !== null) {
            $query->take($limit);
        } elseif ($offset === null) {
            $query->take(self::INITIAL_COUNT);
        }

        return [$query->get(), $total];
    }
}
