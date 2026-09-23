@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-8 flex items-center justify-between animate-fade-in">
        <div>
            <h1 class="text-3xl font-display font-bold text-white mb-2">Settings</h1>
            <p class="text-gray-400">Configure global preferences and dependencies</p>
        </div>
        <a href="{{ route('home') }}" class="px-4 py-2 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm transition-all border border-border-default flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Home
        </a>
    </div>

    @if(session('status'))
        <div class="mb-6 bg-emerald-950/50 border border-emerald-700/50 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg backdrop-blur-sm animate-slide-up">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-8 animate-slide-up" style="animation-delay: 0.05s">
        @csrf

        <!-- FFmpeg Configuration -->
        <div class="glass-surface rounded-2xl overflow-hidden shadow-lg">
            <div class="p-6 border-b border-border-default bg-white/[0.02] flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h2 class="text-xl font-display font-bold text-gray-200">FFmpeg Configuration</h2>
                </div>
                <div id="ffmpeg-badge">
                    @if($ffmpegStatus['is_available'] ?? false)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="relative flex h-2 w-2 mr-1.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Connected ({{ $ffmpegStatus['version'] ?? 'Ready' }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
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
                        <input type="text" name="ffmpeg_path" id="ffmpeg_path" value="{{ old('ffmpeg_path', $settings['ffmpeg_path'] ?? '') }}" class="block w-full bg-white/[0.04] border border-border-default rounded-xl text-gray-200 sm:text-sm px-4 py-2.5 font-mono placeholder-gray-600" placeholder="C:\ffmpeg\bin\ffmpeg.exe or /usr/bin/ffmpeg">
                        <button type="button" onclick="browseFfmpeg()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] border border-border-default rounded-xl text-sm font-medium text-gray-200 transition-all shrink-0">Browse</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Path to the FFmpeg binary. If left empty, the system will auto-detect from system PATH or common Windows locations.</p>
                </div>
                
                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="detectFfmpeg()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] border border-border-default rounded-xl text-sm font-medium text-gray-200 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Auto Detect
                    </button>
                    <button type="button" onclick="testFfmpeg()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] border border-border-default rounded-xl text-sm font-medium text-gray-200 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Test Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Default Output Settings -->
        <div class="glass-surface rounded-2xl overflow-hidden shadow-lg">
            <div class="p-6 border-b border-border-default bg-white/[0.02] flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/15 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                </div>
                <h2 class="text-xl font-display font-bold text-gray-200">Default Output Settings</h2>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="default_format" class="block text-sm font-medium text-gray-300 mb-2">Image Format</label>
                        <select name="default_format" id="default_format" class="block w-full bg-white/[0.04] border border-border-default rounded-xl text-gray-200 sm:text-sm px-4 py-2.5">
                            <option value="jpeg" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'jpeg')>JPEG (.jpg)</option>
                            <option value="png" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'png')>PNG (.png)</option>
                            <option value="webp" @selected(old('default_format', $settings['default_format'] ?? 'jpeg') == 'webp')>WebP (.webp)</option>
                        </select>
                    </div>

                    <div>
                        <label for="default_crop_ratio" class="block text-sm font-medium text-gray-300 mb-2">Default Crop Ratio</label>
                        <select name="default_crop_ratio" id="default_crop_ratio" class="block w-full bg-white/[0.04] border border-border-default rounded-xl text-gray-200 sm:text-sm px-4 py-2.5">
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
                    <input type="range" name="default_quality" id="default_quality" min="1" max="100" value="{{ old('default_quality', $settings['default_quality'] ?? 90) }}" class="w-full" oninput="document.getElementById('quality-val').innerText = this.value">
                    <p class="mt-2 text-xs text-gray-500">Recommended: 90% for clean Facebook recipe post presentation without noticeable compression artifacts.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-border-default">
            <button type="submit" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-8 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg shadow-amber-900/30 hover:shadow-amber-800/50 hover:scale-[1.02] active:scale-[0.98]">
                Save Settings
            </button>
        </div>
    </form>

    <!-- Danger Zone: Clear App Data -->
    <div class="mt-12 rounded-2xl overflow-hidden shadow-lg border border-rose-500/20 animate-slide-up" style="animation-delay: 0.1s">
        <div class="p-6 border-b border-rose-500/10 bg-rose-500/[0.04] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-display font-bold text-rose-200">Danger Zone</h2>
                    <p class="text-xs text-rose-300/60">Irreversible storage operations</p>
                </div>
            </div>
        </div>
        
        <div class="p-6 bg-rose-500/[0.02] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-gray-200">Clear Application Data & Projects</h3>
                <p class="mt-1 text-xs text-gray-400 max-w-lg leading-relaxed">
                    Permanently delete all saved recipe projects, extracted frame images, generated collages, and temporary files. Your configured FFmpeg executable path will be safely kept.
                </p>
            </div>
            <button type="button" 
                    onclick="openClearDataModal()" 
                    class="px-5 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/25 hover:border-rose-500/40 text-rose-200 hover:text-white rounded-xl text-sm font-bold transition-all shrink-0 flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <span>Clear All App Data</span>
            </button>
        </div>
    </div>
</div>

<!-- Clear Data Confirmation Modal -->
<div id="clear-data-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm hidden p-4 modal-backdrop" onclick="if(event.target===this)closeClearDataModal()">
    <div class="glass-surface border-border-default rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
        <div class="flex items-center gap-3 mb-4 text-rose-400">
            <div class="w-11 h-11 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-display font-bold text-white">Clear All App Data?</h3>
                <p class="text-xs text-gray-400">Permanent data wipe</p>
            </div>
        </div>
        
        <p class="text-sm text-gray-300 mb-4 leading-relaxed">
            This will permanently delete <strong>all local projects</strong>, every extracted frame, step card, and export zip file.
        </p>

        <div class="bg-amber-500/[0.06] rounded-xl p-3 text-xs text-amber-300/90 border border-amber-500/15 mb-6 flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>Your original video source files on your computer outside the project folder will <strong>not</strong> be touched.</span>
        </div>

        <form method="POST" action="{{ route('settings.clear-data') }}">
            @csrf
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeClearDataModal()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm font-medium transition-all border border-border-default">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-rose-950/40">
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
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="relative flex h-2 w-2 mr-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Connected (${data.version || 'Ready'})
                    </span>`;
            } else {
                badgeContainer.innerHTML = `
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <span class="w-2 h-2 mr-1.5 bg-rose-500 rounded-full"></span>
                        Not Found
                    </span>`;
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
@endsection
