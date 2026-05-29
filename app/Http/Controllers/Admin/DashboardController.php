<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PortfolioCategory;
use App\Models\PortfolioImage;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'images' => PortfolioImage::count(),
            'categories' => PortfolioCategory::count(),
            'messages' => ContactMessage::count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
