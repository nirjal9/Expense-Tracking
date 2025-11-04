<?php

namespace App\Http\Controllers;

use App\Services\PaymentNotification\PaymentNotificationService;
use App\Models\Expense;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentNotificationController extends Controller
{
    private PaymentNotificationService $paymentService;

    public function __construct(PaymentNotificationService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment notification dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        $statistics = $this->paymentService->getStatistics($user);
        $autoCreatedExpenses = $this->paymentService->getAutoCreatedExpenses($user, 'pending');
        $categories = $user->categories;

        return view('payment-notifications.dashboard', compact('statistics', 'autoCreatedExpenses', 'categories'));
    }

    /**
     * Get Gmail authentication URL
     */
    public function getGmailAuthUrl()
    {
        try {
            $authUrl = $this->paymentService->getGmailAuthUrl();
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Gmail auth URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Authenticate Gmail with authorization code
     */
    public function authenticateGmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auth_code' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $success = $this->paymentService->authenticateGmail($request->auth_code);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Gmail authenticated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gmail authentication failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gmail authentication error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process email notifications
     */
    public function processEmails(Request $request)
    {
        $user = Auth::user();
        $maxEmails = $request->get('max_emails', 10);

        try {
            $result = $this->paymentService->processEmailNotifications($user, $maxEmails);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process SMS notifications
     */
    public function processSMS(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sms_messages' => 'required|array',
            'sms_messages.*.content' => 'required|string',
            'sms_messages.*.id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid SMS data',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();

        try {
            $result = $this->paymentService->processSMSNotifications($user, $request->sms_messages);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMS processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook endpoint for payment notifications
     */
    public function webhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'source' => 'required|string|in:email,sms,webhook',
            'content' => 'required|string',
            'notification_type' => 'nullable|string',
            'signature' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            
            $result = $this->paymentService->processWebhookNotification($user, [
                'source' => $request->source,
                'content' => $request->content,
                'notification_type' => $request->notification_type
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Webhook processing failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get auto-created expenses
     */
    public function getAutoCreatedExpenses(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        try {
            $expenses = $this->paymentService->getAutoCreatedExpenses($user, $status);
            
            return response()->json([
                'success' => true,
                'expenses' => $expenses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get auto-created expenses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve auto-created expense
     */
    public function approveExpense(Request $request, Expense $expense)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        // Check if user owns the expense
        if ($expense->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $success = $this->paymentService->approveExpense($expense, $request->category_id);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense approved successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve expense'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject auto-created expense
     */
    public function rejectExpense(Request $request, Expense $expense)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        // Check if user owns the expense
        if ($expense->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $success = $this->paymentService->rejectExpense($expense, $request->reason ?? '');
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense rejected successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject expense'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test parsing with sample content
     */
    public function testParsing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'source' => 'required|string|in:email,sms'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $result = $this->paymentService->testParsing(
                $request->content,
                $request->source
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parsing test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service statistics
     */
    public function getStatistics()
    {
        $user = Auth::user();

        try {
            $statistics = $this->paymentService->getStatistics($user);
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}