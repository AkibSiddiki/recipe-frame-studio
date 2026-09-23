@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-5xl mx-auto flex flex-col items-center justify-center min-h-[75vh] py-8 relative">
    {{-- Decorative Background --}}
    <div class="absolute inset-0 dot-pattern opacity-50 pointer-events-none"></div>
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[500px] h-[500px] bg-amber-500/[0.03] rounded-full blur-[100px] pointer-events-none"></div>

    @if(session('status'))
        <div class="w-full mb-6 bg-emerald-950/50 border border-emerald-700/50 text-emerald-300 px-4 py-3 rounded-xl flex items-center shadow-lg backdrop-blur-sm animate-slide-up relative z-10">
            <svg class="w-5 h-5 mr-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full mb-6 bg-rose-950/50 border border-rose-700/50 text-rose-300 px-4 py-3 rounded-xl flex items-center shadow-lg backdrop-blur-sm animate-slide-up relative z-10">
            <svg class="w-5 h-5 mr-3 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Hero Section --}}
    <div class="text-center mb-12 animate-fade-in relative z-10">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-gradient-to-br from-amber-500/10 to-orange-500/5 border border-amber-500/15 mb-6 p-2 shadow-inner relative animate-float">
            <img src="{{ asset('images/logo.png') }}" class="w-full h-full rounded-full object-cover shadow-lg" alt="Rannaghorer Diary">
            <div class="absolute inset-0 rounded-2xl animate-glow-pulse pointer-events-none"></div>
        </div>
        <h1 class="text-4xl sm:text-5xl font-display font-extrabold text-white mb-3 tracking-tight">
            Recipe <span class="text-gradient-brand">Frame Studio</span>
        </h1>
        <p class="text-lg text-gray-400 max-w-xl mx-auto leading-relaxed">
            Create step-by-step Facebook recipe image cards from your cooking videos for
            <span class="text-amber-400 font-semibold">রান্নাঘরের Diary</span>.
        </p>
    </div>

    {{-- CTA Buttons --}}
    <div class="flex flex-wrap justify-center gap-4 mb-16 animate-slide-up relative z-10" style="animation-delay: 0.1s">
        <a href="{{ route('project.create') }}" class="btn-shine flex items-center gap-3 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-xl shadow-amber-900/30 hover:shadow-amber-800/50 hover:scale-[1.03] active:scale-[0.98]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>New Project</span>
        </a>
        <a href="{{ route('settings') }}" class="flex items-center gap-3 border border-gray-700/60 hover:border-gray-500/60 bg-white/[0.03] hover:bg-white/[0.06] text-gray-200 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 backdrop-blur-sm">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span>Settings</span>
        </a>
    </div>

    {{-- Recent Projects --}}
    <div class="w-full relative z-10 animate-slide-up" style="animation-delay: 0.2s">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-border-default">
            <h2 class="text-xl font-display font-bold text-gray-200">Recent Projects</h2>
            <span class="text-[11px] text-gray-500 font-medium">Stored locally</span>
        </div>
        
        @if(empty($recentProjects))
            <div class="glass-surface rounded-2xl p-14 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-white/[0.04] border border-border-subtle flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <p class="text-gray-300 font-semibold mb-1">No recent projects found</p>
                <p class="text-sm text-gray-500">Click "New Project" above to import your first cooking video.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 stagger-children">
                @foreach($recentProjects as $project)
                    <div class="relative group card-hover-lift bg-surface-raised rounded-2xl border border-border-default hover:border-amber-500/30 overflow-hidden flex flex-col transition-all duration-300">
                        <!-- Delete Button (Top Right) -->
                        <button type="button" 
                                onclick="openDeleteModal('{{ $project['slug'] }}', '{{ addslashes($project['name'] ?? 'Untitled Project') }}')" 
                                class="absolute top-3 right-3 z-20 p-2 rounded-lg bg-black/60 hover:bg-rose-600 text-gray-400 hover:text-white backdrop-blur-md border border-white/10 hover:border-rose-500 transition-all duration-200 shadow-lg opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0" 
                                title="Delete Project">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>

                        <a href="{{ route('project.show', $project['slug']) }}" class="block flex-1">
                            <div class="h-44 bg-black relative w-full overflow-hidden flex items-center justify-center">
                                <img src="{{ route('project.thumbnail', $project['slug']) }}" 
                                     onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" 
                                     class="w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" 
                                     alt="Thumbnail">
                                <div class="hidden absolute inset-0 bg-gradient-to-br from-surface-raised to-black flex flex-col items-center justify-center text-gray-600">
                                    <svg class="w-10 h-10 mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-xs font-medium">No Preview</span>
                                </div>
                                {{-- Bottom gradient overlay --}}
                                <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-surface-raised to-transparent pointer-events-none"></div>
                                @if(isset($project['video']['formatted_duration']))
                                    <span class="absolute bottom-2.5 right-2.5 bg-black/70 text-white text-[11px] font-mono font-medium px-2 py-0.5 rounded-md backdrop-blur-md border border-white/10">
                                        {{ $project['video']['formatted_duration'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="text-[15px] font-semibold text-gray-100 group-hover:text-amber-400 transition-colors truncate mb-1.5 pr-6">
                                    {{ $project['name'] ?? 'Untitled Project' }}
                                </h3>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                                        {{ $project['video']['aspect_ratio'] ?? '9:16' }}
                                    </span>
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
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm hidden p-4 modal-backdrop" onclick="if(event.target===this)closeDeleteModal()">
    <div class="bg-surface-overlay border border-border-default rounded-2xl max-w-md w-full p-6 shadow-2xl modal-content">
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
        
        <p class="text-sm text-gray-300 mb-6 leading-relaxed">
            Are you sure you want to permanently delete <strong id="delete-project-name" class="text-amber-400"></strong>? All extracted frames, steps, and collages will be permanently erased.
        </p>

        <form id="delete-project-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 bg-white/[0.05] hover:bg-white/[0.08] text-gray-300 rounded-xl text-sm font-medium transition-all border border-border-default">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-rose-950/40">
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
</script>
@endsection
