@extends('layouts.app', ['showSidebar' => true, 'currentStep' => 'video'])

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-center animate-fade-in">
        <div>
            <h1 class="text-3xl font-display font-bold text-white">{{ $project['name'] ?? 'Untitled Project' }}</h1>
            <p class="text-sm text-gray-400 mt-1">Project created: {{ \Carbon\Carbon::parse($project['created_at'] ?? now())->format('M d, Y h:i A') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="px-4 py-2 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm transition-all border border-border-default flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                All Projects
            </a>
            <button type="button" 
                    onclick="openDeleteProjectModal()" 
                    class="px-3.5 py-2 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 hover:border-rose-500/40 text-rose-300 hover:text-white rounded-xl text-sm transition-all flex items-center gap-1.5"
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

    <div class="glass-surface rounded-2xl overflow-hidden shadow-xl mb-8 animate-slide-up" style="animation-delay: 0.05s">
        <div class="p-6 border-b border-border-default bg-white/[0.02] flex items-center justify-between">
            <h2 class="text-xl font-display font-bold text-gray-200">Video Information</h2>
            @if($isVertical)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    9:16 Vertical
                </span>
            @endif
        </div>
        
        <div class="p-6 flex flex-col md:flex-row gap-8">
            <!-- Thumbnail Preview -->
            <div class="shrink-0 w-full md:w-64 flex flex-col gap-4">
                <div class="aspect-[9/16] bg-black rounded-xl overflow-hidden border border-border-default relative flex items-center justify-center shadow-inner group">
                    <img src="{{ route('project.thumbnail', $project['slug']) }}" 
                         onerror="this.onerror=null; this.src=''; this.classList.add('hidden'); document.getElementById('thumb-fallback').classList.remove('hidden');" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                         alt="Video thumbnail">
                    <div id="thumb-fallback" class="hidden absolute inset-0 flex flex-col items-center justify-center bg-surface-raised text-gray-500">
                        <svg class="w-16 h-16 text-gray-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-xs">No thumbnail available</span>
                    </div>
                    {{-- Play hint overlay --}}
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <div class="w-14 h-14 rounded-full bg-black/50 backdrop-blur-md flex items-center justify-center border border-white/10">
                            <svg class="w-6 h-6 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata Grid -->
            <div class="flex-1">
                @if(!$isVertical)
                    <div class="mb-6 bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4 flex items-start">
                        <svg class="w-6 h-6 text-yellow-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-yellow-400 font-semibold">Non-vertical Video Detected</h4>
                            <p class="text-sm text-yellow-500/80 mt-1">This video is not 9:16 vertical format. You can still continue, but results may vary.</p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $metaItems = [
                            ['label' => 'Filename', 'value' => $video['filename'] ?? basename($video['original_path'] ?? 'Video'), 'truncate' => true],
                            ['label' => 'Duration', 'value' => $video['formatted_duration'] ?? 'Unknown'],
                            ['label' => 'Resolution', 'value' => ($video['width'] ?? '?') . ' × ' . ($video['height'] ?? '?')],
                            ['label' => 'FPS', 'value' => isset($video['fps']) ? round((float)$video['fps'], 2) : 'Unknown'],
                            ['label' => 'Aspect Ratio', 'value' => $video['aspect_ratio'] ?? 'Unknown'],
                            ['label' => 'File Size', 'value' => $video['formatted_file_size'] ?? 'Unknown'],
                            ['label' => 'Codec', 'value' => strtoupper($video['codec'] ?? 'Unknown')],
                            ['label' => 'Target Facebook Crop', 'value' => '4:5 (1080 × 1350)', 'highlight' => true],
                        ];
                    @endphp

                    @foreach($metaItems as $item)
                        <div class="p-3 rounded-xl bg-white/[0.02] border border-border-subtle hover:border-border-hover transition-colors">
                            <div class="text-[10px] uppercase tracking-wider text-gray-500 font-bold mb-1">{{ $item['label'] }}</div>
                            <div class="font-medium {{ $item['highlight'] ?? false ? 'text-amber-400' : 'text-gray-200' }} {{ $item['truncate'] ?? false ? 'truncate' : '' }}" 
                                 @if($item['truncate'] ?? false) title="{{ $item['value'] }}" @endif>
                                {{ $item['value'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="p-6 border-t border-border-default bg-white/[0.02] flex flex-wrap gap-4 justify-between items-center">
            <span class="text-xs text-gray-500 truncate max-w-md font-mono" title="{{ $video['original_path'] ?? '' }}">Source: {{ $video['original_path'] ?? 'Unknown path' }}</span>
            
            <div class="flex items-center gap-3">
                @if(!empty($project['frames']) && count($project['frames']) > 0)
                    <a href="{{ route('project.frames', $project['slug']) }}" class="px-5 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] text-gray-200 rounded-xl font-medium text-sm transition-all border border-border-default flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>View Frames ({{ count($project['frames']) }})</span>
                    </a>
                @endif

                <button type="button" onclick="openExtractModal()" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm flex items-center transition-all shadow-lg shadow-amber-900/30 hover:scale-[1.02] active:scale-[0.98] gap-2">
                    <span>Auto Extract Frames</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extraction Modal -->
<div id="extract-modal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 modal-backdrop" onclick="if(event.target===this)closeExtractModal()">
    <div class="glass-surface rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content border-border-default">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-display font-bold text-white flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-amber-500/15 border border-amber-500/20 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </span>
                <span>Auto Extract Candidate Frames</span>
            </h3>
            <button type="button" onclick="closeExtractModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/[0.06] transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="extract-form" action="{{ route('project.extract-frames', $project['slug']) }}" method="POST">
            @csrf
            <p class="text-sm text-gray-400 mb-5 leading-relaxed">
                FFmpeg will analyze your video and extract high-resolution still frames evenly across the entire duration for your recipe steps.
            </p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2.5">Target Frame Count</label>
                    <div class="grid grid-cols-4 gap-2.5">
                        <label class="flex flex-col items-center justify-center p-3.5 rounded-xl border border-border-default bg-white/[0.02] cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:shadow-lg has-[:checked]:shadow-amber-900/20">
                            <input type="radio" name="target_count" value="16" class="hidden">
                            <span class="text-white font-bold text-lg">16</span>
                            <span class="text-[11px] text-gray-400 font-medium">Quick Recipe</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3.5 rounded-xl border border-border-default bg-white/[0.02] cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:shadow-lg has-[:checked]:shadow-amber-900/20">
                            <input type="radio" name="target_count" value="24" checked class="hidden">
                            <span class="text-amber-400 font-bold text-lg">24</span>
                            <span class="text-[11px] text-gray-400 font-medium">Recommended</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3.5 rounded-xl border border-border-default bg-white/[0.02] cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:shadow-lg has-[:checked]:shadow-amber-900/20">
                            <input type="radio" name="target_count" value="36" class="hidden">
                            <span class="text-white font-bold text-lg">36</span>
                            <span class="text-[11px] text-gray-400 font-medium">Detailed Steps</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3.5 rounded-xl border border-border-default bg-white/[0.02] cursor-pointer hover:border-amber-500/40 transition-all has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:shadow-lg has-[:checked]:shadow-amber-900/20">
                            <input type="radio" name="target_count" value="60" class="hidden">
                            <span class="text-white font-bold text-lg">60</span>
                            <span class="text-[11px] text-gray-400 font-medium">Maximum Detail</span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="extract-loading" class="hidden mb-4 p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Extracting frames with FFmpeg... This will take just a few seconds.</span>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-border-subtle">
                <button type="button" onclick="closeExtractModal()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm font-medium transition-all border border-border-default">
                    Cancel
                </button>
                <button type="submit" id="start-extract-btn" class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2 shadow-lg shadow-amber-900/30">
                    <span>Start Extraction</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
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
        if (e.key === 'Escape') { closeExtractModal(); closeDeleteProjectModal(); }
    });
    document.getElementById('delete-project-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteProjectModal();
    });
</script>

<!-- Delete Project Modal -->
<div id="delete-project-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm hidden p-4 modal-backdrop">
    <div class="glass-surface border-border-default rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
        <div class="flex items-center gap-3 mb-4 text-rose-400">
            <div class="w-11 h-11 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-display font-bold text-white">Delete Project</h3>
                <p class="text-xs text-gray-400">This action cannot be undone.</p>
            </div>
        </div>
        
        <p class="text-sm text-gray-300 mb-6">
            Are you sure you want to permanently delete <strong class="text-amber-400">{{ $project['name'] ?? 'this project' }}</strong>? All extracted frames, recipe steps, and collages will be erased.
        </p>

        <form method="POST" action="{{ route('project.destroy', $project['slug']) }}">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteProjectModal()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm font-medium transition-all border border-border-default">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-rose-950/40">
                    Delete Project
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
