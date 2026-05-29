<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') | Model Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="admin-body">
    <header class="admin-topbar d-lg-none">
        <button class="admin-topbar-toggle" type="button" id="sidebarToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="adminSidebar">
            <i class="bi bi-list"></i>
        </button>
        <span class="admin-topbar-brand"><i class="bi bi-gem me-2"></i>Portfolio Admin</span>
    </header>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="admin-sidebar" id="adminSidebar">
        <div class="brand"><i class="bi bi-gem me-2"></i>Portfolio Admin</div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a class="nav-link {{ request()->routeIs('admin.portfolio.*') ? 'active' : '' }}" href="{{ route('admin.portfolio.index') }}"><i class="bi bi-images me-2"></i> Portfolio</a>
            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags me-2"></i> Categories</a>
            <a class="nav-link {{ request()->routeIs('admin.about.*') ? 'active' : '' }}" href="{{ route('admin.about.edit') }}"><i class="bi bi-person-lines-fill me-2"></i> About</a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-gear me-2"></i> Settings</a>
            <a class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}"><i class="bi bi-envelope me-2"></i> Messages</a>
            <a class="nav-link {{ request()->routeIs('admin.account.*') ? 'active' : '' }}" href="{{ route('admin.account.edit') }}"><i class="bi bi-person-gear me-2"></i> Account Settings</a>
            <hr class="border-secondary opacity-25 mx-3">
            <a class="nav-link" href="{{ route('home') }}" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i> View Site</a>
            <form action="{{ route('admin.logout') }}" method="POST" class="px-3">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent text-start w-100"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
            </form>
        </nav>
    </aside>

    <main class="admin-main">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h1 class="h3 mb-0">@yield('page_title', 'Dashboard')</h1>
            @yield('page_actions')
        </div>

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
