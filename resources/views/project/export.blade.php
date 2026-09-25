@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'export'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.steps', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> <span>Recipe Steps</span>
                </a>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full">Step 6: Export & Collage Studio</span>
                <span class="text-gray-600">·</span>
                <span class="text-gray-400">Final Publishing</span>
            </div>
            <h1 class="text-3xl font-display font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Recipe Collage' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Stitch your step cards into composite recipe infographics or download all cards as a ZIP bundle.</span>
                <span class="text-gray-600">·</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} step cards ready</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="applyLastCollageSetup()" class="px-3.5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60" title="Apply last saved collage setup">
                <span>✨</span>
                <span>Use Last Setup</span>
            </button>

            <button type="button" onclick="saveCollageSettings(false)" id="save-collage-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                <span id="save-collage-text">Save Setup</span>
            </button>

            <button type="button" onclick="startZipExport()" id="zip-download-btn" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-950/40 cursor-pointer active:scale-95 group">
                <span class="group-hover:scale-110 transition-transform">📦</span>
                <span>Download ZIP Bundle</span>
            </button>

            <button type="button" onclick="startCollageExport()" id="collage-download-btn" class="btn-shine bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-emerald-950/40 flex items-center gap-2 active:scale-95 cursor-pointer group">
                <span class="group-hover:scale-110 transition-transform">📥</span>
                <span>Download Collage</span>
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 bg-emerald-950/50 border border-emerald-700/50 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(count($selectedFrames) === 0)
        <div class="bg-surface-overlay border border-amber-900/40 rounded-3xl p-12 text-center max-w-xl mx-auto my-12">
            <div class="text-4xl mb-4">🖼️</div>
            <h2 class="text-xl font-bold text-white mb-2">No Step Cards Available</h2>
            <p class="text-gray-400 text-sm mb-6">You need to select candidate frames and configure steps before exporting your recipe collage.</p>
            <a href="{{ route('project.frames', $project['slug']) }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg shadow-amber-900/40">
                <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> <span>Return to Frames Selector</span>
            </a>
        </div>
    @else
        <!-- Main Export Studio Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left Panel: Controls & Options (5 cols) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- 1. Layout Preset Selection -->
                <div class="glass-surface rounded-3xl p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📐</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Collage Grid Layout</h2>
                        </div>
                        <span id="active-layout-badge" class="px-2.5 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-400 text-xs font-mono font-semibold">
                            Auto Grid
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="button" onclick="setLayout('auto-grid')" id="layout-btn-auto-grid" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">Auto Grid</span>
                                <span class="text-sm">⚡</span>
                            </div>
                            <span class="text-[10px] text-gray-400 leading-tight">Adaptive rows & columns matched to your frame count.</span>
                        </button>

                        <button type="button" onclick="setLayout('grid-2x2')" id="layout-btn-grid-2x2" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">2×2 Grid</span>
                                <span class="text-sm">▦</span>
                            </div>
                            <span class="text-[10px] text-gray-400 leading-tight">Balanced 2-column square layout for 4 key recipe steps.</span>
                        </button>

                        <button type="button" onclick="setLayout('grid-3x2')" id="layout-btn-grid-3x2" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">3×2 Grid</span>
                                <span class="text-sm">☷</span>
                            </div>
                            <span class="text-[10px] text-gray-400 leading-tight">Comprehensive 3-column spread for up to 6 cooking steps.</span>
                        </button>

                        <button type="button" onclick="setLayout('hero-strip')" id="layout-btn-hero-strip" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">Hero + Steps</span>
                                <span class="text-sm">⭐</span>
                            </div>
                            <span class="text-[10px] text-gray-400 leading-tight">Featured finished dish header with cooking step grid below.</span>
                        </button>

                        <button type="button" onclick="setLayout('vertical-story')" id="layout-btn-vertical-story" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm col-span-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold">Vertical Story Pin (Pinterest / Instagram)</span>
                                <span class="text-sm">📱</span>
                            </div>
                            <span class="text-[10px] text-gray-400 leading-tight">Single column vertical strip optimized for Pinterest recipe pins & mobile feeds.</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Header Branding & Recipe Info -->
                <div class="glass-surface rounded-3xl p-6 shadow-2xl flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🏷️</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Collage Header Banner</h2>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs text-gray-300 cursor-pointer">
                            <input type="checkbox" id="header-enabled-toggle" {{ ($collageConfig['header_enabled'] ?? true) ? 'checked' : '' }} onchange="updateLive()" class="rounded bg-gray-800 border-gray-700 text-emerald-500 focus:ring-emerald-500 w-4 h-4">
                            <span>Enable Header</span>
                        </label>
                    </div>

                    <div id="header-options-block" class="flex flex-col gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Recipe Title</label>
                            <input type="text" id="collage-title-input" value="{{ $collageConfig['title'] ?? ($project['name'] ?? 'Recipe Collage') }}" oninput="updateLive()" placeholder="e.g. Crispy Garlic Butter Chicken" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-emerald-500 text-xs">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Subtitle / Chef's Note</label>
                            <input type="text" id="collage-subtitle-input" value="{{ $collageConfig['subtitle'] ?? 'Step-by-step culinary guide' }}" oninput="updateLive()" placeholder="e.g. Quick & easy 20-minute weeknight dinner" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-emerald-500 text-xs">
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Prep Time</label>
                                <input type="text" id="prep-time-input" value="{{ $collageConfig['prep_time'] ?? '15m' }}" oninput="updateLive()" placeholder="15m" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-2.5 py-1 text-gray-100 text-xs focus:outline-none focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Cook Time</label>
                                <input type="text" id="cook-time-input" value="{{ $collageConfig['cook_time'] ?? '25m' }}" oninput="updateLive()" placeholder="25m" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-2.5 py-1 text-gray-100 text-xs focus:outline-none focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Servings</label>
                                <input type="text" id="servings-input" value="{{ $collageConfig['servings'] ?? '4 Servings' }}" oninput="updateLive()" placeholder="4" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-2.5 py-1 text-gray-100 text-xs focus:outline-none focus:border-emerald-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Collage Styling & Spacing -->
                <div class="glass-surface rounded-3xl p-6 shadow-2xl flex flex-col gap-4 text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🎨</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Styling & Spacing</h2>
                        </div>
                    </div>

                    <!-- Background Color -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Canvas Background</label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="setBgColor('#0f172a')" class="px-2.5 py-1 rounded-lg border border-gray-700 bg-[#0f172a] text-gray-300 hover:border-gray-500 transition">Dark Slate</button>
                            <button type="button" onclick="setBgColor('#000000')" class="px-2.5 py-1 rounded-lg border border-gray-700 bg-black text-gray-300 hover:border-gray-500 transition">Pitch Black</button>
                            <button type="button" onclick="setBgColor('#ffffff')" class="px-2.5 py-1 rounded-lg border border-gray-700 bg-white text-gray-800 hover:border-gray-500 transition font-bold">White</button>
                            <button type="button" onclick="setBgColor('#fefce8')" class="px-2.5 py-1 rounded-lg border border-amber-300 bg-[#fefce8] text-amber-900 hover:border-gray-500 transition font-bold">Cream</button>
                            <input type="color" id="bg-color-picker" value="{{ $collageConfig['bg_color'] ?? '#0f172a' }}" onchange="setBgColor(this.value)" class="w-6 h-6 rounded cursor-pointer bg-transparent border-0 p-0 ml-auto">
                        </div>
                    </div>

                    <!-- Spacing -->
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Cell Gap</label>
                                <span id="gap-val" class="font-mono text-emerald-400 font-bold">{{ $collageConfig['gap'] ?? 16 }}px</span>
                            </div>
                            <input type="range" id="gap-slider" min="0" max="48" step="4" value="{{ $collageConfig['gap'] ?? 16 }}" oninput="updateLive()" class="w-full accent-emerald-500 bg-gray-800 rounded-lg cursor-pointer h-2">
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Canvas Margin</label>
                                <span id="padding-val" class="font-mono text-emerald-400 font-bold">{{ $collageConfig['padding'] ?? 24 }}px</span>
                            </div>
                            <input type="range" id="padding-slider" min="0" max="64" step="4" value="{{ $collageConfig['padding'] ?? 24 }}" oninput="updateLive()" class="w-full accent-emerald-500 bg-gray-800 rounded-lg cursor-pointer h-2">
                        </div>
                    </div>

                    <!-- Export Resolution & Format -->
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-border-subtle">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Resolution Scale</label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <button type="button" onclick="setScale(1)" id="scale-btn-1" class="scale-btn py-1 rounded-lg border text-center font-bold text-[11px] transition">1x (1080p)</button>
                                <button type="button" onclick="setScale(2)" id="scale-btn-2" class="scale-btn py-1 rounded-lg border text-center font-bold text-[11px] transition">2x (Ultra HD)</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Output Format</label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <button type="button" onclick="setFormat('jpg')" id="format-btn-jpg" class="format-btn py-1 rounded-lg border text-center font-bold text-[11px] transition">JPG (Fast)</button>
                                <button type="button" onclick="setFormat('png')" id="format-btn-png" class="format-btn py-1 rounded-lg border text-center font-bold text-[11px] transition">PNG</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Live Collage Canvas Viewport (7 cols) -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <div class="glass-surface rounded-3xl p-6 shadow-2xl flex flex-col items-center justify-between min-h-[640px] relative">
                    <!-- Top Bar Info -->
                    <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-border-default text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-white text-sm">Collage Live Preview</span>
                            <span class="px-2 py-0.5 rounded-lg bg-emerald-950/80 text-emerald-400 border border-emerald-800/50 text-[10px] font-mono flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Composite Generated
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="canvas-dim" class="font-mono text-gray-400 text-xs bg-gray-900/80 px-2.5 py-0.5 rounded-lg border border-gray-800">Calculating...</span>
                        </div>
                    </div>

                    <!-- Canvas Viewport -->
                    <div class="relative w-full flex-1 flex items-center justify-center p-3 rounded-2xl bg-[#0b0f19] border border-border-subtle overflow-hidden shadow-inner group">
                        <canvas id="collage-canvas" class="max-w-full max-h-[580px] object-contain rounded-xl shadow-2xl transition duration-200"></canvas>

                        <!-- Loading Indicator -->
                        <div id="canvas-loader" class="absolute inset-0 bg-[#0b0f19]/80 backdrop-blur-sm flex flex-col items-center justify-center text-emerald-400 gap-2 hidden">
                            <svg class="animate-spin h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs font-semibold text-gray-300">Stitching recipe collage...</span>
                        </div>
                    </div>

                    <!-- Bottom Individual Step Cards Strip & Download Links -->
                    <div class="w-full mt-4 pt-3 border-t border-border-default">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Individual Step Cards (Multi-Card Carousel)</span>
                            <span class="text-[11px] text-gray-500">Download single cards for Instagram/TikTok</span>
                        </div>

                        <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
                            @foreach($selectedFrames as $index => $frame)
                                <div class="relative shrink-0 rounded-xl border border-gray-800 bg-surface-base overflow-hidden group w-24">
                                    <div class="aspect-square bg-gray-950 overflow-hidden relative">
                                        <img src="{{ route('project.frame.step-image', ['slug' => $project['slug'], 'filename' => $frame['filename']]) }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition"
                                             alt="Step {{ $index + 1 }}"
                                             loading="lazy">
                                        <div class="absolute top-1 left-1 bg-black/80 text-amber-400 font-mono text-[9px] font-bold px-1.5 py-0.5 rounded shadow">
                                            #{{ sprintf('%02d', $index + 1) }}
                                        </div>
                                    </div>
                                    <div class="p-1.5 flex items-center justify-between text-[10px]">
                                        <span class="font-bold text-gray-300 truncate">Step {{ $index + 1 }}</span>
                                        <a href="{{ route('project.export.download.step', ['slug' => $project['slug'], 'filename' => $frame['filename']]) }}" 
                                           class="text-emerald-400 hover:text-emerald-300 transition" 
                                           title="Download Step {{ $index + 1 }} Card">
                                            📥
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Step-by-Step Export Progress Modal -->
<div id="export-progress-modal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md hidden flex items-center justify-center p-4 sm:p-6 transition-all duration-300">
    <div class="relative w-full max-w-xl rounded-3xl bg-[#0c1322] border border-white/10 shadow-[0_0_80px_rgba(16,185,129,0.18)] overflow-hidden flex flex-col">
        <!-- Top Shimmer Glow Bar -->
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-teal-400 to-emerald-400 animate-pulse"></div>

        <div class="p-6 sm:p-7 flex flex-col gap-6">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div id="export-modal-icon-container" class="w-13 h-13 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-3xl shadow-inner relative shrink-0">
                        <span id="export-modal-icon" class="animate-bounce">📦</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span id="export-badge-tag" class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                Background Export Studio
                            </span>
                            <span id="export-timer" class="text-[11px] font-mono text-gray-400 flex items-center gap-1">
                                <span>⏱️</span><span id="export-timer-val">0.0s</span>
                            </span>
                        </div>
                        <h3 id="export-modal-title" class="text-xl font-display font-extrabold text-white tracking-tight">Exporting Recipe Bundle</h3>
                        <p id="export-modal-subtitle" class="text-xs text-gray-400 mt-0.5">Generating collage, step overlays & compiling into high-res ZIP...</p>
                    </div>
                </div>

                <button type="button" id="export-modal-close-btn" onclick="closeExportModal()" class="text-gray-400 hover:text-white p-2 rounded-xl hover:bg-white/10 transition hidden cursor-pointer" title="Close dialog">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Progress Meter Bar -->
            <div class="bg-black/40 rounded-2xl p-4 border border-white/5 flex flex-col gap-2.5">
                <div class="flex items-center justify-between text-xs">
                    <span id="export-status-label" class="font-semibold text-gray-300 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span id="export-status-text">Processing export pipeline...</span>
                    </span>
                    <span id="export-percent-num" class="font-mono font-extrabold text-base text-emerald-400">10%</span>
                </div>

                <!-- Animated Progress Bar -->
                <div class="w-full h-3 bg-gray-900 rounded-full p-0.5 border border-white/10 overflow-hidden relative shadow-inner">
                    <div id="export-progress-bar-fill" class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-teal-400 to-emerald-400 transition-all duration-300 shadow-[0_0_15px_rgba(52,211,153,0.5)]" style="width: 10%"></div>
                </div>
            </div>

            <!-- Step-by-Step Milestones Trackers -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-white/5">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Export Execution Pipeline</span>
                    <span id="export-pipeline-step-count" class="text-[11px] font-mono text-emerald-400 font-semibold">Step 1 of 5</span>
                </div>

                <div id="export-steps-list" class="space-y-2 mt-1">
                    <!-- Dynamic Steps injected by JS -->
                </div>
            </div>

            <!-- "Anti-Boredom" Live Activity Ticker & Pro Tips Card -->
            <div class="bg-gradient-to-r from-emerald-950/30 via-slate-900/40 to-indigo-950/30 border border-emerald-500/20 rounded-2xl p-3.5 flex items-start gap-3">
                <span class="text-xl shrink-0 mt-0.5" id="export-tip-icon">💡</span>
                <div class="flex-1 min-w-0">
                    <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-400/90 mb-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>What's Happening in Background</span>
                    </div>
                    <p id="export-live-tip-text" class="text-xs text-gray-300 leading-relaxed transition-all duration-300">
                        Preparing recipe frames and rendering complex OpenType typography shaping with soft drop shadows...
                    </p>
                </div>
            </div>

            <!-- Completed Summary Section (Revealed when 100% finished) -->
            <div id="export-completed-section" class="hidden flex flex-col gap-4 pt-2 border-t border-white/10">
                <div class="bg-emerald-950/40 border border-emerald-500/40 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-xl font-bold shrink-0">
                            ✓
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-sm font-bold text-white flex items-center gap-1.5">
                                <span>Export Complete!</span>
                                <span class="text-xs text-emerald-400">🎉</span>
                            </h4>
                            <p id="export-result-details" class="text-xs text-gray-300 font-mono mt-0.5 truncate">
                                recipe_bundle.zip • Ready
                            </p>
                            <p id="export-download-status-container" class="text-[11px] text-emerald-400 font-semibold mt-1 flex items-center gap-1.5">
                                <span id="export-download-ping-dot" class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                <span id="export-download-status-text">Saving file to your device...</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" id="export-open-folder-btn" onclick="openExportDestinationFolder()" class="hidden bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/40 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-md">
                            <span>📂</span>
                            <span>Open Folder</span>
                        </button>
                        <button type="button" id="export-download-direct-btn" onclick="downloadExportedFileAgain()" class="btn-shine bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-emerald-950/50 cursor-pointer">
                            <span>📥</span>
                            <span id="export-download-btn-label">Download Again</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" onclick="closeExportModal()" class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 rounded-xl text-xs font-bold transition cursor-pointer">
                        Done
                    </button>
                </div>
            </div>

            <!-- Error Notification (If any failure occurs) -->
            <div id="export-error-section" class="hidden flex flex-col gap-3 pt-2 border-t border-rose-500/20">
                <div class="bg-rose-950/40 border border-rose-500/40 rounded-2xl p-4 flex items-start gap-3">
                    <span class="text-rose-400 text-xl">⚠️</span>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-white">Export Failed</h4>
                        <p id="export-error-message" class="text-xs text-rose-300 mt-1">An unexpected error occurred during export.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5">
                    <button type="button" onclick="closeExportModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Close
                    </button>
                    <button type="button" id="export-retry-btn" onclick="retryCurrentExport()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-lg shadow-rose-950/40">
                        <span>🔄</span>
                        <span>Retry</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectSlug = @json($project['slug']);
    const selectedFrames = @json($selectedFrames);
    const initialCollageConfig = @json($collageConfig);

    let config = {
        layout: initialCollageConfig.layout || 'auto-grid',
        header_enabled: initialCollageConfig.header_enabled !== false,
        title: initialCollageConfig.title || @json($project['name'] ?? 'Recipe Collage'),
        subtitle: initialCollageConfig.subtitle || 'Step-by-step culinary guide',
        prep_time: initialCollageConfig.prep_time || '15m',
        cook_time: initialCollageConfig.cook_time || '25m',
        servings: initialCollageConfig.servings || '4 Servings',
        bg_color: initialCollageConfig.bg_color || '#0f172a',
        gap: parseInt(initialCollageConfig.gap) || 16,
        padding: parseInt(initialCollageConfig.padding) || 24,
        scale: parseInt(initialCollageConfig.scale) || 1,
        format: initialCollageConfig.format || 'jpg',
        quality: parseInt(initialCollageConfig.quality) || 92
    };

    const canvas = document.getElementById('collage-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    let loadedStepImages = [];

    document.addEventListener('DOMContentLoaded', () => {
        if (!canvas || selectedFrames.length === 0) return;

        setLayout(config.layout, false);
        setScale(config.scale, false);
        setFormat(config.format, false);
        setBgColor(config.bg_color, false);

        // Preload step card images
        loadStepCardImages();
    });

    function loadStepCardImages() {
        document.getElementById('canvas-loader').classList.remove('hidden');
        loadedStepImages = [];

        let loadedCount = 0;
        selectedFrames.forEach((frame, idx) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.src = `{{ url('/project') }}/${projectSlug}/frame/${frame.filename}/step-image`;
            img.onload = () => {
                loadedCount++;
                if (loadedCount === selectedFrames.length) {
                    document.getElementById('canvas-loader').classList.add('hidden');
                    renderCollage();
                }
            };
            img.onerror = () => {
                // Fallback to watermarked image if step-image not yet cached
                img.src = `{{ url('/project') }}/${projectSlug}/frame/${frame.filename}/watermarked`;
                loadedCount++;
                if (loadedCount === selectedFrames.length) {
                    document.getElementById('canvas-loader').classList.add('hidden');
                    renderCollage();
                }
            };
            loadedStepImages[idx] = img;
        });
    }

    // --- Controls Handlers ---
    function setLayout(layoutKey, redraw = true) {
        config.layout = layoutKey;

        document.querySelectorAll('.layout-btn').forEach(btn => {
            btn.className = 'layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm bg-gray-800/80 hover:bg-gray-750 text-gray-300 border-gray-700/80';
        });

        const activeBtn = document.getElementById(`layout-btn-${layoutKey}`);
        if (activeBtn) {
            activeBtn.className = 'layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-md bg-emerald-600/90 text-white border-emerald-500 shadow-emerald-900/30';
        }

        const badge = document.getElementById('active-layout-badge');
        if (badge) {
            const labels = {
                'auto-grid': 'Auto Grid',
                'grid-2x2': '2×2 Grid',
                'grid-3x2': '3×2 Grid',
                'hero-strip': 'Hero + Steps',
                'vertical-story': 'Vertical Story'
            };
            badge.innerText = labels[layoutKey] || 'Custom';
        }

        if (redraw) renderCollage();
    }

    function setScale(scaleVal, redraw = true) {
        config.scale = scaleVal;

        document.querySelectorAll('.scale-btn').forEach(btn => {
            btn.className = 'scale-btn py-1 rounded-lg border text-center font-bold text-[11px] transition bg-gray-800 hover:bg-gray-700 text-gray-300 border-gray-700';
        });

        const activeBtn = document.getElementById(`scale-btn-${scaleVal}`);
        if (activeBtn) {
            activeBtn.className = 'scale-btn py-1 rounded-lg border text-center font-bold text-[11px] transition bg-emerald-600 text-white border-emerald-500 shadow';
        }

        if (redraw) renderCollage();
    }

    function setFormat(fmt, redraw = true) {
        config.format = fmt;

        document.querySelectorAll('.format-btn').forEach(btn => {
            btn.className = 'format-btn py-1 rounded-lg border text-center font-bold text-[11px] transition bg-gray-800 hover:bg-gray-700 text-gray-300 border-gray-700';
        });

        const activeBtn = document.getElementById(`format-btn-${fmt}`);
        if (activeBtn) {
            activeBtn.className = 'format-btn py-1 rounded-lg border text-center font-bold text-[11px] transition bg-emerald-600 text-white border-emerald-500 shadow';
        }

        if (redraw) renderCollage();
    }

    function setBgColor(hex, redraw = true) {
        config.bg_color = hex;
        const picker = document.getElementById('bg-color-picker');
        if (picker) picker.value = hex;
        if (redraw) renderCollage();
    }

    function updateLive() {
        config.header_enabled = document.getElementById('header-enabled-toggle').checked;
        config.title = (document.getElementById('collage-title-input').value || '').trim();
        config.subtitle = (document.getElementById('collage-subtitle-input').value || '').trim();
        config.prep_time = (document.getElementById('prep-time-input').value || '').trim();
        config.cook_time = (document.getElementById('cook-time-input').value || '').trim();
        config.servings = (document.getElementById('servings-input').value || '').trim();

        const gapVal = parseInt(document.getElementById('gap-slider').value) || 16;
        config.gap = gapVal;
        document.getElementById('gap-val').innerText = `${gapVal}px`;

        const padVal = parseInt(document.getElementById('padding-slider').value) || 24;
        config.padding = padVal;
        document.getElementById('padding-val').innerText = `${padVal}px`;

        renderCollage();
    }

    // --- Live Canvas Renderer ---
    function renderCollage() {
        if (!loadedStepImages || loadedStepImages.length === 0 || !loadedStepImages[0].complete) return;

        const firstImg = loadedStepImages[0];
        const origCellW = firstImg.naturalWidth || 1080;
        const origCellH = firstImg.naturalHeight || 1350;

        const scale = config.scale || 1;
        const baseCellW = Math.round(540 * scale);
        const baseCellH = Math.round(baseCellW * (origCellH / origCellW));

        const N = loadedStepImages.length;
        const layout = config.layout || 'auto-grid';

        let cols = 2;
        let rows = 2;

        if (layout === 'grid-2x2') {
            cols = 2;
            rows = Math.ceil(N / 2);
        } else if (layout === 'grid-3x2') {
            cols = 3;
            rows = Math.ceil(N / 3);
        } else if (layout === 'vertical-story') {
            cols = 1;
            rows = N;
        } else if (layout === 'hero-strip') {
            cols = 2;
            rows = N <= 1 ? 1 : (1 + Math.ceil((N - 1) / 2));
        } else {
            // auto-grid
            if (N <= 1) cols = 1;
            else if (N <= 4) cols = 2;
            else if (N <= 9) cols = 3;
            else cols = 4;
            rows = Math.ceil(N / cols);
        }

        const gap = Math.round((config.gap ?? 16) * scale);
        const padding = Math.round((config.padding ?? 24) * scale);

        const headerEnabled = config.header_enabled !== false;
        const headerH = headerEnabled ? Math.round(120 * scale) : 0;

        const totalW = (cols * baseCellW) + ((cols - 1) * gap) + (padding * 2);
        const totalH = headerH + (rows * baseCellH) + ((rows - 1) * gap) + (padding * 2);

        canvas.width = totalW;
        canvas.height = totalH;
        document.getElementById('canvas-dim').innerText = `${totalW} × ${totalH} px • ${scale}x Scale`;

        // Fill background
        ctx.fillStyle = config.bg_color || '#0f172a';
        ctx.fillRect(0, 0, totalW, totalH);

        // Draw Header
        if (headerEnabled) {
            const headerTop = padding;
            const title = config.title || 'Recipe Collage';
            const subtitle = config.subtitle || '';

            // Contrast check for text color
            const isLightBg = isColorLight(config.bg_color);
            const titleColor = isLightBg ? '#0f172a' : '#ffffff';
            const subColor = isLightBg ? '#475569' : '#94a3b8';

            const titleSize = Math.round(26 * scale);
            const subSize = Math.round(14 * scale);

            ctx.fillStyle = titleColor;
            ctx.font = `bold ${titleSize}px sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.fillText(title, padding, headerTop);

            if (subtitle) {
                ctx.fillStyle = subColor;
                ctx.font = `${subSize}px sans-serif`;
                ctx.fillText(subtitle, padding, headerTop + titleSize + (8 * scale));
            }

            // Draw Meta Pills on the right
            const pills = [];
            if (config.prep_time) pills.push(`⏱️ Prep: ${config.prep_time}`);
            if (config.cook_time) pills.push(`🔥 Cook: ${config.cook_time}`);
            if (config.servings) pills.push(`🍽️ Serves: ${config.servings}`);

            let pillX = totalW - padding;
            const pillY = headerTop + (4 * scale);
            const pillH = Math.round(28 * scale);

            pills.reverse().forEach(pill => {
                ctx.font = `bold ${Math.round(11 * scale)}px sans-serif`;
                const textW = ctx.measureText(pill).width;
                const pillW = textW + (20 * scale);
                pillX -= pillW;

                ctx.fillStyle = isLightBg ? 'rgba(0,0,0,0.08)' : 'rgba(30, 41, 59, 0.9)';
                roundRect(ctx, pillX, pillY, pillW, pillH, 8 * scale);
                ctx.fill();

                ctx.fillStyle = isLightBg ? '#b45309' : '#fbbf24'; // amber
                ctx.textBaseline = 'middle';
                ctx.fillText(pill, pillX + (10 * scale), pillY + (pillH / 2));

                pillX -= Math.round(10 * scale);
            });
        }

        // Draw Frame Cells
        loadedStepImages.forEach((img, i) => {
            let cX, cY, cW, cH;

            if (layout === 'hero-strip' && i === 0) {
                cX = padding;
                cY = padding + headerH;
                cW = totalW - (padding * 2);
                cH = baseCellH;
            } else if (layout === 'hero-strip') {
                const subIndex = i - 1;
                const cCol = subIndex % 2;
                const cRow = Math.floor(subIndex / 2);
                cW = Math.floor((totalW - (padding * 2) - gap) / 2);
                cH = baseCellH;
                cX = padding + (cCol * (cW + gap));
                cY = padding + headerH + baseCellH + gap + (cRow * (cH + gap));
            } else {
                const cCol = i % cols;
                const cRow = Math.floor(i / cols);
                cW = baseCellW;
                cH = baseCellH;
                cX = padding + (cCol * (baseCellW + gap));
                cY = padding + headerH + (cRow * (baseCellH + gap));
            }

            ctx.drawImage(img, cX, cY, cW, cH);
        });
    }

    function isColorLight(hex) {
        if (!hex) return false;
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        const r = parseInt(hex.substring(0, 2), 16) || 0;
        const g = parseInt(hex.substring(2, 4), 16) || 0;
        const b = parseInt(hex.substring(4, 6), 16) || 0;
        const brightness = (r * 299 + g * 587 + b * 114) / 1000;
        return brightness > 160;
    }

    function roundRect(ctx, x, y, width, height, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
    }

    // --- Save Collage Setup ---
    function saveCollageSettings(silent = false) {
        const btn = document.getElementById('save-collage-btn');
        const text = document.getElementById('save-collage-text');
        if (!silent && btn && text) {
            btn.disabled = true;
            text.innerText = 'Saving...';
        }

        const payload = {
            _token: csrfToken,
            ...config
        };

        fetch("{{ route('project.export.save', $project['slug']) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (!silent && btn && text) {
                btn.disabled = false;
                text.innerText = 'Save Setup';
            }
            if (data.success) {
                showToast('Collage setup saved successfully!');
            } else {
                alert('Could not save collage settings: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            if (!silent && btn && text) {
                btn.disabled = false;
                text.innerText = 'Save Setup';
            }
            alert('Error saving setup: ' + err.message);
        });
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-10 right-10 z-50 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl font-bold text-sm flex items-center gap-2 border border-emerald-400/40 animate-bounce';
        toast.innerHTML = `<span>✓</span><span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    const lastCollageSetup = @json($lastCollage ?? null);

    function applyLastCollageSetup() {
        if (!lastCollageSetup) {
            showToast('No previous collage setup found.');
            return;
        }

        Object.assign(config, lastCollageSetup);

        if (lastCollageSetup.layout && typeof setLayout === 'function') {
            setLayout(lastCollageSetup.layout, false);
        }
        if (lastCollageSetup.scale && typeof setScale === 'function') {
            setScale(lastCollageSetup.scale, false);
        }
        if (lastCollageSetup.format && typeof setFormat === 'function') {
            setFormat(lastCollageSetup.format, false);
        }
        if (lastCollageSetup.bg_color && typeof setBgColor === 'function') {
            setBgColor(lastCollageSetup.bg_color, false);
        }

        if (document.getElementById('header-check') && lastCollageSetup.header_enabled !== undefined) {
            document.getElementById('header-check').checked = !!lastCollageSetup.header_enabled;
        }
        if (document.getElementById('brand-check') && lastCollageSetup.show_brand !== undefined) {
            document.getElementById('brand-check').checked = !!lastCollageSetup.show_brand;
        }
        if (document.getElementById('footer-check') && lastCollageSetup.footer_enabled !== undefined) {
            document.getElementById('footer-check').checked = !!lastCollageSetup.footer_enabled;
        }
        if (document.getElementById('gap-slider') && lastCollageSetup.gap !== undefined) {
            document.getElementById('gap-slider').value = lastCollageSetup.gap;
            const gapVal = document.getElementById('gap-val');
            if (gapVal) gapVal.innerText = lastCollageSetup.gap + 'px';
        }
        if (document.getElementById('padding-slider') && lastCollageSetup.padding !== undefined) {
            document.getElementById('padding-slider').value = lastCollageSetup.padding;
            const padVal = document.getElementById('padding-val');
            if (padVal) padVal.innerText = lastCollageSetup.padding + 'px';
        }
        if (document.getElementById('quality-slider') && lastCollageSetup.quality !== undefined) {
            document.getElementById('quality-slider').value = lastCollageSetup.quality;
            const qVal = document.getElementById('quality-val');
            if (qVal) qVal.innerText = lastCollageSetup.quality + '%';
        }

        renderCollage();
        showToast('Applied last saved collage setup!');
    }

    // ==========================================
    // STEP-BY-STEP EXPORT ORCHESTRATION & MODAL
    // ==========================================
    let currentExportType = null;
    let exportTimerInterval = null;
    let exportPollInterval = null;
    let exportTipsInterval = null;
    let simulatedProgressTimer = null;
    let exportStartTime = 0;
    let currentProgressPercent = 0;
    let activePipelineSteps = [];

    const backgroundTips = [
        "Shaping complex OpenType fonts with HarfBuzz & multi-pass drop shadows...",
        "Validating step badges, color contrast, and alpha blend opacities...",
        "Compositing recipe photos into high-resolution aspect-ratio grid...",
        "Archiving individual recipe step cards formatted for Instagram & TikTok...",
        "Compiling ingredients, cooking times, and step instructions into RECIPE_SUMMARY.txt...",
        "Fast streaming assets with zero-compression-overhead binary packaging into ZIP archive...",
        "💡 Pro Tip: High-DPI export ensures your recipe cards remain razor-sharp when printed!",
        "💡 Pro Tip: You can share single step cards as a multi-photo carousel on social media.",
        "💡 Pro Tip: The included summary text can be directly pasted into food blogs or YouTube descriptions."
    ];

    const ZIP_PIPELINE_STEPS = [
        { id: 'prep', title: 'Asset Verification', desc: 'Validating selected frames, aspect ratios & text' },
        { id: 'cards', title: 'Step Cards Rendering', desc: 'Applying badges, numbers, titles & drop shadows' },
        { id: 'collage', title: 'Composite Recipe Collage', desc: 'Rendering high-resolution master infographic' },
        { id: 'manifest', title: 'Recipe Manifest & Notes', desc: 'Compiling ingredient notes & RECIPE_SUMMARY.txt' },
        { id: 'zip', title: 'ZIP Bundle Compression', desc: 'Packaging all assets into compressed archive' },
        { id: 'ready', title: 'Delivery & Download', desc: 'Finalizing package and dispatching file' }
    ];

    const COLLAGE_PIPELINE_STEPS = [
        { id: 'prep', title: 'Canvas Configuration', desc: 'Calculating layout grid and resolution scale' },
        { id: 'cards', title: 'Step Frames Preparation', desc: 'Optimizing source images & typography overlays' },
        { id: 'collage', title: 'Master Collage Stitching', desc: 'Rendering grid cells, header banners & badges' },
        { id: 'encode', title: 'Image Compression & Encode', desc: 'Encoding high-quality JPG/PNG canvas output' },
        { id: 'ready', title: 'Delivery & Download', desc: 'Preparing image and launching download' }
    ];

    let currentActiveStepIdx = 0;

    function renderPipelineSteps(steps) {
        activePipelineSteps = steps;
        currentActiveStepIdx = 0;
        const container = document.getElementById('export-steps-list');
        if (!container) return;

        container.innerHTML = steps.map((step, idx) => `
            <div id="step-row-${idx}" class="p-2.5 rounded-xl border border-white/5 bg-white/[0.02] flex items-center justify-between gap-3 transition-all duration-300">
                <div class="flex items-center gap-3 min-w-0">
                    <div id="step-indicator-${idx}" class="w-6 h-6 rounded-full border border-gray-700 bg-gray-800 text-gray-500 font-mono text-[11px] font-bold flex items-center justify-center shrink-0 transition-all">
                        ${idx + 1}
                    </div>
                    <div class="min-w-0">
                        <div id="step-title-${idx}" class="text-xs font-semibold text-gray-400 truncate transition-colors">
                            ${step.title}
                        </div>
                        <div id="step-desc-${idx}" class="text-[10px] text-gray-500 truncate transition-colors">
                            ${step.desc}
                        </div>
                    </div>
                </div>
                <span id="step-badge-${idx}" class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-gray-800/80 text-gray-500 shrink-0 transition-colors">
                    Waiting
                </span>
            </div>
        `).join('');
    }

    function setStepStatus(idx, status, customMessage = null, stepPercent = null) {
        const row = document.getElementById(`step-row-${idx}`);
        const indicator = document.getElementById(`step-indicator-${idx}`);
        const title = document.getElementById(`step-title-${idx}`);
        const desc = document.getElementById(`step-desc-${idx}`);
        const badge = document.getElementById(`step-badge-${idx}`);

        if (!row || !indicator) return;

        if (customMessage && desc) {
            desc.innerText = customMessage;
        }

        if (status === 'completed') {
            row.className = 'p-2.5 rounded-xl border border-emerald-500/20 bg-emerald-950/20 flex items-center justify-between gap-3 transition-all duration-300';
            indicator.className = 'w-6 h-6 rounded-full border border-emerald-500 bg-emerald-500/20 text-emerald-400 text-xs font-bold flex items-center justify-center shrink-0 shadow-[0_0_10px_rgba(16,185,129,0.3)]';
            indicator.innerHTML = '✓';
            if (title) title.className = 'text-xs font-semibold text-emerald-300 truncate transition-colors';
            if (desc) desc.className = 'text-[10px] text-emerald-400/70 truncate transition-colors';
            if (badge) {
                badge.className = 'text-[10px] font-mono px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 shrink-0 font-bold border border-emerald-500/30';
                badge.innerText = '100% ✓';
            }
        } else if (status === 'active') {
            currentActiveStepIdx = idx;
            row.className = 'p-2.5 rounded-xl border border-teal-500/30 bg-teal-950/30 flex items-center justify-between gap-3 transition-all duration-300 ring-1 ring-teal-500/20';
            indicator.className = 'w-6 h-6 rounded-full border border-teal-400 bg-teal-500/30 text-teal-300 text-xs font-bold flex items-center justify-center shrink-0 animate-pulse';
            indicator.innerHTML = `
                <svg class="animate-spin h-3.5 w-3.5 text-teal-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            `;
            if (title) title.className = 'text-xs font-bold text-white truncate transition-colors';
            if (desc) desc.className = 'text-[10px] text-teal-300 truncate transition-colors';
            if (badge) {
                const pct = stepPercent !== null ? Math.round(stepPercent) : Math.round(currentProgressPercent);
                badge.className = 'text-[10px] font-mono px-2.5 py-0.5 rounded-md bg-teal-500/25 text-teal-200 shrink-0 font-extrabold border border-teal-400/40 shadow-[0_0_8px_rgba(45,212,191,0.3)] animate-pulse';
                badge.innerText = `${pct}%`;
            }

            const stepCountEl = document.getElementById('export-pipeline-step-count');
            if (stepCountEl) {
                stepCountEl.innerText = `Step ${idx + 1} of ${activePipelineSteps.length}`;
            }
        } else {
            row.className = 'p-2.5 rounded-xl border border-white/5 bg-white/[0.02] flex items-center justify-between gap-3 transition-all duration-300';
            indicator.className = 'w-6 h-6 rounded-full border border-gray-700 bg-gray-800 text-gray-500 font-mono text-[11px] font-bold flex items-center justify-center shrink-0';
            indicator.innerHTML = `${idx + 1}`;
            if (title) title.className = 'text-xs font-semibold text-gray-400 truncate transition-colors';
            if (desc) desc.className = 'text-[10px] text-gray-500 truncate transition-colors';
            if (badge) {
                badge.className = 'text-[10px] font-mono px-2 py-0.5 rounded-md bg-gray-800/80 text-gray-500 shrink-0';
                badge.innerText = 'Waiting';
            }
        }
    }

    function setProgressBar(percent, statusText = null) {
        currentProgressPercent = Math.min(100, Math.max(0, percent));
        const bar = document.getElementById('export-progress-bar-fill');
        const num = document.getElementById('export-percent-num');
        const text = document.getElementById('export-status-text');

        if (bar) bar.style.width = `${currentProgressPercent}%`;
        if (num) num.innerText = `${Math.round(currentProgressPercent)}%`;
        if (statusText && text) text.innerText = statusText;

        // Continuously update the active step badge with percentage
        if (currentActiveStepIdx !== null && currentProgressPercent < 100) {
            const activeBadge = document.getElementById(`step-badge-${currentActiveStepIdx}`);
            if (activeBadge) {
                activeBadge.innerText = `${Math.round(currentProgressPercent)}%`;
            }
        }
    }

    function startLiveTipsRotation() {
        if (exportTipsInterval) clearInterval(exportTipsInterval);
        let tipIndex = 0;
        const tipEl = document.getElementById('export-live-tip-text');
        if (!tipEl) return;

        tipEl.innerText = backgroundTips[0];

        exportTipsInterval = setInterval(() => {
            tipIndex = (tipIndex + 1) % backgroundTips.length;
            tipEl.style.opacity = '0';
            setTimeout(() => {
                tipEl.innerText = backgroundTips[tipIndex];
                tipEl.style.opacity = '1';
            }, 250);
        }, 3200);
    }

    function startElapsedTimer() {
        if (exportTimerInterval) clearInterval(exportTimerInterval);
        exportStartTime = Date.now();
        const timerVal = document.getElementById('export-timer-val');

        exportTimerInterval = setInterval(() => {
            const elapsedSeconds = ((Date.now() - exportStartTime) / 1000).toFixed(1);
            if (timerVal) timerVal.innerText = `${elapsedSeconds}s`;
        }, 100);
    }

    function startZipExport() {
        currentExportType = 'zip';
        prepareAndShowModal({
            title: 'Exporting Complete Recipe Bundle',
            subtitle: 'Stitching collage, rendering step cards & packing into ZIP bundle...',
            icon: '📦',
            badge: 'ZIP Bundle Packaging',
            steps: ZIP_PIPELINE_STEPS
        });

        // Ensure latest unsaved styling adjustments are applied
        saveCollageSettings(true);

        // Simulated intelligent progress progression with clear percentage updates
        startSimulatedProgress([
            { atMs: 150, percent: 12, stepIdx: 0, msg: 'Inspecting recipe step frames and canvas settings (12%)...' },
            { atMs: 900, percent: 28, stepIdx: 1, msg: 'Rendering step cards with badges & font drop shadows (28%)...' },
            { atMs: 1800, percent: 42, stepIdx: 1, msg: 'Applying overlays and formatting typography (42%)...' },
            { atMs: 2700, percent: 56, stepIdx: 2, msg: 'Stitching composite recipe collage canvas (56%)...' },
            { atMs: 3600, percent: 70, stepIdx: 2, msg: 'Compositing recipe header and metadata badges (70%)...' },
            { atMs: 4400, percent: 76, stepIdx: 3, msg: 'Compiling recipe instructions & ingredients manifest (76%)...' },
            { atMs: 5200, percent: 84, stepIdx: 4, msg: 'Packaging collage and step cards into ZIP archive (84%)...' },
            { atMs: 6000, percent: 92, stepIdx: 4, msg: 'Fast streaming binary packaging into ZIP package (92%)...' },
            { atMs: 6800, percent: 98, stepIdx: 4, msg: 'Finalizing ZIP bundle archive package (98%)...' },
        ]);

        // Start backend polling for real progress
        startBackendPolling();

        fetch("{{ route('project.export.prepare.zip', $project['slug']) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                collage_settings: config
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                handleExportSuccess(data);
            } else {
                handleExportError(data.error || 'Failed to generate ZIP archive.');
            }
        })
        .catch(err => {
            handleExportError('Network error during export: ' + err.message);
        });
    }

    function startCollageExport() {
        currentExportType = 'collage';
        prepareAndShowModal({
            title: 'Rendering Master Recipe Collage',
            subtitle: 'Stitching recipe step cells, header banners & high-DPI canvas...',
            icon: '🖼️',
            badge: 'Collage Infographic Studio',
            steps: COLLAGE_PIPELINE_STEPS
        });

        saveCollageSettings(true);

        startSimulatedProgress([
            { atMs: 200, percent: 15, stepIdx: 0, msg: 'Calculating grid dimensions and DPI resolution (15%)...' },
            { atMs: 1500, percent: 45, stepIdx: 1, msg: 'Generating step cards with HarfBuzz script overlays (45%)...' },
            { atMs: 3200, percent: 75, stepIdx: 2, msg: 'Compositing recipe layout grid, banners & meta badges (75%)...' },
            { atMs: 5000, percent: 90, stepIdx: 3, msg: 'Encoding output image format (90%)...' },
        ]);

        startBackendPolling();

        fetch("{{ route('project.export.prepare.collage', $project['slug']) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                collage_settings: config
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                handleExportSuccess(data);
            } else {
                handleExportError(data.error || 'Failed to render recipe collage.');
            }
        })
        .catch(err => {
            handleExportError('Network error during collage render: ' + err.message);
        });
    }

    function prepareAndShowModal(options) {
        document.getElementById('export-modal-title').innerText = options.title;
        document.getElementById('export-modal-subtitle').innerText = options.subtitle;
        document.getElementById('export-modal-icon').innerText = options.icon;
        document.getElementById('export-badge-tag').innerText = options.badge;

        document.getElementById('export-completed-section').classList.add('hidden');
        document.getElementById('export-error-section').classList.add('hidden');
        document.getElementById('export-modal-close-btn').classList.add('hidden');

        renderPipelineSteps(options.steps);
        setProgressBar(5, 'Initializing background export pipeline...');
        setStepStatus(0, 'active', null, 5);

        startElapsedTimer();
        startLiveTipsRotation();

        const modal = document.getElementById('export-progress-modal');
        modal.classList.remove('hidden');
    }

    function startSimulatedProgress(milestones) {
        if (simulatedProgressTimer) clearTimeout(simulatedProgressTimer);

        milestones.forEach((m, idx) => {
            setTimeout(() => {
                const isComplete = !document.getElementById('export-completed-section').classList.contains('hidden');
                if (currentProgressPercent < m.percent && !isComplete) {
                    setProgressBar(m.percent, m.msg);
                    for (let i = 0; i < m.stepIdx; i++) {
                        setStepStatus(i, 'completed');
                    }
                    setStepStatus(m.stepIdx, 'active', m.msg, m.percent);
                }
            }, m.atMs);
        });
    }

    function startBackendPolling() {
        if (exportPollInterval) clearInterval(exportPollInterval);

        exportPollInterval = setInterval(() => {
            fetch("{{ route('project.export.status', $project['slug']) }}", {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.percent !== undefined) {
                    const pct = data.percent;
                    if (pct > currentProgressPercent && pct < 100) {
                        setProgressBar(pct, data.message || 'Processing assets...');

                        const stepIdx = data.step_index !== undefined ? data.step_index : (
                            data.stage === 'prep' ? 0 :
                            data.stage === 'cards' ? 1 :
                            data.stage === 'collage' ? 2 :
                            data.stage === 'manifest' ? 3 :
                            (data.stage === 'zip' || data.stage === 'compressing') ? 4 : 0
                        );

                        for (let i = 0; i < stepIdx; i++) {
                            setStepStatus(i, 'completed');
                        }
                        setStepStatus(stepIdx, 'active', data.message || 'Processing...', pct);
                    }
                }
            })
            .catch(() => {
                // Ignore transient network poll errors
            });
        }, 300);
    }

    let lastExportResult = null;

    function handleExportSuccess(data) {
        clearInterval(exportPollInterval);
        clearInterval(exportTimerInterval);
        clearInterval(exportTipsInterval);
        if (simulatedProgressTimer) clearTimeout(simulatedProgressTimer);

        setProgressBar(100, 'Export pipeline finished successfully!');

        // Mark all pipeline steps completed
        activePipelineSteps.forEach((_, idx) => {
            setStepStatus(idx, 'completed');
        });

        lastExportResult = {
            download_url: data.download_url,
            filename: data.filename,
            file_size: data.file_size,
            type: currentExportType,
            saved_path: null
        };

        // Update details in complete card
        const detailsEl = document.getElementById('export-result-details');
        const openFolderBtn = document.getElementById('export-open-folder-btn');
        if (detailsEl) {
            const countText = data.total_cards ? `${data.total_cards} Cards Included • ` : '';
            detailsEl.innerText = `${data.filename || 'Recipe Export'} • ${countText}${data.file_size || ''}`;
        }

        if (openFolderBtn) {
            openFolderBtn.classList.add('hidden');
        }

        setExportDownloadStatus('Saving file to your device...', true);

        document.getElementById('export-completed-section').classList.remove('hidden');
        document.getElementById('export-modal-close-btn').classList.remove('hidden');

        // Automatically trigger file download immediately
        if (data.download_url) {
            triggerFileDownload(data.download_url, data.filename || '');
        }

        showToast('Export complete! Your download has started automatically.');
    }

    function setExportDownloadStatus(message, isPending = false) {
        const textEl = document.getElementById('export-download-status-text');
        const pingDot = document.getElementById('export-download-ping-dot');
        if (textEl) {
            textEl.innerText = message;
        }
        if (pingDot) {
            if (isPending) {
                pingDot.classList.remove('hidden');
                pingDot.classList.add('animate-ping');
            } else {
                pingDot.classList.add('hidden');
                pingDot.classList.remove('animate-ping');
            }
        }
    }

    async function triggerFileDownload(url, filename) {
        setExportDownloadStatus('Selecting save location...', true);

        // Method 1: Try Native Desktop Save Dialog (NativePHP Electron)
        try {
            const response = await fetch("{{ route('project.export.native-save', $project['slug']) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    type: currentExportType,
                    filename: filename
                })
            });

            if (response.ok) {
                const nativeData = await response.json();

                if (nativeData.success && nativeData.native) {
                    if (lastExportResult) {
                        lastExportResult.saved_path = nativeData.saved_path;
                    }

                    setExportDownloadStatus(`✓ Saved to: ${nativeData.saved_path || 'device'} (Revealed in Explorer)`, false);

                    const openFolderBtn = document.getElementById('export-open-folder-btn');
                    if (openFolderBtn) {
                        openFolderBtn.classList.remove('hidden');
                    }

                    const dlBtnLabel = document.getElementById('export-download-btn-label');
                    if (dlBtnLabel) {
                        dlBtnLabel.innerText = 'Save As...';
                    }

                    showToast('File saved successfully and revealed in folder!');
                    return;
                } else if (nativeData.native && nativeData.cancelled) {
                    setExportDownloadStatus('Save cancelled. Click "Download Again" to save anytime.', false);
                    return;
                }
            }
        } catch (e) {
            console.info('Native save skipped or unavailable, using browser download:', e);
        }

        // Method 2: High-reliability In-Memory Blob Download (Web Browser fallback)
        try {
            setExportDownloadStatus('Downloading via browser...', true);

            const fileRes = await fetch(url);
            if (!fileRes.ok) {
                throw new Error(`Server returned HTTP ${fileRes.status}`);
            }

            const blob = await fileRes.blob();
            const blobUrl = window.URL.createObjectURL(blob);

            const tempLink = document.createElement('a');
            tempLink.style.display = 'none';
            tempLink.href = blobUrl;
            tempLink.download = filename || (currentExportType === 'zip' ? 'recipe_bundle.zip' : 'recipe_collage.jpg');
            document.body.appendChild(tempLink);
            tempLink.click();

            setTimeout(() => {
                if (document.body.contains(tempLink)) {
                    document.body.removeChild(tempLink);
                }
                window.URL.revokeObjectURL(blobUrl);
            }, 4000);

            setExportDownloadStatus('✓ Downloaded automatically to your Downloads folder!', false);
            showToast('Download complete! Check your Downloads folder.');
        } catch (err) {
            console.warn('Blob download encountered an issue, falling back to direct anchor:', err);

            // Method 3: Direct anchor click fallback
            try {
                const fallbackLink = document.createElement('a');
                fallbackLink.href = url;
                fallbackLink.download = filename || '';
                fallbackLink.target = '_blank';
                fallbackLink.style.display = 'none';
                document.body.appendChild(fallbackLink);
                fallbackLink.click();
                setTimeout(() => {
                    if (document.body.contains(fallbackLink)) {
                        document.body.removeChild(fallbackLink);
                    }
                }, 2000);

                setExportDownloadStatus('✓ Download initiated.', false);
            } catch (fallbackErr) {
                setExportDownloadStatus('Download could not be initiated automatically. Please click "Download Again".', false);
            }
        }
    }

    function downloadExportedFileAgain() {
        if (!lastExportResult || !lastExportResult.download_url) {
            if (currentExportType === 'zip') {
                startZipExport();
            } else {
                startCollageExport();
            }
            return;
        }

        triggerFileDownload(lastExportResult.download_url, lastExportResult.filename || '');
    }

    function openExportDestinationFolder() {
        const path = lastExportResult ? lastExportResult.saved_path : null;

        fetch("{{ route('project.export.open-folder', $project['slug']) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path: path })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Opened export folder in File Explorer.');
            } else {
                showToast(data.error || 'Could not open folder in Explorer.');
            }
        })
        .catch(err => {
            showToast('Unable to open explorer: ' + err.message);
        });
    }

    function handleExportError(errorMessage) {
        clearInterval(exportPollInterval);
        clearInterval(exportTimerInterval);
        clearInterval(exportTipsInterval);
        if (simulatedProgressTimer) clearTimeout(simulatedProgressTimer);

        document.getElementById('export-error-message').innerText = errorMessage;
        document.getElementById('export-error-section').classList.remove('hidden');
        document.getElementById('export-modal-close-btn').classList.remove('hidden');
    }

    function retryCurrentExport() {
        document.getElementById('export-error-section').classList.add('hidden');
        if (currentExportType === 'zip') {
            startZipExport();
        } else {
            startCollageExport();
        }
    }

    function closeExportModal() {
        clearInterval(exportPollInterval);
        clearInterval(exportTimerInterval);
        clearInterval(exportTipsInterval);
        if (simulatedProgressTimer) clearTimeout(simulatedProgressTimer);

        document.getElementById('export-progress-modal').classList.add('hidden');
    }
</script>
@endsection
