@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Payment Notifications</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Automatically track expenses from email and SMS notifications</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Gmail Status</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                <span id="gmail-status" class="text-yellow-600">Checking...</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Auto-Created</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="auto-created-count">
                                {{ $statistics['expenses']['total'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pending Approval</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="pending-count">
                                {{ $statistics['expenses']['pending'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Accuracy Rate</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white" id="accuracy-rate">
                                {{ number_format($statistics['categorization']['accuracy_rate'] ?? 0, 1) }}%
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gmail Authentication Section -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Gmail Integration</h3>
            <div id="gmail-auth-section">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Connect your Gmail account to automatically process payment notification emails.
                </p>
                <button id="gmail-auth-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Connect Gmail
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Process Notifications</h3>
            <div class="flex flex-wrap gap-4">
                <button id="process-emails-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50" disabled>
                    Process Emails
                </button>
                <button id="test-parsing-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    Test Parsing
                </button>
                <button id="create-test-expense-btn" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                    Create Test Expense
                </button>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    @if(count($autoCreatedExpenses) > 0)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Pending Approvals</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Merchant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($autoCreatedExpenses as $expense)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $expense['merchant'] ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                Rs. {{ number_format($expense['amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($expense['date'])->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($expense['source'] ?? 'Unknown') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="approveExpense({{ $expense['id'] }})" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                <button onclick="rejectExpense({{ $expense['id'] }})" class="text-red-600 hover:text-red-900">Reject</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Test Parsing Modal -->
<div id="test-parsing-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Test Parsing</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Source Type</label>
                <select id="test-source" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</label>
                <textarea id="test-content" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Paste email or SMS content here..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeTestModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                <button onclick="testParsing()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Test Parsing</button>
                <button onclick="createFromSMS()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Create Expense from SMS</button>
            </div>
            <div id="test-results" class="mt-4 hidden">
                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Results:</h4>
                <pre id="test-output" class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-sm overflow-auto"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Gmail Setup Modal -->
<div id="gmail-setup-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-10 mx-auto p-5 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Gmail API Setup Guide</h3>
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2">Step 1: Create Google Cloud Project</h4>
                    <ol class="list-decimal list-inside text-blue-700 space-y-1">
                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="underline">Google Cloud Console</a></li>
                        <li>Create a new project or select existing one</li>
                        <li>Enable Gmail API for your project</li>
                    </ol>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-semibold text-green-800 mb-2">Step 2: Create OAuth 2.0 Credentials</h4>
                    <ol class="list-decimal list-inside text-green-700 space-y-1">
                        <li>Go to "Credentials" in your Google Cloud project</li>
                        <li>Click "Create Credentials" → "OAuth 2.0 Client ID"</li>
                        <li>Set application type to "Web application"</li>
                        <li>Add authorized redirect URI: <code class="bg-gray-200 px-1 rounded">http://localhost:8001/payment-notifications/gmail/callback</code></li>
                    </ol>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-800 mb-2">Step 3: Add to .env File</h4>
                    <p class="text-yellow-700 mb-2">Add these lines to your <code class="bg-gray-200 px-1 rounded">.env</code> file:</p>
                    <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"><code>GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8001/payment-notifications/gmail/callback</code></pre>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="font-semibold text-purple-800 mb-2">Step 4: Restart Application</h4>
                    <p class="text-purple-700">After adding the credentials, restart your Laravel application:</p>
                    <pre class="bg-gray-100 p-3 rounded text-sm mt-2"><code>php artisan serve --port=8001</code></pre>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">Alternative: Test Without Gmail</h4>
                    <p class="text-gray-700">You can test the system without Gmail setup using the "Test Parsing" feature with sample data.</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeGmailSetupModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Close</button>
                <a href="https://console.cloud.google.com/" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Open Google Cloud Console</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkGmailStatus();
    
    document.getElementById('gmail-auth-btn').addEventListener('click', function() {
        if (this.textContent === 'Setup Gmail API') {
            showGmailSetupModal();
            return;
        }
        window.location.href = '/payment-notifications/gmail/auth-url';
    });
    
    document.getElementById('process-emails-btn').addEventListener('click', function() {
        processEmails();
    });
    
    document.getElementById('test-parsing-btn').addEventListener('click', function() {
        document.getElementById('test-parsing-modal').classList.remove('hidden');
    });
    
    document.getElementById('create-test-expense-btn').addEventListener('click', function() {
        createTestExpense();
    });
});

function checkGmailStatus() {
    fetch('/payment-notifications/statistics')
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('gmail-status');
            const processBtn = document.getElementById('process-emails-btn');
            const authBtn = document.getElementById('gmail-auth-btn');
            
            if (data.success) {
                if (data.statistics.gmail_configured) {
                    if (data.statistics.gmail_authenticated) {
                        statusElement.textContent = 'Connected';
                        statusElement.className = 'text-green-600';
                        processBtn.disabled = false;
                        authBtn.textContent = 'Reconnect Gmail';
                    } else {
                        statusElement.textContent = 'Not Connected';
                        statusElement.className = 'text-yellow-600';
                        processBtn.disabled = true;
                        authBtn.textContent = 'Connect Gmail';
                    }
                } else {
                    statusElement.textContent = 'Not Configured';
                    statusElement.className = 'text-red-600';
                    processBtn.disabled = true;
                    authBtn.textContent = 'Setup Gmail API';
                    authBtn.disabled = false;
                    authBtn.className = 'bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded';
                }
            } else {
                statusElement.textContent = 'Error';
                statusElement.className = 'text-red-600';
                processBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error checking Gmail status:', error);
            document.getElementById('gmail-status').textContent = 'Error';
        });
}

function processEmails() {
    const btn = document.getElementById('process-emails-btn');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    fetch('/payment-notifications/process-emails', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! ${data.message}`);
            location.reload();
        } else {
            alert(`Error: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Error processing emails:', error);
        alert('Error processing emails');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Process Emails';
    });
}

function approveExpense(expenseId) {
    if (confirm('Are you sure you want to approve this expense?')) {
        fetch(`/payment-notifications/expenses/${expenseId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Expense approved successfully');
                location.reload();
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error approving expense:', error);
            alert('Error approving expense');
        });
    }
}

function rejectExpense(expenseId) {
    const reason = prompt('Please provide a reason for rejection (optional):');
    if (reason !== null) {
        fetch(`/payment-notifications/expenses/${expenseId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Expense rejected successfully');
                location.reload();
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error rejecting expense:', error);
            alert('Error rejecting expense');
        });
    }
}

function closeTestModal() {
    document.getElementById('test-parsing-modal').classList.add('hidden');
    document.getElementById('test-results').classList.add('hidden');
    document.getElementById('test-content').value = '';
}

function showGmailSetupModal() {
    document.getElementById('gmail-setup-modal').classList.remove('hidden');
}

function closeGmailSetupModal() {
    document.getElementById('gmail-setup-modal').classList.add('hidden');
}

function createTestExpense() {
    if (confirm('This will create a real test expense in your database. Continue?')) {
        const btn = document.getElementById('create-test-expense-btn');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Creating...';
        
        // Sample transaction data
        const testData = {
            amount: 500.00,
            merchant: 'Test Petrol Pump',
            transaction_id: 'TEST' + Date.now(),
            description: 'Test expense created from dashboard'
        };
        
        fetch('/test-create-expense', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(testData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Test expense created successfully!\nAmount: Rs. ${data.amount}\nMerchant: ${data.merchant}\nExpense ID: ${data.expense_id}`);
                location.reload(); // Refresh to show the new expense
            } else {
                alert('Error creating test expense: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error creating test expense:', error);
            alert('Error creating test expense');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }
}

function createFromSMS() {
    const content = document.getElementById('test-content').value;
    const source = document.getElementById('test-source').value;
    
    if (!content.trim()) {
        alert('Please enter SMS content first');
        return;
    }
    
    if (source !== 'sms') {
        alert('Please select "SMS" as the source for this feature');
        return;
    }
    
    if (!confirm('This will create a real expense from your SMS content. Continue?')) {
        return;
    }
    
    fetch('/test-create-from-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Expense created from SMS successfully!\n\nExpense ID: ' + data.expense_id + '\nAmount: Rs. ' + data.amount + '\nMerchant: ' + data.merchant + '\nCategory: ' + data.category);
            location.reload(); // Refresh to show updated statistics
        } else {
            alert('❌ Error creating expense from SMS: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error creating expense from SMS: ' + error.message);
    });
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const gmailModal = document.getElementById('gmail-setup-modal');
    const testModal = document.getElementById('test-parsing-modal');
    
    if (event.target === gmailModal) {
        closeGmailSetupModal();
    }
    
    if (event.target === testModal) {
        closeTestModal();
    }
});

function testParsing() {
    const source = document.getElementById('test-source').value;
    const content = document.getElementById('test-content').value;
    
    if (!content.trim()) {
        alert('Please enter content to test');
        return;
    }
    
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
        document.getElementById('test-output').textContent = JSON.stringify(data, null, 2);
        document.getElementById('test-results').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error testing parsing:', error);
        document.getElementById('test-output').textContent = 'Error: ' + error.message;
        document.getElementById('test-results').classList.remove('hidden');
    });
}
</script>
@endsection

