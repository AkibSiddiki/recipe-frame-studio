<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Recipe Frame Studio') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-100 bg-[#0f172a] h-screen flex flex-col overflow-hidden">
    <!-- Top Navigation Bar -->
    <header class="h-14 bg-[#1a1a2e] border-b border-gray-800 flex items-center justify-between px-4 shrink-0 shadow-sm z-10">
        <div class="flex items-center space-x-3">
            <a href="{{ route('home') }}" class="flex items-center space-x-2 text-xl font-semibold text-white hover:text-gray-300 transition">
                <span class="text-2xl">🍳</span>
                <span>Recipe Frame Studio</span>
            </a>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('project.create') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-1.5 rounded-md text-sm font-medium flex items-center transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Project
            </a>
            <a href="{{ route('settings') }}" class="text-gray-400 hover:text-white transition" title="Settings">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar -->
        @if($showSidebar ?? true)
            <aside class="w-[220px] bg-[#16213e] border-r border-gray-800 flex flex-col shrink-0 overflow-y-auto">
                <nav class="flex-1 py-4">
                    @php
                        $steps = [
                            'video' => ['icon' => '📹', 'label' => 'Video'],
                            'frames' => ['icon' => '🖼️', 'label' => 'Frames'],
                            'crop' => ['icon' => '✂️', 'label' => 'Crop'],
                            'watermark' => ['icon' => '💧', 'label' => 'Watermark'],
                            'recipe' => ['icon' => '📋', 'label' => 'Recipe Steps'],
                            'export' => ['icon' => '📤', 'label' => 'Export'],
                        ];
                        
                        $currentStep = $currentStep ?? 'video';
                        $stepKeys = array_keys($steps);
                        $currentIndex = array_search($currentStep, $stepKeys);
                    @endphp

                    <ul class="space-y-1 px-2">
                        @foreach($steps as $key => $step)
                            @php
                                $index = array_search($key, $stepKeys);
                                $isActive = $key === $currentStep;
                                $isFuture = $index > $currentIndex;
                                $slug = $project['slug'] ?? null;
                                $stepUrl = '#';
                                if ($slug) {
                                    if ($key === 'video') $stepUrl = route('project.show', $slug);
                                    elseif ($key === 'frames') $stepUrl = route('project.frames', $slug);
                                    elseif ($key === 'crop') $stepUrl = route('project.crop', $slug);
                                    elseif ($key === 'watermark') $stepUrl = route('project.watermark', $slug);
                                }
                            @endphp
                            <li>
                                <a href="{{ $stepUrl !== '#' ? $stepUrl : 'javascript:void(0)' }}" class="flex items-center px-3 py-2 rounded-md transition-colors duration-200 
                                    {{ $isActive ? 'bg-amber-900/30 text-amber-500 border border-amber-800/50' : '' }}
                                    {{ $isFuture && $stepUrl === '#' ? 'opacity-40 cursor-not-allowed grayscale' : 'hover:bg-gray-800' }}
                                    {{ !$isActive && (!$isFuture || $stepUrl !== '#') ? 'text-gray-300' : '' }}
                                ">
                                    <span class="mr-3">{{ $step['icon'] }}</span>
                                    <span class="font-medium text-sm">{{ $step['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif

        <!-- Main Workspace -->
        <main class="flex-1 bg-[#0f172a] overflow-y-auto p-6 relative">
            @yield('content')
        </main>

        <!-- Right Sidebar (Contextual) -->
        @isset($rightSidebar)
            <aside class="w-[280px] bg-[#1a1a2e] border-l border-gray-800 overflow-y-auto shrink-0 p-4">
                {{ $rightSidebar }}
            </aside>
        @endisset
    </div>

    <!-- Bottom Status Bar -->
    <footer class="h-8 bg-[#1a1a2e] border-t border-gray-800 flex items-center px-4 shrink-0 text-xs text-gray-400 z-10 shadow-[0_-1px_2px_rgba(0,0,0,0.1)]">
        @hasSection('status')
            @yield('status')
        @else
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span>Ready</span>
            </div>
        @endif
    </footer>
</body>
</html>
