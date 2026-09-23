@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-2xl mx-auto flex flex-col items-center justify-center min-h-[80vh] relative">
    {{-- Decorative --}}
    <div class="absolute top-0 right-0 w-96 h-96 bg-amber-500/[0.025] rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full text-center mb-10 animate-fade-in">
        <h1 class="text-3xl font-display font-bold text-white mb-2">New Project</h1>
        <p class="text-gray-400">Select a cooking video to get started</p>
    </div>

    @if ($errors->any())
        <div class="w-full mb-6 bg-rose-950/50 border border-rose-700/50 text-rose-300 p-4 rounded-xl shadow-lg backdrop-blur-sm animate-slide-up">
            <div class="flex items-center mb-1">
                <svg class="w-5 h-5 mr-2 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-semibold text-rose-100">Unable to create project</span>
            </div>
            <ul class="list-disc list-inside text-sm text-rose-200 ml-1 mt-1 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="create-project-form" action="{{ route('project.store') }}" method="POST" enctype="multipart/form-data" class="w-full glass-surface rounded-2xl p-8 shadow-xl animate-slide-up" style="animation-delay: 0.05s">
        @csrf
        
        <div class="mb-8">
            <div id="dropzone" onclick="selectVideoFile()" class="border-2 border-dashed border-gray-700/60 hover:border-amber-500/60 rounded-2xl p-10 text-center cursor-pointer transition-all duration-300 group bg-white/[0.015] hover:bg-amber-500/[0.03] relative overflow-hidden">
                {{-- Animated dashed border corner highlights --}}
                <div class="absolute top-0 left-0 w-5 h-5 border-t-2 border-l-2 border-amber-500/0 group-hover:border-amber-500/50 rounded-tl-xl transition-all duration-300"></div>
                <div class="absolute top-0 right-0 w-5 h-5 border-t-2 border-r-2 border-amber-500/0 group-hover:border-amber-500/50 rounded-tr-xl transition-all duration-300"></div>
                <div class="absolute bottom-0 left-0 w-5 h-5 border-b-2 border-l-2 border-amber-500/0 group-hover:border-amber-500/50 rounded-bl-xl transition-all duration-300"></div>
                <div class="absolute bottom-0 right-0 w-5 h-5 border-b-2 border-r-2 border-amber-500/0 group-hover:border-amber-500/50 rounded-br-xl transition-all duration-300"></div>

                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/[0.04] group-hover:bg-amber-500/10 text-gray-500 group-hover:text-amber-400 mb-4 transition-all duration-300 group-hover:scale-110">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-200 mb-1 group-hover:text-white transition-colors">Click to select a video file</h3>
                <p class="text-sm text-gray-500">Supported: MP4, MOV, AVI, MKV, WEBM (or drag & drop here)</p>
                <div id="selected-file-display" class="mt-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm font-medium hidden break-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span id="selected-file-text"></span>
                </div>
            </div>
            
            <div class="mt-4">
                <label for="video_path" class="block text-sm font-medium text-gray-400 mb-1.5">Or enter local path manually:</label>
                <input type="text" name="video_path" id="video_path" value="{{ old('video_path') }}" class="block w-full bg-white/[0.04] border border-border-default rounded-xl text-gray-200 sm:text-sm px-4 py-2.5 font-mono placeholder-gray-600" placeholder="C:\Videos\recipe.mp4">
                <p class="mt-2 text-xs text-gray-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><strong>Pro-Tip:</strong> In Windows Explorer, right-click your video &gt; <em>"Copy as path"</em> and paste it here to bypass all upload limits instantly!</span>
                </p>
            </div>
            <input type="file" id="video_file_picker" name="video_file" accept=".mp4,.mov,.avi,.mkv,.webm,video/*" class="hidden">
        </div>

        <div class="mb-8">
            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Project Name (Optional)</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="block w-full bg-white/[0.04] border border-border-default rounded-xl text-gray-200 sm:text-sm px-4 py-3 placeholder-gray-600" placeholder="e.g. Chicken Alfredo Recipe">
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition-colors text-sm font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to home
            </a>
            <button type="submit" id="submit-btn" class="btn-shine bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white px-8 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg shadow-amber-900/30 hover:shadow-amber-800/50 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2">
                <span id="submit-text">Create Project</span>
                <svg id="submit-spinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
        </div>
    </form>
</div>

<script>
    function updateSelectedDisplay(label) {
        document.getElementById('selected-file-text').innerText = label;
        document.getElementById('selected-file-display').classList.remove('hidden');
    }

    function applySelectedFilePath(filePath) {
        if (!filePath) return;
        filePath = filePath.replace(/^"(.*)"$/, '$1').trim();
        document.getElementById('video_path').value = filePath;
        try { document.getElementById('video_file_picker').value = ''; } catch(e) {}
        updateSelectedDisplay('Local Path: ' + filePath);

        const nameField = document.getElementById('name');
        if (!nameField.value) {
            const filename = filePath.split(/[/\\]/).pop().split('.')[0];
            nameField.value = filename.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }

    function applySelectedFile(file) {
        if (!file) return;
        const sizeMb = file.size ? ' (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB)' : '';
        if (file.path) {
            applySelectedFilePath(file.path);
        } else {
            document.getElementById('video_path').value = '';
            updateSelectedDisplay('Selected upload: ' + file.name + sizeMb);
        }

        const nameField = document.getElementById('name');
        if (!nameField.value) {
            const filename = file.name.split('.')[0];
            nameField.value = filename.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }

    document.getElementById('video_path').addEventListener('input', function() {
        if (this.value.trim()) {
            try { document.getElementById('video_file_picker').value = ''; } catch(e) {}
            const cleaned = this.value.replace(/^"(.*)"$/, '$1').trim();
            updateSelectedDisplay('Local Path: ' + cleaned);
        }
    });

    function selectVideoFile() {
        fetch('{{ route('api.dialog.open-video') }}')
            .then(response => response.json())
            .then(data => {
                if (data && data.path) {
                    applySelectedFilePath(data.path);
                } else {
                    document.getElementById('video_file_picker').click();
                }
            })
            .catch(error => {
                console.warn('Native dialog unavailable, falling back to file picker:', error);
                document.getElementById('video_file_picker').click();
            });
    }

    const filePicker = document.getElementById('video_file_picker');
    filePicker.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            applySelectedFile(this.files[0]);
        }
    });

    const dropzone = document.getElementById('dropzone');
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-amber-500/60', 'bg-amber-500/[0.06]');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-amber-500/60', 'bg-amber-500/[0.06]');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            try {
                filePicker.files = files;
            } catch (err) {}
            applySelectedFile(files[0]);
        }
    });

    document.getElementById('create-project-form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        const text = document.getElementById('submit-text');
        const spinner = document.getElementById('submit-spinner');
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        text.innerText = 'Creating Project & Analyzing Video...';
        spinner.classList.remove('hidden');
    });
</script>
@endsection
