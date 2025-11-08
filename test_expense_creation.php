<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Complete Expense Creation Flow ===\n\n";

try {
    // Get a test user
    $user = App\Models\User::first();
    if (!$user) {
        echo "No users found. Creating a test user...\n";
        $user = App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'income' => 50000
        ]);
    }

    echo "Testing with user: {$user->name}\n\n";

    // Create the services
    $emailParser = new App\Services\PaymentNotification\EmailParserService();
    $categorizationService = new App\Services\PaymentNotification\AutoCategorizationService();
    $expenseCreationService = new App\Services\PaymentNotification\ExpenseCreationService($categorizationService);

    // Test email parsing and expense creation
    $esewaEmail = 'Dear User, Payment of Rs. 500.00 to ABC Store successful. Transaction ID: ES123456789. Date: 15-Jan-2024. Thank you for using eSewa.';
    $transactionData = $emailParser->parse($esewaEmail, 'email');

    echo "Parsed transaction data:\n";
    echo json_encode($transactionData, JSON_PRETTY_PRINT) . "\n\n";

    if ($transactionData) {
        echo "Creating expense...\n";
        $expense = $expenseCreationService->createExpense($transactionData, $user, ['requires_approval' => true]);
        
        if ($expense) {
            echo "✅ Expense created successfully!\n";
            echo "Expense ID: {$expense->id}\n";
            echo "Amount: Rs. {$expense->amount}\n";
            echo "Merchant: {$expense->merchant}\n";
            echo "Category: " . ($expense->category ? $expense->category->name : 'None') . "\n";
            echo "Requires Approval: " . ($expense->requires_approval ? 'Yes' : 'No') . "\n";
            echo "Source: {$expense->source}\n";
            echo "Notification Type: {$expense->notification_type}\n";
        } else {
            echo "❌ Failed to create expense\n";
        }
    } else {
        echo "❌ Failed to parse transaction data\n";
    }

    // Test SMS parsing and expense creation
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Testing SMS parsing and expense creation:\n\n";

    $bankSMS = 'Rs. 1,500.00 debited from A/C **1234 on 15-Jan-24 at Petrol Pump ABC. Avl Bal: Rs. 25,000.00';
    $smsParser = new App\Services\PaymentNotification\SMSParserService();
    $smsTransactionData = $smsParser->parse($bankSMS, 'sms');

    echo "Parsed SMS transaction data:\n";
    echo json_encode($smsTransactionData, JSON_PRETTY_PRINT) . "\n\n";

    if ($smsTransactionData) {
        echo "Creating expense from SMS...\n";
        $smsExpense = $expenseCreationService->createExpense($smsTransactionData, $user, ['requires_approval' => true]);
        
        if ($smsExpense) {
            echo "✅ SMS Expense created successfully!\n";
            echo "Expense ID: {$smsExpense->id}\n";
            echo "Amount: Rs. {$smsExpense->amount}\n";
            echo "Merchant: {$smsExpense->merchant}\n";
            echo "Category: " . ($smsExpense->category ? $smsExpense->category->name : 'None') . "\n";
            echo "Requires Approval: " . ($smsExpense->requires_approval ? 'Yes' : 'No') . "\n";
            echo "Source: {$smsExpense->source}\n";
            echo "Notification Type: {$smsExpense->notification_type}\n";
        } else {
            echo "❌ Failed to create SMS expense\n";
        }
    } else {
        echo "❌ Failed to parse SMS transaction data\n";
    }

    // Test approval workflow
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Testing approval workflow:\n\n";

    $pendingExpenses = $user->expenses()->autoCreated()->requiresApproval()->get();
    echo "Found {$pendingExpenses->count()} pending expenses\n";

    if ($pendingExpenses->count() > 0) {
        $expenseToApprove = $pendingExpenses->first();
        echo "Approving expense ID: {$expenseToApprove->id}\n";
        
        $approvalResult = $expenseCreationService->approveExpense($expenseToApprove);
        if ($approvalResult) {
            echo "✅ Expense approved successfully!\n";
        } else {
            echo "❌ Failed to approve expense\n";
        }
    }

    echo "\n=== Test Complete ===\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}




































