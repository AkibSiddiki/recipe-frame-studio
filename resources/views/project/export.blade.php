@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'export'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.steps', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <span>← Recipe Steps</span>
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full">Step 6: Export & Collage Studio</span>
                <span class="text-gray-600">•</span>
                <span class="text-gray-400">Final Publishing</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Recipe Collage' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Stitch your step cards into composite recipe infographics or download all cards as a ZIP bundle.</span>
                <span class="text-gray-600">•</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} step cards ready</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="saveCollageSettings(false)" id="save-collage-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <span>💾</span>
                <span id="save-collage-text">Save Setup</span>
            </button>

            <a href="{{ route('project.export.download.zip', $project['slug']) }}" id="zip-download-btn" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-950/40">
                <span>📦</span>
                <span>Download ZIP Bundle</span>
            </a>

            <a href="{{ route('project.export.download.collage', $project['slug']) }}" id="collage-download-btn" class="bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-emerald-950/40 flex items-center gap-2 active:scale-95">
                <span>📥</span>
                <span>Download Collage</span>
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 bg-emerald-950/60 border border-emerald-700 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(count($selectedFrames) === 0)
        <div class="bg-[#1a1a2e] border border-amber-900/40 rounded-3xl p-12 text-center max-w-xl mx-auto my-12">
            <div class="text-4xl mb-4">🖼️</div>
            <h2 class="text-xl font-bold text-white mb-2">No Step Cards Available</h2>
            <p class="text-gray-400 text-sm mb-6">You need to select candidate frames and configure steps before exporting your recipe collage.</p>
            <a href="{{ route('project.frames', $project['slug']) }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg shadow-amber-900/40">
                <span>← Return to Frames Selector</span>
            </a>
        </div>
    @else
        <!-- Main Export Studio Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left Panel: Controls & Options (5 cols) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- 1. Layout Preset Selection -->
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-800/60">
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
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-800/60">
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
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col gap-4 text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-800/60">
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
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-gray-800/60">
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
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col items-center justify-between min-h-[640px] relative">
                    <!-- Top Bar Info -->
                    <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-gray-800/80 text-xs">
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
                    <div class="relative w-full flex-1 flex items-center justify-center p-3 rounded-2xl bg-[#0b0f19] border border-gray-800/60 overflow-hidden shadow-inner group">
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
                    <div class="w-full mt-4 pt-3 border-t border-gray-800/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Individual Step Cards (Multi-Card Carousel)</span>
                            <span class="text-[11px] text-gray-500">Download single cards for Instagram/TikTok</span>
                        </div>

                        <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
                            @foreach($selectedFrames as $index => $frame)
                                <div class="relative shrink-0 rounded-xl border border-gray-800 bg-[#12192b] overflow-hidden group w-24">
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
</script>
@endsection
