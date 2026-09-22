@extends('layouts.app', ['showSidebar' => false])

@section('content')
<div class="max-w-2xl mx-auto flex flex-col items-center justify-center min-h-[80vh]">
    <div class="w-full text-center mb-10">
        <h1 class="text-3xl font-bold text-white mb-2">New Project</h1>
        <p class="text-gray-400">Select a cooking video to get started</p>
    </div>

    <form action="{{ route('project.store') }}" method="POST" class="w-full bg-[#1a1a2e] rounded-xl border border-gray-800 p-8 shadow-xl">
        @csrf
        
        <div class="mb-8">
            <div id="dropzone" onclick="selectVideoFile()" class="border-2 border-dashed border-gray-700 hover:border-amber-500 rounded-xl p-12 text-center cursor-pointer transition-colors group bg-gray-900/30 hover:bg-gray-800/50">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-800 group-hover:bg-amber-900/30 text-gray-400 group-hover:text-amber-500 mb-4 transition">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-medium text-gray-200 mb-1 group-hover:text-white">Click to select a video file</h3>
                <p class="text-sm text-gray-500">Supported: MP4, MOV, AVI, MKV, WEBM</p>
                <div id="selected-file-display" class="mt-4 text-amber-500 font-medium hidden break-all"></div>
            </div>
            
            <div class="mt-4">
                <label for="video_path" class="block text-sm font-medium text-gray-400 mb-1">Or enter path manually:</label>
                <input type="text" name="video_path" id="video_path" required class="block w-full bg-gray-800 border-gray-700 rounded-lg text-gray-200 focus:ring-amber-500 focus:border-amber-500 sm:text-sm px-4 py-2" placeholder="/path/to/video.mp4">
            </div>
        </div>

        <div class="mb-8">
            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Project Name (Optional)</label>
            <input type="text" name="name" id="name" class="block w-full bg-gray-800 border-gray-700 rounded-lg text-gray-200 focus:ring-amber-500 focus:border-amber-500 sm:text-sm px-4 py-3" placeholder="e.g. Chicken Alfredo Recipe">
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition">← Back to home</a>
            <button type="submit" id="submit-btn" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-lg font-medium transition shadow-md">
                Create Project
            </button>
        </div>
    </form>
</div>

<script>
    function selectVideoFile() {
        fetch('{{ route('api.dialog.open-video') }}')
            .then(response => response.json())
            .then(data => {
                if (data.path) {
                    document.getElementById('video_path').value = data.path;
                    document.getElementById('selected-file-display').innerText = data.path;
                    document.getElementById('selected-file-display').classList.remove('hidden');
                    
                    // Auto-fill project name if empty
                    const nameField = document.getElementById('name');
                    if (!nameField.value) {
                        // Extract filename without extension
                        const filename = data.path.split(/[/\\]/).pop().split('.')[0];
                        // Replace underscores and dashes with spaces, capitalize words
                        nameField.value = filename.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                }
            })
            .catch(error => console.error('Error opening dialog:', error));
    }
</script>
@endsection
