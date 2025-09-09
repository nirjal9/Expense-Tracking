<?php

namespace App\Services\PaymentNotification;

use App\Models\User;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaymentNotificationService
{
    private GmailService $gmailService;
    private EmailParserService $emailParser;
    private SMSParserService $smsParser;
    private AutoCategorizationService $categorizationService;
    private ExpenseCreationService $expenseCreationService;

    public function __construct(
        GmailService $gmailService,
        EmailParserService $emailParser,
        SMSParserService $smsParser,
        AutoCategorizationService $categorizationService,
        ExpenseCreationService $expenseCreationService
    ) {
        $this->gmailService = $gmailService;
        $this->emailParser = $emailParser;
        $this->smsParser = $smsParser;
        $this->categorizationService = $categorizationService;
        $this->expenseCreationService = $expenseCreationService;
    }

    /**
     * Process email notifications for a user
     */
    public function processEmailNotifications(User $user, int $maxEmails = 10): array
    {
        try {
            Log::info("Processing email notifications for user {$user->id}");

            // Check if Gmail is configured
            if (!$this->gmailService->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Gmail API credentials not configured. Please set up Google API credentials.',
                    'expenses_created' => 0
                ];
            }

            // Check if Gmail is authenticated
            if (!$this->gmailService->isAuthenticated()) {
                return [
                    'success' => false,
                    'message' => 'Gmail not authenticated. Please connect your Gmail account.',
                    'expenses_created' => 0
                ];
            }

            // Fetch payment notification emails
            $emails = $this->gmailService->fetchPaymentNotifications($maxEmails);
            
            if (empty($emails)) {
                return [
                    'success' => true,
                    'message' => 'No payment notification emails found',
                    'expenses_created' => 0
                ];
            }

            $expensesCreated = 0;
            $processedEmails = [];

            foreach ($emails as $email) {
                try {
                    // Parse email content
                    $transactionData = $this->emailParser->parse($email['body'], 'email');
                    
                    if (!$transactionData) {
                        Log::info("Could not parse email: {$email['subject']}");
                        continue;
                    }

                    // Create expense
                    $expense = $this->expenseCreationService->createExpense(
                        $transactionData,
                        $user,
                        ['requires_approval' => true, 'learn_from_categorization' => true]
                    );

                    if ($expense) {
                        $expensesCreated++;
                        $processedEmails[] = [
                            'email_id' => $email['id'],
                            'expense_id' => $expense->id,
                            'amount' => $expense->amount,
                            'merchant' => $expense->merchant
                        ];

                        // Mark email as read
                        $this->gmailService->markAsRead($email['id']);
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to process email {$email['id']}: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => "Processed {count($emails)} emails, created {$expensesCreated} expenses",
                'expenses_created' => $expensesCreated,
                'processed_emails' => $processedEmails
            ];

        } catch (\Exception $e) {
            Log::error("Email notification processing failed for user {$user->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email processing failed: ' . $e->getMessage(),
                'expenses_created' => 0
            ];
        }
    }

    /**
     * Process SMS notifications for a user
     */
    public function processSMSNotifications(User $user, array $smsMessages): array
    {
        try {
            Log::info("Processing SMS notifications for user {$user->id}");

            $expensesCreated = 0;
            $processedSMS = [];

            foreach ($smsMessages as $sms) {
                try {
                    // Parse SMS content
                    $transactionData = $this->smsParser->parse($sms['content'], 'sms');
                    
                    if (!$transactionData) {
                        Log::info("Could not parse SMS: {$sms['content']}");
                        continue;
                    }

                    // Create expense
                    $expense = $this->expenseCreationService->createExpense(
                        $transactionData,
                        $user,
                        ['requires_approval' => true, 'learn_from_categorization' => true]
                    );

                    if ($expense) {
                        $expensesCreated++;
                        $processedSMS[] = [
                            'sms_id' => $sms['id'] ?? null,
                            'expense_id' => $expense->id,
                            'amount' => $expense->amount,
                            'merchant' => $expense->merchant
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to process SMS: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => "Processed {count($smsMessages)} SMS messages, created {$expensesCreated} expenses",
                'expenses_created' => $expensesCreated,
                'processed_sms' => $processedSMS
            ];

        } catch (\Exception $e) {
            Log::error("SMS notification processing failed for user {$user->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'SMS processing failed: ' . $e->getMessage(),
                'expenses_created' => 0
            ];
        }
    }

    /**
     * Process webhook notifications
     */
    public function processWebhookNotification(User $user, array $webhookData): array
    {
        try {
            Log::info("Processing webhook notification for user {$user->id}");

            // Determine the source and parse accordingly
            $source = $webhookData['source'] ?? 'unknown';
            $content = $webhookData['content'] ?? '';

            $transactionData = null;

            if ($source === 'email') {
                $transactionData = $this->emailParser->parse($content, 'webhook');
            } elseif ($source === 'sms') {
                $transactionData = $this->smsParser->parse($content, 'webhook');
            }

            if (!$transactionData) {
                return [
                    'success' => false,
                    'message' => 'Could not parse webhook content',
                    'expense_id' => null
                ];
            }

            // Create expense
            $expense = $this->expenseCreationService->createExpense(
                $transactionData,
                $user,
                ['requires_approval' => false, 'learn_from_categorization' => true]
            );

            if ($expense) {
                return [
                    'success' => true,
                    'message' => 'Expense created successfully',
                    'expense_id' => $expense->id,
                    'amount' => $expense->amount,
                    'merchant' => $expense->merchant
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create expense',
                'expense_id' => null
            ];

        } catch (\Exception $e) {
            Log::error("Webhook notification processing failed for user {$user->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
                'expense_id' => null
            ];
        }
    }

    /**
     * Get Gmail authentication URL
     */
    public function getGmailAuthUrl(): string
    {
        return $this->gmailService->getAuthUrl();
    }

    /**
     * Check if Gmail is configured
     */
    public function isGmailConfigured(): bool
    {
        return $this->gmailService->isConfigured();
    }

    /**
     * Authenticate Gmail with authorization code
     */
    public function authenticateGmail(string $authCode): bool
    {
        return $this->gmailService->authenticate($authCode);
    }

    /**
     * Check if Gmail is authenticated
     */
    public function isGmailAuthenticated(): bool
    {
        return $this->gmailService->isAuthenticated();
    }

    /**
     * Get auto-created expenses for a user
     */
    public function getAutoCreatedExpenses(User $user, string $status = 'all'): array
    {
        $query = $user->expenses()->autoCreated()->with(['category', 'autoCreatedExpense']);

        if ($status === 'pending') {
            $query->requiresApproval();
        } elseif ($status === 'approved') {
            $query->approved();
        } elseif ($status === 'rejected') {
            $query->rejected();
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Create expense from transaction data
     */
    public function createExpenseFromTransaction(array $transactionData, User $user): ?\App\Models\Expense
    {
        return $this->expenseCreationService->createExpense($transactionData, $user);
    }

    /**
     * Approve auto-created expense
     */
    public function approveExpense(Expense $expense, ?int $categoryId = null): bool
    {
        $category = $categoryId ? \App\Models\Category::find($categoryId) : null;
        return $this->expenseCreationService->approveExpense($expense, $category);
    }

    /**
     * Reject auto-created expense
     */
    public function rejectExpense(Expense $expense, string $reason = ''): bool
    {
        return $this->expenseCreationService->rejectExpense($expense, $reason);
    }

    /**
     * Get categorization suggestions for a merchant
     */
    public function getCategorizationSuggestions(string $merchant, string $description): array
    {
        return $this->categorizationService->getSuggestedCategories($merchant, $description);
    }

    /**
     * Add merchant category mapping
     */
    public function addMerchantMapping(string $merchant, int $categoryId, float $confidence = 0.8): bool
    {
        try {
            $category = \App\Models\Category::find($categoryId);
            if (!$category) {
                return false;
            }

            $this->categorizationService->addMerchantMapping($merchant, $category, $confidence);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add merchant mapping: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get service statistics
     */
    public function getStatistics(User $user): array
    {
        $expenseStats = $this->expenseCreationService->getStatistics($user);
        $categorizationStats = $this->categorizationService->getMappingStatistics();

        return [
            'expenses' => $expenseStats,
            'categorization' => $categorizationStats,
            'gmail_configured' => $this->isGmailConfigured(),
            'gmail_authenticated' => $this->isGmailAuthenticated()
        ];
    }

    /**
     * Test parsing with sample data
     */
    public function testParsing(string $content, string $source = 'email'): array
    {
        try {
            if ($source === 'email') {
                $transactionData = $this->emailParser->parse($content, 'test');
            } else {
                $transactionData = $this->smsParser->parse($content, 'test');
            }

            if ($transactionData) {
                $suggestions = $this->categorizationService->getSuggestedCategories(
                    $transactionData['merchant'],
                    $transactionData['description'] ?? ''
                );

                return [
                    'success' => true,
                    'parsed_data' => $transactionData,
                    'categorization_suggestions' => $suggestions
                ];
            }

            return [
                'success' => false,
                'message' => 'Could not parse content'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Parsing failed: ' . $e->getMessage()
            ];
        }
    }
}
