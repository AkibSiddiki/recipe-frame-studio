@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-5xl mx-auto flex flex-col items-center justify-center min-h-[75vh] py-8">
    @if(session('status'))
        <div class="w-full mb-6 bg-emerald-950/60 border border-emerald-700 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full mb-6 bg-rose-950/60 border border-rose-700 text-rose-300 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <svg class="w-5 h-5 mr-3 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-amber-500/10 border border-amber-500/20 mb-6 p-2 shadow-inner">
            <img src="{{ asset('images/logo.png') }}" class="w-full h-full rounded-full object-cover shadow-md" alt="Rannaghorer Diary">
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-3 tracking-tight">Recipe Frame Studio</h1>
        <p class="text-lg text-gray-400 max-w-xl mx-auto">Create step-by-step Facebook recipe image cards from your cooking videos for <span class="text-amber-400 font-semibold">রান্নাঘরের Diary</span>.</p>
    </div>

    <div class="flex flex-wrap justify-center gap-4 mb-16">
        <a href="{{ route('project.create') }}" class="flex items-center space-x-3 bg-amber-600 hover:bg-amber-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition shadow-lg shadow-amber-900/30 hover:scale-[1.02]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>New Project</span>
        </a>
        <a href="{{ route('settings') }}" class="flex items-center space-x-3 border-2 border-gray-700 hover:border-gray-500 hover:bg-gray-800/60 text-gray-200 px-8 py-4 rounded-xl font-semibold text-lg transition">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span>Settings</span>
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
                    <div class="relative group bg-[#1a1a2e] rounded-xl border border-gray-800 hover:border-amber-500/50 hover:shadow-xl hover:shadow-black/40 transition overflow-hidden flex flex-col">
                        <!-- Delete Button (Top Right) -->
                        <button type="button" 
                                onclick="openDeleteModal('{{ $project['slug'] }}', '{{ addslashes($project['name'] ?? 'Untitled Project') }}')" 
                                class="absolute top-2.5 right-2.5 z-20 p-2 rounded-lg bg-black/70 hover:bg-rose-600 text-gray-300 hover:text-white backdrop-blur-sm border border-gray-700/60 hover:border-rose-500 transition shadow-lg opacity-80 group-hover:opacity-100" 
                                title="Delete Project">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>

                        <a href="{{ route('project.show', $project['slug']) }}" class="block flex-1">
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
                                <h3 class="text-base font-semibold text-gray-100 group-hover:text-amber-400 transition truncate mb-1 pr-6">
                                    {{ $project['name'] ?? 'Untitled Project' }}
                                </h3>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ $project['video']['aspect_ratio'] ?? '9:16' }}</span>
                                    <span>{{ \Carbon\Carbon::parse($project['updated_at'] ?? now())->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden p-4">
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
            Are you sure you want to permanently delete <strong id="delete-project-name" class="text-amber-400"></strong>? All extracted frames, steps, and collages will be permanently erased.
        </p>

        <form id="delete-project-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold transition shadow-md shadow-rose-950/50">
                    Delete Project
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeleteModal(slug, name) {
        const modal = document.getElementById('delete-modal');
        const nameEl = document.getElementById('delete-project-name');
        const form = document.getElementById('delete-project-form');

        nameEl.textContent = name;
        form.action = '/project/' + encodeURIComponent(slug);
        modal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
    document.getElementById('delete-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>
@endsection
