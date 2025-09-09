<?php

namespace App\Services\PaymentNotification;

use App\Models\Expense;
use App\Models\Category;
use App\Models\User;
use App\Models\AutoCreatedExpense;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseCreationService
{
    private AutoCategorizationService $categorizationService;

    public function __construct(AutoCategorizationService $categorizationService)
    {
        $this->categorizationService = $categorizationService;
    }

    /**
     * Create expense from parsed transaction data
     */
    public function createExpense(array $transactionData, User $user, array $options = []): ?Expense
    {
        try {
            DB::beginTransaction();

            // Validate required data
            if (!$this->validateTransactionData($transactionData)) {
                throw new \Exception('Invalid transaction data');
            }

            // Check for duplicates
            if ($this->isDuplicateTransaction($transactionData, $user)) {
                Log::info("Duplicate transaction detected for user {$user->id}: " . json_encode($transactionData));
                DB::rollBack();
                return null;
            }

            // Auto-categorize the transaction
            $category = $this->categorizeTransaction($transactionData, $user);
            
            // Create the expense
            $expense = $this->createExpenseRecord($transactionData, $user, $category, $options);

            // Create auto-created expense record for tracking
            $this->createAutoCreatedExpenseRecord($expense, $transactionData);

            // Learn from the categorization if it was successful
            if ($category && isset($options['learn_from_categorization']) && $options['learn_from_categorization']) {
                $this->categorizationService->learn(
                    $transactionData['merchant'],
                    $transactionData['description'] ?? '',
                    $category
                );
            }

            DB::commit();
            
            Log::info("Successfully created expense {$expense->id} for user {$user->id}");
            return $expense;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create expense: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create multiple expenses from batch transaction data
     */
    public function createExpensesBatch(array $transactionsData, User $user, array $options = []): array
    {
        $createdExpenses = [];
        $failedExpenses = [];

        foreach ($transactionsData as $index => $transactionData) {
            try {
                $expense = $this->createExpense($transactionData, $user, $options);
                
                if ($expense) {
                    $createdExpenses[] = $expense;
                } else {
                    $failedExpenses[] = [
                        'index' => $index,
                        'data' => $transactionData,
                        'reason' => 'Creation failed or duplicate'
                    ];
                }
            } catch (\Exception $e) {
                $failedExpenses[] = [
                    'index' => $index,
                    'data' => $transactionData,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return [
            'created' => $createdExpenses,
            'failed' => $failedExpenses,
            'summary' => [
                'total' => count($transactionsData),
                'created' => count($createdExpenses),
                'failed' => count($failedExpenses)
            ]
        ];
    }

    /**
     * Validate transaction data
     */
    private function validateTransactionData(array $data): bool
    {
        $requiredFields = ['amount', 'merchant'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validate amount is numeric and positive
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Check for duplicate transactions
     */
    private function isDuplicateTransaction(array $transactionData, User $user): bool
    {
        $query = Expense::where('user_id', $user->id)
            ->where('amount', $transactionData['amount'])
            ->where('date', $transactionData['date'] ?? now()->format('Y-m-d'));

        // Check by transaction ID if available
        if (isset($transactionData['transaction_id'])) {
            $query->where('description', 'like', '%' . $transactionData['transaction_id'] . '%');
        } else {
            // Check by merchant and amount
            $query->where('description', 'like', '%' . $transactionData['merchant'] . '%');
        }

        return $query->exists();
    }

    /**
     * Categorize the transaction
     */
    private function categorizeTransaction(array $transactionData, User $user): ?Category
    {
        try {
            $category = $this->categorizationService->categorize(
                $transactionData['merchant'],
                $transactionData['description'] ?? '',
                ['user_id' => $user->id]
            );

            // If no category found, try to get user's default category or first available category
            if (!$category) {
                $category = $user->categories()->first();
                if (!$category) {
                    // If user has no categories, use the first available category
                    $category = Category::first();
                }
            }

            return $category;
        } catch (\Exception $e) {
            Log::error("Categorization failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create the expense record
     */
    private function createExpenseRecord(array $transactionData, User $user, ?Category $category, array $options): Expense
    {
        $expenseData = [
            'user_id' => $user->id,
            'amount' => $transactionData['amount'],
            'date' => $transactionData['date'] ?? now()->format('Y-m-d'),
            'description' => $this->generateDescription($transactionData),
            'category_id' => $category?->id,
            'is_auto_created' => true,
            'source' => $transactionData['source'] ?? 'unknown',
            'notification_type' => $transactionData['notification_type'] ?? 'unknown',
            'transaction_id' => $transactionData['transaction_id'] ?? null,
            'merchant' => $transactionData['merchant'],
            'requires_approval' => $options['requires_approval'] ?? true,
            'auto_created_at' => now(),
        ];

        return Expense::create($expenseData);
    }

    /**
     * Create auto-created expense tracking record
     */
    private function createAutoCreatedExpenseRecord(Expense $expense, array $transactionData): void
    {
        AutoCreatedExpense::create([
            'expense_id' => $expense->id,
            'user_id' => $expense->user_id,
            'source' => $transactionData['source'] ?? 'unknown',
            'notification_type' => $transactionData['notification_type'] ?? 'unknown',
            'raw_data' => json_encode($transactionData),
            'confidence_score' => $this->categorizationService->getConfidenceScore(
                $transactionData['merchant'],
                $transactionData['description'] ?? ''
            ),
            'status' => $expense->requires_approval ? 'pending_approval' : 'approved',
            'created_at' => now()
        ]);
    }

    /**
     * Generate description for the expense
     */
    private function generateDescription(array $transactionData): string
    {
        $description = $transactionData['description'] ?? '';
        
        if (empty($description)) {
            $description = "Payment to {$transactionData['merchant']}";
            
            if (isset($transactionData['notification_type'])) {
                $description .= " via " . ucfirst($transactionData['notification_type']);
            }
        }

        // Add transaction ID if available
        if (isset($transactionData['transaction_id'])) {
            $description .= " (TXN: {$transactionData['transaction_id']})";
        }

        return $description;
    }

    /**
     * Approve auto-created expense
     */
    public function approveExpense(Expense $expense, ?Category $category = null): bool
    {
        try {
            DB::beginTransaction();

            $expense->update([
                'requires_approval' => false,
                'category_id' => $category ? $category->id : $expense->category_id,
                'approved_at' => now()
            ]);

            // Update auto-created expense record
            $autoCreatedExpense = AutoCreatedExpense::where('expense_id', $expense->id)->first();
            if ($autoCreatedExpense) {
                $autoCreatedExpense->update([
                    'status' => 'approved',
                    'approved_at' => now()
                ]);
            }

            // Learn from the approval if category was changed
            if ($category && $category->id !== $expense->category_id) {
                $this->categorizationService->learn(
                    $expense->merchant,
                    $expense->description,
                    $category
                );
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to approve expense {$expense->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject auto-created expense
     */
    public function rejectExpense(Expense $expense, string $reason = ''): bool
    {
        try {
            DB::beginTransaction();

            $expense->update([
                'requires_approval' => false,
                'rejected_at' => now(),
                'rejection_reason' => $reason
            ]);

            // Update auto-created expense record
            $autoCreatedExpense = AutoCreatedExpense::where('expense_id', $expense->id)->first();
            if ($autoCreatedExpense) {
                $autoCreatedExpense->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_reason' => $reason
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to reject expense {$expense->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get auto-created expenses statistics
     */
    public function getStatistics(User $user): array
    {
        $total = AutoCreatedExpense::where('user_id', $user->id)->count();
        $pending = AutoCreatedExpense::where('user_id', $user->id)->where('status', 'pending_approval')->count();
        $approved = AutoCreatedExpense::where('user_id', $user->id)->where('status', 'approved')->count();
        $rejected = AutoCreatedExpense::where('user_id', $user->id)->where('status', 'rejected')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'approval_rate' => $total > 0 ? ($approved / $total) * 100 : 0
        ];
    }
}
