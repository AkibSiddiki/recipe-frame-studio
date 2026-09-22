@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'video'])

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-white">{{ $project['name'] ?? 'Untitled Project' }}</h1>
            <p class="text-sm text-gray-400 mt-1">Project created: {{ \Carbon\Carbon::parse($project['created_at'] ?? now())->format('M d, Y h:i A') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition">
                ← All Projects
            </a>
            <button type="button" 
                    onclick="openDeleteProjectModal()" 
                    class="px-3.5 py-2 bg-rose-950/40 hover:bg-rose-900/60 border border-rose-800/60 text-rose-300 hover:text-white rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm"
                    title="Delete this project">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Delete</span>
            </button>
        </div>
    </div>

    @php
        $video = $project['video'] ?? [];
        $isVertical = $video['is_vertical'] ?? true;
    @endphp

    <div class="bg-[#1a1a2e] rounded-xl border border-gray-800 overflow-hidden shadow-lg mb-8">
        <div class="p-6 border-b border-gray-800 bg-gray-900/30 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-200">Video Information</h2>
            @if($isVertical)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-950 text-emerald-400 border border-emerald-800">
                    <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full"></span>
                    9:16 Vertical
                </span>
            @endif
        </div>
        
        <div class="p-6 flex flex-col md:flex-row gap-8">
            <!-- Thumbnail Preview -->
            <div class="shrink-0 w-full md:w-64 flex flex-col gap-4">
                <div class="aspect-[9/16] bg-black rounded-lg overflow-hidden border border-gray-700 relative flex items-center justify-center shadow-inner group">
                    <img src="{{ route('project.thumbnail', $project['slug']) }}" 
                         onerror="this.onerror=null; this.src=''; this.classList.add('hidden'); document.getElementById('thumb-fallback').classList.remove('hidden');" 
                         class="w-full h-full object-cover" 
                         alt="Video thumbnail">
                    <div id="thumb-fallback" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-gray-900 text-gray-500">
                        <svg class="w-16 h-16 text-gray-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-xs">No thumbnail available</span>
                    </div>
                </div>
            </div>

            <!-- Metadata Grid -->
            <div class="flex-1">
                @if(!$isVertical)
                    <div class="mb-6 bg-yellow-950/40 border border-yellow-700/60 rounded-lg p-4 flex items-start">
                        <svg class="w-6 h-6 text-yellow-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-yellow-400 font-medium">Non-vertical Video Detected</h4>
                            <p class="text-sm text-yellow-500/80 mt-1">This video is not 9:16 vertical format. You can still continue, but results may vary.</p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Filename</div>
                        <div class="font-medium text-gray-200 truncate" title="{{ $video['filename'] ?? basename($video['original_path'] ?? 'Video') }}">
                            {{ $video['filename'] ?? basename($video['original_path'] ?? 'Video') }}
                        </div>
                    </div>
                    
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Duration</div>
                        <div class="font-medium text-gray-200">{{ $video['formatted_duration'] ?? 'Unknown' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Resolution</div>
                        <div class="font-medium text-gray-200">
                            {{ $video['width'] ?? '?' }} × {{ $video['height'] ?? '?' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">FPS</div>
                        <div class="font-medium text-gray-200">{{ isset($video['fps']) ? round((float)$video['fps'], 2) : 'Unknown' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Aspect Ratio</div>
                        <div class="font-medium text-gray-200">{{ $video['aspect_ratio'] ?? 'Unknown' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">File Size</div>
                        <div class="font-medium text-gray-200">{{ $video['formatted_file_size'] ?? 'Unknown' }}</div>
                    </div>
                    
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Codec</div>
                        <div class="font-medium text-gray-200 uppercase">{{ $video['codec'] ?? 'Unknown' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-1">Target Facebook Crop</div>
                        <div class="font-medium text-amber-400">4:5 (1080 × 1350)</div>
                    </div>
                </div>
            </div>
        </div>
        
        </div>
        
        <div class="p-6 border-t border-gray-800 bg-gray-900/30 flex flex-wrap gap-4 justify-between items-center">
            <span class="text-xs text-gray-500 truncate max-w-md" title="{{ $video['original_path'] ?? '' }}">Source: {{ $video['original_path'] ?? 'Unknown path' }}</span>
            
            <div class="flex items-center gap-3">
                @if(!empty($project['frames']) && count($project['frames']) > 0)
                    <a href="{{ route('project.frames', $project['slug']) }}" class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-200 rounded-lg font-medium text-sm transition border border-gray-700 flex items-center gap-2">
                        <span>🖼️ View Frames ({{ count($project['frames']) }})</span>
                    </a>
                @endif

                <button type="button" onclick="openExtractModal()" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-lg font-medium text-sm flex items-center transition shadow-md hover:scale-[1.02]">
                    <span>Auto Extract Frames</span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extraction Modal -->
<div id="extract-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-[#1a1a2e] border border-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <span>⚡</span>
                <span>Auto Extract Candidate Frames</span>
            </h3>
            <button type="button" onclick="closeExtractModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
        </div>

        <form id="extract-form" action="{{ route('project.extract-frames', $project['slug']) }}" method="POST">
            @csrf
            <p class="text-sm text-gray-400 mb-5">
                FFmpeg will analyze your video and extract high-resolution still frames evenly across the entire duration for your recipe steps.
            </p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Target Frame Count</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-700 bg-gray-900/50 cursor-pointer hover:border-amber-500 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-950/30">
                            <input type="radio" name="target_count" value="16" class="hidden">
                            <span class="text-white font-bold text-lg">16</span>
                            <span class="text-[11px] text-gray-400">Quick Recipe</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-700 bg-gray-900/50 cursor-pointer hover:border-amber-500 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-950/30">
                            <input type="radio" name="target_count" value="24" checked class="hidden">
                            <span class="text-amber-400 font-bold text-lg">24</span>
                            <span class="text-[11px] text-gray-400">Recommended</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-700 bg-gray-900/50 cursor-pointer hover:border-amber-500 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-950/30">
                            <input type="radio" name="target_count" value="36" class="hidden">
                            <span class="text-white font-bold text-lg">36</span>
                            <span class="text-[11px] text-gray-400">Detailed Steps</span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="extract-loading" class="hidden mb-4 p-4 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-300 text-sm flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Extracting frames with FFmpeg... This will take just a few seconds.</span>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeExtractModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition">
                    Cancel
                </button>
                <button type="submit" id="start-extract-btn" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                    <span>Start Extraction</span>
                    <span>→</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openExtractModal() {
        document.getElementById('extract-modal').classList.remove('hidden');
    }

    function closeExtractModal() {
        document.getElementById('extract-modal').classList.add('hidden');
    }

    document.getElementById('extract-form').addEventListener('submit', function () {
        document.getElementById('extract-loading').classList.remove('hidden');
        const btn = document.getElementById('start-extract-btn');
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    });

    function openDeleteProjectModal() {
        document.getElementById('delete-project-modal').classList.remove('hidden');
    }

    function closeDeleteProjectModal() {
        document.getElementById('delete-project-modal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteProjectModal();
    });
    document.getElementById('delete-project-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteProjectModal();
    });
</script>

<!-- Delete Project Modal -->
<div id="delete-project-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden p-4">
    <div class="bg-[#1a1a2e] border border-gray-700 rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center space-x-3 mb-4 text-rose-400">
            <div class="w-10 h-10 rounded-full bg-rose-950/60 border border-rose-800/80 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">Delete Project</h3>
                <p class="text-xs text-gray-400">This action cannot be undone.</p>
            </div>
        </div>
        
        <p class="text-sm text-gray-300 mb-6">
            Are you sure you want to permanently delete <strong class="text-amber-400">{{ $project['name'] ?? 'this project' }}</strong>? All extracted frames, recipe steps, and collages will be erased.
        </p>

        <form method="POST" action="{{ route('project.destroy', $project['slug']) }}">
            @csrf
            @method('DELETE')
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteProjectModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold transition shadow-md shadow-rose-950/50">
                    Delete Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
