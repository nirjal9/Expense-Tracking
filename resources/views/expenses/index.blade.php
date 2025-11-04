@extends('layouts.app')
@section('content')
    <div class="max-w-6xl mx-auto p-6 bg-white dark:bg-gray-800 shadow-lg rounded-lg text-gray-900 dark:text-white flex flex-col">

        <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-200">Budget Summary</h2>
        <div class="w-full">
            <table class="w-full border-collapse border border-gray-200 dark:border-gray-600 mb-6">
                <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Category</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Allocated Budget</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Spent</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Remaining</th>
                </tr>
                </thead>
                <tbody>
                @foreach($categoryBudgets as $budget)
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $budget['category'] }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ $budget['allocated'] }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3 text-red-600 dark:text-red-400">Rs.{{ $budget['spent'] }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3 {{ $budget['remaining'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rs.{{ $budget['remaining'] }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-6 py-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-md relative">
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

        <div class="mb-4 text-center space-x-4">
            <a href="{{ route('expenses.create') }}" class="bg-blue-500 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                + Add Expense
            </a>
            <button id="add-from-message-btn" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700">
                📱 Add from Message
            </button>
        </div>

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-200">Expense History</h2>

        @if(session('success'))
            <div class="text-green-600 dark:text-green-400 mb-4">{{ session('success') }}</div>
        @endif

        <div class="mb-6 bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="text-xl font-semibold text-gray-900 dark:text-gray-200">
                    Total Expenses: Rs.{{ number_format($totalExpenses, 2) }}
                </div>
            </div>

            <form action="{{ route('expenses.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Date</label>
                        <input type="date"
                               name="start_date"
                               id="start_date"
                               value="{{ $startDate->format('Y-m-d') }}"
                               class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                        @error('date')
                        <p class="text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To Date</label>
                        <input type="date"
                               name="end_date"
                               id="end_date"
                               value="{{ $endDate->format('Y-m-d') }}"
                               class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                    </div>
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Description</label>
                        <input type="text"
                               name="search"
                               id="search"
                               value="{{ request('search') }}"
                               placeholder="Search by description"
                               class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                        <select name="category_id" id="category_id" class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                            <option value="">All Categories</option>
                            @foreach($allCategories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="min_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Amount</label>
                        <input type="number"
                               name="min_amount"
                               id="min_amount"
                               value="{{ request('min_amount') }}"
                               min="0"
                               step="0.01"
                               placeholder="Min amount"
                               class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                    </div>
                    <div>
                        <label for="max_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Amount</label>
                        <input type="number"
                               name="max_amount"
                               id="max_amount"
                               value="{{ request('max_amount') }}"
                               min="0"
                               step="0.01"
                               placeholder="Max amount"
                               class="mt-1 bg-white dark:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-md w-full border border-gray-300 dark:border-gray-500">
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Search
                    </button>
                    <a href="{{ route('expenses.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <div class="w-full">
            <table class="w-full border-collapse border border-gray-200 dark:border-gray-600">
                <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Date</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Category</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Amount</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Description</th>
                    <th class="border border-gray-200 dark:border-gray-600 px-6 py-3">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $expense->date }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $expense->category->name ?? 'N/A' }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">Rs.{{ number_format($expense->amount, 2) }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">{{ $expense->description ?? 'N/A' }}</td>
                        <td class="border border-gray-200 dark:border-gray-600 px-6 py-3">
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
                        <td colspan="5" class="text-center text-gray-500 dark:text-gray-400 p-4">No expenses recorded yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $expenses->appends(request()->except('page'))->links() }}
        </div>

    </div>

<!-- Add from Message Modal -->
<div id="add-from-message-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">📱 Add Expense from Message</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Paste your transaction SMS or email below and we'll automatically create an expense for you!
            </p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message Type</label>
                <select id="message-source" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="sms">SMS</option>
                    <option value="email">Email</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transaction Message</label>
                <textarea id="message-content" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Paste your eSewa, Khalti, or bank transaction message here...

Example: Rs. 500.00 payment to Shell Petrol Station successful via eSewa. Transaction ID: ESW123456"></textarea>
            </div>
            <!-- Step 1: Parse Message -->
            <div id="parse-step" class="flex justify-end space-x-3">
                <button onclick="closeMessageModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                <button onclick="parseMessage()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Parse Message</button>
            </div>
            
            <!-- Step 2: Preview & Create -->
            <div id="preview-step" class="hidden">
                <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-green-800 dark:text-green-200 mb-3">✅ Message Parsed Successfully!</h4>
                    <div id="parsed-data" class="space-y-3">
                        <!-- Parsed data will be inserted here -->
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                    <select id="expense-category" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        @foreach($allCategories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">💡 Correct the category if needed - this helps the system learn!</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="backToParseStep()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Back</button>
                    <button onclick="createExpenseFromParsedData()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Create Expense</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for the "Add from Message" button
    document.getElementById('add-from-message-btn').addEventListener('click', function() {
        document.getElementById('add-from-message-modal').classList.remove('hidden');
    });
});

