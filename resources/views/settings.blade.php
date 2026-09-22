@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Settings</h1>
            <p class="text-gray-400">Configure global preferences and dependencies</p>
        </div>
        <a href="{{ route('home') }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition">
            ← Back to Home
        </a>
    </div>

    @if(session('status'))
        <div class="mb-6 bg-emerald-950/60 border border-emerald-700 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
        @csrf

        <!-- FFmpeg Configuration -->
        <div class="bg-[#1a1a2e] rounded-xl border border-gray-800 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-gray-800 flex justify-between items-center bg-gray-900/30">
                <h2 class="text-xl font-semibold text-gray-200">FFmpeg Configuration</h2>
                <div id="ffmpeg-badge">
                    @if($ffmpegStatus['is_available'] ?? false)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-950/80 text-emerald-400 border border-emerald-800">
                            <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            Connected ({{ $ffmpegStatus['version'] ?? 'Ready' }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-950/80 text-rose-400 border border-rose-800">
                            <span class="w-2 h-2 mr-1.5 bg-rose-500 rounded-full"></span>
                            Not Found
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <div>
                    <label for="ffmpeg_path" class="block text-sm font-medium text-gray-300 mb-2">FFmpeg Executable Path</label>
                    <div class="flex gap-2">
                        <input type="text" name="ffmpeg_path" id="ffmpeg_path" value="{{ old('ffmpeg_path', $settings['ffmpeg_path'] ?? '') }}" class="block w-full bg-gray-800 border-gray-700 rounded-lg text-gray-200 focus:ring-amber-500 focus:border-amber-500 sm:text-sm px-4 py-2 font-mono" placeholder="C:\ffmpeg\bin\ffmpeg.exe or /usr/bin/ffmpeg">
                        <button type="button" onclick="browseFfmpeg()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 border border-gray-600 rounded-lg text-sm font-medium text-gray-200 transition shrink-0">Browse</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Path to the FFmpeg binary. If left empty, the system will auto-detect from system PATH or common Windows locations.</p>
                </div>
                
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="detectFfmpeg()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-600 rounded-lg text-sm font-medium text-gray-200 transition flex items-center">
                        <svg class="w-4 h-4 mr-2 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Auto Detect
                    </button>
                    <button type="button" onclick="testFfmpeg()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-600 rounded-lg text-sm font-medium text-gray-200 transition flex items-center">
                        <svg class="w-4 h-4 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Test Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Default Output Settings -->
        <div class="bg-[#1a1a2e] rounded-xl border border-gray-800 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-gray-800 bg-gray-900/30">
                <h2 class="text-xl font-semibold text-gray-200">Default Output Settings</h2>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="default_format" class="block text-sm font-medium text-gray-300 mb-2">Image Format</label>
                        <select name="default_format" id="default_format" class="block w-full bg-gray-800 border-gray-700 rounded-lg text-gray-200 focus:ring-amber-500 focus:border-amber-500 sm:text-sm px-4 py-2.5">
                            <option value="jpeg" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'jpeg')>JPEG (.jpg)</option>
                            <option value="png" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'png')>PNG (.png)</option>
                            <option value="webp" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'webp')>WebP (.webp)</option>
                        </select>
                    </div>

                    <div>
                        <label for="default_crop_ratio" class="block text-sm font-medium text-gray-300 mb-2">Default Crop Ratio</label>
                        <select name="default_crop_ratio" id="default_crop_ratio" class="block w-full bg-gray-800 border-gray-700 rounded-lg text-gray-200 focus:ring-amber-500 focus:border-amber-500 sm:text-sm px-4 py-2.5">
                            <option value="4:5" @selected(old('default_crop_ratio', $settings['default_crop_ratio'] ?? '4:5') == '4:5')>4:5 (Facebook Portrait - 1080 × 1350)</option>
                            <option value="1:1" @selected(old('default_crop_ratio', $settings['default_crop_ratio'] ?? '4:5') == '1:1')>1:1 (Square - 1080 × 1080)</option>
                            <option value="16:9" @selected(old('default_crop_ratio', $settings['default_crop_ratio'] ?? '4:5') == '16:9')>16:9 (Landscape - 1920 × 1080)</option>
                            <option value="9:16" @selected(old('default_crop_ratio', $settings['default_crop_ratio'] ?? '4:5') == '9:16')>9:16 (Original Vertical - 1080 × 1920)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="default_quality" class="block text-sm font-medium text-gray-300 mb-2 flex justify-between">
                        <span>JPEG / WebP Quality</span>
                        <span class="font-mono text-amber-400 font-bold"><span id="quality-val">{{ old('default_quality', $settings['default_quality'] ?? 90) }}</span>%</span>
                    </label>
                    <input type="range" name="default_quality" id="default_quality" min="1" max="100" value="{{ old('default_quality', $settings['default_quality'] ?? 90) }}" class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500" oninput="document.getElementById('quality-val').innerText = this.value">
                    <p class="mt-2 text-xs text-gray-500">Recommended: 90% for clean Facebook recipe post presentation without noticeable compression artifacts.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-800">
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition shadow-md hover:scale-[1.02]">
                Save Settings
            </button>
        </div>
    </form>
</div>

<script>
    function browseFfmpeg() {
        fetch('{{ route('api.dialog.open-ffmpeg') }}')
            .then(response => response.json())
            .then(data => {
                if (data.path) {
                    document.getElementById('ffmpeg_path').value = data.path;
                    testFfmpeg();
                }
            })
            .catch(error => console.error('Error opening dialog:', error));
    }

    function detectFfmpeg() {
        fetch('{{ route('settings.ffmpeg-detect') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.path) {
                document.getElementById('ffmpeg_path').value = data.path;
                testFfmpeg();
            } else {
                alert('Could not automatically detect FFmpeg. Please select the executable with Browse.');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function testFfmpeg() {
        fetch('{{ route('settings.ffmpeg-status') }}')
        .then(response => response.json())
        .then(data => {
            const badgeContainer = document.getElementById('ffmpeg-badge');
            if (data.is_available) {
                badgeContainer.innerHTML = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-950/80 text-emerald-400 border border-emerald-800">
                        <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                        Connected (${data.version || 'Ready'})
                    </span>`;
            } else {
                badgeContainer.innerHTML = `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-950/80 text-rose-400 border border-rose-800">
                        <span class="w-2 h-2 mr-1.5 bg-rose-500 rounded-full"></span>
                        Not Found
                    </span>`;
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
@endsection
