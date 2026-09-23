@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'crop'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.frames', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg> <span>Frames Gallery</span>
                </a>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Step 3: Crop & Framing</span>
                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-gray-500">Step 4: Watermark</span>
            </div>
            <h1 class="text-3xl font-display font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Crop Frames' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Fine-tune aspect ratio and composition for each recipe step.</span>
                <span class="text-gray-600">·</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} frames in layout</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="resetCurrentCrop()" class="px-3.5 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Reset Framing</span>
            </button>

            <button type="button" onclick="saveAllCrops(false)" id="save-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                <span id="save-btn-text">Save Framing</span>
            </button>

            <button type="button" onclick="saveAndProceed()" id="proceed-btn" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-amber-900/30 flex items-center gap-2 active:scale-95">
                <span>Next: Watermark (Step 4)</span>
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

    <!-- Aspect Ratio & Batch Alignment Toolbar -->
    <div class="glass-surface rounded-2xl p-4 mb-6 shadow-xl flex flex-wrap items-center justify-between gap-4">
        <!-- Preset Ratio Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold mr-1">Aspect Ratio:</span>
            
            <button type="button" onclick="selectPreset('4:5', 'facebook-portrait', 1080, 1350)" class="preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-sm" data-ratio="4:5">
                <span>📱</span>
                <span>4:5 Portrait</span>
                <span class="text-[10px] opacity-80 font-normal">(Facebook / IG)</span>
            </button>

            <button type="button" onclick="selectPreset('1:1', 'square', 1080, 1080)" class="preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-sm" data-ratio="1:1">
                <span>⏹️</span>
                <span>1:1 Square</span>
            </button>

            <button type="button" onclick="selectPreset('16:9', 'landscape', 1920, 1080)" class="preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-sm" data-ratio="16:9">
                <span>🖥️</span>
                <span>16:9 Landscape</span>
            </button>

            <button type="button" onclick="selectPreset('9:16', 'original-vertical', 1080, 1920)" class="preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-sm" data-ratio="9:16">
                <span>🎬</span>
                <span>9:16 Vertical</span>
            </button>

            <button type="button" onclick="selectPreset('free', 'custom', 0, 0)" class="preset-btn px-3 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shadow-sm" data-ratio="free">
                <span>✂️</span>
                <span>Freeform</span>
            </button>
        </div>

        <!-- Alignment & Batch Controls -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 bg-surface-base p-1 rounded-xl border border-gray-800 text-xs">
                <span class="text-gray-400 px-2 font-medium">Align:</span>
                <button type="button" onclick="alignActiveCrop('top')" class="px-2.5 py-1 rounded-lg hover:bg-gray-800 text-gray-300 transition font-medium" title="Align to Top">
                    ⬆️ Top
                </button>
                <button type="button" onclick="alignActiveCrop('center')" class="px-2.5 py-1 rounded-lg hover:bg-gray-800 text-gray-300 transition font-medium" title="Align to Center">
                    ⏺️ Center
                </button>
                <button type="button" onclick="alignActiveCrop('bottom')" class="px-2.5 py-1 rounded-lg hover:bg-gray-800 text-gray-300 transition font-medium" title="Align to Bottom">
                    ⬇️ Bottom
                </button>
            </div>

            <button type="button" onclick="applyToAllFrames()" class="px-3.5 py-2 bg-gradient-to-r from-amber-900/60 to-amber-800/60 hover:from-amber-800/80 hover:to-amber-700/80 text-amber-300 border border-amber-600/50 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md active:scale-95">
                <span>⚡</span>
                <span>Apply to All Frames</span>
            </button>
        </div>
    </div>

    <!-- Main Workspace (Cropper + Live Preview) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Interactive Cropper (2 cols) -->
        <div class="lg:col-span-2 glass-surface rounded-3xl p-5 shadow-2xl flex flex-col">
            <div class="flex items-center justify-between mb-3 text-xs text-gray-400">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-white">Active Frame:</span>
                    <span id="active-frame-name" class="font-mono text-amber-400 font-semibold truncate max-w-[200px]">Loading...</span>
                    <span class="text-gray-600">·</span>
                    <span id="active-frame-time" class="text-gray-300 font-mono">00:00</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="prev-frame-btn" onclick="navigateFrame(-1)" class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed" title="Previous frame (Alt + Left Arrow)" aria-label="Previous frame">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button type="button" id="next-frame-btn" onclick="navigateFrame(1)" class="p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed" title="Next frame (Alt + Right Arrow)" aria-label="Next frame">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path fill="currentColor" d="M9 5l7 7-7 7z"></path></svg>
                    </button>
                    <span class="font-mono text-[11px] bg-surface-base px-2.5 py-1 rounded-lg border border-gray-800 text-gray-300 font-semibold" id="crop-coords-label">
                        X: 0, Y: 0 | 0 × 0 px
                    </span>
                </div>
            </div>

            <!-- Cropper Canvas Container -->
            <div class="relative flex-1 bg-black/80 rounded-2xl overflow-hidden flex items-center justify-center min-h-[440px] max-h-[580px] border border-gray-800/90 select-none shadow-inner" id="cropper-container">
                <!-- Source Image -->
                <img id="cropper-image" src="" alt="Active frame for cropping" class="max-h-[540px] max-w-full object-contain pointer-events-none transition-opacity duration-200" onload="onImageLoaded()" />

                <!-- Crop Box with Smooth Box-Shadow Mask & Brackets -->
                <div id="crop-box" class="absolute cursor-move border border-amber-400/90 shadow-[0_0_0_9999px_rgba(0,0,0,0.65)] z-20 transition-shadow">
                    <!-- Rule of Thirds Grid Lines -->
                    <div class="absolute inset-0 grid grid-cols-3 grid-rows-3 pointer-events-none">
                        <div class="border-r border-b border-white/25"></div>
                        <div class="border-r border-b border-white/25"></div>
                        <div class="border-b border-white/25"></div>
                        <div class="border-r border-b border-white/25"></div>
                        <div class="border-r border-b border-white/25"></div>
                        <div class="border-b border-white/25"></div>
                        <div class="border-r border-white/25"></div>
                        <div class="border-r border-white/25"></div>
                        <div></div>
                    </div>

                    <!-- Ratio & Dimensions Badge Inside Box -->
                    <div class="absolute top-2 left-2 bg-black/85 text-amber-400 text-[10px] font-mono font-bold px-2 py-0.5 rounded-md pointer-events-none border border-amber-500/40 backdrop-blur-sm shadow">
                        <span id="box-ratio-tag">4:5</span>
                    </div>

                    <!-- Corner L-Bracket Handles -->
                    <div class="resize-handle nw absolute -top-1 -left-1 w-4 h-4 border-t-3 border-l-3 border-amber-400 cursor-nw-resize" data-handle="nw"></div>
                    <div class="resize-handle ne absolute -top-1 -right-1 w-4 h-4 border-t-3 border-r-3 border-amber-400 cursor-ne-resize" data-handle="ne"></div>
                    <div class="resize-handle sw absolute -bottom-1 -left-1 w-4 h-4 border-b-3 border-l-3 border-amber-400 cursor-sw-resize" data-handle="sw"></div>
                    <div class="resize-handle se absolute -bottom-1 -right-1 w-4 h-4 border-b-3 border-r-3 border-amber-400 cursor-se-resize" data-handle="se"></div>

                    <!-- Edge Resize Bars -->
                    <div class="resize-handle n absolute -top-1 left-1/2 -translate-x-1/2 w-6 h-1.5 bg-amber-400 rounded-full cursor-n-resize shadow" data-handle="n"></div>
                    <div class="resize-handle s absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-1.5 bg-amber-400 rounded-full cursor-s-resize shadow" data-handle="s"></div>
                    <div class="resize-handle w absolute top-1/2 -translate-y-1/2 -left-1 w-1.5 h-6 bg-amber-400 rounded-full cursor-w-resize shadow" data-handle="w"></div>
                    <div class="resize-handle e absolute top-1/2 -translate-y-1/2 -right-1 w-1.5 h-6 bg-amber-400 rounded-full cursor-e-resize shadow" data-handle="e"></div>
                </div>
            </div>

            <!-- Cropper Controls Footer -->
            <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-800">
                <div class="flex items-center gap-3">
                    <span>💡 <strong class="text-gray-200">Drag box</strong> to position • <strong class="text-gray-200">Corners</strong> to scale framing</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="centerCropBox()" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg border border-gray-700 transition font-medium">
                        Center Box
                    </button>
                    <button type="button" onclick="maximizeCropBox()" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg border border-gray-700 transition font-medium">
                        Fit Max
                    </button>
                </div>
            </div>
            <div class="mt-3 rounded-xl border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-[11px] text-gray-400 leading-relaxed">
                <span class="font-semibold text-amber-400">Keyboard shortcuts:</span>
                <span>Arrow keys move the crop area · Shift + Arrow moves faster · Alt + Left/Right switches frames</span>
            </div>
        </div>

        <!-- Right Side: Live Cropped Output & Presets Info -->
        <div class="flex flex-col gap-6">
            <!-- Cropped Live Preview Card -->
            <div class="glass-surface rounded-3xl p-5 shadow-2xl flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>👁️</span>
                        <span>Live Output Preview</span>
                    </h3>
                    <span id="preview-aspect-tag" class="text-xs px-2.5 py-0.5 rounded-lg font-mono font-bold bg-amber-950/80 text-amber-400 border border-amber-800/60 shadow-sm">
                        4:5
                    </span>
                </div>

                <!-- Preview Canvas Viewport -->
                <div class="w-full bg-black/70 rounded-2xl border border-gray-800/90 flex items-center justify-center p-3 overflow-hidden min-h-[260px] shadow-inner">
                    <canvas id="preview-canvas" class="max-w-full max-h-[250px] rounded-xl shadow-xl object-contain"></canvas>
                </div>

                <div class="mt-3 text-xs text-gray-400 flex items-center justify-between font-mono">
                    <span class="text-gray-400">Target Resolution:</span>
                    <span id="preview-res-label" class="text-amber-400 font-bold">1080 × 1350 px</span>
                </div>
            </div>

            <!-- Recipe Social Publishing Card -->
            <div class="glass-surface rounded-3xl p-5 shadow-2xl flex flex-col justify-between flex-1">
                <div>
                    <h3 class="text-sm font-bold text-white mb-2 flex items-center gap-2">
                        <span>🎯</span>
                        <span>Composition Tips</span>
                    </h3>
                    <div id="ratio-tip" class="text-xs text-gray-300 leading-relaxed bg-surface-base p-3.5 rounded-2xl border border-gray-800">
                        <p class="font-bold text-amber-400 mb-1">Facebook & Instagram (4:5 Portrait)</p>
                        <p>4:5 portrait fills the phone screen vertically without letterboxing. Frame the hero food item in the middle third for maximum engagement.</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-800 flex flex-col gap-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Active Recipe Steps:</span>
                        <span class="text-white font-bold">{{ count($selectedFrames) }} frames</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Layout Consistency:</span>
                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Synced across collage
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Filmstrip / Frame Selector -->
    <div class="glass-surface rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-white uppercase tracking-wider">Frames in Layout ({{ count($selectedFrames) }})</span>
                <span class="text-[11px] text-gray-400">• Click any frame to inspect or adjust its crop</span>
            </div>
            <div class="text-xs text-amber-400 font-medium" id="crop-status-indicator">
                Ready for Watermarking
            </div>
        </div>

        <div class="flex items-center gap-3.5 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-700">
            @foreach($selectedFrames as $index => $frame)
                @php
                    $frameId = $frame['id'] ?? $frame['filename'];
                    $hasCustomCrop = !empty($frame['crop']);
                @endphp
                <div onclick="selectActiveFrame('{{ $frameId }}')" 
                     id="thumb-card-{{ $frameId }}"
                     data-frame-id="{{ $frameId }}"
                     data-filename="{{ $frame['filename'] }}"
                     data-time="{{ $frame['formatted_time'] ?? '00:00' }}"
                     data-custom-crop="{{ $hasCustomCrop ? 'true' : 'false' }}"
                     class="frame-thumb-card relative shrink-0 w-36 cursor-pointer rounded-2xl overflow-hidden border-2 transition-all duration-300 group bg-surface-raised hover:border-amber-500/70 border-gray-800 shadow-md hover:shadow-xl hover:-translate-y-0.5">
                    
                    <div class="aspect-video w-full overflow-hidden bg-black relative">
                        <img src="{{ route('project.frame.image', ['slug' => $project['slug'], 'filename' => $frame['filename']]) }}" 
                             alt="Frame {{ $index + 1 }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        
                        <div class="absolute top-1.5 left-1.5 bg-black/85 px-1.5 py-0.5 rounded text-[10px] font-display font-extrabold text-white backdrop-blur shadow">
                            #{{ sprintf('%02d', $index + 1) }}
                        </div>
                        <div class="absolute bottom-1.5 right-1.5 bg-black/85 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium text-gray-300 backdrop-blur shadow">
                            {{ $frame['formatted_time'] ?? '00:00' }}
                        </div>
                    </div>

                    <div class="p-2 bg-surface-base flex items-center justify-between text-[11px]">
                        <span class="text-gray-300 font-semibold truncate max-w-[85px]">Step {{ $index + 1 }}</span>
                        <span class="custom-badge text-[9px] px-1.5 py-0.5 rounded-full font-bold {{ $hasCustomCrop ? 'bg-amber-950 text-amber-400 border border-amber-800/40' : 'hidden' }}">
                            Custom
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    // --- Application State ---
    const projectSlug = @json($project['slug']);
    const frames = @json($selectedFrames);
    const initialCrop = @json($currentCrop);

    let activeFrameId = frames.length > 0 ? (frames[0].id || frames[0].filename) : null;
    let activePreset = initialCrop.preset || 'facebook-portrait';
    let activeRatioStr = initialCrop.ratio || '4:5';
    let activeAlignment = initialCrop.default_alignment || 'center';

    const perFrameCrops = {};
    frames.forEach(f => {
        const id = f.id || f.filename;
        if (f.crop) {
            perFrameCrops[id] = { ...f.crop };
        }
    });

    let imgDisplay = { x: 0, y: 0, width: 0, height: 0, naturalWidth: 1920, naturalHeight: 1080 };
    let cropBoxDisplay = { x: 0, y: 0, width: 0, height: 0 };

    let isDragging = false;
    let isResizing = false;
    let resizeHandle = null;
    let startPointer = { x: 0, y: 0 };
    let startBox = { x: 0, y: 0, width: 0, height: 0 };

    const container = document.getElementById('cropper-container');
    const imageEl = document.getElementById('cropper-image');
    const cropBoxEl = document.getElementById('crop-box');
    const previewCanvas = document.getElementById('preview-canvas');

    document.addEventListener('DOMContentLoaded', () => {
        highlightActivePresetButton(activeRatioStr);
        if (activeFrameId) {
            selectActiveFrame(activeFrameId);
        }
        setupInteractions();
    });

    // --- Frame Switching ---
    function selectActiveFrame(frameId) {
        activeFrameId = frameId;
        const frame = frames.find(f => (f.id || f.filename) === frameId);
        if (!frame) return;

        document.querySelectorAll('.frame-thumb-card').forEach(el => {
            if (el.dataset.frameId === frameId) {
                el.classList.add('border-amber-500', 'ring-4', 'ring-amber-500/30', 'scale-[1.02]', 'bg-surface-overlay');
                el.classList.remove('border-gray-800');
            } else {
                el.classList.remove('border-amber-500', 'ring-4', 'ring-amber-500/30', 'scale-[1.02]', 'bg-surface-overlay');
                el.classList.add('border-gray-800');
            }
        });

        document.getElementById('active-frame-name').innerText = frame.filename;
        document.getElementById('active-frame-time').innerText = frame.formatted_time || '00:00';

        const imageUrl = `{{ url('/project') }}/${projectSlug}/frame-image/${frame.filename}`;
        imageEl.style.opacity = '0.3';
        imageEl.src = imageUrl;
        updateFrameNavigationButtons();
    }

    function navigateFrame(direction) {
        if (!activeFrameId || frames.length < 2) return;

        const currentIndex = frames.findIndex(frame => (frame.id || frame.filename) === activeFrameId);
        if (currentIndex === -1) return;

        const nextIndex = currentIndex + direction;
        if (nextIndex < 0 || nextIndex >= frames.length) return;

        selectActiveFrame(frames[nextIndex].id || frames[nextIndex].filename);
    }

    function updateFrameNavigationButtons() {
        const currentIndex = frames.findIndex(frame => (frame.id || frame.filename) === activeFrameId);
        const previousButton = document.getElementById('prev-frame-btn');
        const nextButton = document.getElementById('next-frame-btn');

        if (previousButton) previousButton.disabled = currentIndex <= 0;
        if (nextButton) nextButton.disabled = currentIndex === -1 || currentIndex >= frames.length - 1;
    }

    function onImageLoaded() {
        imageEl.style.opacity = '1';
        imgDisplay.naturalWidth = imageEl.naturalWidth || 1920;
        imgDisplay.naturalHeight = imageEl.naturalHeight || 1080;

        recalculateImageDisplayBounds();
        applyOrInitCropBoxForActiveFrame();
        updatePreview();
    }

    window.addEventListener('resize', () => {
        if (!imageEl.complete) return;
        recalculateImageDisplayBounds();
        applyOrInitCropBoxForActiveFrame();
        updatePreview();
    });

    function recalculateImageDisplayBounds() {
        const containerRect = container.getBoundingClientRect();
        const imgRect = imageEl.getBoundingClientRect();

        imgDisplay.x = imgRect.left - containerRect.left;
        imgDisplay.y = imgRect.top - containerRect.top;
        imgDisplay.width = imgRect.width;
        imgDisplay.height = imgRect.height;
    }

    // --- Aspect Ratio Presets ---
    function selectPreset(ratioStr, presetName, width, height) {
        activeRatioStr = ratioStr;
        activePreset = presetName;

        highlightActivePresetButton(ratioStr);
        updatePresetTips(ratioStr, width, height);

        centerCropBox();
        updatePreview();
    }

    function highlightActivePresetButton(ratioStr) {
        document.querySelectorAll('.preset-btn').forEach(btn => {
            if (btn.dataset.ratio === ratioStr) {
                btn.className = 'preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 bg-amber-600 text-white border-amber-500 shadow-md shadow-amber-900/40';
            } else {
                btn.className = 'preset-btn px-3.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 bg-gray-800/80 hover:bg-gray-700 text-gray-300 border-gray-700/80';
            }
        });

        document.getElementById('box-ratio-tag').innerText = ratioStr.toUpperCase();
        document.getElementById('preview-aspect-tag').innerText = ratioStr.toUpperCase();
    }

    function updatePresetTips(ratioStr, width, height) {
        const tipEl = document.getElementById('ratio-tip');
        const resEl = document.getElementById('preview-res-label');

        if (ratioStr === '4:5') {
            resEl.innerText = '1080 × 1350 px';
            tipEl.innerHTML = `<p class="font-bold text-amber-400 mb-1">Facebook & Instagram (4:5 Portrait)</p>
            <p>4:5 portrait fills the phone screen vertically without letterboxing. Frame the hero food item in the middle third for maximum engagement.</p>`;
        } else if (ratioStr === '1:1') {
            resEl.innerText = '1080 × 1080 px';
            tipEl.innerHTML = `<p class="font-bold text-amber-400 mb-1">Instagram Post & Carousel (1:1 Square)</p>
            <p>Standard square format. Ideal for multi-slide carousel recipe cards and step-by-step ingredient collections.</p>`;
        } else if (ratioStr === '16:9') {
            resEl.innerText = '1920 × 1080 px';
            tipEl.innerHTML = `<p class="font-bold text-amber-400 mb-1">YouTube & Website (16:9 Landscape)</p>
            <p>Traditional widescreen framing. Best suited for desktop food blogs, video cover stills, and YouTube thumbnails.</p>`;
        } else if (ratioStr === '9:16') {
            resEl.innerText = '1080 × 1920 px';
            tipEl.innerHTML = `<p class="font-bold text-amber-400 mb-1">Reels, Shorts & Stories (9:16 Vertical)</p>
            <p>Full-screen smartphone format for TikTok, Instagram Reels, and YouTube Shorts recipe clips.</p>`;
        } else {
            resEl.innerText = 'Freeform';
            tipEl.innerHTML = `<p class="font-bold text-amber-400 mb-1">Freeform Crop</p>
            <p>Freely scale width and height handles to isolate any section of the cooking frame.</p>`;
        }
    }

    function getTargetAspectRatioNumber() {
        if (activeRatioStr === 'free') return null;
        const parts = activeRatioStr.split(':');
        if (parts.length === 2 && parseFloat(parts[1]) > 0) {
            return parseFloat(parts[0]) / parseFloat(parts[1]);
        }
        return 4 / 5;
    }

    // --- Crop Box Geometry ---
    function applyOrInitCropBoxForActiveFrame() {
        const saved = perFrameCrops[activeFrameId];
        if (saved && saved.width && saved.height) {
            const scaleX = imgDisplay.width / imgDisplay.naturalWidth;
            const scaleY = imgDisplay.height / imgDisplay.naturalHeight;

            cropBoxDisplay.x = imgDisplay.x + (saved.x * scaleX);
            cropBoxDisplay.y = imgDisplay.y + (saved.y * scaleY);
            cropBoxDisplay.width = saved.width * scaleX;
            cropBoxDisplay.height = saved.height * scaleY;
        } else {
            centerCropBox();
        }
        renderCropBoxDOM();
    }

    function centerCropBox() {
        const ratio = getTargetAspectRatioNumber();
        const imgW = imgDisplay.width;
        const imgH = imgDisplay.height;

        if (ratio) {
            const currentImgRatio = imgW / imgH;
            if (currentImgRatio > ratio) {
                cropBoxDisplay.height = imgH * 0.94;
                cropBoxDisplay.width = cropBoxDisplay.height * ratio;
            } else {
                cropBoxDisplay.width = imgW * 0.94;
                cropBoxDisplay.height = cropBoxDisplay.width / ratio;
            }
        } else {
            cropBoxDisplay.width = imgW * 0.8;
            cropBoxDisplay.height = imgH * 0.8;
        }

        cropBoxDisplay.x = imgDisplay.x + (imgW - cropBoxDisplay.width) / 2;
        cropBoxDisplay.y = imgDisplay.y + (imgH - cropBoxDisplay.height) / 2;

        renderCropBoxDOM();
        recordActiveFrameCrop();
    }

    function alignActiveCrop(alignment) {
        activeAlignment = alignment;
        const imgH = imgDisplay.height;

        if (alignment === 'top') {
            cropBoxDisplay.y = imgDisplay.y;
        } else if (alignment === 'bottom') {
            cropBoxDisplay.y = imgDisplay.y + (imgH - cropBoxDisplay.height);
        } else {
            cropBoxDisplay.y = imgDisplay.y + (imgH - cropBoxDisplay.height) / 2;
        }

        renderCropBoxDOM();
        updatePreview();
        recordActiveFrameCrop();
    }

    function maximizeCropBox() {
        const ratio = getTargetAspectRatioNumber();
        const imgW = imgDisplay.width;
        const imgH = imgDisplay.height;

        if (ratio) {
            const currentImgRatio = imgW / imgH;
            if (currentImgRatio > ratio) {
                cropBoxDisplay.height = imgH;
                cropBoxDisplay.width = imgH * ratio;
            } else {
                cropBoxDisplay.width = imgW;
                cropBoxDisplay.height = imgW / ratio;
            }
        } else {
            cropBoxDisplay.width = imgW;
            cropBoxDisplay.height = imgH;
        }

        cropBoxDisplay.x = imgDisplay.x + (imgW - cropBoxDisplay.width) / 2;
        cropBoxDisplay.y = imgDisplay.y + (imgH - cropBoxDisplay.height) / 2;

        renderCropBoxDOM();
        updatePreview();
        recordActiveFrameCrop();
    }

    function renderCropBoxDOM() {
        cropBoxEl.style.left = `${cropBoxDisplay.x}px`;
        cropBoxEl.style.top = `${cropBoxDisplay.y}px`;
        cropBoxEl.style.width = `${cropBoxDisplay.width}px`;
        cropBoxEl.style.height = `${cropBoxDisplay.height}px`;

        const naturalCoords = getActiveFrameNaturalCoordinates();
        document.getElementById('crop-coords-label').innerText = 
            `X: ${naturalCoords.x}, Y: ${naturalCoords.y} | ${naturalCoords.width} × ${naturalCoords.height} px`;
    }

    function moveCropBoxByKeyboard(dx, dy) {
        if (!activeFrameId || !imgDisplay.width || !imgDisplay.height) return;

        const minX = imgDisplay.x;
        const maxX = imgDisplay.x + imgDisplay.width - cropBoxDisplay.width;
        const minY = imgDisplay.y;
        const maxY = imgDisplay.y + imgDisplay.height - cropBoxDisplay.height;

        cropBoxDisplay.x = Math.max(minX, Math.min(maxX, cropBoxDisplay.x + dx));
        cropBoxDisplay.y = Math.max(minY, Math.min(maxY, cropBoxDisplay.y + dy));

        renderCropBoxDOM();
        updatePreview();
        recordActiveFrameCrop();
    }

    function updatePreview() {
        if (!imageEl.complete || imageEl.naturalWidth === 0) return;

        const natural = getActiveFrameNaturalCoordinates();
        const ctx = previewCanvas.getContext('2d');

        const targetW = 400;
        const targetH = Math.round(targetW * (natural.height / natural.width));
        previewCanvas.width = targetW;
        previewCanvas.height = targetH;

        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        ctx.drawImage(
            imageEl,
            natural.x, natural.y, natural.width, natural.height,
            0, 0, targetW, targetH
        );
    }

    function getActiveFrameNaturalCoordinates() {
        const scaleX = imgDisplay.naturalWidth / imgDisplay.width;
        const scaleY = imgDisplay.naturalHeight / imgDisplay.height;

        const relX = cropBoxDisplay.x - imgDisplay.x;
        const relY = cropBoxDisplay.y - imgDisplay.y;

        const natX = Math.round(Math.max(0, relX * scaleX));
        const natY = Math.round(Math.max(0, relY * scaleY));
        const natW = Math.round(Math.min(imgDisplay.naturalWidth - natX, cropBoxDisplay.width * scaleX));
        const natH = Math.round(Math.min(imgDisplay.naturalHeight - natY, cropBoxDisplay.height * scaleY));

        return { x: natX, y: natY, width: natW, height: natH };
    }

    function recordActiveFrameCrop() {
        if (!activeFrameId) return;
        const coords = getActiveFrameNaturalCoordinates();
        perFrameCrops[activeFrameId] = {
            ...coords,
            ratio: activeRatioStr,
            alignment: activeAlignment,
        };

        const card = document.getElementById(`thumb-card-${activeFrameId}`);
        if (card) {
            const badge = card.querySelector('.custom-badge');
            if (badge) badge.classList.remove('hidden');
        }
    }

    // --- Interactive Drag & Resize ---
    function setupInteractions() {
        cropBoxEl.addEventListener('mousedown', (e) => {
            if (e.target.classList.contains('resize-handle')) return;
            isDragging = true;
            startPointer = { x: e.clientX, y: e.clientY };
            startBox = { ...cropBoxDisplay };
            e.preventDefault();
        });

        document.querySelectorAll('.resize-handle').forEach(h => {
            h.addEventListener('mousedown', (e) => {
                isResizing = true;
                resizeHandle = h.dataset.handle;
                startPointer = { x: e.clientX, y: e.clientY };
                startBox = { ...cropBoxDisplay };
                e.stopPropagation();
                e.preventDefault();
            });
        });

        window.addEventListener('mousemove', (e) => {
            if (isDragging) {
                const dx = e.clientX - startPointer.x;
                const dy = e.clientY - startPointer.y;

                let nextX = startBox.x + dx;
                let nextY = startBox.y + dy;

                const minX = imgDisplay.x;
                const maxX = imgDisplay.x + imgDisplay.width - cropBoxDisplay.width;
                const minY = imgDisplay.y;
                const maxY = imgDisplay.y + imgDisplay.height - cropBoxDisplay.height;

                cropBoxDisplay.x = Math.max(minX, Math.min(maxX, nextX));
                cropBoxDisplay.y = Math.max(minY, Math.min(maxY, nextY));

                renderCropBoxDOM();
                updatePreview();
            } else if (isResizing) {
                handleResizeDrag(e);
            }
        });

        window.addEventListener('mouseup', () => {
            if (isDragging || isResizing) {
                isDragging = false;
                isResizing = false;
                resizeHandle = null;
                recordActiveFrameCrop();
            }
        });
    }

    window.addEventListener('keydown', (event) => {
        if (event.target.matches('input, textarea, select')) return;

        if (event.altKey && event.key === 'ArrowLeft') {
            event.preventDefault();
            navigateFrame(-1);
            return;
        }

        if (event.altKey && event.key === 'ArrowRight') {
            event.preventDefault();
            navigateFrame(1);
            return;
        }

        const movement = event.shiftKey ? 10 : 2;
        const movements = {
            ArrowLeft: [-movement, 0],
            ArrowRight: [movement, 0],
            ArrowUp: [0, -movement],
            ArrowDown: [0, movement],
        };
        const [dx, dy] = movements[event.key] || [];

        if (dx === undefined || dy === undefined) return;

        event.preventDefault();
        moveCropBoxByKeyboard(dx, dy);
    });

    function handleResizeDrag(e) {
        const dx = e.clientX - startPointer.x;
        const dy = e.clientY - startPointer.y;
        const ratio = getTargetAspectRatioNumber();

        let newW = startBox.width;
        let newH = startBox.height;
        let newX = startBox.x;
        let newY = startBox.y;

        const minSize = 50;

        if (resizeHandle.includes('e')) {
            newW = Math.max(minSize, startBox.width + dx);
            if (ratio) newH = newW / ratio;
        } else if (resizeHandle.includes('w')) {
            newW = Math.max(minSize, startBox.width - dx);
            if (ratio) newH = newW / ratio;
            newX = startBox.x + (startBox.width - newW);
        }

        if (resizeHandle.includes('s')) {
            newH = Math.max(minSize, startBox.height + dy);
            if (ratio) newW = newH * ratio;
        } else if (resizeHandle.includes('n')) {
            newH = Math.max(minSize, startBox.height - dy);
            if (ratio) newW = newH * ratio;
            newY = startBox.y + (startBox.height - newH);
        }

        if (newX < imgDisplay.x) {
            newW -= (imgDisplay.x - newX);
            newX = imgDisplay.x;
            if (ratio) newH = newW / ratio;
        }
        if (newY < imgDisplay.y) {
            newH -= (imgDisplay.y - newY);
            newY = imgDisplay.y;
            if (ratio) newW = newH * ratio;
        }
        if (newX + newW > imgDisplay.x + imgDisplay.width) {
            newW = imgDisplay.x + imgDisplay.width - newX;
            if (ratio) newH = newW / ratio;
        }
        if (newY + newH > imgDisplay.y + imgDisplay.height) {
            newH = imgDisplay.y + imgDisplay.height - newY;
            if (ratio) newW = newH * ratio;
        }

        cropBoxDisplay.width = Math.max(minSize, newW);
        cropBoxDisplay.height = Math.max(minSize, newH);
        cropBoxDisplay.x = newX;
        cropBoxDisplay.y = newY;

        renderCropBoxDOM();
        updatePreview();
    }

    // --- Reset & Batch Actions ---
    function resetCurrentCrop() {
        if (!activeFrameId) return;
        delete perFrameCrops[activeFrameId];
        centerCropBox();
        updatePreview();

        const card = document.getElementById(`thumb-card-${activeFrameId}`);
        if (card) {
            const badge = card.querySelector('.custom-badge');
            if (badge) badge.classList.add('hidden');
        }
    }

    function applyToAllFrames() {
        const activeCoords = getActiveFrameNaturalCoordinates();
        frames.forEach(f => {
            const id = f.id || f.filename;
            perFrameCrops[id] = {
                ...activeCoords,
                ratio: activeRatioStr,
                alignment: activeAlignment,
            };

            const card = document.getElementById(`thumb-card-${id}`);
            if (card) {
                const badge = card.querySelector('.custom-badge');
                if (badge) badge.classList.remove('hidden');
            }
        });

        document.getElementById('crop-status-indicator').innerText = 
            `Applied ${activeRatioStr} crop across all ${frames.length} frames`;
        
        saveAllCrops(false, true);
    }

    // --- Persistence (AJAX Save) ---
    function saveAllCrops(proceedAfter = false, silent = false) {
        recordActiveFrameCrop();

        const saveBtn = document.getElementById('save-btn');
        const saveText = document.getElementById('save-btn-text');
        if (!silent) {
            saveBtn.disabled = true;
            saveText.innerText = 'Saving...';
        }

        const payload = {
            _token: '{{ csrf_token() }}',
            ratio: activeRatioStr,
            preset: activePreset,
            default_alignment: activeAlignment,
            frames: perFrameCrops,
            global_crop: getActiveFrameNaturalCoordinates(),
        };

        fetch("{{ route('project.crop.save', $project['slug']) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (!silent) {
                saveBtn.disabled = false;
                saveText.innerText = 'Save Framing';
            }
            if (data.success) {
                if (proceedAfter) {
                    window.location.href = "{{ route('project.watermark', $project['slug']) }}";
                } else if (!silent) {
                    showToast('Crop settings saved successfully!');
                }
            } else {
                alert('Could not save crop settings: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            if (!silent) {
                saveBtn.disabled = false;
                saveText.innerText = 'Save Framing';
            }
            alert('Error saving crop: ' + err.message);
        });
    }

    function saveAndProceed() {
        saveAllCrops(true);
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-10 right-10 z-50 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl font-bold text-sm flex items-center gap-2 animate-bounce border border-emerald-400/40';
        toast.innerHTML = `<span>✓</span><span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
</script>
@endsection
