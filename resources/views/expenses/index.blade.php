@extends('layouts.app')
@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-gray-800 shadow-lg rounded-lg text-white flex flex-col">

        <h2 class="text-xl font-bold mb-2 text-gray-200">Budget Summary</h2>
        <div class="w-full">
            <table class="w-full border-collapse border border-gray-600 mb-6">
                <thead>
                <tr class="bg-gray-700">
                    <th class="border border-gray-500 px-6 py-3">Category</th>
                    <th class="border border-gray-500 px-6 py-3">Allocated Budget</th>
                    <th class="border border-gray-500 px-6 py-3">Spent</th>
                    <th class="border border-gray-500 px-6 py-3">Remaining</th>
                </tr>
                </thead>
                <tbody>
                @foreach($categoryBudgets as $budget)
                    <tr>
                        <td class="border border-gray-500 px-6 py-3">{{ $budget['category'] }}</td>
                        <td class="border border-gray-500 px-6 py-3">Rs.{{ $budget['allocated'] }}</td>
                        <td class="border border-gray-500 px-6 py-3 text-red-400">Rs.{{ $budget['spent'] }}</td>
                        <td class="border border-gray-500 px-6 py-3 {{ $budget['remaining'] < 0 ? 'text-red-600' : 'text-green-400' }}">
                            Rs.{{ $budget['remaining'] }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-6 py-3">
                            <div class="w-full bg-gray-600 rounded-md relative">
                                <div class="h-4 rounded-md text-center text-xs text-white"
                                     style="width: {{ min($budget['budget_used_percentage'], 100) }}%;
                                               background-color: {{ $budget['budget_used_percentage'] > 100 ? 'red' : 'green' }};">
                                    {{ $budget['budget_used_percentage'] }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                    @if(isset($categoryWarnings[$budget['category']]))
                        <tr>
                            <td colspan="4" class="px-6 py-3">
                                <div class="bg-yellow-500 text-white p-2 rounded-md text-center">
                                    {{ $categoryWarnings[$budget['category']] }}
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-4 text-center">
            <a href="{{ route('expenses.create') }}" class="bg-blue-500 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                + Add Expense
            </a>
        </div>

        <h2 class="text-xl font-bold mb-4 text-gray-200">Expense History</h2>

        @if(session('success'))
            <div class="text-green-400 mb-4">{{ session('success') }}</div>
        @endif

        <div class="mb-6 bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="text-xl font-semibold">
                    Total Expenses: Rs.{{ number_format($totalExpenses, 2) }}
                </div>
            </div>

            <form action="{{ route('expenses.index') }}" method="GET" class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-300">From Date</label>
                        <input type="date"
                               name="start_date"
                               id="start_date"
                               value="{{ $startDate->format('Y-m-d') }}"
                               class="mt-1 bg-gray-600 text-white px-4 py-2 rounded-md">
                        @error('date')
                        <p class="text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-300">To Date</label>
                        <input type="date"
                               name="end_date"
                               id="end_date"
                               value="{{ $endDate->format('Y-m-d') }}"
                               class="mt-1 bg-gray-600 text-white px-4 py-2 rounded-md">
                    </div>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700 mt-6">
                    Filter
                </button>
            </form>
        </div>

        <div class="w-full">
            <table class="w-full border-collapse border border-gray-600">
                <thead>
                <tr class="bg-gray-700">
                    <th class="border border-gray-500 px-6 py-3">Date</th>
                    <th class="border border-gray-500 px-6 py-3">Category</th>
                    <th class="border border-gray-500 px-6 py-3">Amount</th>
                    <th class="border border-gray-500 px-6 py-3">Description</th>
                    <th class="border border-gray-500 px-6 py-3">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td class="border border-gray-500 px-6 py-3">{{ $expense->date }}</td>
                        <td class="border border-gray-500 px-6 py-3">{{ $expense->category->name ?? 'N/A' }}</td>
                        <td class="border border-gray-500 px-6 py-3">Rs.{{ number_format($expense->amount, 2) }}</td>
                        <td class="border border-gray-500 px-6 py-3">{{ $expense->description ?? 'N/A' }}</td>
                        <td class="border border-gray-500 px-6 py-3">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('expenses.edit', $expense) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Edit
                                </a>
                                <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-800">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 p-4">No expenses recorded yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $expenses->links() }}
        </div>

    </div>
@endsection
