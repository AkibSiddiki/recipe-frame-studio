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

    <!-- Danger Zone: Clear App Data -->
    <div class="mt-12 bg-rose-950/20 rounded-xl border border-rose-900/40 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-rose-900/30 bg-rose-950/30 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-rose-900/50 border border-rose-700/60 flex items-center justify-center text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-rose-200">Danger Zone</h2>
                    <p class="text-xs text-rose-300/70">Irreversible storage operations</p>
                </div>
            </div>
        </div>
        
        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-200">Clear Application Data & Projects</h3>
                <p class="mt-1 text-xs text-gray-400 max-w-lg">
                    Permanently delete all saved recipe projects, extracted frame images, generated collages, and temporary files. Your configured FFmpeg executable path will be safely kept.
                </p>
            </div>
            <button type="button" 
                    onclick="openClearDataModal()" 
                    class="px-5 py-2.5 bg-rose-900/40 hover:bg-rose-800/60 border border-rose-700 text-rose-200 hover:text-white rounded-lg text-sm font-semibold transition shrink-0 shadow-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span>Clear All App Data</span>
            </button>
        </div>
    </div>
</div>

<!-- Clear Data Confirmation Modal -->
<div id="clear-data-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden p-4">
    <div class="bg-[#1a1a2e] border border-gray-700 rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center space-x-3 mb-4 text-rose-400">
            <div class="w-10 h-10 rounded-full bg-rose-950/60 border border-rose-800/80 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">Clear All App Data?</h3>
                <p class="text-xs text-gray-400">Permanent data wipe</p>
            </div>
        </div>
        
        <p class="text-sm text-gray-300 mb-4">
            This will permanently delete <strong>all local projects</strong>, every extracted frame, step card, and export zip file.
        </p>

        <div class="bg-gray-900/60 rounded-lg p-3 text-xs text-amber-300/90 border border-amber-800/40 mb-6">
            ⚠️ Note: Your original video source files on your computer outside the project folder will <strong>not</strong> be touched.
        </div>

        <form method="POST" action="{{ route('settings.clear-data') }}">
            @csrf
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeClearDataModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold transition shadow-md shadow-rose-950/50">
                    Yes, Clear Everything
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openClearDataModal() {
        document.getElementById('clear-data-modal').classList.remove('hidden');
    }

    function closeClearDataModal() {
        document.getElementById('clear-data-modal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeClearDataModal();
    });
    document.getElementById('clear-data-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeClearDataModal();
    });

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
