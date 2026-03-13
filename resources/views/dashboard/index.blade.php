<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChatBoard - Performance Dashboard</title>
    <link rel="icon" type="image/png" href="/LogoT.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/loading-alerts.css') }}">
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
</head>

<body
    class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-gray-800 dark:text-white min-h-screen">
    <!-- Floating shapes background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="flex items-center space-x-4 transition-all duration-300">
                <div
                    class="w-12 h-12 rounded-xl overflow-hidden bg-white/10 backdrop-blur-sm flex items-center justify-center shadow-inner ring-1 ring-white/20">
                    <img src="{{ asset('assets/img/LOGO2.png') }}" alt="Logo" class="h-16 w-16 object-contain">
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                        ChatBoard
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Performance Dashboard
                    </p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item active" data-target="dashboardSection">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </div>
            <div class="nav-item" data-target="analyticsSection">
                <i class="fas fa-chart-bar"></i>
                <span>Analytic</span>
            </div>
            <div class="nav-item" data-target="agentsSection">
                <i class="fas fa-users"></i>
                <span>Agent</span>
            </div>
            <div class="nav-item" data-target="customerSection">
                <i class="fas fa-user"></i>
                <span>Customer</span>
            </div>
             {{-- <div class="nav-item" data-target="analyticsSection">
                <i class="fas fa-map-marked-alt"></i>
                <span>Area</span>
            </div> --}}
            <a id="navChatbotLink" data-external="true" href="{{ route('admin.chatbot') }}" class="nav-item">
                <i class="fas fa-robot"></i>
                <span>Chatbot</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="relative container mx-auto px-4 py-8">
            <div id="dashboardSection" class="content-section transition-opacity duration-300 py-5 px-3">
                <div class="header-section flex flex-col md:flex-row justify-between items-center mb-6">
                    <div class="text-center md:text-left mb-3 md:mb-0">
                        <h1
                            class="flex items-center text-3xl md:text-4xl font-light text-gray-700 dark:text-gray-200 mb-1">
                            Report Monitoring</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-light">Monitor Layanan & Feedback System
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button id="darkToggle"
                            class="glass-button px-4 py-2 rounded-full shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-adjust text-xs text-gray-600 dark:text-gray-400"></i>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Switch Theme</span>
                            </span>
                        </button>
                        <div class="profile-container" id="profileContainer">
                            <button id="profileToggle"
                                class="glass-button px-3 py-2 rounded-full shadow border border-gray-200 dark:border-gray-700 flex items-center gap-1.5 hover:shadow-md transition-all duration-300">
                                <i class="fas fa-user-circle text-xs text-gray-600 dark:text-gray-400"></i>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Profile</span>
                                <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400 transition-transform duration-200"
                                    id="chevronIcon"></i>
                            </button>
                            <div id="profileDropdown"
                                class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                                <div class="p-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-white shadow">
                                            <img id="user-avatar"
                                                src="{{ Auth::user()->photo ?: asset('assets/img/profile.jpeg') }}"
                                                data-name="{{ Auth::user()->name ?? 'User' }}" alt="User Avatar"
                                                class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ Auth::user()->name ?? 'User' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ Auth::user()->email ??
                                        'user@example.com' }}</p>
                                </div>
                                <div class="p-2">
                                    <button id="logoutButton" onclick="document.getElementById('logoutForm').submit();"
                                        class="rounded-lg w-full flex items-center px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs">
                                        <i class="fas fa-sign-out-alt mr-2"></i>
                                        <span>Logout</span>
                                    </button>
                                    <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div
                        class="glass-effect p-5 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-light text-gray-700 dark:text-gray-200">Response Status</h2>
                            <div
                                class="icon-container bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-600 dark:text-gray-300 p-2 rounded-lg">
                                <i class="fas fa-tachometer-alt text-sm"></i>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fast
                                        Response</span>
                                </div>
                                <span
                                    class="text-xl font-bold text-green-600">{{ number_format((float) ($fastCount ?? 125), 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Slow
                                        Response</span>
                                </div>
                                <span
                                    class="text-xl font-bold text-red-600">{{ number_format((float) ($slowCount ?? 23), 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div
                        class="glass-effect p-5 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-light text-gray-700 dark:text-gray-200">Data Summary</h2>
                            <div
                                class="icon-container bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-600 dark:text-gray-300 p-2 rounded-lg">
                                <i class="fas fa-chart-bar text-sm"></i>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-1">
                                    {{ number_format((float) ($totalAllMessages ?? 0), 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Chat Masuk</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-1">
                                    {{ number_format((float) ($totalFeedback ?? 0), 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Feedback</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-1">
                                    {{ number_format((float) ($positivePercentage ?? 0), 2) }}%
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Positive Rating</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <div class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-1">
                                    {{ $negativePercentage }}%
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Negative Rating</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Satisfaction Ratings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div
                        class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="text-center">
                            <div class="text-5xl mb-3">😡</div>
                            <h3 class="text-lg font-light text-gray-700 dark:text-gray-200 mb-1">Sangat Tidak Puas</h3>
                            <div class="text-2xl font-light text-red-500 mb-1">{{ $ratingCounts['marah'] ?? 5 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pengguna</div>
                        </div>
                    </div>
                    <div
                        class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="text-center">
                            <div class="text-5xl mb-3">☹️</div>
                            <h3 class="text-lg font-light text-gray-700 dark:text-gray-200 mb-1">Tidak Puas</h3>
                            <div class="text-2xl font-light text-orange-500 mb-1">{{ $ratingCounts['sedih'] ?? 12 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pengguna</div>
                        </div>
                    </div>
                    <div
                        class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="text-center">
                            <div class="text-5xl mb-3">😐</div>
                            <h3 class="text-lg font-light text-gray-700 dark:text-gray-200 mb-1">Netral</h3>
                            <div class="text-2xl font-light text-gray-500 mb-1">{{ $ratingCounts['datar'] ?? 28 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pengguna</div>
                        </div>
                    </div>
                    <div
                        class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="text-center">
                            <div class="text-5xl mb-3">😊</div>
                            <h3 class="text-lg font-light text-gray-700 dark:text-gray-200 mb-1">Puas</h3>
                            <div class="text-2xl font-light text-green-500 mb-1">{{ $ratingCounts['puas'] ?? 67 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pengguna</div>
                        </div>
                    </div>
                    <div
                        class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-300">
                        <div class="text-center">
                            <div class="text-5xl mb-3">😍</div>
                            <h3 class="text-lg font-light text-gray-700 dark:text-gray-200 mb-1">Sangat Puas</h3>
                            <div class="text-2xl font-light text-blue-500 mb-1">{{ $ratingCounts['sangat puas'] ?? 36 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Pengguna</div>
                        </div>
                    </div>
                </div>

                <!-- Interaction Log Table -->
                <div class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-light text-gray-700 dark:text-gray-200">Review Log</h3>
                        <div class="flex gap-2 items-center">
                            <!-- Date Filter -->
                            <div class="flex gap-2">
                                <input type="date" name="date_from"
                                    value="{{ request()->date_from ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-3 py-1 border rounded text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600">
                                <input type="date" name="date_to"
                                    value="{{ request()->date_to ?: now()->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-3 py-1 border rounded text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600">
                            </div>
                            <!-- Dropdown for Export Type -->
                            <select name="export_type"
                                class="px-3 py-1 border rounded text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600">
                                <option value="review_log">Export Review Log</option>
                                <option value="message_log">Export Message Log</option>
                            </select>
                            <a href="#" id="export-button"
                                data-export-review-log="{{ route('dashboard.export-review-log') }}"
                                data-export-message-log="{{ route('dashboard.export-message-log') }}"
                                class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                </svg>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full text-xs text-left text-gray-700 dark:text-gray-300">
                            <thead
                                class="text-xs uppercase bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">CS ID</th>
                                    <th class="px-3 py-2">Agent Name</th>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Rating</th>
                                </tr>
                            </thead>
                            <tbody id="ratingsTableBody">
                                @foreach ($satisfactionRatings as $rating)
                                    <tr
                                        class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-3 py-1.5">{{ $rating->cs_id ?? 'N/A' }}</td>
                                        <td class="px-3 py-1.5">{{ $rating->cs->username ?? $rating->cs_id ?? 'N/A' }}</td>
                                        <td class="px-3 py-1.5">
                                            {{ \Carbon\Carbon::parse($rating->received_at)->format('Y-m-d') }}
                                        </td>
                                        <td class="px-3 py-1.5">
                                            @php
                                                $decodedRating = json_decode($rating->rating, true);
                                                $ratingId = strtolower($decodedRating['id'] ?? 'datar');
                                                $starCount = match ($ratingId) {
                                                    'marah' => 1,
                                                    'sedih' => 2,
                                                    'datar' => 3,
                                                    'puas' => 4,
                                                    'sangat puas' => 5,
                                                    default => 3,
                                                };
                                                $colorClass = match ($ratingId) {
                                                    'marah' => 'text-red-500',
                                                    'sedih' => 'text-orange-500',
                                                    'datar' => 'text-gray-500',
                                                    'puas' => 'text-green-500',
                                                    'sangat puas' => 'text-green-600 font-semibold',
                                                    default => 'text-gray-500',
                                                };
                                            @endphp
                                            <span class="{{ $colorClass }}">
                                                {{ ucfirst($ratingId) }}
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if ($i <= $starCount)
                                                        <span>★</span>
                                                    @else
                                                        <span>☆</span>
                                                    @endif
                                                @endfor
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($satisfactionRatings, 'total') && $satisfactionRatings->total() > 0)
                        <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                Showing {{ $satisfactionRatings->firstItem() }} to {{ $satisfactionRatings->lastItem() }} of
                                {{ $satisfactionRatings->total() }} ratings
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $ratingQuery = request()->except(['rating_page']);
                                    $prevRatingUrl = $satisfactionRatings->previousPageUrl();
                                    $nextRatingUrl = $satisfactionRatings->nextPageUrl();
                                @endphp
                                @if($satisfactionRatings->onFirstPage())
                                    <span
                                        class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 rounded-lg">Prev</span>
                                @else
                                    <a data-ajax="ratings" href="{{ $prevRatingUrl }}&section=dashboard"
                                        class="rating-nav px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">Prev</a>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400">Page
                                    {{ $satisfactionRatings->currentPage() }} /
                                    {{ $satisfactionRatings->lastPage() }}</span>
                                @if($satisfactionRatings->hasMorePages())
                                    <a data-ajax="ratings" href="{{ $nextRatingUrl }}&section=dashboard"
                                        class="rating-nav px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">Next</a>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 rounded-lg">Next</span>
                                @endif
                                <form method="GET" class="ml-2">
                                    @foreach(request()->except(['rating_per_page', 'rating_page']) as $k => $v)
                                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                                    @endforeach
                                    <input type="hidden" name="section" value="dashboard" />
                                    <select name="rating_per_page" onchange="this.form.submit()"
                                        class="px-2 py-1 border border-gray-300 rounded-lg bg-white dark:bg-gray-800 text-xs focus:ring-1 focus:ring-blue-500">
                                        @foreach([10, 20, 50, 100] as $opt)
                                            <option value="{{ $opt }}" {{ ($ratingPerPage ?? 10) == $opt ? 'selected' : '' }}>
                                                {{ $opt }}/page
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div id="agentsSection" class="content-section hidden py-6" x-data="{
                searchQuery: '{{ $searchQuery ?? '' }}',
                agents: {{ $csAgents->isEmpty() ? '[]' : Js::from($csAgents) }},
                formatDuration(hours) {
                    if (!hours) return '0h 0m';
                    const h = Math.floor(hours);
                    const m = Math.floor((hours - h) * 60);
                    return `${h}h ${m}m`;
                },
                filteredAgents() {
                    if (!this.searchQuery) return this.agents;
                    return this.agents.filter(agent => agent.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
                }
                }">
                <!-- Header -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Our Customer Service Agents</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Meet our dedicated support team, ready to
                        assist you anytime.</p>
                    <div class="mt-2 h-0.5 w-12 bg-blue-500 rounded-full mx-auto"></div>
                </div>

                <!-- CSAT Statistics Cards -->
                <div id="csatStatsCards" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5 mt-6 px-3">
                    <div
                        class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg shadow border border-orange-200 dark:border-orange-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300 mb-1">Total CSAT
                                    Sent</h3>
                                <p class="text-2xl font-bold text-orange-600">
                                    {{ number_format($totalCSATSent ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg shadow border border-green-200 dark:border-green-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-green-700 dark:text-green-300 mb-1">Total CSAT
                                    Responded</h3>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ number_format($totalCSATResponded ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg shadow border border-blue-200 dark:border-blue-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-1">Average First
                                    Response Time</h3>
                                <p class="text-2xl font-bold text-blue-600">{{ $avgResponseTime ?? '0m 0s' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Metrics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5 mt-6 px-3">
                    <div
                        class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg shadow border border-purple-200 dark:border-purple-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-300 mb-1">Average
                                    Handle Time</h3>
                                <p class="text-2xl font-bold text-purple-600">{{ $avgAHT ?? '0m 0s' }}</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg shadow border border-yellow-200 dark:border-yellow-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-yellow-700 dark:text-yellow-300 mb-1">Customer
                                    Satisfaction</h3>
<p class="text-2xl font-bold text-yellow-600">{{ number_format($avgCSAT ?? 0, 1) }}%</p>
{{-- PERUBAHAN BLACKBOXAI: CSAT sekarang dalam persentase (4.5/5*100%=90%) dengan format 1 desimal --}}
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg shadow border border-indigo-200 dark:border-indigo-800 transition-transform hover:-translate-y-0.5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-indigo-700 dark:text-indigo-300 mb-1">Chat
                                    Resolved</h3>
                                <p class="text-2xl font-bold text-indigo-600">
                                    {{ number_format($chatResolved ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agent CSAT Response Table -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-6 mt-6 mx-3">
                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4 gap-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-white">
                            Agent Response
                        </h3>
                        <!-- Filter Wrapper -->
                        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 w-full lg:w-auto">
                            <!-- Search -->
                            <input type="text" id="csatSearch" placeholder="Search customer or agent..."
                                value="{{ $csatSearch }}" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg
                                    bg-white dark:bg-gray-700 text-xs focus:outline-none focus:ring-1
                                    focus:ring-blue-500 w-full sm:w-36">
                            <!-- Date range -->
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <input type="date" id="csatStartDate"
                                    value="{{ $csatStartDate ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-white dark:bg-gray-700 text-xs focus:outline-none
                                        focus:ring-1 focus:ring-blue-500 flex-1">
                                <span class="text-gray-500 dark:text-gray-400 text-xs">to</span>
                                <input type="date" id="csatEndDate" value="{{ $csatEndDate ?: now()->toDateString() }}"
                                    max="{{ now()->toDateString() }}" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-white dark:bg-gray-700 text-xs focus:outline-none
                                        focus:ring-1 focus:ring-blue-500 flex-1">
                            </div>
                            <!-- CS Selector -->
                            <select id="csatCsSelect" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg
                                    bg-white dark:bg-gray-700 text-xs focus:outline-none focus:ring-1
                                    focus:ring-blue-500 w-full sm:w-32">
                                <option value="">All CS</option>
                                @foreach($allCsAgents ?? [] as $username)
                                    <option value="{{ $username }}" {{ $csatCsId == $username ? 'selected' : '' }}>
                                        {{ $username }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- Action Buttons -->
                            <div class="flex gap-2 w-full sm:w-auto">
                                <!-- Filter Button -->
                                <button onclick="filterCSATTable()" class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg
                                        text-xs font-medium transition-colors flex items-center gap-1
                                        flex-1 sm:flex-none">
                                    <i class="fas fa-filter text-xs"></i> Filter
                                </button>
                                <!-- Report Dropdown -->
                                <div class="relative flex-1 sm:flex-none">
                                    <button onclick="toggleReportDropdown()" class="px-2 py-1 bg-gradient-to-r from-green-500 to-blue-500
                                            hover:from-green-600 hover:to-blue-600 text-white rounded-lg
                                            text-xs font-medium transition-all flex items-center gap-1
                                            w-full sm:w-auto">
                                        <i class="fas fa-chart-line text-xs"></i> Report
                                        <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                                            id="reportDropdownIcon"></i>
                                    </button>
                                    <div id="reportDropdownMenu" class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 rounded-lg
                                            shadow-xl border border-gray-200 dark:border-gray-700 hidden
                                            z-50 transition-all duration-200">
                                        <div class="py-1">

                                            <!-- Export Report -->
                                            <a href="#" onclick="exportFilteredReport()" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700
                                                    dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700
                                                    transition-colors border-b border-gray-100 dark:border-gray-600">
                                                <div class="w-5 h-5 bg-green-100 dark:bg-green-900 rounded-full
                                                            flex items-center justify-center">
                                                    <i
                                                        class="fas fa-download text-green-600 dark:text-blue-400 text-xs"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium">Export Report</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">Excel report
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Show</span>
                            <select id="csatPerPage" onchange="changeCSATPerPage()"
                                class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="10" {{ $csatPerPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $csatPerPage == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $csatPerPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $csatPerPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <span class="text-xs text-gray-600 dark:text-gray-400">entries</span>
                        </div>
                        <div id="csatSummaryTop" class="text-xs text-gray-600 dark:text-gray-400">
                            Showing {{ $csatResponsesPaginated->firstItem() ?? 0 }} to
                            {{ $csatResponsesPaginated->lastItem() ?? 0 }} of {{ $csatResponsesPaginated->total() }}
                            entries
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">
                                        Customer Name</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Agent
                                        Name</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Date
                                    </th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                        onclick="sortTable('first_response_time')">
                                        <div class="flex items-center gap-1">
                                            <span>First Response Time</span>
                                            <span class="text-gray-400">↑↓</span>
                                        </div>
                                    </th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                        onclick="sortTable('average_response_time')">
                                        <div class="flex items-center gap-1">
                                            <span>Average Response Time</span>
                                            <span class="text-gray-400">↑↓</span>
                                        </div>
                                    </th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                        onclick="sortTable('resolved_time')">
                                        <div class="flex items-center gap-1">
                                            <span>Resolved Time</span>
                                            <span class="text-gray-400">↑↓</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="csatTableBody">
                                @forelse($csatResponsesPaginated as $response)
                                    <tr
                                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                            {{ $response['customer_name'] }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $response['agent_name'] }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $response['date'] }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                            {{ $response['first_response_time'] ?? '-' }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                            {{ $response['average_response_time'] ?? '00:00' }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                            {{ $response['resolved_time'] ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 px-3 text-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl mb-3 block"></i>No CSAT data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($csatResponsesPaginated->hasPages())
                        <div id="csatPagination" class="mt-5 flex flex-col items-center gap-3">
                            <div id="csatSummaryBottom" class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                Showing {{ $csatResponsesPaginated->firstItem() ?? 0 }} to
                                {{ $csatResponsesPaginated->lastItem() ?? 0 }} of {{ $csatResponsesPaginated->total() }}
                                entries
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($csatResponsesPaginated->onFirstPage())
                                    <span
                                        class="px-2 py-1 text-xs text-gray-400 border rounded cursor-not-allowed">Previous</span>
                                @else
                                    <a href="{{ $csatResponsesPaginated->previousPageUrl() }}"
                                        class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Previous</a>
                                @endif
                                @php
                                    $current = $csatResponsesPaginated->currentPage();
                                    $last = $csatResponsesPaginated->lastPage();
                                    $start = max($current - 2, 1);
                                    $end = min($current + 2, $last);
                                @endphp
                                @if ($start > 1)
                                    <a href="{{ $csatResponsesPaginated->url(1) }}"
                                        class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">1</a>
                                    @if ($start > 2)
                                        <span class="px-2 py-1 text-xs">...</span>
                                    @endif
                                @endif
                                @for ($i = $start; $i <= $end; $i++)
                                    @if ($i == $current)
                                        <span
                                            class="px-2 py-1 text-xs bg-blue-500 text-white border border-blue-500 rounded">{{ $i }}</span>
                                    @else
                                        <a href="{{ $csatResponsesPaginated->url($i) }}"
                                            class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">{{ $i }}</a>
                                    @endif
                                @endfor
                                @if ($end < $last)
                                    @if ($end < $last - 1)
                                        <span class="px-2 py-1 text-xs">...</span>
                                    @endif
                                    <a href="{{ $csatResponsesPaginated->url($last) }}"
                                        class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">{{ $last }}</a>
                                @endif
                                @if ($csatResponsesPaginated->hasMorePages())
                                    <a href="{{ $csatResponsesPaginated->nextPageUrl() }}"
                                        class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Next</a>
                                @else
                                    <span class="px-2 py-1 text-xs text-gray-400 border rounded cursor-not-allowed">Next</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Filter & Search -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 mb-5 px-3">
                    <form method="GET" id="agentDateFilter" class="flex items-center gap-2"
                        x-data="{ isLoading: false }" x-on:submit="isLoading = true">
                        @foreach(request()->except(['status_date', 'agent_page', 'agent_per_page']) as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                        @endforeach
                        <input type="hidden" name="section" value="agents" />
                        <input type="hidden" name="search" x-bind:value="searchQuery" />
                        <label for="status_date" class="text-xs text-gray-600 dark:text-gray-400">Filter by
                            Date:</label>
                        <input type="date" name="status_date" id="status_date"
                            value="{{ $selectedDate ?? now()->toDateString() }}" max="{{ now()->toDateString() }}"
                            class="px-2 py-1 border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 w-36" />
                        <button type="submit"
                            class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-xs font-medium transition-colors"
                            :disabled="isLoading">
                            <span x-show="!isLoading">Apply</span>
                            <span x-show="isLoading" class="flex items-center"><i
                                    class="fas fa-spinner fa-spin mr-1 text-xs"></i>Loading</span>
                        </button>
                    </form>
                    <div class="w-full sm:w-auto relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" x-model="searchQuery" placeholder="Search agents by name..."
                            class="w-full pl-7 pr-2 py-1 border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            aria-label="Search agents">
                    </div>
                </div>

                <!-- Selected Date -->
                <div class="text-center text-xs text-gray-600 dark:text-gray-400 mb-4 px-3">
                    Showing status duration for:
                    <strong>{{ \Carbon\Carbon::parse($selectedDate ?? now()->toDateString())->format('d M Y') }}</strong>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow mx-3">
                    <table class="min-w-full table-fixed text-xs text-gray-700 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr class="border-b border-gray-200 dark:border-gray-600">
                                <th class="w-36 py-2 px-3 text-left font-medium text-gray-900 dark:text-gray-100">Name
                                </th>
                                <th class="w-48 py-2 px-3 text-left font-medium text-gray-900 dark:text-gray-100">Email
                                </th>
                                <th class="w-20 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Status</th>
                                <th class="w-16 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Feedback</th>
                                <th class="w-24 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">Fast
                                    Response</th>
                                <th class="w-24 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">Slow
                                    Response</th>
                                <th class="w-24 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Online Time</th>
                                <th class="w-16 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Chats</th>
                                <th class="w-28 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Satisfaction</th>
                                <th class="w-40 py-2 px-3 text-center font-medium text-gray-900 dark:text-gray-100">
                                    Status Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(agent, index) in filteredAgents()" :key="index">
                                <tr
                                    class="border-b border-gray-100 dark:border-gray-700 odd:bg-white dark:odd:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="py-2 px-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-7 h-7 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                <span x-text="agent.name.charAt(0)"></span>
                                            </span>
                                            <span x-text="agent.name"
                                                class="truncate text-gray-900 dark:text-gray-100"></span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 truncate text-gray-600 dark:text-gray-300"
                                        x-text="agent.contact || '-'"></td>
                                    <td class="py-2 px-3 text-center">
                                        <span class="px-1.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                            'bg-green-100 text-green-700': agent.status === 'online',
                                            'bg-yellow-100 text-yellow-700': agent.status === 'busy',
                                            'bg-gray-100 text-gray-600': agent.status === 'offline'
                                            }">
                                            <span
                                                x-text="agent.status.charAt(0).toUpperCase() + agent.status.slice(1)"></span>
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-center" x-text="agent.feedback ?? 0"></td>
                                    <td class="py-2 px-3 text-center"
                                        x-text="agent.avg_fast ? agent.avg_fast + 'm' : '0m'"></td>
                                    <td class="py-2 px-3 text-center"
                                        x-text="agent.avg_slow ? agent.avg_slow + 'm' : '0m'"></td>
                                    <td class="py-2 px-3 text-center" x-text="agent.online_time || '-'"></td>
                                    <td class="py-2 px-3 text-center" x-text="agent.total_handle_chat ?? 0"></td>
                                    <td class="py-2 px-3 text-center">
                                        <span x-text="(agent.satisfaction ?? 0) + '%'"
                                            class="font-medium text-gray-900 dark:text-gray-100"></span>
                                        <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full mt-1">
                                            <div class="h-1.5 rounded-full transition-all duration-300"
                                                :style="`width: ${(agent.satisfaction ?? 0)}%`" :class="{
                                                    'bg-green-600': (agent.satisfaction ?? 0) >= 90,
                                                    'bg-yellow-500': (agent.satisfaction ?? 0) >= 75 && (agent.satisfaction ?? 0) < 90,
                                                    'bg-red-500': (agent.satisfaction ?? 0) < 75
                                                }">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-xs min-w-[150px]">
                                        <template x-for="duration in agent.status_durations" :key="duration.status">
                                            <div class="flex justify-between mb-1 px-1">
                                                <span class="capitalize" x-text="duration.status"></span>
                                                <span x-text="formatDuration(duration.durasi_jam)"></span>
                                            </div>
                                        </template>
                                        <div class="flex justify-between font-medium bg-blue-50 dark:bg-blue-800
                                                p-1.5 rounded mt-1">
                                            <span>Total</span>
                                            <span
                                                x-text="formatDuration(agent.status_durations.reduce((sum, d) => sum + d.durasi_jam, 0))"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($csAgentsPaginated) && $csAgentsPaginated->total() > 0)
                    <div class="mt-5 flex flex-col sm:flex-row justify-between items-center gap-3 px-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Showing {{ $csAgentsPaginated->firstItem() }} to {{ $csAgentsPaginated->lastItem() }} of
                            {{ $csAgentsPaginated->total() }} agents
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $prevAgentUrl = $csAgentsPaginated->previousPageUrl();
                                $nextAgentUrl = $csAgentsPaginated->nextPageUrl();
                                $currentDate = $selectedDate ?? now()->toDateString();
                            @endphp
                            @if($csAgentsPaginated->onFirstPage())
                                <span
                                    class="px-2 py-1 text-xs bg-gray-200 text-gray-400 rounded-md cursor-not-allowed">Prev</span>
                            @else
                                <a data-ajax="agents"
                                    href="{{ $prevAgentUrl }}&section=agents&status_date={{ $currentDate }}&search={{ $searchQuery ?? '' }}"
                                    class="agent-nav px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors">Prev</a>
                            @endif
                            <span class="text-xs text-gray-500 font-medium">Page {{ $csAgentsPaginated->currentPage() }} /
                                {{ $csAgentsPaginated->lastPage() }}</span>
                            @if($csAgentsPaginated->hasMorePages())
                                <a data-ajax="agents"
                                    href="{{ $nextAgentUrl }}&section=agents&status_date={{ $currentDate }}&search={{ $searchQuery ?? '' }}"
                                    class="agent-nav px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors">Next</a>
                            @else
                                <span
                                    class="px-2 py-1 text-xs bg-gray-200 text-gray-400 rounded-md cursor-not-allowed">Next</span>
                            @endif
                            <form method="GET" class="ml-2">
                                @foreach(request()->except(['agent_per_page', 'agent_page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                                @endforeach
                                <input type="hidden" name="section" value="agents" />
                                <input type="hidden" name="status_date" value="{{ $currentDate }}" />
                                <input type="hidden" name="search" x-bind:value="searchQuery" />
                                <select name="agent_per_page" onchange="this.form.submit()"
                                    class="px-2 py-1 border border-gray-300 rounded-md bg-white dark:bg-gray-800 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    aria-label="Agents per page">
                                    @foreach([6, 12, 24, 48, 96] as $opt)
                                        <option value="{{ $opt }}" {{ ($agentPerPage ?? 6) == $opt ? 'selected' : '' }}>
                                            {{ $opt }}/page
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <!--
            <div id="reviewsSection" class="content-section hidden py-5">
                <div class="flex justify-between items-center mb-5 px-3">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Customer Reviews</h2>
                    <div class="flex items-center gap-2">
                        <label for="perPageSelect" class="text-xs text-gray-600 dark:text-gray-400">Show:</label>
                        <select id="perPageSelect"
                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            onchange="changePerPage(this.value)">
                            <option value="10" {{ $currentPerPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $currentPerPage == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $currentPerPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $currentPerPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="text-xs text-gray-600 dark:text-gray-400">per page</span>
                    </div>
                </div>

                <div class="space-y-5 px-3">
                    @if ($reviewsPaginated->count() > 0)
                        @foreach ($reviewsPaginated as $review)
                            <div
                                class="relative bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700 transition-transform hover:-translate-y-0.5 hover:shadow-md">
                                <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" title="Customer review details"><i
                                            class="fas fa-info-circle"></i></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $review['avatar'] }}" alt="{{ $review['name'] }}"
                                            class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700"
                                            onerror="this.src=`https://ui-avatars.com/api/?name={{ urlencode($review['name']) }}&size=40`;">
                                        <div>
                                            <p class="font-semibold text-base text-gray-800 dark:text-gray-100">
                                                {{ $review['name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $review['time_ago'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 text-yellow-400">
                                        <span class="text-xs"
                                            title="{{ $review['rating'] }} out of 5 stars">{{ $review['stars'] }}</span>
                                    </div>
                                </div>
                                <p class="mt-3 text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                                    {{ Str::limit($review['message'], 150) }}</p>
                                <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                    Sent: {{ \Carbon\Carbon::parse($review['timestamp'])->format('M d, Y \a\t H:i') }}
                                </div>
                            </div>
                        @endforeach

                        <div
                            class="mt-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    Showing {{ $reviewsPaginated->firstItem() ?? 0 }} to
                                    {{ $reviewsPaginated->lastItem() ?? 0 }} of {{ $reviewsPaginated->total() }} results
                                </div>
                                <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2">
                                    @if ($reviewsPaginated->onFirstPage())
                                        <span
                                            class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed"
                                            aria-label="Previous page disabled">Previous</span>
                                    @else
                                        <a href="{{ $reviewsPaginated->appends(['per_page' => $currentPerPage, 'section' => 'reviews'])->previousPageUrl() }}"
                                            class="px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            aria-label="Go to previous page">Previous</a>
                                    @endif

                                    @php
                                        $start = max($reviewsPaginated->currentPage() - 2, 1);
                                        $end = min($start + 4, $reviewsPaginated->lastPage());
                                        $start = max($end - 4, 1);
                                    @endphp

                                    @if ($start > 1)
                                        <a href="{{ $reviewsPaginated->appends(['per_page' => $currentPerPage, 'section' => 'reviews'])->url(1) }}"
                                            class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            aria-label="Go to page 1">1</a>
                                        @if ($start > 2)
                                            <span class="px-2 text-xs text-gray-500 dark:text-gray-400">...</span>
                                        @endif
                                    @endif

                                    @for ($i = $start; $i <= $end; $i++)
                                        @if ($i == $reviewsPaginated->currentPage())
                                            <span class="px-2 py-1 text-xs bg-blue-500 text-white rounded-lg"
                                                aria-current="page">{{ $i }}</span>
                                        @else
                                            <a href="{{ $reviewsPaginated->appends(['per_page' => $currentPerPage, 'section' => 'reviews'])->url($i) }}"
                                                class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                aria-label="Go to page {{ $i }}">{{ $i }}</a>
                                        @endif
                                    @endfor

                                    @if ($end < $reviewsPaginated->lastPage())
                                        @if ($end < $reviewsPaginated->lastPage() - 1)
                                            <span class="px-2 text-xs text-gray-500 dark:text-gray-400">...</span>
                                        @endif
                                        <a href="{{ $reviewsPaginated->appends(['per_page' => $currentPerPage, 'section' => 'reviews'])->url($reviewsPaginated->lastPage()) }}"
                                            class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            aria-label="Go to page {{ $reviewsPaginated->lastPage() }}">{{ $reviewsPaginated->lastPage() }}</a>
                                    @endif

                                    @if ($reviewsPaginated->hasMorePages())
                                        <a href="{{ $reviewsPaginated->appends(['per_page' => $currentPerPage, 'section' => 'reviews'])->nextPageUrl() }}"
                                            class="px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            aria-label="Go to next page">Next</a>
                                    @else
                                        <span
                                            class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed"
                                            aria-label="Next page disabled">Next</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="text-5xl mb-3">📝</div>
                            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300 mb-1">Belum Ada Review</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Review dari customer akan muncul di sini</p>
                        </div>
                    @endif
                </div>

                @if (config('app.debug'))
                    <div class="mt-5 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs mx-3">
                        <strong>Debug Info:</strong>
                        Total reviews found: {{ $reviewsPaginated->total() }}
                        | Current page: {{ $reviewsPaginated->currentPage() }}
                        | Per page: {{ $currentPerPage }}
                        | Last page: {{ $reviewsPaginated->lastPage() }}
                    </div>
                @endif
            </div>
            -->

            <div id="analyticsSection" class="content-section hidden py-5 px-3">
                <h2 class="text-xl font-semibold mb-5 text-gray-800 dark:text-white">Analytics Overview</h2>
                <!-- Analytics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Card 1: Total Messages -->
                    <div id="totalMessagesCard"
                        class="relative bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700 transition-transform hover:-translate-y-0.5 hover:shadow-md">
                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                title="Total number of chat messages"><i class="fas fa-info-circle"></i></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-white mb-1">Total Messages</h3>
                                <p id="totalMessagesValue" class="text-2xl font-bold text-blue-500">{{ number_format($totalAllMessages ?? 0) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Customer Satisfaction -->
                    <div id="feedbackCard"
                        class="relative bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700 transition-transform hover:-translate-y-0.5 hover:shadow-md">
                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                title="Percentage of positive feedback"><i class="fas fa-info-circle"></i></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-white mb-1">Positive & Negative
                                    Feedback</h3>
                                <div class="flex space-x-3">
                                    <p class="text-base font-semibold text-green-500">Positive:
                                        <span id="positivePercentageValue">{{ $positivePercentage }}</span>%
                                    </p>
                                    <p class="text-base font-semibold text-red-500">Negative: <span id="negativePercentageValue">{{ $negativePercentage }}</span>%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: From Ads -->
                    <div id="fromAdsCard"
                        class="relative bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700 transition-transform hover:-translate-y-0.5 hover:shadow-md">
                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                title="Customers from advertisements"><i class="fas fa-info-circle"></i></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-white mb-1">From Ads</h3>
                                <p id="fromAdsValue" class="text-2xl font-bold text-green-500">{{ number_format($totalFromAds ?? 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Activity Trends Section -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-white">Chat Activity Trends</h3>
                        <form method="GET" id="chartDateFilterForm"
                            class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <input type="hidden" name="section" value="analytics">
                            <div class="flex items-center gap-2">
                                <input type="date" name="chart_start_date"
                                    value="{{ $chartStartDate ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <span class="text-xs text-gray-500 dark:text-gray-400">to</span>
                                <input type="date" name="chart_end_date"
                                    value="{{ $chartEndDate ?: now()->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <button type="submit"
                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-medium transition-colors">Filter
                                Chart</button>
                        </form>
                    </div>
                    <div class="relative h-64">
                        <div id="chartLoading"
                            class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-50 hidden">
                            <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
                        </div>
                        <canvas id="chatTrendChart" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Customer Report Section -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-white">Customer Report</h3>
                        <form method="GET" id="customerDateFilterForm"
                            class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <input type="hidden" name="section" value="analytics">
                            <div class="flex items-center gap-2">
                                <input type="date" name="second_start_date"
                                    value="{{ $startDate ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-green-500">
                                <span class="text-xs text-gray-500 dark:text-gray-400">to</span>
                                <input type="date" name="second_end_date"
                                    value="{{ $endDate ?: now()->toDateString() }}" max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-green-500">
                            </div>
                            <button type="submit"
                                class="px-2 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-xs font-medium transition-colors">Filter
                                Data</button>
                        </form>
                    </div>

                    <!-- Customer Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div
                            class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" data-new-cust>
                                {{ number_format($newCustomer) }}
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300 font-medium">New Customer</div>
                        </div>
                        <div
                            class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" data-existing-cust>
                                {{ number_format($existingCustomer) }}
                            </div>
                            <div class="text-sm text-blue-700 dark:text-blue-300 font-medium">Existing Customer</div>
                        </div>
                    </div>

                    <!-- Customer Chart -->
                    <div class="relative h-64">
                        <div id="customerChartLoading"
                            class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-50 hidden">
                            <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
                        </div>
                        <canvas id="customerChart" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- From Ads Report Section -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-white">From Ads Report</h3>
                        <form method="GET" id="adsDateFilterForm"
                            class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <input type="hidden" name="section" value="analytics">
                            <div class="flex items-center gap-2">
                                <input type="date" name="ads_start_date"
                                    value="{{ $adsStartDate ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-orange-500">
                                <span class="text-xs text-gray-500 dark:text-gray-400">to</span>
                                <input type="date" name="ads_end_date"
                                    value="{{ $adsEndDate ?: now()->toDateString() }}" max="{{ now()->toDateString() }}"
                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-orange-500">
                            </div>
                            <button type="submit"
                                class="px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium transition-colors">Filter
                                Ads</button>
                        </form>
                    </div>

                    <!-- From Ads Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div
                            class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-xl border border-orange-200 dark:border-orange-800">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400" data-from-ads>
                                {{ number_format($totalFromAds ?? 0) }}
                            </div>
                            <div class="text-sm text-orange-700 dark:text-orange-300 font-medium">Customers from Ads</div>
                        </div>
                        <div
                            class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-xl border border-gray-200 dark:border-gray-800">
                            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400" data-non-ads>
                                {{ number_format($totalNonAds ?? 0) }}
                            </div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 font-medium">Customers Non Ads</div>
                        </div>
                    </div>

                    <!-- From Ads Chart -->
                    <div class="relative h-64">
                        <div id="adsChartLoading"
                            class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-50 hidden">
                            <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-orange-500"></div>
                        </div>
                        <canvas id="adsChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>

            <!-- Customer Section (new) -->
            <div id="customerSection" class="content-section hidden py-5 px-3">
                <h2 class="text-xl font-semibold mb-5 text-gray-800 dark:text-white">Customer</h2>
                @php
                    $c3 = $customerSentiment3 ?? [
                        'negatif' => 0,
                        'netral' => 0,
                        'positif' => 0,
                    ];
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Positif Card -->
                    <div
                        class="rounded-2xl shadow border border-green-200 dark:border-green-900 bg-green-500 text-white p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-face-smile text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide opacity-90">Sentimen Positif</div>
                                    <div class="text-3xl font-bold">{{ number_format($c3['positif']) }}</div>
                                </div>
                            </div>
                            <i class="fas fa-arrow-up-right-from-square opacity-70"></i>
                        </div>
                    </div>

                    <!-- Netral Card -->
                    <div
                        class="rounded-2xl shadow border border-purple-200 dark:border-purple-900 bg-white dark:bg-gray-800 p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 flex items-center justify-center">
                                    <i class="fas fa-face-meh text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-purple-700 dark:text-purple-300">
                                        Sentimen Netral</div>
                                    <div class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                                        {{ number_format($c3['netral']) }}
                                    </div>
                                </div>
                            </div>
                            <i class="fas fa-wave-square text-purple-400"></i>
                        </div>
                    </div>

                    <!-- Negatif Card -->
                    <div class="rounded-2xl shadow border border-red-200 dark:border-red-900 bg-red-500 text-white p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-face-frown text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide opacity-90">Sentimen Negatif</div>
                                    <div class="text-3xl font-bold">{{ number_format($c3['negatif']) }}</div>
                                </div>
                            </div>
                            <i class="fas fa-arrow-down-wide-short opacity-70"></i>
                        </div>
                    </div>
                </div>

                <!-- Review Log (Conversation Analysis) -->
                <div class="glass-effect p-4 rounded-2xl shadow border border-gray-200 dark:border-gray-700 mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-light text-gray-700 dark:text-gray-200">Review Log</h3>
                        <div class="flex items-center gap-2">
                            <form method="GET" class="flex items-center gap-2" id="customerFilterForm">
                                @foreach(request()->except(['ca_sentiment', 'ca_per_page', 'ca_page', 'ca_date_from', 'ca_date_to', 'ca_kategori', 'ca_product', 'ca_cs']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                                @endforeach
                                <input type="hidden" name="section" value="customer" />
                                <input type="hidden" name="ca_kategori" id="ca_kategori_hidden"
                                    value="{{ is_array($caKategori ?? null) ? implode(',', $caKategori) : ($caKategori ?? '') }}" />
                                <input type="hidden" name="ca_product" id="ca_product_hidden"
                                    value="{{ is_array($caProduct ?? null) ? implode(',', $caProduct) : ($caProduct ?? '') }}" />
                                <input type="hidden" name="ca_cs" id="ca_cs_hidden"
                                    value="{{ is_array($caCs ?? null) ? implode(',', $caCs) : ($caCs ?? '') }}" />
                                <input type="hidden" name="ca_label" id="ca_label_hidden"
                                    value="{{ is_array($caLabel ?? null) ? implode(',', $caLabel) : ($caLabel ?? '') }}" />



                                <!-- Filter Button -->
                                <button type="button" onclick="openFilterModal()"
                                    class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 flex items-center gap-1">
                                    <i class="fas fa-filter"></i>
                                    <span>Filter</span>
                                </button>
                            </form>

                            @php
                                $caPageVal = request('ca_page');
                                $caPerPageVal = request('ca_per_page');
                                if (isset($conversationAnalysis) && method_exists($conversationAnalysis, 'currentPage')) {
                                    $caPageVal = $conversationAnalysis->currentPage();
                                }
                                if (isset($conversationAnalysis) && method_exists($conversationAnalysis, 'perPage')) {
                                    $caPerPageVal = $conversationAnalysis->perPage();
                                }
                                $exportParams = array_merge(
                                    request()->except([]),
                                    [
                                        'section' => 'customer',
                                        'date_from' => request('ca_date_from'),
                                        'date_to' => request('ca_date_to'),
                                        'ca_kategori' => is_array($caKategori ?? null) ? implode(',', $caKategori) : ($caKategori ?? ''),
                                        'ca_product' => is_array($caProduct ?? null) ? implode(',', $caProduct) : ($caProduct ?? ''),
                                        'ca_cs' => is_array($caCs ?? null) ? implode(',', $caCs) : ($caCs ?? ''),
                                        'ca_label' => is_array($caLabel ?? null) ? implode(',', $caLabel) : ($caLabel ?? ''),
                                    ]
                                );
                                $exportUrl = route('dashboard.export-conversation-analysis', $exportParams);
                            @endphp
                            <a href="{{ $exportUrl }}"
                                class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 flex items-center gap-1">
                                <i class="fas fa-file-excel"></i>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead
                                class="text-xs uppercase bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">Customer</th>
                                    <th class="px-3 py-2">Customer Service</th>
                                    {{-- <th class="px-3 py-2">Sentiment</th>
                                    <th class="px-3 py-2">Kategori</th>
                                    <th class="px-3 py-2">Product</th> --}}
                                    <th class="px-3 py-2">Labels</th>
                                    <th class="px-3 py-2">Room ID</th>
                                    <th class="px-3 py-2">Tanggal</th>
                                    <th class="px-3 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $caList = $conversationAnalysis ?? collect(); @endphp
                                @forelse ($caList as $row)
                                    @php
                                        // Gabungkan sentimen, kategori, product menjadi Labels
                                        $labels = collect([$row->sentimen, $row->kategori, $row->product])
                                            ->filter()
                                            ->implode(', ');
                                    @endphp
                                    <tr
                                        class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-3 py-2">{{ $row->customer_name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $row->agent_name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $labels ?: '-' }}</td>
                                        <td class="px-3 py-2 font-mono text-xs">{{ $row->room_id ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            {{ isset($row->created_at) ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <button type="button"
                                                class="px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded"
                                                onclick="showDetailModal({{ json_encode($row->pesan ?? '-') }}, {{ json_encode($row->alasan ?? '-') }})">
                                                Lihat Detail
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-3 text-center text-gray-500">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($conversationAnalysis) && method_exists($conversationAnalysis, 'total') && $conversationAnalysis->total() > 0)
                        <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3">
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                Showing {{ $conversationAnalysis->firstItem() }} to {{ $conversationAnalysis->lastItem() }}
                                of {{ $conversationAnalysis->total() }} records
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $caPrev = $conversationAnalysis->previousPageUrl();
                                    $caNext = $conversationAnalysis->nextPageUrl();
                                @endphp
                                @if($conversationAnalysis->onFirstPage())
                                    <span
                                        class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 rounded-lg">Prev</span>
                                @else
                                    <a href="{{ $caPrev }}"
                                        class="px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg">Prev</a>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400">Page
                                    {{ $conversationAnalysis->currentPage() }} /
                                    {{ $conversationAnalysis->lastPage() }}</span>
                                @if($conversationAnalysis->hasMorePages())
                                    <a href="{{ $caNext }}"
                                        class="px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-lg">Next</a>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-400 rounded-lg">Next</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Filter Options</h3>
                    <button onclick="closeFilterModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="GET" id="modalFilterForm">
                    @foreach(request()->except(['ca_sentiment', 'ca_per_page', 'ca_page', 'ca_date_from', 'ca_date_to', 'ca_kategori', 'ca_product', 'ca_cs']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                    @endforeach
                    <input type="hidden" name="section" value="customer" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date Range -->
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                            <div class="flex items-center gap-2">
                                <input type="date" name="ca_date_from"
                                    value="{{ $caDateFrom ?: now()->subDays(30)->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex-1">
                                <span class="text-sm text-gray-500 dark:text-gray-400">to</span>
                                <input type="date" name="ca_date_to"
                                    value="{{ $caDateTo ?: now()->toDateString() }}"
                                    max="{{ now()->toDateString() }}"
                                    class="px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex-1">
                            </div>
                        </div>

                        {{-- <!-- Kategori -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
                            <div class="relative" x-data="multiSelectFilter({
                                name: 'kategori',
                                options: @js($caCategories ?? []),
                                selected: @js(is_array($caKategori ?? null) ? $caKategori : (empty($caKategori) ? [] : explode(',', $caKategori))),
                                label: 'Select Kategori'
                            })">
                                <button type="button" @click="open = !open" @keydown.escape.window="open=false"
                                    class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex items-center justify-between">
                                    <span x-text="getLabel()"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="selected.length > 0"
                                            class="px-1.5 py-0.5 bg-blue-500 text-white rounded-full text-xs font-semibold"
                                            x-text="selected.length"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </div>
                                </button>
                                <div x-show="open" x-transition @click.outside="open=false"
                                    class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            Pilih Kategori (<span x-text="selected.length"></span>)
                                        </span>
                                        <button type="button" @click="clearAll()"
                                            class="text-xs text-red-500 hover:text-red-700">
                                            Clear All
                                        </button>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="opt in options" :key="opt">
                                            <li>
                                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                                    <input type="checkbox" :checked="selected.includes(opt)"
                                                        @change="toggleOption(opt)"
                                                        class="mr-2 rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="opt"></span>
                                                </label>
                                            </li>
                                        </template>
                                        <li x-show="options.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center">
                                            Tidak ada kategori
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" name="ca_kategori" id="ca_kategori_modal"
                                    value="{{ is_array($caKategori ?? null) ? implode(',', $caKategori) : ($caKategori ?? '') }}" />
                            </div>
                        </div>

                        <!-- Product -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product</label>
                            <div class="relative" x-data="multiSelectFilter({
                                name: 'product',
                                options: @js($caProducts ?? []),
                                selected: @js(is_array($caProduct ?? null) ? $caProduct : (empty($caProduct) ? [] : explode(',', $caProduct))),
                                label: 'Select Product'
                            })">
                                <button type="button" @click="open = !open" @keydown.escape.window="open=false"
                                    class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex items-center justify-between">
                                    <span x-text="getLabel()"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="selected.length > 0"
                                            class="px-1.5 py-0.5 bg-blue-500 text-white rounded-full text-xs font-semibold"
                                            x-text="selected.length"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </div>
                                </button>
                                <div x-show="open" x-transition @click.outside="open=false"
                                    class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            Pilih Product (<span x-text="selected.length"></span>)
                                        </span>
                                        <button type="button" @click="clearAll()"
                                            class="text-xs text-red-500 hover:text-red-700">
                                            Clear All
                                        </button>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="opt in options" :key="opt">
                                            <li>
                                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                                    <input type="checkbox" :checked="selected.includes(opt)"
                                                        @change="toggleOption(opt)"
                                                        class="mr-2 rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="opt"></span>
                                                </label>
                                            </li>
                                        </template>
                                        <li x-show="options.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center">
                                            Tidak ada product
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" name="ca_product" id="ca_product_modal"
                                    value="{{ is_array($caProduct ?? null) ? implode(',', $caProduct) : ($caProduct ?? '') }}" />
                            </div>
                        </div>

                        <!-- Customer Service -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer Service</label>
                            <div class="relative" x-data="multiSelectFilter({
                                name: 'cs',
                                options: @js($allCsAgents ?? []),
                                selected: @js(is_array($caCs ?? null) ? $caCs : (empty($caCs) ? [] : explode(',', $caCs))),
                                label: 'Select CS Agent'
                            })">
                                <button type="button" @click="open = !open" @keydown.escape.window="open=false"
                                    class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex items-center justify-between">
                                    <span x-text="getLabel()"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="selected.length > 0"
                                            class="px-1.5 py-0.5 bg-blue-500 text-white rounded-full text-xs font-semibold"
                                            x-text="selected.length"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </div>
                                </button>
                                <div x-show="open" x-transition @click.outside="open=false"
                                    class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            Pilih CS Agent (<span x-text="selected.length"></span>)
                                        </span>
                                        <button type="button" @click="clearAll()"
                                            class="text-xs text-red-500 hover:text-red-700">
                                            Clear All
                                        </button>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="opt in options" :key="opt">
                                            <li>
                                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                                    <input type="checkbox" :checked="selected.includes(opt)"
                                                        @change="toggleOption(opt)"
                                                        class="mr-2 rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="opt"></span>
                                                </label>
                                            </li>
                                        </template>
                                        <li x-show="options.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center">
                                            Tidak ada CS agent
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" name="ca_cs" id="ca_cs_modal"
                                    value="{{ is_array($caCs ?? null) ? implode(',', $caCs) : ($caCs ?? '') }}" />
                            </div>
                        </div> --}}



                         <!-- Label -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Label</label>
                            <div class="relative" x-data="multiSelectFilter({
                                name: 'label',
                                options: @js($allLabels ?? []),
                                selected: @js(is_array($caLabel ?? null) ? $caLabel : (empty($caLabel) ? [] : explode(',', $caLabel))),
                                label: 'Select Label'
                            })">
                                <button type="button" @click="open = !open" @keydown.escape.window="open=false"
                                    class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex items-center justify-between">
                                    <span x-text="getLabel()"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="selected.length > 0"
                                            class="px-1.5 py-0.5 bg-blue-500 text-white rounded-full text-xs font-semibold"
                                            x-text="selected.length"></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </div>
                                </button>
                                <div x-show="open" x-transition @click.outside="open=false"
                                    class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            Pilih Label (<span x-text="selected.length"></span>)
                                        </span>
                                        <button type="button" @click="clearAll()"
                                            class="text-xs text-red-500 hover:text-red-700">
                                            Clear All
                                        </button>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="opt in options" :key="opt">
                                            <li>
                                                <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                                    <input type="checkbox" :checked="selected.includes(opt)"
                                                        @change="toggleOption(opt)"
                                                        class="mr-2 rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="opt"></span>
                                                </label>
                                            </li>
                                        </template>
                                        <li x-show="options.length === 0" class="px-3 py-2 text-sm text-gray-500 text-center">
                                            Tidak ada label
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" name="ca_label" id="ca_label_modal"
                                    value="{{ is_array($caLabel ?? null) ? implode(',', $caLabel) : ($caLabel ?? '') }}" />
                            </div>
                        </div>




                        {{-- <!-- Sentiment -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sentiment</label>
                            <div class="relative" x-data="{
                                open: false,
                                value: @js($caSentiment ?? 'all'),
                                options: ['all','negatif','netral','positif'],
                                label() {
                                    const m={all:'All Sentiment',negatif:'Negatif',netral:'Netral',positif:'Positif'};
                                    return m[this.value]||'All';
                                },
                                choose(v){
                                    this.value=v;
                                    this.$refs.sentInput.value = v || 'all';
                                    this.open=false;
                                }
                            }">
                                <input type="hidden" name="ca_sentiment" x-ref="sentInput" value="{{ $caSentiment ?? 'all' }}" />
                                <button type="button" @click="open = !open" @keydown.escape.window="open=false"
                                    class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 flex items-center justify-between">
                                    <span x-text="label()"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="open" x-transition @click.outside="open=false"
                                    class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
                                    <ul class="py-1 text-sm">
                                        <template x-for="opt in options" :key="opt">
                                            <li>
                                                <button type="button"
                                                    class="w-full text-left px-3 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors"
                                                    @click="choose(opt)"
                                                    x-text="opt === 'all' ? 'All Sentiment' : (opt.charAt(0).toUpperCase() + opt.slice(1))"></button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div> --}}

                        <!-- Per Page -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Per Page</label>
                            <select name="ca_per_page" class="w-full px-3 py-2 border rounded-lg text-sm text-gray-700 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600">
                                @foreach([10, 20, 50, 100] as $opt)
                                    <option value="{{ $opt }}" {{ ($caPerPage ?? 10) === $opt ? 'selected' : '' }}>
                                        {{ $opt }}/page
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" onclick="closeFilterModal()"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                            Cancel
                        </button>
                        <button type="submit" onclick="updateFilterInputs(); return true;"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- External Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Dashboard Main Script -->
    <script>
        // ===== LOADING ALERT SYSTEM =====
        console.log('✅ Loading alert system ready for all sections');

        // ===== GLOBAL VARIABLES =====
        window.chartLabels = @json($chartLabels ?? []);
        window.customerData = @json($customerData ?? []);
        window.csData = @json($csData ?? []);
        window.customerChartDates = @json($customerChartDates ?? []);
        window.customerChartNewCustomers = @json($customerChartNewCustomers ?? []);
        window.customerChartExistingCustomers = @json($customerChartExistingCustomers ?? []);
        window.fromAdsChartDates = @json($fromAdsChartDates ?? []);
        window.fromAdsChartData = @json($fromAdsChartData ?? []);
        window.nonAdsChartData = @json($nonAdsChartData ?? []);

        // ===== DATE VALIDATION HELPER =====
        window.validateDateRange = function (startInput, endInput) {
            const today = new Date().toISOString().split('T')[0];
            const startDate = startInput.value;
            const endDate = endInput.value;

            if (startDate > today) {
                startInput.value = today;
                console.log('📅 Corrected start date to today');
            }

            if (endDate > today) {
                endInput.value = today;
                console.log('📅 Corrected end date to today');
            }

            if (startDate > endDate) {
                startInput.value = endDate;
                console.log('📅 Corrected start date to not exceed end date');
            }
        };

        // ===== MULTI-SELECT FILTER FUNCTION =====
        window.multiSelectFilter = function (config) {
            return {
                open: false,
                name: config.name,
                options: config.options || [],
                selected: config.selected || [],
                label: config.label || 'Select',
                getLabel() {
                    if (this.selected.length === 0) return this.label;
                    if (this.selected.length === 1) return this.selected[0];
                    return `${this.selected.length} selected`;
                },
                toggleOption(option) {
                    const index = this.selected.indexOf(option);
                    if (index > -1) {
                        this.selected.splice(index, 1);
                    } else {
                        this.selected.push(option);
                    }
                    this.updateHiddenInput();
                },
                clearAll() {
                    this.selected = [];
                    this.updateHiddenInput();
                },
                updateHiddenInput() {
                    const hiddenInput = document.getElementById(`ca_${this.name}_hidden`) || document.getElementById(`ca_${this.name}_modal`);
                    if (hiddenInput) {
                        hiddenInput.value = this.selected.join(',');
                    }
                }
            };
        };

        // ===== FILTER MODAL FUNCTIONS =====
        window.openFilterModal = function() {
            document.getElementById('filterModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Update label selected from hidden input
            const labelHidden = document.getElementById('ca_label_hidden');
            if (labelHidden && labelHidden.value) {
                const labels = labelHidden.value.split(',').map(s => s.trim()).filter(s => s);
                // Find label Alpine component and update selected
                const labelElements = document.querySelectorAll('[x-data*="label"]');
                labelElements.forEach(element => {
                    if (element._x_dataStack && element._x_dataStack[0]) {
                        element._x_dataStack[0].selected = labels;
                        // Trigger Alpine update
                        element._x_dataStack[0].$nextTick(() => {});
                    }
                });
            }
        };

        window.closeFilterModal = function() {
            document.getElementById('filterModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        };

        // Handle form submission to ensure all filter values are updated
        document.addEventListener('DOMContentLoaded', function() {
            const modalForm = document.getElementById('modalFilterForm');
            if (modalForm) {
                modalForm.addEventListener('submit', function(e) {
                    console.log('Form submission intercepted - updating filters');
                    updateFilterInputs();
                    
                    // Small delay to ensure Alpine.js has processed any pending updates
                    setTimeout(() => {
                        console.log('Form submission proceeding');
                    }, 50);
                });
            }
        });

        // Function to update filter inputs before form submission
        window.updateFilterInputs = function() {
            try {
                // Method 1: Try to get Alpine.js data directly
                const kategoriElements = document.querySelectorAll('[x-data*="kategori"]');
                const productElements = document.querySelectorAll('[x-data*="product"]');
                const csElements = document.querySelectorAll('[x-data*="cs"]');
                const labelElements = document.querySelectorAll('[x-data*="label"]');
                
                kategoriElements.forEach(element => {
                    if (element._x_dataStack && element._x_dataStack[0]) {
                        const data = element._x_dataStack[0];
                        const input = document.getElementById('ca_kategori_modal');
                        if (data.selected && input) {
                            input.value = data.selected.join(',');
                            console.log('Updated kategori filter:', data.selected);
                        }
                    }
                });
                
                productElements.forEach(element => {
                    if (element._x_dataStack && element._x_dataStack[0]) {
                        const data = element._x_dataStack[0];
                        const input = document.getElementById('ca_product_modal');
                        if (data.selected && input) {
                            input.value = data.selected.join(',');
                            console.log('Updated product filter:', data.selected);
                        }
                    }
                });
                
                csElements.forEach(element => {
                    if (element._x_dataStack && element._x_dataStack[0]) {
                        const data = element._x_dataStack[0];
                        const input = document.getElementById('ca_cs_modal');
                        if (data.selected && input) {
                            input.value = data.selected.join(',');
                            console.log('Updated CS filter:', data.selected);
                        }
                    }
                });

                labelElements.forEach(element => {
                    if (element._x_dataStack && element._x_dataStack[0]) {
                        const data = element._x_dataStack[0];
                        const input = document.getElementById('ca_label_modal');
                        if (data.selected && input) {
                            input.value = data.selected.join(',');
                            console.log('Updated label filter:', data.selected);
                        }
                    }
                });

                // Update hidden form inputs
                const kategoriHidden = document.getElementById('ca_kategori_hidden');
                const productHidden = document.getElementById('ca_product_hidden');
                const csHidden = document.getElementById('ca_cs_hidden');
                const labelHidden = document.getElementById('ca_label_hidden');

                if (kategoriHidden && kategoriInput) kategoriHidden.value = kategoriInput.value;
                if (productHidden && productInput) productHidden.value = productInput.value;
                if (csHidden && csInput) csHidden.value = csInput.value;
                if (labelHidden && labelInput) labelHidden.value = labelInput.value;
                
                // Method 2: Fallback - check all checkboxes manually
                const kategoriInput = document.getElementById('ca_kategori_modal');
                const productInput = document.getElementById('ca_product_modal');
                const csInput = document.getElementById('ca_cs_modal');
                const labelInput = document.getElementById('ca_label_modal');
                
                if (kategoriInput) {
                    const kategoriCheckboxes = document.querySelectorAll('[x-data*="kategori"] input[type="checkbox"]:checked');
                    const kategoriValues = Array.from(kategoriCheckboxes).map(cb => {
                        return cb.parentElement.querySelector('span').textContent.trim();
                    });
                    if (kategoriValues.length > 0) {
                        kategoriInput.value = kategoriValues.join(',');
                        console.log('Fallback kategori update:', kategoriValues);
                    }
                }
                
                if (productInput) {
                    const productCheckboxes = document.querySelectorAll('[x-data*="product"] input[type="checkbox"]:checked');
                    const productValues = Array.from(productCheckboxes).map(cb => {
                        return cb.parentElement.querySelector('span').textContent.trim();
                    });
                    if (productValues.length > 0) {
                        productInput.value = productValues.join(',');
                        console.log('Fallback product update:', productValues);
                    }
                }
                
                if (csInput) {
                    const csCheckboxes = document.querySelectorAll('[x-data*="cs"] input[type="checkbox"]:checked');
                    const csValues = Array.from(csCheckboxes).map(cb => {
                        return cb.parentElement.querySelector('span').textContent.trim();
                    });
                    if (csValues.length > 0) {
                        csInput.value = csValues.join(',');
                        console.log('Fallback CS update:', csValues);
                    }
                }

                if (labelInput) {
                    const labelCheckboxes = document.querySelectorAll('[x-data*="label"] input[type="checkbox"]:checked');
                    const labelValues = Array.from(labelCheckboxes).map(cb => {
                        return cb.parentElement.querySelector('span').textContent.trim();
                    });
                    if (labelValues.length > 0) {
                        labelInput.value = labelValues.join(',');
                        console.log('Fallback label update:', labelValues);
                    }
                }
                
                // Debug: Log all form values
                const form = document.getElementById('modalFilterForm');
                if (form) {
                    const formData = new FormData(form);
                    console.log('Form data before submission:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                }
            } catch (error) {
                console.log('Filter update error (non-critical):', error);
            }
        };

        // Test function to check current filter state
        window.testFilters = function() {
            console.log('=== FILTER TEST ===');
            console.log('Kategori input value:', document.getElementById('ca_kategori_modal')?.value);
            console.log('Product input value:', document.getElementById('ca_product_modal')?.value);
            console.log('CS input value:', document.getElementById('ca_cs_modal')?.value);
            console.log('Sentiment input value:', document.querySelector('[name="ca_sentiment"]')?.value);
            
            const kategoriCheckboxes = document.querySelectorAll('[x-data*="kategori"] input[type="checkbox"]:checked');
            const productCheckboxes = document.querySelectorAll('[x-data*="product"] input[type="checkbox"]:checked');
            const csCheckboxes = document.querySelectorAll('[x-data*="cs"] input[type="checkbox"]:checked');
            
            console.log('Checked kategori boxes:', kategoriCheckboxes.length);
            console.log('Checked product boxes:', productCheckboxes.length);
            console.log('Checked CS boxes:', csCheckboxes.length);
            console.log('==================');
        };

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('filterModal');
            if (e.target === modal) {
                closeFilterModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFilterModal();
            }
        });

        // ===== DATE INPUT LISTENERS =====
        function attachDateValidationListeners() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function () {
                    const today = new Date().toISOString().split('T')[0];
                    if (this.value > today) {
                        this.value = today;
                        console.log('📅 Date corrected to today:', this.name || this.id);
                    }
                });
            });
        }

        // ===== CUSTOMER SECTION AUTO-SUBMIT PREVENTION =====
        function preventCustomerAutoSubmit() {
            const customerForm = document.getElementById('customerFilterForm');
            const customerSection = document.getElementById('customerSection');

            if (!customerForm) return;

            let allowSubmit = false;
            let isSubmitting = false;
            const applyBtn = customerForm.querySelector('button[type="submit"]');

            if (applyBtn) {
                applyBtn.addEventListener('click', (e) => {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    allowSubmit = true;
                });
            }

            customerForm.addEventListener('submit', e => {
                if (!allowSubmit || isSubmitting) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('❌ Form submit blocked:', {allowSubmit, isSubmitting});
                    return false;
                }

                isSubmitting = true;
                allowSubmit = false;

                // Reset after delay
                setTimeout(() => {
                    isSubmitting = false;
                }, 2000);
            });

            // Block rapid change events
            const inputs = customerForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                let changeTimeout;
                input.addEventListener('change', e => {
                    e.stopPropagation();

                    // Debounce rapid changes
                    clearTimeout(changeTimeout);
                    changeTimeout = setTimeout(() => {
                        // Allow processing after debounce
                    }, 300);
                });
            });

            // Block section-level change events
            if (customerSection) {
                customerSection.addEventListener('change', e => e.stopPropagation(), true);
            }
        }

        // ===== LOADING ALERTS OVERRIDE =====
        function overrideLoadingAlerts() {
            // Enable loading alerts for all sections including customer
            console.log('✅ Loading alerts enabled for all sections');
        }

        // ===== SUCCESS NOTIFICATION =====
        @if(session('section_loading'))
            function showSuccessNotification() {
                const currentSection = '{{ $current_section ?? "dashboard" }}';
                const loadTime = {{ $load_time ?? 0 }};

                function getSectionDisplayName(section) {
                    const names = {
                        dashboard: 'Dashboard',
                        analytics: 'Analytics',
                        agents: 'Agents',
                        customer: 'Customer'
                    };
                    return names[section] || 'Section';
                }

                Swal.fire({
                    title: '✅ Data Berhasil Dimuat!',
                    text: `${getSectionDisplayName(currentSection)} berhasil dimuat dalam ${loadTime}ms`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#1f2937',
                    toast: true,
                    position: 'top-end'
                });
            }
        @endif

        // ===== AUTO-DISMISS LOADING ALERTS =====
        function autoDismissLoadingAlerts() {
            // Simple fixed delay dismiss after page load
            window.addEventListener('load', () => {
                setTimeout(() => {
                    if (window.Swal && window.Swal.close) {
                        window.Swal.close();
                    }
                }, 5000); // 5 seconds after page load
            });
        }

        // ===== DETAIL MODAL FUNCTION =====
        window.showDetailModal = function(pesan, alasan) {
            // Parse chat messages from the pesan string
            function parseChatMessages(pesanText) {
                if (!pesanText) return [];
                
                const messages = [];
                const lines = pesanText.split(/\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\]/);
                const timestamps = pesanText.match(/\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z\]/g) || [];
                
                for (let i = 1; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line) {
                        const timestamp = timestamps[i-1] ? timestamps[i-1].replace(/[\[\]]/g, '') : '';
                        
                        // Extract sender and role
                        const match = line.match(/^(.+?)\s*\(([^)]+)\):\s*(.+)$/);
                        if (match) {
                            const [, sender, role, message] = match;
                            messages.push({
                                timestamp: timestamp,
                                sender: sender.trim(),
                                role: role.trim(),
                                message: message.trim()
                            });
                        }
                    }
                }
                
                return messages;
            }
            
            // Generate chat bubbles HTML
            function generateChatBubbles(messages) {
                if (messages.length === 0) {
                    return '<div class="text-center text-gray-500 py-4">Tidak ada pesan</div>';
                }
                
                return messages.map(msg => {
                    const isCustomerService = msg.role === 'customer_service';
                    const time = new Date(msg.timestamp).toLocaleTimeString('id-ID', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    return `
                        <div class="flex ${isCustomerService ? 'justify-end' : 'justify-start'} mb-3">
                            <div class="max-w-xs lg:max-w-md">
                                <div class="${isCustomerService 
                                    ? 'bg-blue-500 text-white rounded-l-lg rounded-tr-lg' 
                                    : 'bg-gray-200 text-gray-800 rounded-r-lg rounded-tl-lg'
                                } px-4 py-2 shadow">
                                    <div class="text-xs font-semibold mb-1 ${isCustomerService ? 'text-blue-100' : 'text-gray-600'}">
                                        ${msg.sender}
                                    </div>
                                    <div class="text-sm leading-relaxed">
                                        ${msg.message}
                                    </div>
                                    <div class="text-xs mt-1 ${isCustomerService ? 'text-blue-200' : 'text-gray-500'}">
                                        ${time}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            const messages = parseChatMessages(pesan);
            const chatBubblesHtml = generateChatBubbles(messages);
            
            Swal.fire({
                title: 'Detail Percakapan',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-3">Pesan:</h4>
                            <div class="bg-gray-50 p-4 rounded-lg max-h-96 overflow-y-auto">
                                ${chatBubblesHtml}
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Alasan:</h4>
                            <div class="bg-gray-100 p-3 rounded-lg text-sm text-gray-800">
                                ${alasan || 'Tidak ada alasan'}
                            </div>
                        </div>
                    </div>
                `,
                width: '700px',
                showCloseButton: true,
                showConfirmButton: false,
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#1f2937',
                customClass: {
                    popup: 'text-left'
                }
            });
        };

        // ===== INITIALIZATION =====
        document.addEventListener('DOMContentLoaded', function () {
            overrideLoadingAlerts();
            preventCustomerAutoSubmit();
            autoDismissLoadingAlerts();

            // Initialize date refresh system
            initDateRefreshSystem();
            attachDateValidationListeners();

            @if(session('section_loading'))
                showSuccessNotification();
            @endif
        });

        // ===== DATE REFRESH SYSTEM =====
        function initDateRefreshSystem() {
            const today = new Date().toISOString().split('T')[0];
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            const thirtyDaysAgoStr = thirtyDaysAgo.toISOString().split('T')[0];

            // Update all date inputs with current max date
            const allDateInputs = document.querySelectorAll('input[type="date"]');
            allDateInputs.forEach(input => {
                input.setAttribute('max', today);

                // Set default values if empty
                if (!input.value) {
                    const isEndDate = input.name && (input.name.includes('_end') || input.name.includes('_to') || input.id === 'csatEndDate' || input.name === 'status_date');
                    input.value = isEndDate ? today : thirtyDaysAgoStr;
                }

                // Correct future dates
                if (input.value > today) {
                    input.value = today;
                }
            });

            console.log('📅 Date refresh system initialized - Today:', today);
        }

        window.originalShowLoadingAlert = window.showLoadingAlert;

        // ===== AUTO DATE UPDATE ON FOCUS =====
        document.addEventListener('focusin', function (e) {
            if (e.target.type === 'date') {
                const today = new Date().toISOString().split('T')[0];
                e.target.setAttribute('max', today);

                // If it's an end date and value is in future, update it
                const isEndDate = e.target.name && (e.target.name.includes('_end') || e.target.name.includes('_to') || e.target.id === 'csatEndDate' || e.target.name === 'status_date');
                if (isEndDate && e.target.value > today) {
                    e.target.value = today;
                }
            }
        });
    </script>

    <!-- Auto Date Update Script -->
    <script>
        // Update dates every 5 minutes when page is active
        setInterval(function () {
            if (!document.hidden) {
                const today = new Date().toISOString().split('T')[0];
                const allDateInputs = document.querySelectorAll('input[type="date"]');

                allDateInputs.forEach(input => {
                    input.setAttribute('max', today);

                    // Update end dates if they're in the future
                    const isEndDate = input.name && (input.name.includes('_end') || input.name.includes('_to') || input.id === 'csatEndDate' || input.name === 'status_date');
                    if (isEndDate && input.value > today) {
                        input.value = today;
                        console.log('📅 Updated future date to today:', input.name || input.id);
                    }
                });
            }
        }, 5 * 60 * 1000); // 5 minutes

        // Update dates when page becomes visible
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                setTimeout(function () {
                    const today = new Date().toISOString().split('T')[0];
                    const allDateInputs = document.querySelectorAll('input[type="date"]');

                    allDateInputs.forEach(input => {
                        input.setAttribute('max', today);

                        const isEndDate = input.name && (input.name.includes('_end') || input.name.includes('_to') || input.id === 'csatEndDate' || input.name === 'status_date');
                        if (isEndDate && input.value > today) {
                            input.value = today;
                        }
                    });

                    console.log('📅 Dates refreshed on page visibility change');
                }, 100);
            }
        });
    </script>

    <!-- Dashboard External Scripts -->
    <script src="{{ asset('js/dashboard-inline.js') }}"></script>
    <script src="{{ asset('js/loading-alerts.js') }}"></script>
    <script src="{{ asset('js/sorting.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
</body>

</html>
