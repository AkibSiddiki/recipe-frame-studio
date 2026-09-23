@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'frames'])

@section('content')
<div class="max-w-7xl mx-auto pb-24">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 animate-fade-in">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.show', $project['slug']) }}" class="text-gray-400 hover:text-white transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span>Video Source</span>
                </a>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Step 2: Frames</span>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <a href="{{ route('project.crop', $project['slug']) }}" class="text-gray-500 hover:text-gray-300 transition-colors">
                    Step 3: Crop
                </a>
            </div>
            <h1 class="text-3xl font-display font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Project Frames' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Select high-impact frames to assemble your recipe steps collage.</span>
                <span class="text-gray-600">•</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($frames) }} frames candidate pool</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openCaptureModal()" class="px-3.5 py-2 bg-white/[0.05] hover:bg-white/[0.08] text-gray-200 border border-border-default rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm hover:border-border-hover">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Custom Timestamp</span>
            </button>

            <a href="{{ route('project.show', $project['slug']) }}" class="px-3.5 py-2 bg-white/[0.05] hover:bg-white/[0.08] text-gray-200 border border-border-default rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm hover:border-border-hover">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Re-Extract</span>
            </a>

            <button type="button" onclick="goToCropStep()" id="next-step-btn" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition-all shadow-lg shadow-amber-900/30 flex items-center gap-2 active:scale-95">
                <span>Next: Crop & Aspect Ratio</span>
                <span id="selected-badge" class="px-2 py-0.5 rounded-full bg-black/30 text-white text-[11px] font-extrabold border border-white/15">
                    {{ count(array_filter($frames, fn($f) => $f['selected'] ?? false)) }} Selected
                </span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 bg-emerald-950/50 border border-emerald-700/50 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg backdrop-blur-sm animate-slide-up">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Controls Toolbar -->
    <div class="glass-surface rounded-2xl p-4 mb-6 flex flex-wrap items-center justify-between gap-4 shadow-xl">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Selection:</span>
                <button type="button" onclick="selectAllFrames(true)" class="text-xs px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700/80 font-medium transition hover:border-gray-600">
                    Select All
                </button>
                <button type="button" onclick="selectAllFrames(false)" class="text-xs px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700/80 font-medium transition hover:border-gray-600">
                    Deselect All
                </button>
            </div>
            
            <span class="text-gray-700 hidden sm:inline">|</span>
            
            <div class="text-xs text-gray-400">
                Click any card to select for recipe steps
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <!-- Grid Size Switcher -->
            <div class="flex items-center gap-1 bg-[#12192b] p-1 rounded-xl border border-gray-800 text-xs">
                <span class="text-gray-400 px-2 font-medium">Grid:</span>
                <button type="button" onclick="setGridSize('medium')" id="grid-btn-medium" class="grid-btn px-2.5 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm">
                    Medium
                </button>
                <button type="button" onclick="setGridSize('large')" id="grid-btn-large" class="grid-btn px-2.5 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 transition">
                    Large
                </button>
                <button type="button" onclick="setGridSize('compact')" id="grid-btn-compact" class="grid-btn px-2.5 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 transition">
                    Compact
                </button>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-1.5 bg-[#12192b] p-1 rounded-xl border border-gray-800 text-xs">
                <button type="button" onclick="filterFrames('all')" id="filter-all-btn" class="px-3 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm">
                    All ({{ count($frames) }})
                </button>
                <button type="button" onclick="filterFrames('selected')" id="filter-selected-btn" class="px-3 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 font-medium transition">
                    Selected Only
                </button>
            </div>
        </div>
    </div>

    <!-- Frames Gallery Grid -->
    @if(empty($frames))
        <div class="glass-surface border border-dashed border-border-default rounded-3xl p-16 text-center shadow-xl">
            <div class="text-6xl mb-4">🖼️</div>
            <h3 class="text-xl font-bold text-white mb-2">No Frames Extracted Yet</h3>
            <p class="text-gray-400 max-w-md mx-auto mb-6 text-sm">
                Run auto-extraction to pull candidate frames from your video file automatically.
            </p>
            <form action="{{ route('project.extract-frames', $project['slug']) }}" method="POST">
                @csrf
                <input type="hidden" name="target_count" value="24">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition shadow-lg shadow-amber-900/30">
                    Extract 24 Frames Now
                </button>
            </form>
        </div>
    @else
        @php
            $isVertical = !empty($project['video']['is_vertical']);
            $cardAspectClass = $isVertical ? 'aspect-[4/5]' : 'aspect-video';
        @endphp
        <div id="frames-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 stagger-children">
            @foreach($frames as $index => $frame)
                @php
                    $isSelected = !empty($frame['selected']);
                    $frameFilename = $frame['filename'] ?? '';
                    $frameId = $frame['id'] ?? $frameFilename;
                    $imageUrl = route('project.frame.image', ['slug' => $project['slug'], 'filename' => $frameFilename]);
                @endphp
                <div class="frame-card group relative rounded-2xl overflow-hidden border-2 transition-all duration-300 bg-surface-raised shadow-lg hover:shadow-2xl hover:-translate-y-1 cursor-pointer select-none {{ $isSelected ? 'border-amber-500 shadow-[0_0_25px_rgba(245,158,11,0.22)] ring-2 ring-amber-500/30 bg-surface-overlay' : 'border-border-default hover:border-border-hover' }}"
                     data-frame-id="{{ $frameId }}"
                     data-filename="{{ $frameFilename }}"
                     data-image-url="{{ $imageUrl }}"
                     data-time="{{ $frame['formatted_time'] ?? '00:00' }}"
                     data-timestamp="{{ $frame['timestamp'] ?? 0 }}"
                     data-selected="{{ $isSelected ? 'true' : 'false' }}"
                     onclick="handleCardClick(event, '{{ $frameId }}')">
                    
                    <!-- Media Area -->
                    <div class="{{ $cardAspectClass }} bg-black relative flex items-center justify-center overflow-hidden">
                        <img src="{{ $imageUrl }}" 
                             loading="lazy" 
                             alt="Frame at {{ $frame['formatted_time'] ?? '' }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <!-- Top Badges Overlay -->
                        <div class="absolute top-2.5 left-2.5 right-2.5 flex justify-between items-center pointer-events-none z-10">
                            <!-- Frame Number & Time -->
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-lg bg-black/85 text-[11px] font-bold text-white backdrop-blur-md shadow border border-white/10">
                                    #{{ sprintf('%02d', $index + 1) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-lg bg-black/85 text-[11px] font-mono font-medium text-gray-300 backdrop-blur-md shadow border border-white/10">
                                    {{ $frame['formatted_time'] ?? '00:00' }}
                                </span>
                            </div>

                            <!-- Clear Easy-to-Select Button -->
                            <div class="selection-indicator flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold shadow-lg transition-transform duration-200 {{ $isSelected ? 'bg-amber-500 text-black scale-100' : 'bg-black/75 hover:bg-black text-gray-200 border border-gray-500 backdrop-blur-md' }}">
                                @if($isSelected)
                                    <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Selected</span>
                                @else
                                    <span class="w-2.5 h-2.5 rounded-full border border-gray-400 inline-block"></span>
                                    <span>Select</span>
                                @endif
                            </div>
                        </div>

                        <!-- Hover Overlay Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10"></div>
                    </div>

                    <!-- Card Footer Info Bar -->
                    <div class="p-3 bg-surface-base/80 border-t border-border-subtle flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $isSelected ? 'bg-amber-400 animate-pulse' : 'bg-gray-600' }}"></span>
                            <span class="font-medium text-gray-300 status-label">
                                {{ $isSelected ? 'Included in Recipe' : 'Click to include' }}
                            </span>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center gap-1">
                            <button type="button" 
                                    onclick="event.stopPropagation(); openLightbox('{{ $index }}')" 
                                    class="p-1.5 rounded-lg bg-gray-800/80 hover:bg-gray-700 text-gray-300 hover:text-white border border-gray-700/60 transition shadow-sm"
                                    title="View Fullscreen">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                            </button>

                            <button type="button" 
                                    onclick="event.stopPropagation(); deleteFrameConfirm('{{ $frameFilename }}', '{{ $frameId }}')" 
                                    class="p-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-rose-300 hover:text-white border border-rose-800/50 transition shadow-sm"
                                    title="Delete Frame">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Floating Bottom Sticky Bar -->
<div id="sticky-bottom-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 glass-surface border-amber-500/30 px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-6 transition-all duration-300 {{ count(array_filter($frames, fn($f) => $f['selected'] ?? false)) > 0 ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0 pointer-events-none' }}">
    <div class="flex items-center gap-3">
        <span class="w-3 h-3 rounded-full bg-amber-500 animate-ping"></span>
        <span class="text-sm font-bold text-white">
            <span id="floating-selected-count">{{ count(array_filter($frames, fn($f) => $f['selected'] ?? false)) }}</span> Frames Selected for Collage
        </span>
    </div>

    <button type="button" onclick="goToCropStep()" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-5 py-2 rounded-xl text-xs font-bold tracking-wide transition-all shadow-lg shadow-amber-900/30 flex items-center gap-2">
        <span>Proceed to Step 3: Crop</span>
        <span>→</span>
    </button>
</div>

<!-- Lightbox Modal -->
<div id="lightbox-modal" class="hidden fixed inset-0 z-50 bg-black/95 backdrop-blur-md flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-4xl flex justify-between items-center text-white mb-3 px-2">
        <div class="flex items-center gap-3">
            <span id="lightbox-time" class="text-lg font-mono font-bold text-amber-400">00:00</span>
            <span id="lightbox-title" class="text-xs text-gray-400 font-mono truncate max-w-xs"></span>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="lightbox-toggle-btn" onclick="toggleLightboxFrame()" class="px-4 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                Toggle Selection
            </button>
            <button type="button" onclick="closeLightbox()" class="p-2 rounded-xl text-gray-400 hover:text-white text-lg">✕</button>
        </div>
    </div>

    <div class="relative max-h-[82vh] max-w-4xl flex items-center justify-center">
        <img id="lightbox-img" src="" class="max-h-[82vh] max-w-full rounded-2xl object-contain shadow-2xl border border-gray-800">

        <!-- Navigation Buttons -->
        <button type="button" onclick="prevLightbox()" class="absolute left-2 top-1/2 -translate-y-1/2 p-3 rounded-full bg-black/70 text-white hover:bg-black transition text-lg shadow-lg">
            ❮
        </button>
        <button type="button" onclick="nextLightbox()" class="absolute right-2 top-1/2 -translate-y-1/2 p-3 rounded-full bg-black/70 text-white hover:bg-black transition text-lg shadow-lg">
            ❯
        </button>
    </div>
</div>

<!-- Custom Timestamp Capture Modal -->
<div id="capture-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 modal-backdrop" onclick="if(event.target===this)closeCaptureModal()">
    <div class="glass-surface border-border-default rounded-2xl max-w-sm w-full p-6 shadow-2xl modal-content">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>⏱️</span>
                <span>Capture at Timestamp</span>
            </h3>
            <button type="button" onclick="closeCaptureModal()" class="text-gray-400 hover:text-white">✕</button>
        </div>

        <div class="mb-4">
            <label for="custom-time-input" class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Timestamp (seconds or MM:SS)</label>
            <input type="text" id="custom-time-input" placeholder="e.g. 14.5 or 01:25" class="block w-full bg-gray-800/80 border-gray-700 rounded-xl text-gray-200 focus:ring-amber-500 focus:border-amber-500 text-sm px-4 py-2.5 font-mono">
            <p class="text-xs text-gray-500 mt-2">Extract an exact second where a dish or ingredient looks best.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-800">
            <button type="button" onclick="closeCaptureModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-xl text-xs font-semibold transition">
                Cancel
            </button>
            <button type="button" id="capture-submit-btn" onclick="submitCustomCapture()" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-md">
                <span>Capture Frame</span>
            </button>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectSlug = '{{ $project['slug'] }}';
    let framesData = @json($frames);
    let currentLightboxIndex = 0;

    function handleCardClick(event, frameId) {
        const card = document.querySelector(`[data-frame-id="${frameId}"]`);
        if (!card) return;

        fetch('{{ route('project.frames.toggle', $project['slug']) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ frame_id: frameId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.frame) {
                applyCardState(card, data.frame.selected);
                const item = framesData.find(f => (f.id === frameId || f.filename === frameId));
                if (item) item.selected = data.frame.selected;
                updateSelectedCounter();
            }
        })
        .catch(err => console.error('Error toggling frame:', err));
    }

    function setGridSize(size) {
        const gridEl = document.getElementById('frames-grid');
        if (!gridEl) return;

        const btnMed = document.getElementById('grid-btn-medium');
        const btnLg = document.getElementById('grid-btn-large');
        const btnComp = document.getElementById('grid-btn-compact');

        [btnMed, btnLg, btnComp].forEach(btn => {
            if (btn) {
                btn.className = 'grid-btn px-2.5 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 transition';
            }
        });

        gridEl.classList.remove(
            'grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-2', 'md:grid-cols-3', 'md:grid-cols-4',
            'lg:grid-cols-2', 'lg:grid-cols-3', 'lg:grid-cols-4', 'lg:grid-cols-5',
            'xl:grid-cols-3', 'xl:grid-cols-4', 'xl:grid-cols-6',
            'gap-4', 'gap-6', 'gap-8'
        );

        if (size === 'large') {
            gridEl.classList.add('grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-2', 'xl:grid-cols-3', 'gap-8');
            if (btnLg) btnLg.className = 'grid-btn px-2.5 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm';
        } else if (size === 'compact') {
            gridEl.classList.add('grid-cols-2', 'sm:grid-cols-3', 'md:grid-cols-4', 'lg:grid-cols-5', 'xl:grid-cols-6', 'gap-4');
            if (btnComp) btnComp.className = 'grid-btn px-2.5 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm';
        } else {
            size = 'medium';
            gridEl.classList.add('grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-3', 'xl:grid-cols-4', 'gap-6');
            if (btnMed) btnMed.className = 'grid-btn px-2.5 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm';
        }

        try {
            localStorage.setItem('recipe_studio_frames_grid', size);
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        try {
            const savedSize = localStorage.getItem('recipe_studio_frames_grid') || 'medium';
            setGridSize(savedSize);
        } catch (e) {}
        updateSelectedCounter();
    });

    function applyCardState(card, isSelected) {
        card.setAttribute('data-selected', isSelected ? 'true' : 'false');
        const indicator = card.querySelector('.selection-indicator');
        const statusLabel = card.querySelector('.status-label');
        const statusDot = card.querySelector('.status-label')?.previousElementSibling;

        if (isSelected) {
            card.classList.add('border-amber-500', 'shadow-[0_0_25px_rgba(245,158,11,0.22)]', 'ring-2', 'ring-amber-500/30', 'bg-[#1b2545]');
            card.classList.remove('border-gray-800/80');
            if (indicator) {
                indicator.className = 'selection-indicator flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold shadow-lg transition-transform duration-200 bg-amber-500 text-black scale-100';
                indicator.innerHTML = `<svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg><span>Selected</span>`;
            }
            if (statusLabel) statusLabel.innerText = 'Included in Recipe';
            if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
        } else {
            card.classList.remove('border-amber-500', 'shadow-[0_0_25px_rgba(245,158,11,0.22)]', 'ring-2', 'ring-amber-500/30', 'bg-[#1b2545]');
            card.classList.add('border-gray-800/80');
            if (indicator) {
                indicator.className = 'selection-indicator flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold shadow-lg transition-transform duration-200 bg-black/75 hover:bg-black text-gray-200 border border-gray-500 backdrop-blur-md';
                indicator.innerHTML = `<span class="w-2.5 h-2.5 rounded-full border border-gray-400 inline-block"></span><span>Select</span>`;
            }
            if (statusLabel) statusLabel.innerText = 'Click to include';
            if (statusDot) statusDot.className = 'w-2 h-2 rounded-full bg-gray-600';
        }
    }

    function updateSelectedCounter() {
        const selectedCount = document.querySelectorAll('.frame-card[data-selected="true"]').length;
        const badge = document.getElementById('selected-badge');
        if (badge) badge.innerText = `${selectedCount} Selected`;

        const floatCount = document.getElementById('floating-selected-count');
        if (floatCount) floatCount.innerText = selectedCount;

        const stickyBar = document.getElementById('sticky-bottom-bar');
        if (stickyBar) {
            if (selectedCount > 0) {
                stickyBar.classList.remove('translate-y-12', 'opacity-0', 'pointer-events-none');
                stickyBar.classList.add('translate-y-0', 'opacity-100');
            } else {
                stickyBar.classList.add('translate-y-12', 'opacity-0', 'pointer-events-none');
                stickyBar.classList.remove('translate-y-0', 'opacity-100');
            }
        }
    }

    function selectAllFrames(selectState) {
        const cards = document.querySelectorAll('.frame-card');
        cards.forEach(card => {
            const isCurrentlySelected = card.getAttribute('data-selected') === 'true';
            if (isCurrentlySelected !== selectState) {
                const frameId = card.getAttribute('data-frame-id');
                handleCardClick(null, frameId);
            }
        });
    }

    function filterFrames(type) {
        const cards = document.querySelectorAll('.frame-card');
        const allBtn = document.getElementById('filter-all-btn');
        const selBtn = document.getElementById('filter-selected-btn');

        if (type === 'selected') {
            allBtn.className = 'px-3 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 font-medium transition';
            selBtn.className = 'px-3 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm';
            cards.forEach(card => {
                if (card.getAttribute('data-selected') === 'true') {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        } else {
            allBtn.className = 'px-3 py-1 rounded-lg bg-amber-600 text-white font-semibold transition shadow-sm';
            selBtn.className = 'px-3 py-1 rounded-lg bg-gray-800/70 hover:bg-gray-700 text-gray-300 font-medium transition';
            cards.forEach(card => card.classList.remove('hidden'));
        }
    }

    // --- Lightbox Modal ---
    function openLightbox(index) {
        currentLightboxIndex = parseInt(index);
        updateLightboxView();
        document.getElementById('lightbox-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeLightbox() {
        document.getElementById('lightbox-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function updateLightboxView() {
        const frame = framesData[currentLightboxIndex];
        if (!frame) return;

        document.getElementById('lightbox-time').innerText = frame.formatted_time || '00:00';
        document.getElementById('lightbox-title').innerText = frame.filename;
        document.getElementById('lightbox-img').src = `{{ url('/project') }}/${projectSlug}/frame-image/${frame.filename}`;

        const toggleBtn = document.getElementById('lightbox-toggle-btn');
        if (frame.selected) {
            toggleBtn.className = 'px-4 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-amber-500 text-black shadow-lg';
            toggleBtn.innerHTML = '<span>✓</span><span>Selected for Recipe</span>';
        } else {
            toggleBtn.className = 'px-4 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-gray-800 text-gray-200 hover:bg-gray-700 border border-gray-700';
            toggleBtn.innerHTML = '<span>+</span><span>Select for Recipe</span>';
        }
    }

    function prevLightbox() {
        if (currentLightboxIndex > 0) {
            currentLightboxIndex--;
            updateLightboxView();
        } else {
            currentLightboxIndex = framesData.length - 1;
            updateLightboxView();
        }
    }

    function nextLightbox() {
        if (currentLightboxIndex < framesData.length - 1) {
            currentLightboxIndex++;
            updateLightboxView();
        } else {
            currentLightboxIndex = 0;
            updateLightboxView();
        }
    }

    function toggleLightboxFrame() {
        const frame = framesData[currentLightboxIndex];
        if (!frame) return;
        const frameId = frame.id || frame.filename;
        handleCardClick(null, frameId);
        frame.selected = !frame.selected;
        updateLightboxView();
    }

    // Keyboard support for Lightbox
    window.addEventListener('keydown', (e) => {
        const modal = document.getElementById('lightbox-modal');
        if (modal && !modal.classList.contains('hidden')) {
            if (e.key === 'ArrowLeft') prevLightbox();
            if (e.key === 'ArrowRight') nextLightbox();
            if (e.key === 'Escape') closeLightbox();
            if (e.key === ' ') {
                e.preventDefault();
                toggleLightboxFrame();
            }
        }
    });

    // --- Delete Frame ---
    function deleteFrameConfirm(filename, frameId) {
        if (!confirm('Are you sure you want to remove this frame from candidates?')) return;

        fetch(`{{ url('/project') }}/${projectSlug}/frames/${filename}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`[data-frame-id="${frameId}"]`);
                if (card) {
                    card.classList.add('opacity-0', 'scale-90');
                    setTimeout(() => card.remove(), 250);
                }
                framesData = framesData.filter(f => f.filename !== filename && f.id !== frameId);
                updateSelectedCounter();
            } else {
                alert('Could not delete frame.');
            }
        })
        .catch(err => alert('Error deleting frame: ' + err.message));
    }

    // --- Custom Timestamp Capture Modal ---
    function openCaptureModal() {
        document.getElementById('capture-modal').classList.remove('hidden');
        document.getElementById('custom-time-input').focus();
    }

    function closeCaptureModal() {
        document.getElementById('capture-modal').classList.add('hidden');
        document.getElementById('custom-time-input').value = '';
    }

    function submitCustomCapture() {
        const input = document.getElementById('custom-time-input').value.trim();
        if (!input) {
            alert('Please enter a timestamp in seconds or MM:SS format.');
            return;
        }

        let seconds = 0;
        if (input.includes(':')) {
            const parts = input.split(':').map(Number);
            if (parts.length === 2) {
                seconds = (parts[0] * 60) + parts[1];
            } else if (parts.length === 3) {
                seconds = (parts[0] * 3600) + (parts[1] * 60) + parts[2];
            }
        } else {
            seconds = parseFloat(input);
        }

        if (isNaN(seconds) || seconds < 0) {
            alert('Invalid timestamp.');
            return;
        }

        const btn = document.getElementById('capture-submit-btn');
        btn.disabled = true;
        btn.innerText = 'Capturing...';

        fetch('{{ route('project.frames.capture-at', $project['slug']) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ timestamp: seconds })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Capture Frame';
            if (data.success && data.frame) {
                closeCaptureModal();
                window.location.reload();
            } else {
                alert('Could not capture frame: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Capture Frame';
            alert('Error capturing frame: ' + err.message);
        });
    }

    function goToCropStep() {
        const selectedCount = document.querySelectorAll('.frame-card[data-selected="true"]').length;
        if (selectedCount === 0) {
            if (!confirm('You have not selected any specific frames yet. Would you like to proceed with all extracted frames?')) {
                return;
            }
        }
        window.location.href = "{{ route('project.crop', $project['slug']) }}";
    }
</script>
@endsection
