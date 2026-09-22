@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'recipe'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.watermark', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <span>← Watermark & Branding</span>
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Step 5: Recipe Steps & Text Overlays</span>
                <span class="text-gray-600">/</span>
                <span class="text-gray-500">Step 6: Export & Collage</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Recipe Steps' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Add step numbers, culinary instructions, and ingredient notes over your candidate frames.</span>
                <span class="text-gray-600">•</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} frames selected</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="resetAllSteps()" class="px-3.5 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <span>↺</span>
                <span>Reset Defaults</span>
            </button>

            <button type="button" onclick="saveSteps(false)" id="save-steps-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60">
                <span>💾</span>
                <span id="save-steps-text">Save Steps</span>
            </button>

            <button type="button" onclick="saveAndProceed()" id="proceed-btn" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-amber-900/30 flex items-center gap-2 active:scale-95">
                <span>Next: Export & Collage (Step 6)</span>
                <span>→</span>
            </button>
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
            <h2 class="text-xl font-bold text-white mb-2">No Frames Selected</h2>
            <p class="text-gray-400 text-sm mb-6">You need to select at least one video frame to build recipe steps and text overlays.</p>
            <a href="{{ route('project.frames', $project['slug']) }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg shadow-amber-900/40">
                <span>← Return to Frames Selector</span>
            </a>
        </div>
    @else
        <!-- Main Studio Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left Panel: Global Styling & Step Content Cards (5 cols) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- 1. Global Styling & Overlay Theme -->
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-800/60">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🎨</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Overlay Style & Layout</h2>
                        </div>
                        <span id="active-layout-badge" class="px-2.5 py-0.5 rounded-lg bg-amber-500/20 text-amber-400 text-xs font-mono font-semibold">
                            Bottom Banner
                        </span>
                    </div>

                    <!-- Layout Selector Grid -->
                    <div class="mb-5">
                        <label class="block text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">Layout Template</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button type="button" onclick="setLayout('bottom-banner')" id="layout-btn-bottom-banner" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold">Bottom Banner</span>
                                    <span class="text-sm">🗂️</span>
                                </div>
                                <span class="text-[10px] text-gray-400 leading-tight">Dark gradient banner with step badge & instructions at bottom.</span>
                            </button>

                            <button type="button" onclick="setLayout('lower-third')" id="layout-btn-lower-third" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold">Floating Pill</span>
                                    <span class="text-sm">💊</span>
                                </div>
                                <span class="text-[10px] text-gray-400 leading-tight">Modern rounded floating card placed above the bottom edge.</span>
                            </button>

                            <button type="button" onclick="setLayout('top-banner')" id="layout-btn-top-banner" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold">Top Banner</span>
                                    <span class="text-sm">🔝</span>
                                </div>
                                <span class="text-[10px] text-gray-400 leading-tight">Top-aligned header overlay ideal for short titles & headers.</span>
                            </button>

                            <button type="button" onclick="setLayout('badge-only')" id="layout-btn-badge-only" class="layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold">Badge Only</span>
                                    <span class="text-sm">🏷️</span>
                                </div>
                                <span class="text-[10px] text-gray-400 leading-tight">Minimalist #01 corner badge without full instruction text.</span>
                            </button>
                        </div>
                    </div>

                    <!-- Styling Controls Grid -->
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-gray-800/60 text-xs">
                        <!-- Background Opacity -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-gray-400 font-bold uppercase tracking-wider text-[11px]">Banner Opacity</label>
                                <span id="opacity-val" class="font-mono text-amber-400 font-bold">85%</span>
                            </div>
                            <input type="range" id="bg-opacity-slider" min="20" max="100" value="{{ $recipeSteps['style']['bg_opacity'] ?? 85 }}" oninput="updateLive()" class="w-full accent-amber-500 bg-gray-800 rounded-lg cursor-pointer h-2">
                        </div>

                        <!-- Font Scale -->
                        <div>
                            <label class="block text-gray-400 font-bold uppercase tracking-wider text-[11px] mb-1">Font Scale</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button type="button" onclick="setFontSize('small')" id="font-size-small" class="font-size-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition">Small</button>
                                <button type="button" onclick="setFontSize('medium')" id="font-size-medium" class="font-size-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition">Med</button>
                                <button type="button" onclick="setFontSize('large')" id="font-size-large" class="font-size-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition">Large</button>
                            </div>
                        </div>

                        <!-- Text Color -->
                        <div>
                            <label class="block text-gray-400 font-bold uppercase tracking-wider text-[11px] mb-1.5">Text Color</label>
                            <div class="flex items-center gap-1.5">
                                <button type="button" onclick="setTextColor('#ffffff')" class="w-5 h-5 rounded-full bg-white border border-gray-600 hover:scale-110 transition shadow" title="Pure White"></button>
                                <button type="button" onclick="setTextColor('#fde047')" class="w-5 h-5 rounded-full bg-yellow-300 border border-gray-600 hover:scale-110 transition shadow" title="Warm Yellow"></button>
                                <button type="button" onclick="setTextColor('#fdba74')" class="w-5 h-5 rounded-full bg-orange-300 border border-gray-600 hover:scale-110 transition shadow" title="Peach Gold"></button>
                                <input type="color" id="text-color-picker" value="{{ $recipeSteps['style']['text_color'] ?? '#ffffff' }}" onchange="setTextColor(this.value)" class="w-6 h-6 rounded cursor-pointer bg-transparent border-0 p-0 ml-1">
                            </div>
                        </div>

                        <!-- Badge Accent Color -->
                        <div>
                            <label class="block text-gray-400 font-bold uppercase tracking-wider text-[11px] mb-1.5">Badge Accent</label>
                            <div class="flex items-center gap-1.5">
                                <button type="button" onclick="setBadgeColor('#f59e0b')" class="w-5 h-5 rounded-full bg-amber-500 border border-gray-600 hover:scale-110 transition shadow" title="Amber"></button>
                                <button type="button" onclick="setBadgeColor('#ef4444')" class="w-5 h-5 rounded-full bg-red-500 border border-gray-600 hover:scale-110 transition shadow" title="Crimson"></button>
                                <button type="button" onclick="setBadgeColor('#10b981')" class="w-5 h-5 rounded-full bg-emerald-500 border border-gray-600 hover:scale-110 transition shadow" title="Emerald"></button>
                                <button type="button" onclick="setBadgeColor('#6366f1')" class="w-5 h-5 rounded-full bg-indigo-500 border border-gray-600 hover:scale-110 transition shadow" title="Indigo"></button>
                                <input type="color" id="badge-color-picker" value="{{ $recipeSteps['style']['badge_color'] ?? '#f59e0b' }}" onchange="setBadgeColor(this.value)" class="w-6 h-6 rounded cursor-pointer bg-transparent border-0 p-0 ml-1">
                            </div>
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="mt-4 pt-3 border-t border-gray-800/60 flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-300">
                            <input type="checkbox" id="show-badge-checkbox" checked onchange="updateLive()" class="rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-4 h-4">
                            <span>Display numbered step badge (e.g. <b>#01</b>)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Step Items Editor (Accordion / Stack) -->
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-800/60 gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📝</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Step Instructions</h2>
                            <span class="text-xs text-gray-500 font-mono">({{ count($recipeSteps['items'] ?? []) }})</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs">
                            <button type="button" onclick="setAllStepsEnabled(true)" class="px-2.5 py-1 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[11px] font-semibold transition flex items-center gap-1 active:scale-95 shadow-sm" title="Enable overlay on all step frames">
                                <span>✓</span>
                                <span>Enable All</span>
                            </button>
                            <button type="button" onclick="setAllStepsEnabled(false)" class="px-2.5 py-1 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-[11px] font-semibold transition flex items-center gap-1 active:scale-95 shadow-sm" title="Disable overlay on all step frames">
                                <span>✕</span>
                                <span>Disable All</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 max-h-[520px] overflow-y-auto pr-1" id="step-cards-container">
                        @foreach($recipeSteps['items'] as $idx => $step)
                            @php
                                $frame = collect($selectedFrames)->firstWhere('filename', $step['frame_id']) 
                                      ?? collect($selectedFrames)->firstWhere('id', $step['frame_id'])
                                      ?? ($selectedFrames[$idx] ?? null);
                            @endphp
                            <div class="step-card bg-[#141b2d] border border-gray-800/80 rounded-2xl p-4 transition-all duration-200" 
                                 id="step-card-{{ $idx }}" 
                                 data-index="{{ $idx }}"
                                 onclick="selectStep({{ $idx }})">
                                <!-- Card Header -->
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-mono font-bold text-xs shrink-0">
                                            #{{ sprintf('%02d', $step['step_number'] ?? ($idx + 1)) }}
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-white block">Step {{ $step['step_number'] ?? ($idx + 1) }}</span>
                                            <span class="text-[10px] text-gray-500 font-mono">{{ $frame['formatted_time'] ?? '' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                        <label class="text-[11px] text-gray-400 flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" id="step-enabled-{{ $idx }}" {{ ($step['enabled'] ?? true) ? 'checked' : '' }} onchange="onStepEnabledChange({{ $idx }})" class="rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-3.5 h-3.5">
                                            <span>Enabled</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Inputs (Visible when expanded or active) -->
                                <div class="flex flex-col gap-2.5 text-xs" onclick="event.stopPropagation()">
                                    <!-- Title Input -->
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Step Title / Headline</label>
                                        <input type="text" id="step-title-{{ $idx }}" value="{{ $step['title'] ?? '' }}" oninput="onStepTextChange({{ $idx }})" placeholder="e.g. Sear the Chicken Thighs" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-amber-500 text-xs">
                                    </div>

                                    <!-- Description Area -->
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Culinary Instructions</label>
                                        <textarea id="step-desc-{{ $idx }}" rows="2" oninput="onStepTextChange({{ $idx }})" placeholder="e.g. Cook on high heat for 3-4 minutes each side until deeply golden and crispy." class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-amber-500 text-xs resize-none"></textarea>
                                    </div>

                                    <!-- Ingredients / Notes Input -->
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Key Ingredients & Quantities (Optional)</label>
                                        <input type="text" id="step-ing-{{ $idx }}" value="{{ $step['ingredients'] ?? '' }}" oninput="onStepTextChange({{ $idx }})" placeholder="e.g. 2 tbsp olive oil, 1 tsp sea salt, fresh thyme" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-amber-500 text-xs">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Panel: Live Composite Canvas & Step Card Preview (7 cols) -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <div class="bg-[#1a1a2e]/95 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col items-center justify-between min-h-[600px] relative">
                    <!-- Preview Top Bar -->
                    <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-gray-800/80 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-white text-sm" id="active-step-label">Step #01 Preview</span>
                            <span class="px-2 py-0.5 rounded-lg bg-emerald-950/80 text-emerald-400 border border-emerald-800/50 text-[10px] font-mono flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Live Composite
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="canvas-dim" class="font-mono text-gray-500 text-xs bg-gray-900/80 px-2 py-0.5 rounded-lg border border-gray-800">1080 × 1080 px</span>
                        </div>
                    </div>

                    <!-- Canvas Viewport -->
                    <div class="relative w-full flex-1 flex items-center justify-center p-2 rounded-2xl bg-[#0b0f19] border border-gray-800/60 overflow-hidden shadow-inner group">
                        <canvas id="step-canvas" class="max-w-full max-h-[520px] object-contain rounded-xl shadow-2xl transition duration-200"></canvas>
                        
                        <!-- Loading Indicator -->
                        <div id="canvas-loader" class="absolute inset-0 bg-[#0b0f19]/80 backdrop-blur-sm flex flex-col items-center justify-center text-amber-500 gap-2 hidden">
                            <svg class="animate-spin h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs font-semibold text-gray-300">Rendering composite frame...</span>
                        </div>
                    </div>

                    <!-- Bottom Frame Carousel Thumbnails -->
                    <div class="w-full mt-4 pt-3 border-t border-gray-800/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Select Frame to Preview</span>
                            <span class="text-[11px] text-gray-500">Click any card to edit its recipe step</span>
                        </div>
                        <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
                            @foreach($selectedFrames as $index => $frame)
                                <div class="step-thumb-card relative shrink-0 cursor-pointer rounded-xl border transition-all duration-200 overflow-hidden group w-20"
                                     data-index="{{ $index }}"
                                     onclick="selectStep({{ $index }})"
                                     id="thumb-card-{{ $index }}">
                                    <div class="aspect-square bg-gray-950 overflow-hidden relative">
                                        <img src="{{ route('project.frame.watermarked', ['slug' => $project['slug'], 'filename' => $frame['filename']]) }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition"
                                             alt="Frame {{ $index + 1 }}"
                                             loading="lazy">
                                        <div class="absolute top-1 left-1 bg-black/80 text-amber-400 font-mono text-[9px] font-bold px-1.5 py-0.5 rounded shadow">
                                            #{{ sprintf('%02d', $index + 1) }}
                                        </div>
                                    </div>
                                    <div class="p-1 bg-[#12192b] text-[9px] font-semibold text-center text-gray-300 truncate">
                                        Step {{ $index + 1 }}
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
    const initialStepsData = @json($recipeSteps);

    // State
    let activeIndex = 0;
    let styleConfig = initialStepsData.style || {
        layout: 'bottom-banner',
        bg_color: '#0f172a',
        bg_opacity: 85,
        text_color: '#ffffff',
        badge_color: '#f59e0b',
        badge_text_color: '#ffffff',
        show_badge: true,
        font_size: 'medium'
    };

    let stepItems = (initialStepsData.items && initialStepsData.items.length > 0) 
        ? JSON.parse(JSON.stringify(initialStepsData.items)) 
        : [];

    const canvas = document.getElementById('step-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    let currentBaseImage = new Image();

    document.addEventListener('DOMContentLoaded', () => {
        if (!canvas || selectedFrames.length === 0) return;

        // Populate textareas with initial values (to preserve newlines)
        stepItems.forEach((item, idx) => {
            const descEl = document.getElementById(`step-desc-${idx}`);
            if (descEl && item.description) {
                descEl.value = item.description;
            }
        });

        // Initialize Style Buttons
        setLayout(styleConfig.layout || 'bottom-banner', false);
        setFontSize(styleConfig.font_size || 'medium', false);
        setTextColor(styleConfig.text_color || '#ffffff', false);
        setBadgeColor(styleConfig.badge_color || '#f59e0b', false);

        if (document.getElementById('show-badge-checkbox')) {
            document.getElementById('show-badge-checkbox').checked = styleConfig.show_badge !== false;
        }
        if (document.getElementById('bg-opacity-slider')) {
            document.getElementById('bg-opacity-slider').value = styleConfig.bg_opacity || 85;
            document.getElementById('opacity-val').innerText = `${styleConfig.bg_opacity || 85}%`;
        }

        // Select the first frame
        selectStep(0);
    });

    // --- Step Selection ---
    function selectStep(index) {
        if (index < 0 || index >= selectedFrames.length) return;
        activeIndex = index;

        // Highlight active cards
        document.querySelectorAll('.step-card').forEach((el, i) => {
            if (i === index) {
                el.classList.add('border-amber-500', 'ring-2', 'ring-amber-500/30', 'bg-[#18233c]');
                el.classList.remove('border-gray-800/80', 'bg-[#141b2d]');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                el.classList.remove('border-amber-500', 'ring-2', 'ring-amber-500/30', 'bg-[#18233c]');
                el.classList.add('border-gray-800/80', 'bg-[#141b2d]');
            }
        });

        document.querySelectorAll('.step-thumb-card').forEach((el, i) => {
            if (i === index) {
                el.classList.add('border-amber-500', 'ring-2', 'ring-amber-500/40', 'scale-105');
                el.classList.remove('border-gray-800');
            } else {
                el.classList.remove('border-amber-500', 'ring-2', 'ring-amber-500/40', 'scale-105');
                el.classList.add('border-gray-800');
            }
        });

        const activeStep = stepItems[index] || {};
        const stepNum = activeStep.step_number || (index + 1);
        document.getElementById('active-step-label').innerText = `Step #${String(stepNum).padStart(2, '0')} Preview`;

        // Load base watermarked frame image
        const frame = selectedFrames[index];
        const imageUrl = `{{ url('/project') }}/${projectSlug}/frame/${frame.filename}/watermarked`;

        document.getElementById('canvas-loader').classList.remove('hidden');

        currentBaseImage = new Image();
        currentBaseImage.crossOrigin = 'anonymous';
        currentBaseImage.src = imageUrl;
        currentBaseImage.onload = () => {
            document.getElementById('canvas-loader').classList.add('hidden');
            renderLiveCanvas();
        };
        currentBaseImage.onerror = () => {
            document.getElementById('canvas-loader').classList.add('hidden');
            // Fallback to cropped frame if watermark frame is generating
            const fallbackUrl = `{{ url('/project') }}/${projectSlug}/frame/${frame.filename}/cropped`;
            currentBaseImage.src = fallbackUrl;
        };
    }

    // --- Inputs Change Handlers ---
    function onStepTextChange(idx) {
        if (!stepItems[idx]) return;
        stepItems[idx].title = (document.getElementById(`step-title-${idx}`).value || '').trim();
        stepItems[idx].description = (document.getElementById(`step-desc-${idx}`).value || '').trim();
        stepItems[idx].ingredients = (document.getElementById(`step-ing-${idx}`).value || '').trim();

        if (idx === activeIndex) {
            renderLiveCanvas();
        }
    }

    function onStepEnabledChange(idx) {
        if (!stepItems[idx]) return;
        stepItems[idx].enabled = document.getElementById(`step-enabled-${idx}`).checked;
        if (idx === activeIndex) {
            renderLiveCanvas();
        }
    }

    function setAllStepsEnabled(enabled) {
        stepItems.forEach((item, idx) => {
            item.enabled = enabled;
            const checkbox = document.getElementById(`step-enabled-${idx}`);
            if (checkbox) {
                checkbox.checked = enabled;
            }
        });
        renderLiveCanvas();
        showToast(enabled ? 'All steps enabled!' : 'All steps disabled!');
    }

    // --- Global Style Controls ---
    function setLayout(layoutKey, redraw = true) {
        styleConfig.layout = layoutKey;

        document.querySelectorAll('.layout-btn').forEach(btn => {
            btn.className = 'layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-sm bg-gray-800/80 hover:bg-gray-750 text-gray-300 border-gray-700/80';
        });

        const activeBtn = document.getElementById(`layout-btn-${layoutKey}`);
        if (activeBtn) {
            activeBtn.className = 'layout-btn p-3 rounded-2xl border transition text-left flex flex-col gap-1.5 shadow-md bg-amber-600/90 text-white border-amber-500 shadow-amber-900/30';
        }

        const badge = document.getElementById('active-layout-badge');
        if (badge) {
            const labels = {
                'bottom-banner': 'Bottom Banner',
                'lower-third': 'Floating Pill',
                'top-banner': 'Top Banner',
                'badge-only': 'Badge Only'
            };
            badge.innerText = labels[layoutKey] || 'Custom';
        }

        if (redraw) renderLiveCanvas();
    }

    function setFontSize(size, redraw = true) {
        styleConfig.font_size = size;

        document.querySelectorAll('.font-size-btn').forEach(btn => {
            btn.className = 'font-size-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition bg-gray-800 hover:bg-gray-700 text-gray-300 border-gray-700';
        });

        const activeBtn = document.getElementById(`font-size-${size}`);
        if (activeBtn) {
            activeBtn.className = 'font-size-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition bg-amber-600 text-white border-amber-500 shadow';
        }

        if (redraw) renderLiveCanvas();
    }

    function setTextColor(hex, redraw = true) {
        styleConfig.text_color = hex;
        const picker = document.getElementById('text-color-picker');
        if (picker) picker.value = hex;
        if (redraw) renderLiveCanvas();
    }

    function setBadgeColor(hex, redraw = true) {
        styleConfig.badge_color = hex;
        const picker = document.getElementById('badge-color-picker');
        if (picker) picker.value = hex;
        if (redraw) renderLiveCanvas();
    }

    function updateLive() {
        const opVal = parseInt(document.getElementById('bg-opacity-slider').value) || 85;
        styleConfig.bg_opacity = opVal;
        document.getElementById('opacity-val').innerText = `${opVal}%`;

        const badgeCheck = document.getElementById('show-badge-checkbox');
        styleConfig.show_badge = badgeCheck ? badgeCheck.checked : true;

        renderLiveCanvas();
    }

    // --- Live Canvas Renderer ---
    function renderLiveCanvas() {
        if (!currentBaseImage.complete || currentBaseImage.naturalWidth === 0) return;

        const w = currentBaseImage.naturalWidth;
        const h = currentBaseImage.naturalHeight;

        canvas.width = w;
        canvas.height = h;
        document.getElementById('canvas-dim').innerText = `${w} × ${h} px`;

        ctx.clearRect(0, 0, w, h);
        ctx.drawImage(currentBaseImage, 0, 0, w, h);

        const currentStep = stepItems[activeIndex] || {};
        if (currentStep.enabled === false) {
            return; // Disabled, show raw watermarked frame
        }

        const layout = styleConfig.layout || 'bottom-banner';
        const textColor = styleConfig.text_color || '#ffffff';
        const badgeColor = styleConfig.badge_color || '#f59e0b';
        const badgeTextColor = styleConfig.badge_text_color || '#ffffff';
        const bgOpacity = (styleConfig.bg_opacity ?? 85) / 100;
        const showBadge = styleConfig.show_badge !== false;
        const stepNum = currentStep.step_number || (activeIndex + 1);
        const title = currentStep.title || `Step ${stepNum}`;
        const description = currentStep.description || '';
        const ingredients = currentStep.ingredients || '';

        // Scale factor relative to 1080px standard width
        const scale = w / 1080;
        const fontSizeScale = styleConfig.font_size === 'small' ? 0.85 : (styleConfig.font_size === 'large' ? 1.2 : 1.0);

        const titleSize = Math.max(20, Math.round(36 * scale * fontSizeScale));
        const descSize = Math.max(16, Math.round(26 * scale * fontSizeScale));
        const ingSize = Math.max(14, Math.round(22 * scale * fontSizeScale));
        const badgeSize = Math.max(16, Math.round(24 * scale * fontSizeScale));

        ctx.save();

        if (layout === 'badge-only') {
            // Render minimal #01 pill on top-left
            if (showBadge) {
                const badgeText = `#${String(stepNum).padStart(2, '0')}`;
                ctx.font = `bold ${badgeSize}px sans-serif`;
                const badgeMetrics = ctx.measureText(badgeText);
                const pillW = badgeMetrics.width + (30 * scale);
                const pillH = badgeSize + (20 * scale);
                const posX = 40 * scale;
                const posY = 40 * scale;

                ctx.fillStyle = badgeColor;
                roundRect(ctx, posX, posY, pillW, pillH, 12 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, posX + (pillW / 2), posY + (pillH / 2));
            }
        } else if (layout === 'top-banner') {
            const bannerH = Math.round(h * 0.22);
            // Draw top gradient
            const grad = ctx.createLinearGradient(0, 0, 0, bannerH);
            grad.addColorStop(0, `rgba(15, 23, 42, ${bgOpacity})`);
            grad.addColorStop(0.85, `rgba(15, 23, 42, ${bgOpacity * 0.8})`);
            grad.addColorStop(1, 'rgba(15, 23, 42, 0)');

            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, w, bannerH);

            let curY = 35 * scale;
            let startX = 40 * scale;

            if (showBadge) {
                const badgeText = `#${String(stepNum).padStart(2, '0')}`;
                ctx.font = `bold ${badgeSize}px sans-serif`;
                const badgeMetrics = ctx.measureText(badgeText);
                const pillW = badgeMetrics.width + (24 * scale);
                const pillH = badgeSize + (16 * scale);

                ctx.fillStyle = badgeColor;
                roundRect(ctx, startX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, startX + (pillW / 2), curY + (pillH / 2));

                startX += pillW + (16 * scale);
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px sans-serif`;
            ctx.fillText(title, startX, curY - (2 * scale));

            if (description) {
                curY += titleSize + (18 * scale);
                ctx.font = `${descSize}px sans-serif`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                wrapText(ctx, description, 40 * scale, curY, w - (80 * scale), descSize * 1.35);
            }
        } else if (layout === 'lower-third') {
            const cardW = w - (80 * scale);
            const cardH = Math.round(h * 0.22);
            const cardX = 40 * scale;
            const cardY = h - cardH - (40 * scale);

            // Floating pill background
            ctx.fillStyle = `rgba(15, 23, 42, ${bgOpacity})`;
            roundRect(ctx, cardX, cardY, cardW, cardH, 24 * scale);
            ctx.fill();

            // Contrast subtle border
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
            ctx.lineWidth = 1.5 * scale;
            ctx.stroke();

            let curX = cardX + (30 * scale);
            let curY = cardY + (30 * scale);

            if (showBadge) {
                const badgeText = `#${String(stepNum).padStart(2, '0')}`;
                ctx.font = `bold ${badgeSize}px sans-serif`;
                const badgeMetrics = ctx.measureText(badgeText);
                const pillW = badgeMetrics.width + (24 * scale);
                const pillH = badgeSize + (16 * scale);

                ctx.fillStyle = badgeColor;
                roundRect(ctx, curX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, curX + (pillW / 2), curY + (pillH / 2));

                curX += pillW + (16 * scale);
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px sans-serif`;
            ctx.fillText(title, curX, curY);

            curY += titleSize + (16 * scale);

            if (description) {
                ctx.font = `${descSize}px sans-serif`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                wrapText(ctx, description, cardX + (30 * scale), curY, cardW - (60 * scale), descSize * 1.35);
            }
        } else {
            // Default: bottom-banner
            const bannerH = Math.round(h * 0.28);
            const startY = h - bannerH;

            // Gradient fading from transparent to dark slate
            const grad = ctx.createLinearGradient(0, startY, 0, h);
            grad.addColorStop(0, 'rgba(15, 23, 42, 0)');
            grad.addColorStop(0.25, `rgba(15, 23, 42, ${bgOpacity * 0.7})`);
            grad.addColorStop(0.7, `rgba(15, 23, 42, ${bgOpacity})`);
            grad.addColorStop(1, `rgba(15, 23, 42, ${bgOpacity})`);

            ctx.fillStyle = grad;
            ctx.fillRect(0, startY, w, bannerH);

            let curY = startY + (bannerH * 0.28);
            let curX = 40 * scale;

            if (showBadge) {
                const badgeText = `#${String(stepNum).padStart(2, '0')}`;
                ctx.font = `bold ${badgeSize}px sans-serif`;
                const badgeMetrics = ctx.measureText(badgeText);
                const pillW = badgeMetrics.width + (24 * scale);
                const pillH = badgeSize + (16 * scale);

                ctx.fillStyle = badgeColor;
                roundRect(ctx, curX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, curX + (pillW / 2), curY + (pillH / 2));

                curX += pillW + (16 * scale);
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px sans-serif`;
            ctx.fillText(title, curX, curY - (2 * scale));

            curY += titleSize + (16 * scale);

            if (description) {
                ctx.font = `${descSize}px sans-serif`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                const linesDrawn = wrapText(ctx, description, 40 * scale, curY, w - (80 * scale), descSize * 1.35);
                curY += (linesDrawn * descSize * 1.35) + (10 * scale);
            }

            if (ingredients) {
                ctx.font = `italic ${ingSize}px sans-serif`;
                ctx.fillStyle = 'rgba(253, 224, 71, 0.95)'; // Warm yellow highlight
                ctx.fillText(`🧂 ${ingredients}`, 40 * scale, curY);
            }
        }

        ctx.restore();
    }

    // Helper: Wrap text in Canvas 2D
    function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
        const words = text.split(' ');
        let line = '';
        let lineCount = 0;

        for (let n = 0; n < words.length; n++) {
            const testLine = line + words[n] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            if (testWidth > maxWidth && n > 0) {
                ctx.fillText(line, x, y);
                line = words[n] + ' ';
                y += lineHeight;
                lineCount++;
                if (lineCount >= 3) {
                    ctx.fillText(line.trim() + '...', x, y);
                    return lineCount + 1;
                }
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, y);
        return lineCount + 1;
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

    // --- Reset ---
    function resetAllSteps() {
        if (!confirm('Reset all steps back to default titles and layout?')) return;

        styleConfig = {
            layout: 'bottom-banner',
            bg_color: '#0f172a',
            bg_opacity: 85,
            text_color: '#ffffff',
            badge_color: '#f59e0b',
            badge_text_color: '#ffffff',
            show_badge: true,
            font_size: 'medium'
        };

        stepItems = selectedFrames.map((f, i) => ({
            frame_id: f.filename,
            step_number: i + 1,
            title: `Step ${i + 1}`,
            description: '',
            ingredients: '',
            enabled: true
        }));

        stepItems.forEach((item, idx) => {
            const titleEl = document.getElementById(`step-title-${idx}`);
            const descEl = document.getElementById(`step-desc-${idx}`);
            const ingEl = document.getElementById(`step-ing-${idx}`);
            const enEl = document.getElementById(`step-enabled-${idx}`);
            if (titleEl) titleEl.value = item.title;
            if (descEl) descEl.value = '';
            if (ingEl) ingEl.value = '';
            if (enEl) enEl.checked = true;
        });

        setLayout('bottom-banner', false);
        setFontSize('medium', false);
        setTextColor('#ffffff', false);
        setBadgeColor('#f59e0b', false);

        if (document.getElementById('show-badge-checkbox')) {
            document.getElementById('show-badge-checkbox').checked = true;
        }
        if (document.getElementById('bg-opacity-slider')) {
            document.getElementById('bg-opacity-slider').value = 85;
            document.getElementById('opacity-val').innerText = '85%';
        }

        renderLiveCanvas();
        showToast('Default steps and layout restored!');
    }

    // --- Save Steps ---
    function saveSteps(proceedAfter = false, silent = false) {
        const btn = document.getElementById('save-steps-btn');
        const text = document.getElementById('save-steps-text');
        if (!silent && btn && text) {
            btn.disabled = true;
            text.innerText = 'Saving...';
        }

        // Collect latest data
        stepItems.forEach((item, idx) => {
            const titleEl = document.getElementById(`step-title-${idx}`);
            const descEl = document.getElementById(`step-desc-${idx}`);
            const ingEl = document.getElementById(`step-ing-${idx}`);
            const enEl = document.getElementById(`step-enabled-${idx}`);

            if (titleEl) item.title = titleEl.value.trim();
            if (descEl) item.description = descEl.value.trim();
            if (ingEl) item.ingredients = ingEl.value.trim();
            if (enEl) item.enabled = enEl.checked;
        });

        const payload = {
            _token: csrfToken,
            style: styleConfig,
            items: stepItems
        };

        fetch("{{ route('project.steps.save', $project['slug']) }}", {
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
                text.innerText = 'Save Steps';
            }

            if (data.success) {
                if (proceedAfter) {
                    window.location.href = "{{ route('project.export', $project['slug']) }}";
                } else if (!silent) {
                    showToast('Recipe steps saved successfully!');
                }
            } else {
                alert('Could not save steps: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            if (!silent && btn && text) {
                btn.disabled = false;
                text.innerText = 'Save Steps';
            }
            alert('Error saving recipe steps: ' + err.message);
        });
    }

    function saveAndProceed() {
        saveSteps(true);
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
