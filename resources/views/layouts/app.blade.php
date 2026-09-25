<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Recipe Frame Studio') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:400,500,600,700,800|hind-siliguri:400,500,600,700" rel="stylesheet" />
    <link rel="preload" href="/HindSiliguri-Bold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/HindSiliguri-Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/Li%20Alinur%20Mayaboti%20Unicode.ttf" as="font" type="font/ttf" crossorigin>
    <style>
        @font-face {
            font-family: 'Hind Siliguri';
            src: url('/HindSiliguri-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Hind Siliguri';
            src: url('/HindSiliguri-Medium.ttf') format('truetype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Hind Siliguri';
            src: url('/HindSiliguri-SemiBold.ttf') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Hind Siliguri';
            src: url('/HindSiliguri-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Li Alinur Mayaboti';
            src: url('/Li%20Alinur%20Mayaboti%20Unicode.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Li Alinur Mayaboti';
            src: url('/Li%20Alinur%20Mayaboti%20Unicode%20Italic.ttf') format('truetype');
            font-weight: normal;
            font-style: italic;
            font-display: swap;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-100 bg-surface-base h-screen flex flex-col overflow-hidden">
    <!-- Top Navigation Bar -->
    <header class="h-14 glass-surface border-b border-border-default flex items-center justify-between px-5 shrink-0 z-20 relative">
        {{-- Subtle top-edge highlight --}}
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-amber-500/20 to-transparent"></div>

        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-semibold text-white hover:text-amber-400 transition-all duration-300 group">
                <div class="relative">
                    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full border border-amber-500/40 shadow-md group-hover:scale-110 transition-transform duration-300 object-cover" alt="রান্নাঘরের ডায়েরি">
                    <div class="absolute inset-0 rounded-full bg-amber-500/0 group-hover:bg-amber-500/10 transition-colors duration-300"></div>
                </div>
                <span class="font-display font-bold tracking-tight">Recipe Frame Studio</span>
                    <span class="hidden sm:inline-block text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 backdrop-blur-sm">রান্নাঘরের ডায়েরি</span>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('project.create') }}" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-4 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1.5 transition-all duration-300 shadow-lg shadow-amber-900/25 hover:shadow-amber-800/40 hover:scale-[1.03] active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                New Project
            </a>
            <a href="{{ route('settings') }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-200" title="Settings (Ctrl+,)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar -->
        @if($showSidebar ?? true)
            <aside class="w-[230px] bg-surface-raised border-r border-border-default flex flex-col shrink-0 overflow-y-auto relative">
                {{-- Subtle sidebar inner glow --}}
                <div class="absolute inset-0 bg-gradient-to-b from-amber-500/[0.02] to-transparent pointer-events-none"></div>

                <nav class="flex-1 py-5 relative z-10">
                    @php
                        $steps = [
                            'video' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>',
                                'label' => 'Video Source',
                                'num' => 1
                            ],
                            'frames' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
                                'label' => 'Frames',
                                'num' => 2
                            ],
                            'crop' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4M3 12h18"></path></svg>',
                                'label' => 'Crop & Frame',
                                'num' => 3
                            ],
                            'watermark' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>',
                                'label' => 'Watermark',
                                'num' => 4
                            ],
                            'recipe' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>',
                                'label' => 'Recipe Steps',
                                'num' => 5
                            ],
                            'export' => [
                                'icon' => '<svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>',
                                'label' => 'Export',
                                'num' => 6
                            ],
                        ];
                        
                        $currentStep = $currentStep ?? 'video';
                        $stepKeys = array_keys($steps);
                        $currentIndex = array_search($currentStep, $stepKeys);
                    @endphp

                    <div class="px-4 mb-4">
                        <p class="text-[10px] uppercase tracking-[0.15em] font-bold text-gray-500 px-2">Workflow</p>
                    </div>

                    <ul class="space-y-0.5 px-3">
                        @foreach($steps as $key => $step)
                            @php
                                $index = array_search($key, $stepKeys);
                                $isActive = $key === $currentStep;
                                $isPast = $index < $currentIndex;
                                $isFuture = $index > $currentIndex;
                                $slug = $project['slug'] ?? null;
                                $stepUrl = '#';
                                if ($slug) {
                                    if ($key === 'video') $stepUrl = route('project.show', $slug);
                                    elseif ($key === 'frames') $stepUrl = route('project.frames', $slug);
                                    elseif ($key === 'crop') $stepUrl = route('project.crop', $slug);
                                    elseif ($key === 'watermark') $stepUrl = route('project.watermark', $slug);
                                    elseif ($key === 'recipe') $stepUrl = route('project.steps', $slug);
                                    elseif ($key === 'export') $stepUrl = route('project.export', $slug);
                                }
                            @endphp
                            <li>
                                <a href="{{ $stepUrl !== '#' ? $stepUrl : 'javascript:void(0)' }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group
                                    {{ $isActive ? 'sidebar-step-active bg-amber-500/[0.08] text-amber-400' : '' }}
                                    {{ $isPast ? 'text-gray-300 hover:bg-white/[0.04] hover:text-white' : '' }}
                                    {{ $isFuture && $stepUrl === '#' ? 'opacity-35 cursor-not-allowed' : '' }}
                                    {{ $isFuture && $stepUrl !== '#' ? 'text-gray-500 hover:bg-white/[0.04] hover:text-gray-300' : '' }}
                                ">
                                    {{-- Step Number Circle --}}
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all duration-200
                                        {{ $isActive ? 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/30' : '' }}
                                        {{ $isPast ? 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/20' : '' }}
                                        {{ $isFuture ? 'bg-white/[0.04] text-gray-500 ring-1 ring-white/[0.06]' : '' }}
                                    ">
                                        @if($isPast)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            {{ $step['num'] }}
                                        @endif
                                    </div>

                                    {{-- Icon + Label --}}
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="{{ $isActive ? 'text-amber-400' : '' }} {{ $isPast ? 'text-gray-400 group-hover:text-gray-300' : '' }} {{ $isFuture ? 'text-gray-600' : '' }} transition-colors shrink-0">
                                            {!! $step['icon'] !!}
                                        </span>
                                        <span class="text-[13px] font-medium truncate">{{ $step['label'] }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                {{-- Bottom sidebar info --}}
                @if(isset($project))
                <div class="px-4 pb-4 mt-auto relative z-10">
                    <div class="p-3 rounded-xl bg-white/[0.03] border border-border-subtle">
                        <p class="text-[10px] uppercase tracking-wider font-bold text-gray-500 mb-1">Project</p>
                        <p class="text-xs font-medium text-gray-300 truncate">{{ $project['name'] ?? 'Untitled' }}</p>
                    </div>
                </div>
                @endif
            </aside>
        @endif

        <!-- Main Workspace -->
        <main class="flex-1 bg-surface-base overflow-y-auto p-6 relative">
            {{-- Subtle corner radial --}}
            <div class="absolute top-0 left-0 w-80 h-80 bg-amber-500/[0.015] rounded-full blur-3xl pointer-events-none"></div>
            <div class="page-content-enter relative z-10">
                @yield('content')
            </div>
        </main>

        <!-- Right Sidebar (Contextual) -->
        @isset($rightSidebar)
            <aside class="w-[280px] glass-surface border-l border-border-default overflow-y-auto shrink-0 p-4">
                {{ $rightSidebar }}
            </aside>
        @endisset
    </div>

    <!-- Bottom Status Bar -->
    <footer class="h-8 glass-surface-light border-t border-border-default flex items-center px-5 shrink-0 text-xs text-gray-500 z-20 relative">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/[0.04] to-transparent"></div>
        @hasSection('status')
            @yield('status')
        @else
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="font-medium">Ready</span>
            </div>
        @endif
    </footer>
</body>
</html>
