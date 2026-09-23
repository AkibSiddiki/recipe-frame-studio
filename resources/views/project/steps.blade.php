@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'recipe'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.watermark', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> <span>Watermark & Branding</span>
                </a>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Step 5: Recipe Steps & Text Overlays</span>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-gray-500">Step 6: Export & Collage</span>
            </div>
            <h1 class="text-3xl font-display font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Recipe Steps' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Add step numbers, culinary instructions, and ingredient notes over your candidate frames.</span>
                <span class="text-gray-600">·</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} frames selected</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openPasteJsonModal()" id="open-json-modal-btn" class="px-3.5 py-2 bg-gradient-to-r from-amber-500/20 to-orange-500/20 hover:from-amber-500/30 hover:to-orange-500/30 text-amber-300 border border-amber-500/40 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-amber-400 active:scale-95">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Paste JSON Captions</span>
            </button>

            <button type="button" onclick="applyLastStepsStyle()" class="px-3.5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60" title="Apply last saved recipe card styling">
                <span>✨</span>
                <span>Use Last Style</span>
            </button>

            <button type="button" onclick="resetAllSteps()" class="px-3.5 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Reset Defaults</span>
            </button>

            <button type="button" onclick="saveSteps(false)" id="save-steps-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                <span id="save-steps-text">Save Steps</span>
            </button>

            <button type="button" onclick="saveAndProceed()" id="proceed-btn" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-amber-900/30 flex items-center gap-2 active:scale-95">
                <span>Next: Export & Collage (Step 6)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
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
            <h2 class="text-xl font-bold text-white mb-2">No Frames Selected</h2>
            <p class="text-gray-400 text-sm mb-6">You need to select at least one video frame to build recipe steps and text overlays.</p>
            <a href="{{ route('project.frames', $project['slug']) }}" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg shadow-amber-900/40">
                <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> <span>Return to Frames Selector</span>
            </a>
        </div>
    @else
        <!-- Main Studio Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Left Panel: Global Styling & Step Content Cards (5 cols) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- 1. Global Styling & Overlay Theme -->
                <div class="glass-surface rounded-3xl p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-border-subtle">
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
                    <div class="grid grid-cols-2 gap-4 pt-3 border-t border-border-subtle text-xs">
                        <!-- Background Opacity -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-gray-400 font-bold uppercase tracking-wider text-[11px]">Banner Opacity</label>
                                <div class="flex items-center gap-1 font-mono text-[10px]">
                                    <button type="button" onclick="setOpacity(0)" class="px-1.5 py-0.5 rounded bg-surface-base border border-border-subtle text-gray-400 hover:text-amber-400 hover:border-amber-500/50 transition" title="0% (Transparent)">0%</button>
                                    <button type="button" onclick="setOpacity(50)" class="px-1.5 py-0.5 rounded bg-surface-base border border-border-subtle text-gray-400 hover:text-amber-400 hover:border-amber-500/50 transition" title="50%">50%</button>
                                    <button type="button" onclick="setOpacity(85)" class="px-1.5 py-0.5 rounded bg-surface-base border border-border-subtle text-gray-400 hover:text-amber-400 hover:border-amber-500/50 transition" title="85% (Default)">85%</button>
                                    <button type="button" onclick="setOpacity(100)" class="px-1.5 py-0.5 rounded bg-surface-base border border-border-subtle text-gray-400 hover:text-amber-400 hover:border-amber-500/50 transition" title="100% (Solid)">100%</button>
                                    <span class="text-gray-600">|</span>
                                    <span id="opacity-val" class="font-mono text-amber-400 font-bold ml-0.5">{{ $recipeSteps['style']['bg_opacity'] ?? 85 }}%</span>
                                </div>
                            </div>
                            <input type="range" id="bg-opacity-slider" min="0" max="100" value="{{ $recipeSteps['style']['bg_opacity'] ?? 85 }}" oninput="updateLive()" class="w-full accent-amber-500 bg-gray-800 rounded-lg cursor-pointer h-2">
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

                        <!-- Headline Alignment -->
                        <div>
                            <label class="block text-gray-400 font-bold uppercase tracking-wider text-[11px] mb-1">Headline Alignment</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button type="button" onclick="setTextAlign('left')" id="text-align-left" class="text-align-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"></path></svg>
                                    <span>Left</span>
                                </button>
                                <button type="button" onclick="setTextAlign('center')" id="text-align-center" class="text-align-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14"></path></svg>
                                    <span>Center</span>
                                </button>
                                <button type="button" onclick="setTextAlign('right')" id="text-align-right" class="text-align-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"></path></svg>
                                    <span>Right</span>
                                </button>
                            </div>
                        </div>

                        <!-- Text & Badge Colors -->
                        <div>
                            <label class="block text-gray-400 font-bold uppercase tracking-wider text-[11px] mb-1.5">Text & Accent Color</label>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1" title="Headline text color">
                                    <button type="button" onclick="setTextColor('#ffffff')" class="w-5 h-5 rounded-full bg-white border border-gray-600 hover:scale-110 transition shadow" title="Pure White"></button>
                                    <button type="button" onclick="setTextColor('#fde047')" class="w-5 h-5 rounded-full bg-yellow-300 border border-gray-600 hover:scale-110 transition shadow" title="Warm Yellow"></button>
                                    <input type="color" id="text-color-picker" value="{{ $recipeSteps['style']['text_color'] ?? '#ffffff' }}" onchange="setTextColor(this.value)" class="w-5 h-5 rounded cursor-pointer bg-transparent border-0 p-0">
                                </div>
                                <span class="text-gray-600">|</span>
                                <div class="flex items-center gap-1" title="Badge accent color">
                                    <button type="button" onclick="setBadgeColor('#f59e0b')" class="w-5 h-5 rounded-full bg-amber-500 border border-gray-600 hover:scale-110 transition shadow" title="Amber"></button>
                                    <button type="button" onclick="setBadgeColor('#ef4444')" class="w-5 h-5 rounded-full bg-red-500 border border-gray-600 hover:scale-110 transition shadow" title="Crimson"></button>
                                    <input type="color" id="badge-color-picker" value="{{ $recipeSteps['style']['badge_color'] ?? '#f59e0b' }}" onchange="setBadgeColor(this.value)" class="w-5 h-5 rounded cursor-pointer bg-transparent border-0 p-0">
                                </div>
                            </div>
                        </div>

                        <!-- Title / Headline Padding -->
                        <div class="col-span-2 pt-2 border-t border-border-subtle">
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-gray-400 font-bold uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>Step Title / Headline Padding</span>
                                    <span class="text-gray-500 font-normal text-[10px]">(Canvas & Overlay spacing)</span>
                                </label>
                                <span id="title-padding-val" class="font-mono text-amber-400 font-bold text-xs">{{ $recipeSteps['style']['title_padding'] ?? 30 }}px</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="range" id="title-padding-slider" min="10" max="80" value="{{ $recipeSteps['style']['title_padding'] ?? 30 }}" oninput="updateTitlePadding(this.value)" class="w-full accent-amber-500 bg-gray-800 rounded-lg cursor-pointer h-2">
                                <div class="flex items-center gap-1 shrink-0">
                                    <input type="number" id="title-padding-input" min="5" max="150" value="{{ $recipeSteps['style']['title_padding'] ?? 30 }}" oninput="updateTitlePadding(this.value)" class="w-16 bg-gray-800 border border-gray-700 rounded-xl px-2 py-1 text-center font-mono text-xs text-amber-400 focus:outline-none focus:border-amber-500">
                                    <span class="text-[10px] text-gray-400">px</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="mt-4 pt-3 border-t border-border-subtle flex flex-wrap items-center justify-between gap-3">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-300">
                            <input type="checkbox" id="show-badge-checkbox" checked onchange="updateLive()" class="rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-4 h-4">
                            <span>Display step badge (<b>#01</b>)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-300" title="Adds a soft, deep drop shadow behind headline text">
                            <input type="checkbox" id="has-shadow-checkbox" {{ ($recipeSteps['style']['has_shadow'] ?? true) ? 'checked' : '' }} onchange="updateLive()" class="rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-4 h-4">
                            <span class="flex items-center gap-1">
                                <span>Drop shadow effect</span>
                                <span class="text-amber-400 text-[10px] font-mono">✨</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- 2. Step Items Editor (Accordion / Stack) -->
                <div class="glass-surface rounded-3xl p-6 shadow-2xl flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-3 border-b border-border-subtle gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📝</span>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Step Instructions</h2>
                            <span class="text-xs text-gray-500 font-mono">({{ count($recipeSteps['items'] ?? []) }})</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs">
                            <button type="button" onclick="openPasteJsonModal()" class="px-2 py-1 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-semibold transition flex items-center gap-1 active:scale-95 shadow-sm" title="Paste JSON formatted captions">
                                <span>📋</span>
                                <span>Paste JSON</span>
                            </button>
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
                            <div class="step-card bg-[#141b2d] border border-border-default rounded-2xl p-4 transition-all duration-200" 
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
                                    <!-- Title Input & Padding -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Step Title / Headline</label>
                                            <div class="flex items-center gap-1.5" title="Padding for this step title/banner (leave blank to use global padding)">
                                                <span class="text-[10px] text-gray-400 font-medium">Padding:</span>
                                                <input type="number" id="step-padding-{{ $idx }}" value="{{ $step['padding'] ?? '' }}" min="5" max="150" placeholder="{{ $recipeSteps['style']['title_padding'] ?? 30 }}" oninput="onStepPaddingChange({{ $idx }})" class="w-14 bg-[#0d1322] border border-gray-700 rounded-lg px-1.5 py-0.5 text-amber-300 placeholder-gray-600 focus:outline-none focus:border-amber-500 text-[11px] text-center font-mono">
                                                <span class="text-[10px] text-gray-500">px</span>
                                            </div>
                                        </div>
                                        <input type="text" id="step-title-{{ $idx }}" value="{{ $step['title'] ?? '' }}" oninput="onStepTextChange({{ $idx }})" placeholder="e.g. Sear the Chicken Thighs" style="font-family: 'Li Alinur Mayaboti', 'Hind Siliguri', sans-serif; text-align: {{ $step['text_align'] ?? ($recipeSteps['style']['text_align'] ?? 'left') }};" class="w-full bg-[#0d1322] border border-gray-700 rounded-xl px-3 py-1.5 text-gray-100 placeholder-gray-600 focus:outline-none focus:border-amber-500 text-xs">
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
                <div class="glass-surface rounded-3xl p-6 shadow-2xl flex flex-col items-center justify-between min-h-[600px] relative">
                    <!-- Preview Top Bar -->
                    <div class="w-full flex items-center justify-between pb-3 mb-3 border-b border-border-default text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-white text-sm" id="active-step-label">Step #01 Preview</span>
                            <span id="step-counter-badge" class="px-2 py-0.5 rounded-lg bg-gray-800 text-amber-400 border border-gray-700 font-mono text-[11px] font-semibold">1 / {{ count($selectedFrames) }}</span>
                            
                            <!-- Top bar compact arrows -->
                            <div class="flex items-center gap-1">
                                <button type="button" 
                                        id="top-prev-frame-btn" 
                                        onclick="prevStep()" 
                                        class="w-7 h-7 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 border border-gray-700 flex items-center justify-center transition active:scale-95" 
                                        title="Previous Frame (Left Arrow)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button type="button" 
                                        id="top-next-frame-btn" 
                                        onclick="nextStep()" 
                                        class="w-7 h-7 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 border border-gray-700 flex items-center justify-center transition active:scale-95" 
                                        title="Next Frame (Right Arrow)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>

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
                    <div class="relative w-full flex-1 flex items-center justify-center p-2 rounded-2xl bg-[#0b0f19] border border-border-subtle overflow-hidden shadow-inner group">
                        <!-- Left Navigation Arrow Button -->
                        <button type="button" 
                                id="prev-frame-btn" 
                                onclick="prevStep()" 
                                aria-label="Previous step frame"
                                title="Previous Frame (Left Arrow key)" 
                                class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-11 h-11 md:w-12 md:h-12 rounded-full bg-gray-950/70 hover:bg-gray-950/95 text-white hover:text-amber-400 border border-white/20 hover:border-amber-400/60 shadow-2xl backdrop-blur-md flex items-center justify-center transition-all duration-200 active:scale-90 group/btn focus:outline-none focus:ring-2 focus:ring-amber-500 hover:shadow-amber-500/20">
                            <svg class="w-6 h-6 transition-transform duration-200 group-hover/btn:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <canvas id="step-canvas" class="max-w-full max-h-[520px] object-contain rounded-xl shadow-2xl transition duration-200"></canvas>

                        <!-- Right Navigation Arrow Button -->
                        <button type="button" 
                                id="next-frame-btn" 
                                onclick="nextStep()" 
                                aria-label="Next step frame"
                                title="Next Frame (Right Arrow key)" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-11 h-11 md:w-12 md:h-12 rounded-full bg-gray-950/70 hover:bg-gray-950/95 text-white hover:text-amber-400 border border-white/20 hover:border-amber-400/60 shadow-2xl backdrop-blur-md flex items-center justify-center transition-all duration-200 active:scale-90 group/btn focus:outline-none focus:ring-2 focus:ring-amber-500 hover:shadow-amber-500/20">
                            <svg class="w-6 h-6 transition-transform duration-200 group-hover/btn:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        
                        <!-- Loading Indicator -->
                        <div id="canvas-loader" class="absolute inset-0 bg-[#0b0f19]/80 backdrop-blur-sm flex flex-col items-center justify-center text-amber-500 gap-2 hidden z-30">
                            <svg class="animate-spin h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs font-semibold text-gray-300">Rendering composite frame...</span>
                        </div>
                    </div>

                    <!-- Bottom Frame Carousel Thumbnails -->
                    <div class="w-full mt-4 pt-3 border-t border-border-default">
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
                                    <div class="p-1 bg-surface-base text-[9px] font-semibold text-center text-gray-300 truncate">
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

    <!-- Paste JSON Captions Modal -->
    <div id="paste-json-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md hidden transition-opacity duration-300">
        <div class="bg-[#0f172a] border border-amber-500/30 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] animate-scale-in">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-border-default flex items-center justify-between bg-surface-raised">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center font-bold text-lg shadow-sm">
                        📋
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                            <span>Paste JSON Image Captions</span>
                            <span class="text-[11px] font-mono px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/30">Top Steps Overlay</span>
                        </h3>
                        <p class="text-xs text-gray-400">Paste your structured recipe captions. They will automatically be placed at the top of candidate images as steps.</p>
                    </div>
                </div>
                <button type="button" onclick="closePasteJsonModal()" class="w-8 h-8 rounded-xl bg-gray-800 hover:bg-gray-750 text-gray-400 hover:text-white flex items-center justify-center transition border border-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1 flex flex-col gap-4">
                <!-- Helper / Quick Actions Bar -->
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2 text-gray-400">
                        <span>Format: <code class="font-mono text-amber-300 bg-gray-900 px-1.5 py-0.5 rounded text-[11px]">{ "recipe": "...", "steps": [{"image": 1, "caption": "..."}] }</code></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="loadSampleJson()" class="text-amber-400 hover:text-amber-300 underline font-medium text-xs">Load Sample</button>
                        <span class="text-gray-600">·</span>
                        <button type="button" onclick="clearJsonInput()" class="text-gray-400 hover:text-gray-200 text-xs">Clear</button>
                    </div>
                </div>

                <!-- Textarea -->
                <div class="relative">
                    <textarea id="json-caption-input" rows="11" oninput="onJsonInputChange()" placeholder='{&#10;  "recipe": "নরম তুলতুলে পাউরুটি",&#10;  "steps": [&#10;    { "image": 1, "caption": "তৈরি নরম তুলতুলে পাউরুটি" },&#10;    { "image": 2, "caption": "মচমচে করে স্লাইস কাটা" }&#10;  ]&#10;}' class="w-full bg-[#080d1a] border border-gray-700/90 rounded-2xl p-4 font-mono text-xs text-amber-100 placeholder-gray-600 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 leading-relaxed resize-none shadow-inner"></textarea>
                </div>

                <!-- Live Status / Detection Feedback -->
                <div id="json-status-container" class="rounded-2xl p-3 bg-gray-900/80 border border-gray-800 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2" id="json-status-message">
                        <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                        <span class="text-gray-400">Ready to paste JSON recipe captions</span>
                    </div>
                    <div id="json-meta-badges" class="flex items-center gap-2 hidden">
                        <span id="detected-recipe-badge" class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 font-medium text-[11px] truncate max-w-[200px]"></span>
                        <span id="detected-steps-badge" class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono font-semibold text-[11px]"></span>
                    </div>
                </div>

                <!-- Options Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 text-xs">
                    <!-- Option 1: Top Banner Placement -->
                    <label class="flex items-start gap-2.5 p-3 rounded-2xl bg-gray-900/60 border border-border-default cursor-pointer hover:border-amber-500/40 transition">
                        <input type="checkbox" id="json-opt-top-banner" checked class="mt-0.5 rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-4 h-4 shrink-0">
                        <div>
                            <span class="font-bold text-white block">Place on Images Top (Top Banner)</span>
                            <span class="text-gray-400 text-[11px] leading-tight block">Automatically switches layout to Top Banner so captions appear at the top of frames as steps.</span>
                        </div>
                    </label>

                    <!-- Option 2: Update Recipe Name -->
                    <label class="flex items-start gap-2.5 p-3 rounded-2xl bg-gray-900/60 border border-border-default cursor-pointer hover:border-amber-500/40 transition">
                        <input type="checkbox" id="json-opt-update-name" checked class="mt-0.5 rounded bg-gray-800 border-gray-700 text-amber-500 focus:ring-amber-500 w-4 h-4 shrink-0">
                        <div>
                            <span class="font-bold text-white block">Update Recipe Title</span>
                            <span class="text-gray-400 text-[11px] leading-tight block">Syncs project/recipe title if JSON includes a <code>recipe</code> attribute.</span>
                        </div>
                    </label>
                </div>

                <!-- Target Field Selection -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-900/60 border border-border-default text-xs">
                    <span class="font-semibold text-gray-300">Apply caption field to:</span>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 cursor-pointer text-gray-300">
                            <input type="radio" name="json-target-field" value="title" checked class="text-amber-500 focus:ring-amber-500 bg-gray-800 border-gray-700">
                            <span>Step Title (Banner Headline)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-gray-400 hover:text-gray-300">
                            <input type="radio" name="json-target-field" value="both" class="text-amber-500 focus:ring-amber-500 bg-gray-800 border-gray-700">
                            <span>Both (Title & Description)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-border-default bg-surface-raised flex items-center justify-between gap-3">
                <button type="button" onclick="closePasteJsonModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-750 transition border border-gray-700">
                    Cancel
                </button>
                <button type="button" onclick="applyJsonCaptions()" id="apply-json-btn" disabled class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 disabled:opacity-40 disabled:cursor-not-allowed text-white px-6 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-amber-900/30 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span id="apply-json-btn-text">Apply Captions & Place on Images</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectSlug = @json($project['slug']);
    const selectedFrames = @json($selectedFrames);
    const initialStepsData = @json($recipeSteps);
    let currentRecipeName = @json($project['name'] ?? '');

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
        has_shadow: initialStepsData.style?.has_shadow ?? true,
        font_size: 'medium',
        title_padding: initialStepsData.style?.title_padding ?? 30,
        text_align: initialStepsData.style?.text_align || 'left'
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
        setTextAlign(styleConfig.text_align || 'left', false);
        setTextColor(styleConfig.text_color || '#ffffff', false);
        setBadgeColor(styleConfig.badge_color || '#f59e0b', false);

        if (document.getElementById('show-badge-checkbox')) {
            document.getElementById('show-badge-checkbox').checked = styleConfig.show_badge !== false;
        }
        if (document.getElementById('has-shadow-checkbox')) {
            document.getElementById('has-shadow-checkbox').checked = styleConfig.has_shadow !== false;
        }
        if (document.getElementById('bg-opacity-slider')) {
            const initialOp = (styleConfig.bg_opacity !== undefined && styleConfig.bg_opacity !== null) ? parseInt(styleConfig.bg_opacity, 10) : 85;
            document.getElementById('bg-opacity-slider').value = initialOp;
            document.getElementById('opacity-val').innerText = `${initialOp}%`;
        }
        if (document.getElementById('title-padding-slider')) {
            const padVal = styleConfig.title_padding ?? 30;
            document.getElementById('title-padding-slider').value = padVal;
            if (document.getElementById('title-padding-input')) {
                document.getElementById('title-padding-input').value = padVal;
            }
            if (document.getElementById('title-padding-val')) {
                document.getElementById('title-padding-val').innerText = `${padVal}px`;
            }
        }

        // Preload and refresh canvas with Li Alinur Mayaboti font
        if (document.fonts) {
            document.fonts.load("bold 24px 'Li Alinur Mayaboti'").then(() => {
                renderLiveCanvas();
            });
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
                el.classList.remove('border-border-default', 'bg-[#141b2d]');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                el.classList.remove('border-amber-500', 'ring-2', 'ring-amber-500/30', 'bg-[#18233c]');
                el.classList.add('border-border-default', 'bg-[#141b2d]');
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

        const counterBadge = document.getElementById('step-counter-badge');
        if (counterBadge) {
            counterBadge.innerText = `${index + 1} / ${selectedFrames.length}`;
        }

        const activeThumb = document.getElementById(`thumb-card-${index}`);
        if (activeThumb) {
            activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

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

    // --- Arrow Navigation (Prev / Next Frame) ---
    function prevStep() {
        if (!selectedFrames || selectedFrames.length <= 1) return;
        const newIndex = (activeIndex - 1 + selectedFrames.length) % selectedFrames.length;
        selectStep(newIndex);
    }

    function nextStep() {
        if (!selectedFrames || selectedFrames.length <= 1) return;
        const newIndex = (activeIndex + 1) % selectedFrames.length;
        selectStep(newIndex);
    }

    // Keyboard navigation (ArrowLeft / ArrowRight)
    window.addEventListener('keydown', (e) => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
        const modal = document.getElementById('paste-json-modal');
        if (modal && !modal.classList.contains('hidden')) return;

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevStep();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextStep();
        }
    });

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

    function onStepPaddingChange(idx) {
        if (!stepItems[idx]) return;
        const padInput = document.getElementById(`step-padding-${idx}`);
        const val = padInput ? padInput.value.trim() : '';
        stepItems[idx].padding = val !== '' ? parseInt(val, 10) : null;

        if (idx === activeIndex) {
            renderLiveCanvas();
        }
    }

    function updateTitlePadding(val) {
        const parsed = Math.max(5, Math.min(150, parseInt(val, 10) || 30));
        styleConfig.title_padding = parsed;
        const slider = document.getElementById('title-padding-slider');
        const input = document.getElementById('title-padding-input');
        const valLabel = document.getElementById('title-padding-val');
        if (slider) slider.value = parsed;
        if (input) input.value = parsed;
        if (valLabel) valLabel.innerText = `${parsed}px`;

        // Update placeholder in step cards that have no override
        stepItems.forEach((item, i) => {
            const stepPadEl = document.getElementById(`step-padding-${i}`);
            if (stepPadEl && !item.padding) {
                stepPadEl.placeholder = parsed;
            }
        });

        renderLiveCanvas();
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

    function setTextAlign(align, redraw = true) {
        styleConfig.text_align = align;

        document.querySelectorAll('.text-align-btn').forEach(btn => {
            btn.className = 'text-align-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition flex items-center justify-center gap-1 bg-gray-800 hover:bg-gray-700 text-gray-300 border-gray-700';
        });

        const activeBtn = document.getElementById(`text-align-${align}`);
        if (activeBtn) {
            activeBtn.className = 'text-align-btn py-1 rounded-lg border text-center font-semibold text-[11px] transition flex items-center justify-center gap-1 bg-amber-600 text-white border-amber-500 shadow';
        }

        // Also update text-align on all step title input elements
        stepItems.forEach((_, idx) => {
            const titleInput = document.getElementById(`step-title-${idx}`);
            if (titleInput) {
                titleInput.style.textAlign = align;
            }
        });

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

    function setOpacity(val) {
        const slider = document.getElementById('bg-opacity-slider');
        if (slider) {
            slider.value = val;
            updateLive();
        }
    }

    function updateLive() {
        const slider = document.getElementById('bg-opacity-slider');
        const rawVal = slider ? slider.value : '85';
        const opVal = (rawVal !== '' && !isNaN(rawVal)) ? Math.max(0, Math.min(100, parseInt(rawVal, 10))) : 85;
        styleConfig.bg_opacity = opVal;
        const opValEl = document.getElementById('opacity-val');
        if (opValEl) opValEl.innerText = `${opVal}%`;

        const badgeCheck = document.getElementById('show-badge-checkbox');
        styleConfig.show_badge = badgeCheck ? badgeCheck.checked : true;

        const shadowCheck = document.getElementById('has-shadow-checkbox');
        styleConfig.has_shadow = shadowCheck ? shadowCheck.checked : true;

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
        const hasShadow = styleConfig.has_shadow !== false;
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

        function applyTextShadow(isHeading = true) {
            if (hasShadow) {
                ctx.shadowColor = 'rgba(0, 0, 0, 0.95)';
                ctx.shadowBlur = Math.round((isHeading ? 10 : 6) * scale);
                ctx.shadowOffsetX = Math.round((isHeading ? 2 : 1) * scale);
                ctx.shadowOffsetY = Math.round((isHeading ? 3.5 : 2) * scale);
            } else {
                clearTextShadow();
            }
        }

        function clearTextShadow() {
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
        }

        clearTextShadow();

        const fontFallback = "'Li Alinur Mayaboti', 'Hind Siliguri', 'Noto Sans Bengali', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";

        const currentStepPadding = (currentStep.padding !== undefined && currentStep.padding !== null && currentStep.padding !== '')
            ? parseInt(currentStep.padding, 10)
            : (styleConfig.title_padding !== undefined && styleConfig.title_padding !== null ? parseInt(styleConfig.title_padding, 10) : 30);
        const pad = Math.max(8, currentStepPadding) * scale;
        const textAlign = currentStep.text_align || styleConfig.text_align || 'left';

        if (layout === 'badge-only') {
            // Render minimal #01 pill on top-left, center, or top-right
            if (showBadge) {
                const badgeText = `#${String(stepNum).padStart(2, '0')}`;
                ctx.font = `bold ${badgeSize}px ${fontFallback}`;
                const badgeMetrics = ctx.measureText(badgeText);
                const pillW = badgeMetrics.width + (30 * scale);
                const pillH = badgeSize + (20 * scale);
                let posX = pad;
                if (textAlign === 'center') {
                    posX = (w - pillW) / 2;
                } else if (textAlign === 'right') {
                    posX = w - pad - pillW;
                }
                const posY = pad;

                ctx.fillStyle = badgeColor;
                roundRect(ctx, posX, posY, pillW, pillH, 12 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, posX + (pillW / 2), posY + (pillH / 2));
            }
        } else if (layout === 'top-banner') {
            const bannerH = Math.max(Math.round(h * 0.18), Math.round(pad * 2 + (badgeSize * 1.6)));
            if (bgOpacity > 0) {
                // Draw top gradient
                const grad = ctx.createLinearGradient(0, 0, 0, bannerH);
                grad.addColorStop(0, `rgba(15, 23, 42, ${bgOpacity})`);
                grad.addColorStop(0.85, `rgba(15, 23, 42, ${bgOpacity * 0.8})`);
                grad.addColorStop(1, 'rgba(15, 23, 42, 0)');

                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, w, bannerH);
            }

            let curY = pad;

            const badgeText = `#${String(stepNum).padStart(2, '0')}`;
            ctx.font = `bold ${badgeSize}px ${fontFallback}`;
            const badgeMetrics = showBadge ? ctx.measureText(badgeText) : { width: 0 };
            const pillW = showBadge ? (badgeMetrics.width + (24 * scale)) : 0;
            const pillH = badgeSize + (16 * scale);
            const badgeGap = showBadge ? (16 * scale) : 0;

            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            const titleWidth = ctx.measureText(title).width;
            const totalHeaderW = pillW + badgeGap + titleWidth;

            let startX = pad;
            if (textAlign === 'center') {
                startX = Math.max(pad, (w - totalHeaderW) / 2);
            } else if (textAlign === 'right') {
                startX = Math.max(pad, w - pad - totalHeaderW);
            }

            if (showBadge) {
                clearTextShadow();
                ctx.fillStyle = badgeColor;
                roundRect(ctx, startX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, startX + (pillW / 2), curY + (pillH / 2));

                startX += pillW + badgeGap;
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            
            applyTextShadow(true);
            const maxTitleW = w - startX - pad;
            if (titleWidth > maxTitleW) {
                wrapText(ctx, title, startX, curY - (2 * scale), maxTitleW, titleSize * 1.25, textAlign);
            } else {
                ctx.fillText(title, startX, curY - (2 * scale));
            }
            clearTextShadow();

            if (description && description !== title) {
                curY += titleSize + (18 * scale);
                ctx.font = `${descSize}px ${fontFallback}`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                applyTextShadow(false);
                wrapText(ctx, description, pad, curY, w - (pad * 2), descSize * 1.35, textAlign);
                clearTextShadow();
            }
        } else if (layout === 'lower-third') {
            const cardW = w - (pad * 2);
            const cardH = Math.round(h * 0.22);
            const cardX = pad;
            const cardY = h - cardH - (pad * 0.8);

            if (bgOpacity > 0) {
                // Floating pill background
                ctx.fillStyle = `rgba(15, 23, 42, ${bgOpacity})`;
                roundRect(ctx, cardX, cardY, cardW, cardH, 24 * scale);
                ctx.fill();

                // Contrast subtle border
                ctx.strokeStyle = `rgba(255, 255, 255, ${Math.min(0.12, bgOpacity * 0.2)})`;
                ctx.lineWidth = 1.5 * scale;
                ctx.stroke();
            }

            const badgeText = `#${String(stepNum).padStart(2, '0')}`;
            ctx.font = `bold ${badgeSize}px ${fontFallback}`;
            const badgeMetrics = showBadge ? ctx.measureText(badgeText) : { width: 0 };
            const pillW = showBadge ? (badgeMetrics.width + (24 * scale)) : 0;
            const pillH = badgeSize + (16 * scale);
            const badgeGap = showBadge ? (16 * scale) : 0;

            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            const titleWidth = ctx.measureText(title).width;
            const totalHeaderW = pillW + badgeGap + titleWidth;

            let curX = cardX + (30 * scale);
            if (textAlign === 'center') {
                curX = Math.max(cardX + (20 * scale), cardX + (cardW - totalHeaderW) / 2);
            } else if (textAlign === 'right') {
                curX = Math.max(cardX + (20 * scale), cardX + cardW - (30 * scale) - totalHeaderW);
            }
            let curY = cardY + (30 * scale);

            if (showBadge) {
                clearTextShadow();
                ctx.fillStyle = badgeColor;
                roundRect(ctx, curX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, curX + (pillW / 2), curY + (pillH / 2));

                curX += pillW + badgeGap;
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            applyTextShadow(true);
            ctx.fillText(title, curX, curY);
            clearTextShadow();

            curY += titleSize + (16 * scale);

            if (description) {
                ctx.font = `${descSize}px ${fontFallback}`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                applyTextShadow(false);
                wrapText(ctx, description, cardX + (30 * scale), curY, cardW - (60 * scale), descSize * 1.35, textAlign);
                clearTextShadow();
            }
        } else {
            // Default: bottom-banner
            const bannerH = Math.round(h * 0.28);
            const startY = h - bannerH;

            if (bgOpacity > 0) {
                // Gradient fading from transparent to dark slate
                const grad = ctx.createLinearGradient(0, startY, 0, h);
                grad.addColorStop(0, 'rgba(15, 23, 42, 0)');
                grad.addColorStop(0.25, `rgba(15, 23, 42, ${bgOpacity * 0.7})`);
                grad.addColorStop(1, `rgba(15, 23, 42, ${bgOpacity})`);

                ctx.fillStyle = grad;
                ctx.fillRect(0, startY, w, bannerH);
            }

            const badgeText = `#${String(stepNum).padStart(2, '0')}`;
            ctx.font = `bold ${badgeSize}px ${fontFallback}`;
            const badgeMetrics = showBadge ? ctx.measureText(badgeText) : { width: 0 };
            const pillW = showBadge ? (badgeMetrics.width + (24 * scale)) : 0;
            const pillH = badgeSize + (16 * scale);
            const badgeGap = showBadge ? (16 * scale) : 0;

            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            const titleWidth = ctx.measureText(title).width;
            const totalHeaderW = pillW + badgeGap + titleWidth;

            let curX = pad;
            if (textAlign === 'center') {
                curX = Math.max(pad, (w - totalHeaderW) / 2);
            } else if (textAlign === 'right') {
                curX = Math.max(pad, w - pad - totalHeaderW);
            }
            let curY = startY + (bannerH * 0.28);

            if (showBadge) {
                clearTextShadow();
                ctx.fillStyle = badgeColor;
                roundRect(ctx, curX, curY, pillW, pillH, 10 * scale);
                ctx.fill();

                ctx.fillStyle = badgeTextColor;
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';
                ctx.fillText(badgeText, curX + (pillW / 2), curY + (pillH / 2));

                curX += pillW + badgeGap;
            }

            ctx.fillStyle = textColor;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.font = `bold ${titleSize}px ${fontFallback}`;
            applyTextShadow(true);
            ctx.fillText(title, curX, curY - (2 * scale));
            clearTextShadow();

            curY += titleSize + (16 * scale);

            if (description) {
                ctx.font = `${descSize}px ${fontFallback}`;
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                applyTextShadow(false);
                const linesDrawn = wrapText(ctx, description, pad, curY, w - (pad * 2), descSize * 1.35, textAlign);
                clearTextShadow();
                curY += (linesDrawn * descSize * 1.35) + (10 * scale);
            }

            if (ingredients) {
                ctx.font = `italic ${ingSize}px ${fontFallback}`;
                ctx.fillStyle = 'rgba(253, 224, 71, 0.95)'; // Warm yellow highlight
                applyTextShadow(false);
                if (textAlign === 'center') {
                    ctx.textAlign = 'center';
                    ctx.fillText(`🧂 ${ingredients}`, w / 2, curY);
                } else if (textAlign === 'right') {
                    ctx.textAlign = 'right';
                    ctx.fillText(`🧂 ${ingredients}`, w - pad, curY);
                } else {
                    ctx.textAlign = 'left';
                    ctx.fillText(`🧂 ${ingredients}`, pad, curY);
                }
                clearTextShadow();
            }
        }

        ctx.restore();
    }

    // Helper: Wrap text in Canvas 2D
    function wrapText(ctx, text, x, y, maxWidth, lineHeight, align = 'left') {
        const words = text.split(' ');
        let line = '';
        let lineCount = 0;

        for (let n = 0; n < words.length; n++) {
            const testLine = line + words[n] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            if (testWidth > maxWidth && n > 0) {
                let drawX = x;
                if (align === 'center') {
                    const lineWidth = ctx.measureText(line.trim()).width;
                    drawX = x + (maxWidth - lineWidth) / 2;
                } else if (align === 'right') {
                    const lineWidth = ctx.measureText(line.trim()).width;
                    drawX = x + maxWidth - lineWidth;
                }
                ctx.fillText(line.trim(), drawX, y);
                line = words[n] + ' ';
                y += lineHeight;
                lineCount++;
                if (lineCount >= 3) {
                    const trLine = line.trim() + '...';
                    let trX = x;
                    if (align === 'center') {
                        const trW = ctx.measureText(trLine).width;
                        trX = x + (maxWidth - trW) / 2;
                    } else if (align === 'right') {
                        const trW = ctx.measureText(trLine).width;
                        trX = x + maxWidth - trW;
                    }
                    ctx.fillText(trLine, trX, y);
                    return lineCount + 1;
                }
            } else {
                line = testLine;
            }
        }
        let finalX = x;
        if (align === 'center') {
            const finalW = ctx.measureText(line.trim()).width;
            finalX = x + (maxWidth - finalW) / 2;
        } else if (align === 'right') {
            const finalW = ctx.measureText(line.trim()).width;
            finalX = x + maxWidth - finalW;
        }
        ctx.fillText(line.trim(), finalX, y);
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
            has_shadow: true,
            font_size: 'medium',
            title_padding: 30,
            text_align: 'left'
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
        setTextAlign('left', false);
        setTextColor('#ffffff', false);
        setBadgeColor('#f59e0b', false);

        if (document.getElementById('show-badge-checkbox')) {
            document.getElementById('show-badge-checkbox').checked = true;
        }
        if (document.getElementById('has-shadow-checkbox')) {
            document.getElementById('has-shadow-checkbox').checked = true;
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
            const padEl = document.getElementById(`step-padding-${idx}`);

            if (titleEl) item.title = titleEl.value.trim();
            if (descEl) item.description = descEl.value.trim();
            if (ingEl) item.ingredients = ingEl.value.trim();
            if (enEl) item.enabled = enEl.checked;
            if (padEl) item.padding = padEl.value.trim() !== '' ? parseInt(padEl.value.trim(), 10) : null;
        });

        const payload = {
            _token: csrfToken,
            recipe_name: currentRecipeName || undefined,
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

    // --- JSON Captions Import Feature ---
    const sampleRecipeJson = {
        "recipe": "নরম তুলতুলে পাউরুটি",
        "steps": [
            { "image": 1, "caption": "তৈরি নরম তুলতুলে পাউরুটি" },
            { "image": 2, "caption": "মচমচে করে স্লাইস কাটা" },
            { "image": 3, "caption": "দেখুন কত নরম টেক্সচার" },
            { "image": 4, "caption": "কুসুম গরম দুধ নিন ১ কাপ" },
            { "image": 5, "caption": "ইস্ট দিন ১ চা-চামচ" },
            { "image": 6, "caption": "ভালোভাবে গুলে নিন" },
            { "image": 7, "caption": "আটা দিন ২ কাপ" },
            { "image": 8, "caption": "বেকিং পাউডার দিন সামান্য" },
            { "image": 9, "caption": "তেল, লবণ ও চিনি দিন" },
            { "image": 10, "caption": "মেখে নরম ডো তৈরি করুন" },
            { "image": 11, "caption": "তেল মাখিয়ে ঢেকে দিন" },
            { "image": 12, "caption": "১ ঘণ্টা রেস্টে রাখুন (ডাবল হবে)" },
            { "image": 13, "caption": "হাতে চেপে বাতাস বের করুন" },
            { "image": 14, "caption": "রুটির মতো বিছিয়ে নিন" },
            { "image": 15, "caption": "গড়িয়ে লম্বা শেপ দিন" },
            { "image": 16, "caption": "পছন্দমতো শেপ করে নিন" },
            { "image": 17, "caption": "পাত্রে তেল ব্রাশ করুন" },
            { "image": 18, "caption": "ডো বসিয়ে দিন পাত্রে" },
            { "image": 19, "caption": "কাপড় দিয়ে ঢেকে ২০ মিনিট রাখুন" },
            { "image": 20, "caption": "ডিমের কুসুম ব্রাশ করুন" },
            { "image": 21, "caption": "সাদা তিল ছড়িয়ে দিন" },
            { "image": 22, "caption": "বেক করার জন্য প্রস্তুত" },
            { "image": 23, "caption": "স্ট্যান্ডের উপর বসান" },
            { "image": 24, "caption": "ঢেকে হালকা আঁচে ৩০-৩৫ মিনিট বেক করুন" },
            { "image": 25, "caption": "সোনালি রং চলে এসেছে" },
            { "image": 26, "caption": "ফুলে ডাবল হয়ে গেছে" },
            { "image": 27, "caption": "পাত্র থেকে বের করুন" },
            { "image": 28, "caption": "গরম গরম স্লাইস করুন" },
            { "image": 29, "caption": "নরম তুলতুলে স্লাইস" },
            { "image": 30, "caption": "পরিবেশনের জন্য প্রস্তুত" },
            { "image": 31, "caption": "হাতে ছিঁড়ে দেখুন কত নরম!" }
        ]
    };

    function openPasteJsonModal() {
        const modal = document.getElementById('paste-json-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        const input = document.getElementById('json-caption-input');
        if (input) {
            input.focus();
            onJsonInputChange();
        }
    }

    function closePasteJsonModal() {
        const modal = document.getElementById('paste-json-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function loadSampleJson() {
        const input = document.getElementById('json-caption-input');
        if (input) {
            input.value = JSON.stringify(sampleRecipeJson, null, 2);
            onJsonInputChange();
        }
    }

    function clearJsonInput() {
        const input = document.getElementById('json-caption-input');
        if (input) {
            input.value = '';
            onJsonInputChange();
            input.focus();
        }
    }

    function parseJsonCaptions(text) {
        if (!text || !text.trim()) return null;
        let data;
        try {
            data = JSON.parse(text.trim());
        } catch (e) {
            return { error: e.message };
        }

        let recipeName = '';
        let rawItems = [];

        if (Array.isArray(data)) {
            rawItems = data;
        } else if (typeof data === 'object' && data !== null) {
            if (data.recipe && typeof data.recipe === 'string') {
                recipeName = data.recipe.trim();
            } else if (data.name && typeof data.name === 'string') {
                recipeName = data.name.trim();
            } else if (data.title && typeof data.title === 'string') {
                recipeName = data.title.trim();
            }

            if (Array.isArray(data.steps)) {
                rawItems = data.steps;
            } else if (Array.isArray(data.items)) {
                rawItems = data.items;
            } else if (Array.isArray(data.captions)) {
                rawItems = data.captions;
            } else {
                return { error: 'Could not find a "steps" array in JSON object.' };
            }
        } else {
            return { error: 'Expected JSON object or array.' };
        }

        const normalizedSteps = [];
        rawItems.forEach((item, idx) => {
            if (!item) return;
            let imageNum = idx + 1;
            let captionText = '';
            let descText = '';

            if (typeof item === 'string') {
                captionText = item;
            } else if (typeof item === 'object') {
                if (item.image !== undefined && item.image !== null) {
                    imageNum = parseInt(item.image, 10) || (idx + 1);
                } else if (item.step !== undefined && item.step !== null) {
                    imageNum = parseInt(item.step, 10) || (idx + 1);
                } else if (item.step_number !== undefined && item.step_number !== null) {
                    imageNum = parseInt(item.step_number, 10) || (idx + 1);
                } else if (item.frame_number !== undefined && item.frame_number !== null) {
                    imageNum = parseInt(item.frame_number, 10) || (idx + 1);
                }

                captionText = item.caption || item.title || item.text || item.instruction || item.description || '';
                descText = item.description || item.instructions || item.notes || '';
            }

            normalizedSteps.push({
                image: imageNum,
                caption: String(captionText).trim(),
                description: String(descText).trim()
            });
        });

        return {
            recipe: recipeName,
            steps: normalizedSteps,
            count: normalizedSteps.length
        };
    }

    function onJsonInputChange() {
        const input = document.getElementById('json-caption-input');
        const statusMsg = document.getElementById('json-status-message');
        const metaBadges = document.getElementById('json-meta-badges');
        const recipeBadge = document.getElementById('detected-recipe-badge');
        const stepsBadge = document.getElementById('detected-steps-badge');
        const applyBtn = document.getElementById('apply-json-btn');
        const applyBtnText = document.getElementById('apply-json-btn-text');

        if (!input || !statusMsg) return;

        const val = input.value.trim();
        if (!val) {
            statusMsg.innerHTML = '<span class="w-2 h-2 rounded-full bg-gray-500"></span><span class="text-gray-400">Ready to paste JSON recipe captions</span>';
            metaBadges.classList.add('hidden');
            if (applyBtn) applyBtn.disabled = true;
            if (applyBtnText) applyBtnText.innerText = 'Apply Captions & Place on Images';
            return;
        }

        const res = parseJsonCaptions(val);
        if (!res || res.error) {
            statusMsg.innerHTML = `<span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span><span class="text-rose-400 font-mono text-[11px] truncate">Syntax error: ${res?.error || 'Invalid JSON'}</span>`;
            metaBadges.classList.add('hidden');
            if (applyBtn) applyBtn.disabled = true;
            if (applyBtnText) applyBtnText.innerText = 'Apply Captions & Place on Images';
            return;
        }

        const totalSelected = selectedFrames.length;
        const matched = res.steps.filter(s => s.image >= 1 && s.image <= totalSelected).length;

        statusMsg.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span><span class="text-emerald-300 font-semibold">Valid JSON: ${res.count} captions found</span>`;
        
        if (res.recipe) {
            recipeBadge.innerText = `📖 ${res.recipe}`;
            recipeBadge.classList.remove('hidden');
        } else {
            recipeBadge.classList.add('hidden');
        }

        stepsBadge.innerText = `${matched}/${totalSelected} frames matched`;
        metaBadges.classList.remove('hidden');

        if (applyBtn) {
            applyBtn.disabled = res.count === 0;
            if (applyBtnText) {
                applyBtnText.innerText = `Apply ${res.count} Captions & Place on Images`;
            }
        }
    }

    function applyJsonCaptions() {
        const input = document.getElementById('json-caption-input');
        if (!input) return;

        const parsed = parseJsonCaptions(input.value);
        if (!parsed || parsed.error || parsed.steps.length === 0) {
            alert('Please paste valid JSON before applying.');
            return;
        }

        const optTopBanner = document.getElementById('json-opt-top-banner')?.checked ?? true;
        const optUpdateName = document.getElementById('json-opt-update-name')?.checked ?? true;
        const targetFieldRadio = document.querySelector('input[name="json-target-field"]:checked');
        const targetField = targetFieldRadio ? targetFieldRadio.value : 'title';

        // 1. Update recipe name if present and requested
        if (optUpdateName && parsed.recipe) {
            currentRecipeName = parsed.recipe;
            const heading = document.querySelector('h1');
            if (heading) {
                heading.innerText = parsed.recipe;
            }
        }

        // 2. Map step captions to frames
        let updatedCount = 0;
        const stepsMap = {};
        parsed.steps.forEach(s => {
            stepsMap[s.image] = s;
        });

        stepItems.forEach((item, idx) => {
            const stepNum = item.step_number || (idx + 1);
            const matched = stepsMap[stepNum] || stepsMap[idx + 1];

            if (matched && matched.caption) {
                updatedCount++;
                item.enabled = true;
                item.title = matched.caption;

                if (targetField === 'both') {
                    item.description = matched.description || matched.caption;
                } else if (matched.description) {
                    item.description = matched.description;
                }

                // Update UI elements in the left panel
                const titleInput = document.getElementById(`step-title-${idx}`);
                const descInput = document.getElementById(`step-desc-${idx}`);
                const enabledInput = document.getElementById(`step-enabled-${idx}`);

                if (titleInput) titleInput.value = item.title;
                if (descInput) descInput.value = item.description || '';
                if (enabledInput) enabledInput.checked = true;
            }
        });

        // 3. Switch layout to Top Banner if requested
        if (optTopBanner) {
            setLayout('top-banner', false);
        }

        // 4. Close modal
        closePasteJsonModal();

        // 5. Render live composite canvas for current active step
        renderLiveCanvas();

        // 6. Save steps immediately via AJAX
        saveSteps(false, true);

        showToast(`Applied ${updatedCount} captions to image tops & saved!`);
    }

    // Modal keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePasteJsonModal();
        }
    });

    const lastStepStyleSetup = @json($lastStepStyle ?? null);

    function applyLastStepsStyle() {
        if (!lastStepStyleSetup) {
            showToast('No previous styling setup found.');
            return;
        }

        Object.assign(styleConfig, lastStepStyleSetup);

        if (typeof setLayout === 'function') setLayout(styleConfig.layout || 'bottom-banner', false);
        if (typeof setFontSize === 'function') setFontSize(styleConfig.font_size || styleConfig.size || 'medium', false);
        if (typeof setTextAlign === 'function') setTextAlign(styleConfig.text_align || 'left', false);
        if (typeof setTextColor === 'function') setTextColor(styleConfig.text_color || '#ffffff', false);
        if (typeof setBadgeColor === 'function') setBadgeColor(styleConfig.badge_color || '#f59e0b', false);

        if (document.getElementById('show-badge-checkbox')) {
            document.getElementById('show-badge-checkbox').checked = styleConfig.show_badge !== false;
        }
        if (document.getElementById('has-shadow-checkbox')) {
            document.getElementById('has-shadow-checkbox').checked = styleConfig.has_shadow !== false;
        }
        if (document.getElementById('bg-opacity-slider')) {
            const op = styleConfig.bg_opacity !== undefined ? styleConfig.bg_opacity : 85;
            document.getElementById('bg-opacity-slider').value = op;
            const opVal = document.getElementById('opacity-val');
            if (opVal) opVal.innerText = op + '%';
        }
        if (document.getElementById('title-padding-slider') && styleConfig.title_padding !== undefined) {
            document.getElementById('title-padding-slider').value = styleConfig.title_padding;
            const padVal = document.getElementById('title-padding-val');
            if (padVal) padVal.innerText = styleConfig.title_padding + 'px';
        }

        if (typeof renderLiveCanvas === 'function') {
            renderLiveCanvas();
        }
        showToast('Applied last saved recipe steps styling!');
    }
</script>
@endsection
