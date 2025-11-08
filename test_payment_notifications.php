<?php

/**
 * Test Script for Payment Notifications System
 * 
 * This script demonstrates how the payment notification system works
 * with sample email and SMS content.
 */

require_once 'vendor/autoload.php';

// Sample email and SMS content for testing
$sampleData = [
    'esewa_email' => [
        'subject' => 'Payment Confirmation - eSewa',
        'content' => 'Dear User, Payment of Rs. 500.00 to ABC Store successful. Transaction ID: ES123456789. Date: 15-Jan-2024. Thank you for using eSewa.'
    ],
    'khalti_email' => [
        'subject' => 'Payment Successful - Khalti',
        'content' => 'Payment of Rs. 200.00 to Restaurant XYZ successful via Khalti. Transaction ID: KHT123456. Date: 15-Jan-2024. Available Balance: Rs. 1,500.00'
    ],
    'bank_sms' => [
        'content' => 'Rs. 1,500.00 debited from A/C **1234 on 15-Jan-24 at Petrol Pump ABC. Avl Bal: Rs. 25,000.00'
    ],
    'nmb_sms' => [
        'content' => 'NMB Bank: Rs. 800.00 debited from A/C **5678 on 15-Jan-24 at Hospital XYZ. Avl Bal: Rs. 15,000.00'
    ]
];

echo "=== Payment Notifications System Test ===\n\n";

// Test email parsing
echo "1. Testing Email Parsing:\n";
echo "----------------------------------------\n";

foreach (['esewa_email', 'khalti_email'] as $key) {
    $data = $sampleData[$key];
    echo "Testing: {$data['subject']}\n";
    echo "Content: {$data['content']}\n";
    
    // Simulate parsing (in real implementation, this would use the actual parser)
    $parsed = parseEmailContent($data['content']);
    echo "Parsed Result: " . json_encode($parsed, JSON_PRETTY_PRINT) . "\n\n";
}

// Test SMS parsing
echo "2. Testing SMS Parsing:\n";
echo "----------------------------------------\n";

foreach (['bank_sms', 'nmb_sms'] as $key) {
    $data = $sampleData[$key];
    echo "Testing: {$key}\n";
    echo "Content: {$data['content']}\n";
    
    // Simulate parsing
    $parsed = parseSMSContent($data['content']);
    echo "Parsed Result: " . json_encode($parsed, JSON_PRETTY_PRINT) . "\n\n";
}

// Test categorization
echo "3. Testing Auto-Categorization:\n";
echo "----------------------------------------\n";

$merchants = ['ABC Store', 'Restaurant XYZ', 'Petrol Pump ABC', 'Hospital XYZ'];
foreach ($merchants as $merchant) {
    $category = categorizeMerchant($merchant);
    echo "Merchant: {$merchant} -> Category: {$category}\n";
}

echo "\n=== Test Complete ===\n";

/**
 * Simulate email content parsing
 */
function parseEmailContent($content) {
    $patterns = [
        'amount' => '/Rs\.?\s*([0-9,]+\.?[0-9]*)/i',
        'merchant' => '/to\s+([^.]+?)(?:\s+successful|\.|$)/i',
        'transaction_id' => '/transaction\s*(?:id|no)\.?\s*:?\s*([A-Z0-9]+)/i',
        'date' => '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
    ];

    $data = [];
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $data[$key] = trim($matches[1]);
        }
    }

    if (empty($data['amount'])) {
        return null;
    }

    return [
        'amount' => (float) str_replace(',', '', $data['amount']),
        'merchant' => $data['merchant'] ?? 'Unknown Merchant',
        'transaction_id' => $data['transaction_id'] ?? null,
        'date' => $data['date'] ?? null,
        'description' => "Payment to {$data['merchant']} via Email",
        'type' => 'expense',
        'source' => 'email'
    ];
}

/**
 * Simulate SMS content parsing
 */
function parseSMSContent($content) {
    $patterns = [
        'amount' => '/Rs\.?\s*([0-9,]+\.?[0-9]*)/i',
        'merchant' => '/at\s+([^.]+?)(?:\s+on|\.|$)/i',
        'account' => '/A\/C\s*\*+(\d+)/i',
        'date' => '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
        'balance' => '/Avl\s+Bal:?\s*Rs\.?\s*([0-9,]+\.?[0-9]*)/i'
    ];

    $data = [];
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $data[$key] = trim($matches[1]);
        }
    }

    if (empty($data['amount'])) {
        return null;
    }

    return [
        'amount' => (float) str_replace(',', '', $data['amount']),
        'merchant' => $data['merchant'] ?? 'Unknown Merchant',
        'account' => $data['account'] ?? null,
        'date' => $data['date'] ?? null,
        'balance' => isset($data['balance']) ? (float) str_replace(',', '', $data['balance']) : null,
        'description' => "Payment to {$data['merchant']} via Bank",
        'type' => 'expense',
        'source' => 'sms'
    ];
}

/**
 * Simulate merchant categorization
 */
function categorizeMerchant($merchant) {
    $merchantCategories = [
        'ABC Store' => 'Shopping',
        'Restaurant XYZ' => 'Food & Dining',
        'Petrol Pump ABC' => 'Transportation',
        'Hospital XYZ' => 'Healthcare',
    ];

    // Check exact match first
    if (isset($merchantCategories[$merchant])) {
        return $merchantCategories[$merchant];
    }

    // Check keyword-based categorization
    $merchant = strtolower($merchant);
    
    if (str_contains($merchant, 'restaurant') || str_contains($merchant, 'cafe') || str_contains($merchant, 'food')) {
        return 'Food & Dining';
    } elseif (str_contains($merchant, 'store') || str_contains($merchant, 'shop') || str_contains($merchant, 'mall')) {
        return 'Shopping';
    } elseif (str_contains($merchant, 'petrol') || str_contains($merchant, 'fuel') || str_contains($merchant, 'gas')) {
        return 'Transportation';
    } elseif (str_contains($merchant, 'hospital') || str_contains($merchant, 'clinic') || str_contains($merchant, 'medical')) {
        return 'Healthcare';
    }

    return 'Other';
}




































