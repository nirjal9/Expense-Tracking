@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white">
        <h1 class="text-2xl font-bold text-gray-200 mb-6">Edit Category</h1>

        @if ($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('categories.update', $categoryWithPivot->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-gray-700 p-6 rounded-lg">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Category Name</label>
                        <input type="text" name="name" id="name"
                               class="mt-1 block w-full rounded-md bg-gray-600 border-gray-500 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               value="{{ $categoryWithPivot->name }}" required>
                        @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="budget_percentage" class="block text-sm font-medium text-gray-300">
                            Budget Allocation (%)
                        </label>
                        <div class="mt-1 relative">
                            <input type="number" name="budget_percentage" id="budget_percentage"
                                   class="block w-full rounded-md bg-gray-600 border-gray-500 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ $categoryWithPivot->pivot->budget_percentage ?? 0 }}"
                                   min="0" max="100" required
                                   onchange="updateProgressBar(this.value)">
                            <div class="mt-2">
                                <div class="h-2 bg-gray-600 rounded-full overflow-hidden">
                                    <div id="budget-progress" class="h-full bg-blue-500 rounded-full"
                                         style="width: {{ $categoryWithPivot->pivot->budget_percentage ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                        @error('budget_percentage')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('categories.index') }}"
                   class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Category
                </button>
            </div>
        </form>
    </div>

    <script>
        function updateProgressBar(value) {
            const progressBar = document.getElementById('budget-progress');
            progressBar.style.width = value + '%';
        }
    </script>
@endsection
