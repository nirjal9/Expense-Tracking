@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-200">
                <i class="fas fa-money-bill-wave me-2"></i>Income
            </h2>
            <a href="{{ route('incomes.create') }}" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus me-2"></i>Add Income
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-500 text-white p-4 rounded-md mb-6">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 bg-gray-700 p-4 rounded-lg">
            <div class="text-xl font-semibold text-gray-200">
                <i class="fas fa-chart-line me-2"></i>
                Total Monthly Income:
                <span class="text-green-400">Rs.{{ number_format(auth()->user()->total_income, 2) }}</span>
            </div>
        </div>

        @if($incomes->isEmpty())
            <div class="text-center py-8">
                <i class="fas fa-money-bill-wave fa-4x text-gray-400 mb-4"></i>
                <h4 class="text-gray-400 mb-2">No income entries added yet</h4>
                <p class="text-gray-400 mb-4">Start tracking your income by adding your first entry</p>
                <a href="{{ route('incomes.create') }}" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus me-2"></i>Add Income
                </a>
            </div>
        @else
            <div class="w-full">
                <table class="w-full border-collapse border border-gray-600">
                    <thead>
                    <tr class="bg-gray-700">
                        <th class="border border-gray-500 px-6 py-3">Date</th>
                        <th class="border border-gray-500 px-6 py-3">Description</th>
                        <th class="border border-gray-500 px-6 py-3">Amount</th>
                        <th class="border border-gray-500 px-6 py-3">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($incomes as $income)
                        <tr>
                            <td class="border border-gray-500 px-6 py-3">
                                <div class="flex items-center">
                                    <i class="far fa-calendar-alt me-2 text-gray-400"></i>
                                    {{ $income->date->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="border border-gray-500 px-6 py-3">
                                <div class="flex items-center">
                                    <i class="far fa-file-alt me-2 text-gray-400"></i>
                                    {{ $income->description }}
                                </div>
                            </td>
                            <td class="border border-gray-500 px-6 py-3 text-green-400">
                                <div class="flex items-center">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    Rs.{{ number_format($income->amount, 2) }}
                                </div>
                            </td>
                            <td class="border border-gray-500 px-6 py-3">
                                <div class="flex justify-center space-x-2">
                                    <a href="{{ route('incomes.edit', $income) }}"
                                       class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                                       title="Edit">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a>
                                    <form action="{{ route('incomes.destroy', $income) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this income entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-800"
                                                title="Delete">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
