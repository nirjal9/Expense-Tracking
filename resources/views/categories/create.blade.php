@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-200 mb-6">Create Category</h1>

        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 p-4 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">Predefined Categories</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($predefinedCategories as $category)
                        <div class="bg-white dark:bg-gray-600 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors duration-150">
                            <div class="flex items-center">
                                <input class="form-radio h-5 w-5 text-blue-500" type="radio"
                                       name="predefined_category" id="predefined-{{ $category->id }}"
                                       value="{{ $category->id }}">
                                <label class="ml-3 text-gray-900 dark:text-gray-200" for="predefined-{{ $category->id }}">
                                    {{ $category->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">Custom Category</h4>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category Name</label>
                        <input type="text" name="name" id="name"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>

                    <div>
                        <label for="budget_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Budget Percentage</label>
                        <input type="number" name="budget_percentage" id="budget_percentage"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               min="0" max="100" step="0.01" required>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                    Create Category
                </button>
            </div>
        </form>
    </div>
@endsection

