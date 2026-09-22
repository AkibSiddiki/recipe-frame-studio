@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-5xl mx-auto flex flex-col items-center justify-center min-h-[75vh] py-8">
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-amber-500/10 border border-amber-500/20 mb-6 text-4xl shadow-inner">
            🍳
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-3 tracking-tight">Recipe Frame Studio</h1>
        <p class="text-lg text-gray-400 max-w-xl mx-auto">Turn your 9:16 cooking videos into step-by-step Facebook recipe image posts automatically.</p>
    </div>

    <div class="flex flex-wrap justify-center gap-4 mb-16">
        <a href="{{ route('project.create') }}" class="flex items-center space-x-3 bg-amber-600 hover:bg-amber-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition shadow-lg shadow-amber-900/30 hover:scale-[1.02]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>New Project</span>
        </a>
        <a href="{{ route('project.create') }}" class="flex items-center space-x-3 border-2 border-gray-700 hover:border-gray-500 hover:bg-gray-800/60 text-gray-200 px-8 py-4 rounded-xl font-semibold text-lg transition">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <span>Open Project</span>
        </a>
    </div>

    <div class="w-full">
        <div class="flex items-center justify-between mb-6 border-b border-gray-800 pb-3">
            <h2 class="text-xl font-semibold text-gray-200">Recent Projects</h2>
            <span class="text-xs text-gray-500">Stored locally</span>
        </div>
        
        @if(empty($recentProjects))
            <div class="bg-[#1a1a2e] rounded-xl p-12 text-center border border-gray-800">
                <div class="text-4xl mb-3 opacity-30">📁</div>
                <p class="text-gray-300 font-medium">No recent projects found</p>
                <p class="text-sm text-gray-500 mt-1">Click "New Project" above to import your first cooking video.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($recentProjects as $project)
                    <a href="{{ route('project.show', $project['slug']) }}" class="group block bg-[#1a1a2e] rounded-xl border border-gray-800 hover:border-amber-500/50 hover:shadow-xl hover:shadow-black/40 transition overflow-hidden">
                        <div class="h-44 bg-gray-900 relative w-full overflow-hidden border-b border-gray-800 flex items-center justify-center">
                            <img src="{{ route('project.thumbnail', $project['slug']) }}" 
                                 onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" 
                                 class="w-full h-full object-cover opacity-85 group-hover:opacity-100 group-hover:scale-105 transition duration-300" 
                                 alt="Thumbnail">
                            <div class="hidden absolute inset-0 bg-gradient-to-br from-gray-800 to-gray-900 flex flex-col items-center justify-center text-gray-600">
                                <span class="text-4xl mb-1">📹</span>
                                <span class="text-xs">No Preview</span>
                            </div>
                            @if(isset($project['video']['formatted_duration']))
                                <span class="absolute bottom-2 right-2 bg-black/80 text-white text-[11px] font-mono px-2 py-0.5 rounded backdrop-blur-sm">
                                    {{ $project['video']['formatted_duration'] }}
                                </span>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-base font-semibold text-gray-100 group-hover:text-amber-400 transition truncate mb-1">
                                {{ $project['name'] ?? 'Untitled Project' }}
                            </h3>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $project['video']['aspect_ratio'] ?? '9:16' }}</span>
                                <span>{{ \Carbon\Carbon::parse($project['updated_at'] ?? now())->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
