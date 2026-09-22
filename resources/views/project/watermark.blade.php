@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'watermark'])

@section('content')
<div class="max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-1">
                <a href="{{ route('project.crop', $project['slug']) }}" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    <span>← Crop & Framing</span>
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-amber-500 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">Step 4: Watermark & Branding</span>
                <span class="text-gray-600">/</span>
                <span class="text-gray-500">Step 5: Recipe Steps</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">{{ $project['name'] ?? 'Watermark Studio' }}</h1>
            <p class="text-sm text-gray-400 mt-1 flex items-center gap-2">
                <span>Protect and brand your recipe step cards with your logo or social handle.</span>
                <span class="text-gray-600">•</span>
                <span class="text-gray-300 font-mono text-xs">{{ count($selectedFrames) }} frames in collage</span>
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" onclick="resetWatermark()" class="px-3.5 py-2 bg-gray-800/90 hover:bg-gray-750 text-gray-300 border border-gray-700/80 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shadow-sm hover:border-gray-600">
                <span>↺</span>
                <span>Reset</span>
            </button>

            <button type="button" onclick="saveWatermark(false)" id="save-wm-btn" class="px-4 py-2 bg-gray-800/90 hover:bg-gray-750 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm hover:border-amber-500/60">
                <span>💾</span>
                <span id="save-wm-text">Save Watermark</span>
            </button>

            <button type="button" onclick="saveAndProceed()" id="proceed-btn" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-5 py-2 rounded-xl font-bold text-xs tracking-wide transition shadow-lg shadow-amber-900/30 flex items-center gap-2 active:scale-95">
                <span>Next: Recipe Steps (Step 5)</span>
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

    <!-- Main Watermark Studio Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
        <!-- Left Panel: Watermark Customization Controls (5 cols) -->
        <div class="lg:col-span-5 bg-[#1a1a2e]/90 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col gap-6">
            <!-- 1. Watermark Type Selector -->
            <div>
                <label class="block text-xs uppercase tracking-wider text-gray-400 font-bold mb-2.5">Watermark Format</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="setWatermarkType('text')" id="type-btn-text" class="type-btn p-3 rounded-2xl border transition text-center flex flex-col items-center justify-center gap-1 shadow-sm">
                        <span class="text-lg">🏷️</span>
                        <span class="text-xs font-bold">Text Handle</span>
                    </button>

                    <button type="button" onclick="setWatermarkType('image')" id="type-btn-image" class="type-btn p-3 rounded-2xl border transition text-center flex flex-col items-center justify-center gap-1 shadow-sm">
                        <span class="text-lg">🖼️</span>
                        <span class="text-xs font-bold">Logo Image</span>
                    </button>

                    <button type="button" onclick="setWatermarkType('none')" id="type-btn-none" class="type-btn p-3 rounded-2xl border transition text-center flex flex-col items-center justify-center gap-1 shadow-sm">
                        <span class="text-lg">🚫</span>
                        <span class="text-xs font-bold">Disabled</span>
                    </button>
                </div>
            </div>

            <!-- 2. Text Configuration Section -->
            <div id="section-text-options" class="flex flex-col gap-4">
                <div>
                    <label for="wm-text-input" class="block text-xs font-semibold text-gray-300 mb-1.5">Brand Text / Social Handle</label>
                    <input type="text" id="wm-text-input" value="{{ $watermarkConfig['text'] ?? '@RecipeFrameStudio' }}" placeholder="e.g. @YourKitchenName" class="block w-full bg-[#12192b] border border-gray-700/80 rounded-xl text-gray-100 text-sm px-4 py-2.5 font-medium focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-inner">
                </div>

                <!-- Text Style / Contrast Shield -->
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-[#12192b] border border-gray-800 cursor-pointer hover:border-gray-700 transition">
                        <input type="checkbox" id="wm-pill-check" {{ !empty($watermarkConfig['has_pill']) ? 'checked' : '' }} onchange="updateWatermarkLive()" class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 bg-gray-900 border-gray-700">
                        <span class="text-xs text-gray-200 font-semibold select-none">Contrast Pill</span>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-[#12192b] border border-gray-800 cursor-pointer hover:border-gray-700 transition">
                        <input type="checkbox" id="wm-shadow-check" {{ !empty($watermarkConfig['has_shadow']) ? 'checked' : '' }} onchange="updateWatermarkLive()" class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400 bg-gray-900 border-gray-700">
                        <span class="text-xs text-gray-200 font-semibold select-none">Drop Shadow</span>
                    </label>
                </div>

                <!-- Preset Colors -->
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-2">Text Color</label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="setTextColor('#ffffff')" class="w-7 h-7 rounded-full bg-white border-2 border-white shadow-sm hover:scale-110 transition" title="Pure White"></button>
                        <button type="button" onclick="setTextColor('#fbbf24')" class="w-7 h-7 rounded-full bg-amber-400 border-2 border-amber-400 shadow-sm hover:scale-110 transition" title="Amber Gold"></button>
                        <button type="button" onclick="setTextColor('#34d399')" class="w-7 h-7 rounded-full bg-emerald-400 border-2 border-emerald-400 shadow-sm hover:scale-110 transition" title="Emerald"></button>
                        <button type="button" onclick="setTextColor('#f87171')" class="w-7 h-7 rounded-full bg-red-400 border-2 border-red-400 shadow-sm hover:scale-110 transition" title="Coral"></button>
                        <input type="color" id="wm-color-picker" value="{{ $watermarkConfig['color'] ?? '#ffffff' }}" onchange="setTextColor(this.value)" class="w-8 h-8 rounded-lg bg-transparent cursor-pointer border border-gray-700 ml-1">
                    </div>
                </div>
            </div>

            <!-- 3. Logo Image Upload Section -->
            <div id="section-image-options" class="hidden flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-gray-300">Channel Logo / Badge</label>
                    <button type="button" onclick="resetToDefaultLogo()" class="text-[11px] text-amber-400 hover:text-amber-300 underline cursor-pointer">Use Default Logo</button>
                </div>
                
                <div id="logo-dropzone" onclick="document.getElementById('logo-file-input').click()" class="border-2 border-dashed border-gray-700 hover:border-amber-500/70 rounded-2xl p-5 text-center cursor-pointer transition bg-[#12192b]/50 group">
                    <input type="file" id="logo-file-input" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden" onchange="handleLogoUpload(this.files)">
                    
                    <div id="logo-preview-box" class="{{ !empty($watermarkConfig['image_path']) ? '' : 'hidden' }} mb-2">
                        <div class="p-2.5 bg-black/40 rounded-2xl inline-block border border-gray-800 shadow-md">
                            <img id="logo-preview-img" src="{{ !empty($watermarkConfig['image_path']) ? route('project.watermark.logo.image', $project['slug']) : '' }}" alt="Logo Preview" class="max-h-20 max-w-full mx-auto object-contain">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2 font-medium">Click or drag & drop to replace with custom logo</p>
                    </div>

                    <div id="logo-upload-prompt" class="{{ !empty($watermarkConfig['image_path']) ? 'hidden' : '' }}">
                        <div class="text-3xl mb-1 group-hover:scale-110 transition-transform">📁</div>
                        <p class="text-xs text-gray-300 font-semibold">Click or Drag & Drop Logo</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Transparent PNG works best</p>
                    </div>

                    <span id="logo-uploading-spin" class="hidden text-xs text-amber-400 font-bold">Uploading logo...</span>
                </div>
            </div>

            <!-- 4. 9-Point Positioning Grid -->
            <div>
                <label class="block text-xs uppercase tracking-wider text-gray-400 font-bold mb-2.5">Placement Position</label>
                <div class="grid grid-cols-3 gap-2 max-w-[240px]">
                    @php
                        $positions = [
                            'top-left' => '↖ Top-L',
                            'top-center' => '↑ Top',
                            'top-right' => '↗ Top-R',
                            'left' => '← Left',
                            'center' => '• Center',
                            'right' => '→ Right',
                            'bottom-left' => '↙ Btm-L',
                            'bottom-center' => '↓ Btm',
                            'bottom-right' => '↘ Btm-R',
                        ];
                    @endphp
                    @foreach($positions as $posKey => $posLabel)
                        <button type="button" onclick="setPosition('{{ $posKey }}')" id="pos-btn-{{ $posKey }}" class="pos-btn py-2 rounded-xl text-[11px] font-bold border transition text-center shadow-sm" data-position="{{ $posKey }}">
                            {{ $posLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- 5. Fine-Tuning Controls -->
            <div class="flex flex-col gap-4 pt-2 border-t border-gray-800">
                <!-- Size / Scale -->
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-300">Watermark Scale</span>
                        <span id="size-val" class="text-amber-400 font-mono">{{ $watermarkConfig['size'] ?? 18 }}%</span>
                    </div>
                    <input type="range" id="size-slider" min="5" max="40" value="{{ $watermarkConfig['size'] ?? 18 }}" oninput="updateWatermarkLive()" class="w-full accent-amber-500 h-1.5 bg-gray-800 rounded-lg cursor-pointer">
                </div>

                <!-- Opacity -->
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-300">Opacity / Transparency</span>
                        <span id="opacity-val" class="text-amber-400 font-mono">{{ $watermarkConfig['opacity'] ?? 85 }}%</span>
                    </div>
                    <input type="range" id="opacity-slider" min="10" max="100" value="{{ $watermarkConfig['opacity'] ?? 85 }}" oninput="updateWatermarkLive()" class="w-full accent-amber-500 h-1.5 bg-gray-800 rounded-lg cursor-pointer">
                </div>

                <!-- Edge Margin (Input Field - Any value) -->
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                        <label for="margin-input" class="text-gray-300">Edge Spacing (Margin)</label>
                        <span class="text-[11px] text-amber-400 font-mono font-medium">Any value</span>
                    </div>
                    <div class="relative flex items-center">
                        <input type="number" 
                               id="margin-input" 
                               value="{{ $watermarkConfig['margin'] ?? 30 }}" 
                               oninput="updateWatermarkLive()" 
                               placeholder="30" 
                               class="w-full bg-[#12192b] border border-gray-700/80 rounded-xl text-amber-400 font-mono text-sm px-4 py-2.5 pr-12 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition shadow-inner">
                        <span class="absolute right-4 text-xs text-gray-400 font-mono font-bold pointer-events-none">px</span>
                    </div>
                    <div class="flex items-center justify-between mt-1.5 text-[11px] text-gray-500">
                        <span>Distance from outer edge</span>
                        <div class="flex items-center gap-1.5 font-mono text-[10px]">
                            <button type="button" onclick="setMarginValue(0)" class="px-2 py-0.5 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 transition cursor-pointer">0px</button>
                            <button type="button" onclick="setMarginValue(15)" class="px-2 py-0.5 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 transition cursor-pointer">15px</button>
                            <button type="button" onclick="setMarginValue(30)" class="px-2 py-0.5 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 transition cursor-pointer">30px</button>
                            <button type="button" onclick="setMarginValue(60)" class="px-2 py-0.5 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-amber-400 transition cursor-pointer">60px</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Live Composite Viewport & Tips (7 cols) -->
        <div class="lg:col-span-7 flex flex-col gap-6">
            <!-- Live Preview Viewport -->
            <div class="bg-[#1a1a2e]/90 backdrop-blur border border-gray-800/80 rounded-3xl p-6 shadow-2xl flex flex-col">
                <div class="flex items-center justify-between mb-3 text-xs text-gray-400">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white">Live Composite Preview</span>
                        <span class="text-gray-600">•</span>
                        <span id="active-frame-label" class="font-mono text-amber-400">Frame #1</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-lg bg-[#12192b] border border-gray-800 font-mono text-[11px] text-gray-300" id="wm-status-tag">
                        Watermark Active
                    </span>
                </div>

                <!-- Canvas Viewport -->
                <div class="relative bg-black/85 rounded-2xl overflow-hidden flex items-center justify-center p-3 min-h-[460px] max-h-[560px] border border-gray-800/90 shadow-inner">
                    <canvas id="wm-canvas" class="max-h-[530px] max-w-full rounded-xl shadow-2xl object-contain"></canvas>
                </div>

                <!-- Viewport Footer -->
                <div class="mt-3 flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-800 font-mono">
                    <span>Active Resolution: <strong id="canvas-dim" class="text-gray-200">1080 × 1350 px</strong></span>
                    <span>Applied to: <strong class="text-amber-400">All {{ count($selectedFrames) }} recipe steps</strong></span>
                </div>
            </div>

            <!-- Recipe Branding Advice Card -->
            <div class="bg-[#1a1a2e]/90 backdrop-blur border border-gray-800/80 rounded-3xl p-5 shadow-2xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-2xl shrink-0">
                    💡
                </div>
                <div class="text-xs text-gray-300 leading-relaxed">
                    <p class="font-bold text-white mb-0.5">Brand Consistency Tip</p>
                    <p class="text-gray-400">Placing your handle in the bottom-right with a contrast pill keeps it visible across bright flour, dark frying pans, and colorful plating without distracting viewers from the cooking steps.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Frame Switcher (Check across backgrounds) -->
    <div class="bg-[#1a1a2e]/90 backdrop-blur border border-gray-800/80 rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-white uppercase tracking-wider">Test Against Frames ({{ count($selectedFrames) }})</span>
                <span class="text-[11px] text-gray-400">• Click any frame to test watermark legibility against different cooking shots</span>
            </div>
            <div class="text-xs text-emerald-400 font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Automatic Multi-Frame Sync</span>
            </div>
        </div>

        <div class="flex items-center gap-3.5 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-700">
            @foreach($selectedFrames as $index => $frame)
                @php
                    $frameId = $frame['id'] ?? $frame['filename'];
                    $imageUrl = route('project.frame.cropped', ['slug' => $project['slug'], 'filename' => $frame['filename']]);
                @endphp
                <div onclick="selectPreviewFrame('{{ $frameId }}', '{{ $imageUrl }}', '{{ $index + 1 }}')" 
                     id="thumb-card-{{ $frameId }}"
                     data-frame-id="{{ $frameId }}"
                     class="frame-thumb-card relative shrink-0 w-36 cursor-pointer rounded-2xl overflow-hidden border-2 transition-all duration-300 group bg-[#16213e] hover:border-amber-500/70 border-gray-800 shadow-md hover:shadow-xl hover:-translate-y-0.5">
                    
                    <div class="aspect-video w-full overflow-hidden bg-black relative">
                        <img src="{{ $imageUrl }}" 
                             alt="Frame {{ $index + 1 }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        
                        <div class="absolute top-1.5 left-1.5 bg-black/85 px-1.5 py-0.5 rounded text-[10px] font-extrabold text-white backdrop-blur shadow">
                            #{{ sprintf('%02d', $index + 1) }}
                        </div>
                    </div>

                    <div class="p-2 bg-[#12192b] flex items-center justify-between text-[11px]">
                        <span class="text-gray-300 font-semibold truncate max-w-[90px]">Step {{ $index + 1 }}</span>
                        <span class="text-gray-500 font-mono text-[10px]">{{ $frame['formatted_time'] ?? '' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectSlug = @json($project['slug']);
    const frames = @json($selectedFrames);
    const initialWm = @json($watermarkConfig);

    let activeType = initialWm.type || 'image';
    let activePosition = initialWm.position || 'bottom-right';
    let activeColor = initialWm.color || '#ffffff';
    let logoImageSrc = initialWm.image_path ? `{{ route('project.watermark.logo.image', $project['slug']) }}` : `{{ asset('images/default-watermark.png') }}`;
    let logoImgElement = null;

    let activeFrameId = frames.length > 0 ? (frames[0].id || frames[0].filename) : null;
    let activeFrameUrl = frames.length > 0 ? `{{ url('/project') }}/${projectSlug}/frame/${frames[0].filename}/cropped` : '';
    let currentImageElement = new Image();

    const canvas = document.getElementById('wm-canvas');
    const ctx = canvas.getContext('2d');

    document.addEventListener('DOMContentLoaded', () => {
        setWatermarkType(activeType);
        setPosition(activePosition);
        
        if (logoImageSrc) {
            logoImgElement = new Image();
            logoImgElement.crossOrigin = 'anonymous';
            logoImgElement.src = logoImageSrc;
            logoImgElement.onload = () => updateWatermarkLive();
        }

        if (frames.length > 0) {
            selectPreviewFrame(activeFrameId, activeFrameUrl, 1);
        }

        document.getElementById('wm-text-input').addEventListener('input', updateWatermarkLive);
    });

    // --- Frame Switching ---
    function selectPreviewFrame(frameId, imageUrl, stepNumber) {
        activeFrameId = frameId;
        activeFrameUrl = imageUrl;

        document.querySelectorAll('.frame-thumb-card').forEach(el => {
            if (el.dataset.frameId === frameId) {
                el.classList.add('border-amber-500', 'ring-4', 'ring-amber-500/30', 'scale-[1.02]', 'bg-[#1b2545]');
                el.classList.remove('border-gray-800');
            } else {
                el.classList.remove('border-amber-500', 'ring-4', 'ring-amber-500/30', 'scale-[1.02]', 'bg-[#1b2545]');
                el.classList.add('border-gray-800');
            }
        });

        document.getElementById('active-frame-label').innerText = `Step #${stepNumber}`;

        currentImageElement = new Image();
        currentImageElement.crossOrigin = 'anonymous';
        currentImageElement.src = imageUrl;
        currentImageElement.onload = () => updateWatermarkLive();
    }

    // --- Type & Position Controls ---
    function setWatermarkType(type) {
        activeType = type;

        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.className = 'type-btn p-3 rounded-2xl border transition text-center flex flex-col items-center justify-center gap-1 shadow-sm bg-gray-800/80 hover:bg-gray-700 text-gray-300 border-gray-700/80';
        });

        const activeBtn = document.getElementById(`type-btn-${type}`);
        if (activeBtn) {
            activeBtn.className = 'type-btn p-3 rounded-2xl border transition text-center flex flex-col items-center justify-center gap-1 shadow-md bg-amber-600 text-white border-amber-500 shadow-amber-900/40';
        }

        document.getElementById('section-text-options').classList.toggle('hidden', type !== 'text');
        document.getElementById('section-image-options').classList.toggle('hidden', type !== 'image');

        const tag = document.getElementById('wm-status-tag');
        if (type === 'none') {
            tag.innerText = 'Watermark Disabled';
            tag.className = 'px-2.5 py-0.5 rounded-lg bg-gray-800 text-gray-400 font-mono text-[11px]';
        } else {
            tag.innerText = 'Watermark Active';
            tag.className = 'px-2.5 py-0.5 rounded-lg bg-amber-950/80 border border-amber-800/60 text-amber-400 font-mono text-[11px]';
        }

        updateWatermarkLive();
    }

    function setPosition(posKey) {
        activePosition = posKey;

        document.querySelectorAll('.pos-btn').forEach(btn => {
            if (btn.dataset.position === posKey) {
                btn.className = 'pos-btn py-2 rounded-xl text-[11px] font-bold border transition text-center shadow-md bg-amber-600 text-white border-amber-500 shadow-amber-900/40';
            } else {
                btn.className = 'pos-btn py-2 rounded-xl text-[11px] font-bold border transition text-center shadow-sm bg-gray-800/80 hover:bg-gray-700 text-gray-300 border-gray-700/80';
            }
        });

        updateWatermarkLive();
    }

    function setTextColor(hex) {
        activeColor = hex;
        document.getElementById('wm-color-picker').value = hex;
        updateWatermarkLive();
    }

    // --- Live Canvas Rendering ---
    function updateWatermarkLive() {
        if (!currentImageElement.complete || currentImageElement.naturalWidth === 0) return;

        const sizeVal = parseInt(document.getElementById('size-slider').value);
        const opacityVal = parseInt(document.getElementById('opacity-slider').value);
        const marginInput = document.getElementById('margin-input');
        const marginVal = marginInput ? (parseInt(marginInput.value) || 0) : 30;

        document.getElementById('size-val').innerText = `${sizeVal}%`;
        document.getElementById('opacity-val').innerText = `${opacityVal}%`;

        const natW = currentImageElement.naturalWidth;
        const natH = currentImageElement.naturalHeight;

        canvas.width = natW;
        canvas.height = natH;
        document.getElementById('canvas-dim').innerText = `${natW} × ${natH} px`;

        ctx.clearRect(0, 0, natW, natH);
        ctx.drawImage(currentImageElement, 0, 0, natW, natH);

        if (activeType === 'none') return;

        ctx.save();
        ctx.globalAlpha = opacityVal / 100;

        if (activeType === 'text') {
            const text = document.getElementById('wm-text-input').value.trim() || '@RecipeFrameStudio';
            const fontSize = Math.max(16, Math.round(natW * (sizeVal / 100) * 0.28));

            ctx.font = `bold ${fontSize}px sans-serif`;
            ctx.textBaseline = 'top';

            const metrics = ctx.measureText(text);
            const textW = metrics.width;
            const textH = fontSize * 1.1;

            const hasPill = document.getElementById('wm-pill-check').checked;
            const hasShadow = document.getElementById('wm-shadow-check').checked;

            const padX = hasPill ? Math.round(fontSize * 0.8) : 0;
            const padY = hasPill ? Math.round(fontSize * 0.4) : 0;
            const totalW = textW + (padX * 2);
            const totalH = textH + (padY * 2);

            const [posX, posY] = calculateCoords(natW, natH, totalW, totalH, activePosition, marginVal);

            // Draw contrast pill
            if (hasPill) {
                ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                roundRect(ctx, posX, posY, totalW, totalH, Math.round(totalH * 0.35));
                ctx.fill();
            }

            // Draw text shadow
            if (hasShadow) {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                ctx.fillText(text, posX + padX + 2, posY + padY + 2);
            }

            // Draw text
            ctx.fillStyle = activeColor;
            ctx.fillText(text, posX + padX, posY + padY);

        } else if (activeType === 'image' && logoImgElement && logoImgElement.complete) {
            const logoOrigW = logoImgElement.naturalWidth || 100;
            const logoOrigH = logoImgElement.naturalHeight || 100;

            const targetW = Math.round(natW * (sizeVal / 100));
            const targetH = Math.round(logoOrigH * (targetW / logoOrigW));

            const [posX, posY] = calculateCoords(natW, natH, targetW, targetH, activePosition, marginVal);

            ctx.drawImage(logoImgElement, posX, posY, targetW, targetH);
        }

        ctx.restore();
    }

    function calculateCoords(canvasW, canvasH, elemW, elemH, position, margin) {
        let x = margin;
        let y = margin;

        if (position.includes('center')) {
            x = Math.round((canvasW - elemW) / 2);
        } else if (position.includes('right')) {
            x = canvasW - elemW - margin;
        } else {
            x = margin;
        }

        if (position.startsWith('top')) {
            y = margin;
        } else if (position.startsWith('bottom')) {
            y = canvasH - elemH - margin;
        } else if (position === 'center' || position === 'left' || position === 'right') {
            y = Math.round((canvasH - elemH) / 2);
        }

        return [x, y];
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

    // --- Logo Upload ---
    function handleLogoUpload(fileList) {
        if (!fileList || fileList.length === 0) return;
        const file = fileList[0];

        const formData = new FormData();
        formData.append('logo', file);
        formData.append('_token', csrfToken);

        document.getElementById('logo-uploading-spin').classList.remove('hidden');

        fetch("{{ route('project.watermark.logo', $project['slug']) }}", {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('logo-uploading-spin').classList.add('hidden');
            if (data.success && (data.url || data.path)) {
                logoImageSrc = data.url || `{{ asset('') }}${data.path}`;
                document.getElementById('logo-preview-img').src = logoImageSrc;
                document.getElementById('logo-preview-box').classList.remove('hidden');
                document.getElementById('logo-upload-prompt').classList.add('hidden');

                logoImgElement = new Image();
                logoImgElement.crossOrigin = 'anonymous';
                logoImgElement.src = logoImageSrc;
                logoImgElement.onload = () => updateWatermarkLive();

                showToast('Logo uploaded and applied!');
            } else {
                alert('Could not upload logo: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            document.getElementById('logo-uploading-spin').classList.add('hidden');
            alert('Error uploading logo: ' + err.message);
        });
    }

    function setMarginValue(val) {
        const input = document.getElementById('margin-input');
        if (input) {
            input.value = val;
            updateWatermarkLive();
        }
    }

    function resetToDefaultLogo() {
        logoImageSrc = `{{ route('project.watermark.logo.image', $project['slug']) }}`;
        document.getElementById('logo-preview-img').src = logoImageSrc;
        document.getElementById('logo-preview-box').classList.remove('hidden');
        document.getElementById('logo-upload-prompt').classList.add('hidden');

        if (!logoImgElement) {
            logoImgElement = new Image();
            logoImgElement.crossOrigin = 'anonymous';
        }
        logoImgElement.src = logoImageSrc;
        logoImgElement.onload = () => updateWatermarkLive();

        setWatermarkType('image');
        updateWatermarkLive();
        showToast('Default logo selected!');
    }

    // --- Reset & Save ---
    function resetWatermark() {
        document.getElementById('wm-text-input').value = '@RecipeFrameStudio';
        document.getElementById('size-slider').value = 18;
        document.getElementById('opacity-slider').value = 85;
        if (document.getElementById('margin-input')) {
            document.getElementById('margin-input').value = 30;
        }
        document.getElementById('wm-pill-check').checked = true;
        document.getElementById('wm-shadow-check').checked = false;
        setTextColor('#ffffff');
        setWatermarkType('image');
        setPosition('bottom-right');

        logoImageSrc = `{{ route('project.watermark.logo.image', $project['slug']) }}`;
        document.getElementById('logo-preview-img').src = logoImageSrc;
        document.getElementById('logo-preview-box').classList.remove('hidden');
        document.getElementById('logo-upload-prompt').classList.add('hidden');

        if (!logoImgElement) {
            logoImgElement = new Image();
            logoImgElement.crossOrigin = 'anonymous';
        }
        logoImgElement.src = logoImageSrc;
        logoImgElement.onload = () => updateWatermarkLive();
        updateWatermarkLive();
    }

    function saveWatermark(proceedAfter = false, silent = false) {
        const btn = document.getElementById('save-wm-btn');
        const text = document.getElementById('save-wm-text');
        if (!silent) {
            btn.disabled = true;
            text.innerText = 'Saving...';
        }

        const marginInput = document.getElementById('margin-input');
        const marginVal = marginInput ? (parseInt(marginInput.value) || 0) : 30;

        const payload = {
            _token: csrfToken,
            type: activeType,
            enabled: activeType !== 'none',
            text: document.getElementById('wm-text-input').value.trim(),
            position: activePosition,
            opacity: parseInt(document.getElementById('opacity-slider').value),
            size: parseInt(document.getElementById('size-slider').value),
            margin: marginVal,
            color: activeColor,
            has_pill: document.getElementById('wm-pill-check').checked,
            has_shadow: document.getElementById('wm-shadow-check').checked,
        };

        fetch("{{ route('project.watermark.save', $project['slug']) }}", {
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
            if (!silent) {
                btn.disabled = false;
                text.innerText = 'Save Watermark';
            }
            if (data.success) {
                if (proceedAfter) {
                    alert('Watermark settings saved! Next: Recipe Steps (Phase 6) will be activated in the next step.');
                } else if (!silent) {
                    showToast('Watermark saved successfully!');
                }
            } else {
                alert('Could not save watermark: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            if (!silent) {
                btn.disabled = false;
                text.innerText = 'Save Watermark';
            }
            alert('Error saving watermark: ' + err.message);
        });
    }

    function saveAndProceed() {
        saveWatermark(true);
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
