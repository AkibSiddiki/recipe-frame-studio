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
        
        <div class="p-6 border-t border-gray-800 bg-gray-900/30 flex justify-between items-center">
            <span class="text-xs text-gray-500">Source: {{ $video['original_path'] ?? 'Unknown path' }}</span>
            <button disabled title="Frame extraction will be activated in Phase 3" class="bg-gray-700/60 text-gray-400 px-6 py-2.5 rounded-lg font-medium flex items-center cursor-not-allowed border border-gray-600/50">
                <span>Auto Extract Frames</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
@endsection
