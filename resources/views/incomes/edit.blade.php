@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-200">
                <i class="fas fa-edit me-2"></i>Edit Income
            </h2>
            <a href="{{ route('incomes.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-700">
                <i class="fas fa-arrow-left me-2"></i>Back to Income
            </a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 p-4 rounded-md mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('incomes.update', $income) }}" method="POST" class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
            @csrf
            @method('PUT')
            <div class="mb-6">
                <label for="date" class="block text-gray-700 dark:text-gray-300 mb-2">Date</label>
                <input type="date" name="date" id="date" value="{{ old('date', $income->date->format('Y-m-d')) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label for="description" class="block text-gray-700 dark:text-gray-300 mb-2">Description</label>
                <input type="text" name="description" id="description" value="{{ old('description', $income->description) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter income description">
            </div>

            <div class="mb-6">
                <label for="amount" class="block text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount', $income->amount) }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter amount" step="0.01" min="0">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save me-2"></i>Update Income
                </button>
            </div>
        </form>
    </div>
@endsection
