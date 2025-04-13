@extends('layouts.app')
@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white">
        <h2 class="text-2xl font-bold mb-6">Select Your Expense Categories</h2>

        @if($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.categories') }}" class="space-y-6">
            @csrf

            <div class="mb-6">
                <h4 class="text-lg font-semibold mb-4">Predefined Categories</h4>
                <div class="space-y-3">
                    @foreach($categories as $category)
                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="categories[]"
                                   value="{{ $category->id }}"
                                   id="category-{{ $category->id }}"
                                   class="rounded border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <label for="category-{{ $category->id }}" class="ml-2 text-gray-300">{{ $category->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-6">
                <h4 class="text-lg font-semibold mb-4">Add a custom category (optional)</h4>
                <input type="text"
                       name="custom_category"
                       placeholder="Enter custom category"
                       class="block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                    Next
                </button>
            </div>
        </form>
    </div>
@endsection