let parsedExpenseData = null;

function closeMessageModal() {
    document.getElementById('add-from-message-modal').classList.add('hidden');
    document.getElementById('message-content').value = '';
    // Reset to step 1
    document.getElementById('parse-step').classList.remove('hidden');
    document.getElementById('preview-step').classList.add('hidden');
    parsedExpenseData = null;
}

function parseMessage() {
    const content = document.getElementById('message-content').value;
    const source = document.getElementById('message-source').value;
    
    if (!content.trim()) {
        alert('Please enter your transaction message first');
        return;
    }
    
    // Add loading state
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Parsing...';
    
    console.log('Parsing message:', content);
    
    // First, test parsing to get the data
    fetch('/payment-notifications/test-parsing', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ source: source, content: content })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Parse response:', data);
        if (data.success && data.parsed_data) {
            // Store parsed data
            parsedExpenseData = data.parsed_data;
            
            // Get best suggestion from categorization_suggestions
            let bestSuggestion = null;
            if (data.categorization_suggestions && data.categorization_suggestions.length > 0) {
                bestSuggestion = data.categorization_suggestions[0]; // First one is highest scored
            }
            
            // Show preview
            showPreview(data.parsed_data, bestSuggestion);
            
            // Switch to step 2
            document.getElementById('parse-step').classList.add('hidden');
            document.getElementById('preview-step').classList.remove('hidden');
        } else {
            alert(`❌ Could not parse message: ${data.message || 'Unknown error'}\n\nTip: Make sure your message contains "eSewa", "Khalti", or bank keywords like "debited from A/C"`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`❌ Error parsing message: ${error.message}`);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

function showPreview(parsedData, bestSuggestion) {
    const previewDiv = document.getElementById('parsed-data');
    
    let html = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Amount:</span>
                <span class="text-lg font-bold text-green-600 dark:text-green-400">Rs. ${parsedData.amount}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Merchant:</span>
                <span class="font-medium text-gray-900 dark:text-white">${parsedData.merchant || 'Unknown'}</span>
            </div>
        </div>
    `;
    
    if (parsedData.transaction_id) {
        html += `
            <div>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Transaction ID:</span>
                <span class="font-mono text-sm text-gray-700 dark:text-gray-300">${parsedData.transaction_id}</span>
            </div>
        `;
    }
    
    previewDiv.innerHTML = html;
    
    // Set suggested category if available
    if (bestSuggestion && bestSuggestion.category) {
        const categorySelect = document.getElementById('expense-category');
        categorySelect.value = bestSuggestion.category.id;
        
        // Clear any existing confidence text
        const existingConfidence = categorySelect.parentNode.querySelector('.confidence-text');
        if (existingConfidence) {
            existingConfidence.remove();
        }
        
        // Show confidence if available
        if (bestSuggestion.confidence || bestSuggestion.score) {
            const confidenceText = document.createElement('span');
            confidenceText.className = 'text-xs text-blue-600 dark:text-blue-400 ml-2 confidence-text';
            const confidence = bestSuggestion.confidence || bestSuggestion.score;
            confidenceText.textContent = `(${Math.round(confidence * 100)}% confidence)`;
            categorySelect.parentNode.querySelector('label').appendChild(confidenceText);
        }
    }
}

function backToParseStep() {
    document.getElementById('preview-step').classList.add('hidden');
    document.getElementById('parse-step').classList.remove('hidden');
}

function createExpenseFromParsedData() {
    if (!parsedExpenseData) {
        alert('No parsed data available');
        return;
    }
    
    const selectedCategoryId = document.getElementById('expense-category').value;
    const selectedCategoryName = document.getElementById('expense-category').options[document.getElementById('expense-category').selectedIndex].text;
    
    // Add loading state
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Creating...';
    
    // Create expense with selected category
    const expenseData = {
        ...parsedExpenseData,
        category_id: selectedCategoryId,
        source: document.getElementById('message-source').value
    };
    
    console.log('Creating expense with data:', expenseData);
    
    fetch('/test-create-from-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            content: document.getElementById('message-content').value,
            category_id: selectedCategoryId
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Create response:', data);
        if (data.success) {
            alert(`✅ Expense created successfully!\n\nAmount: Rs. ${data.amount}\nMerchant: ${data.merchant}\nCategory: ${selectedCategoryName}\n\n🧠 The system learned from your category choice!`);
            closeMessageModal();
            location.reload(); // Refresh to show the new expense
        } else {
            alert(`❌ Error creating expense: ${data.error || 'Unknown error'}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`❌ Error creating expense: ${error.message}`);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('add-from-message-modal');
    if (event.target === modal) {
        closeMessageModal();
    }
});
</script>

@endsection
