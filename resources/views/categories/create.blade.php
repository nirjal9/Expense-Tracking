@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white">
        <h1 class="text-2xl font-bold text-gray-200 mb-6">Create Category</h1>

        @if ($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-gray-700 p-6 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-200 mb-4">Predefined Categories</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($predefinedCategories as $category)
                        <div class="bg-gray-600 rounded-lg p-4 hover:bg-gray-500 transition-colors duration-150">
                            <div class="flex items-center">
                                <input class="form-radio h-5 w-5 text-blue-500" type="radio"
                                       name="predefined_category" id="predefined-{{ $category->id }}"
                                       value="{{ $category->id }}">
                                <label class="ml-3 text-gray-200" for="predefined-{{ $category->id }}">
                                    {{ $category->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-gray-700 p-6 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-200 mb-4">Or Create Custom Category</h4>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Category Name</label>
                        <input type="text" name="name" id="name"
                               class="mt-1 block w-full rounded-md bg-gray-600 border-gray-500 text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               value="{{ old('name') }}">
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
                                   value="{{ old('budget_percentage') }}" min="0" max="100" required
                                   onchange="updateProgressBar(this.value)">
                            <div class="mt-2">
                                <div class="h-2 bg-gray-600 rounded-full overflow-hidden">
                                    <div id="budget-progress" class="h-full bg-blue-500 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        @error('budget_percentage')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Category
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

