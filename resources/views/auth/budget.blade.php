@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white">
        <h2 class="text-2xl font-bold mb-6">Allocate Budget to Categories</h2>

        @if($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.budget') }}" class="space-y-6">
            @csrf

            @foreach($categories as $category)
                <div class="mb-4">
                    <label for="category-{{ $category->id }}" class="block text-sm font-medium text-gray-300 mb-2">
                        {{ $category->name }} (%):
                    </label>
                    <input
                        type="number"
                        name="percentages[{{ $category->id }}]"
                        id="category-{{ $category->id }}"
                        value="{{ old("percentages.{$category->id}") }}"
                        step="0.01"
                        min="0"
                        max="100"
                        class="block w-full rounded-md border-gray-600 bg-gray-700 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        required
                    >
                </div>
            @endforeach

            <div class="mt-6">
                <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                    Submit Budget Allocation
                </button>
            </div>
        </form>
    </div>
@endsection

