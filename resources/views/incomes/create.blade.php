@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-200">
                <i class="fas fa-plus-circle me-2"></i>Add Income
            </h2>
            <a href="{{ route('incomes.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>

        <form method="POST" action="{{ route('incomes.store') }}" class="space-y-6">
            @csrf

            <div class="space-y-2">
                <label for="description" class="block text-sm font-medium text-gray-300">
                    <i class="fas fa-file-alt me-2"></i>Description
                </label>
                <div class="flex items-center">
                <span class="bg-gray-700 text-gray-400 px-4 py-2 rounded-l-md">
                    <i class="fas fa-pencil-alt"></i>
                </span>
                    <input type="text"
                           class="bg-gray-700 text-white px-4 py-2 rounded-r-md w-full focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                           id="description"
                           name="description"
                           value="{{ old('description') }}"
                           placeholder="Enter income description"
                           required>
                </div>
                @error('description')
                <p class="text-red-500 text-sm mt-1">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                </p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="amount" class="block text-sm font-medium text-gray-300">
                    <i class="fas fa-dollar-sign me-2"></i>Amount
                </label>
                <div class="flex items-center">
                <span class="bg-gray-700 text-gray-400 px-4 py-2 rounded-l-md">
                    <i class="fas fa-money-bill-wave"></i>
                </span>
                    <input type="number"
                           step="0.01"
                           class="bg-gray-700 text-white px-4 py-2 rounded-r-md w-full focus:outline-none focus:ring-2 focus:ring-blue-500 @error('amount') border-red-500 @enderror"
                           id="amount"
                           name="amount"
                           value="{{ old('amount') }}"
                           placeholder="0.00"
                           required>
                </div>
                @error('amount')
                <p class="text-red-500 text-sm mt-1">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                </p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="date" class="block text-sm font-medium text-gray-300">
                    <i class="far fa-calendar-alt me-2"></i>Date
                </label>
                <div class="flex items-center">
                <span class="bg-gray-700 text-gray-400 px-4 py-2 rounded-l-md">
                    <i class="fas fa-calendar"></i>
                </span>
                    <input type="date"
                           class="bg-gray-700 text-white px-4 py-2 rounded-r-md w-full focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date') border-red-500 @enderror"
                           id="date"
                           name="date"
                           value="{{ old('date', now()->format('Y-m-d')) }}"
                           required>
                </div>
                @error('date')
                <p class="text-red-500 text-sm mt-1">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                </p>
                @enderror
            </div>

            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save me-2"></i>Add Income
                </button>
                <a href="{{ route('incomes.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

@endsection
