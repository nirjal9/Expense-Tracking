<?php

namespace App\Services\PaymentNotification;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GmailService
{
    private Client $client;
    private Gmail $service;
    private string $userId = 'me';

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google Client with credentials
     */
    private function initializeClient(): void
    {
        $this->client = new Client();
        
        // Check if credentials file exists
        $credentialsPath = config('services.gmail.credentials_path');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            // Use environment variables instead of file
            $this->client->setClientId(config('services.google.client_id'));
            $this->client->setClientSecret(config('services.google.client_secret'));
            $this->client->setRedirectUri(config('services.google.redirect_uri'));
        } else {
            $this->client->setAuthConfig($credentialsPath);
        }
        
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->service = new Gmail($this->client);
    }

    /**
     * Check if Gmail is properly configured
     */
    public function isConfigured(): bool
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');
        
        return !empty($clientId) && !empty($clientSecret) && !empty($redirectUri);
    }

    /**
     * Get authorization URL for OAuth flow
     */
    public function getAuthUrl(): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Gmail API credentials not configured. Please set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI in your .env file.');
        }
        
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     */
    public function authenticate(string $authCode): bool
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
            
            if (isset($accessToken['error'])) {
                Log::error('Gmail authentication failed: ' . $accessToken['error']);
                return false;
            }

            $this->client->setAccessToken($accessToken);
            
            // Store token for future use
            Cache::put('gmail_access_token', $accessToken, now()->addHour());
            
            return true;
        } catch (\Exception $e) {
            Log::error('Gmail authentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        $token = Cache::get('gmail_access_token');
        
        if (!$token) {
            return false;
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                Cache::put('gmail_access_token', $this->client->getAccessToken(), now()->addHour());
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Fetch payment notification emails
     */
    public function fetchPaymentNotifications(int $maxResults = 10): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Gmail not authenticated');
        }

        try {
            // Search for payment-related emails
            $query = $this->buildPaymentQuery();
            $messages = $this->service->users_messages->listUsersMessages($this->userId, [
                'q' => $query,
                'maxResults' => $maxResults
            ]);

            $emails = [];
            foreach ($messages->getMessages() as $message) {
                $email = $this->getEmailContent($message->getId());
                if ($email) {
                    $emails[] = $email;
                }
            }

            return $emails;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Gmail messages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build search query for payment notifications
     */
    private function buildPaymentQuery(): string
    {
        $paymentKeywords = [
            'esewa',
            'khalti',
            'payment',
            'transaction',
            'debit',
            'credit',
            'bank',
            'nmb',
            'nabil',
            'himalayan',
            'machhapuchhre'
        ];

        $query = implode(' OR ', array_map(function($keyword) {
            return "subject:{$keyword} OR from:{$keyword}";
        }, $paymentKeywords));

        return "({$query}) newer_than:7d";
    }

    /**
     * Get full email content
     */
    private function getEmailContent(string $messageId): ?array
    {
        try {
            $message = $this->service->users_messages->get($this->userId, $messageId);
            $headers = $message->getPayload()->getHeaders();
            
            $email = [
                'id' => $messageId,
                'subject' => $this->getHeader($headers, 'Subject'),
                'from' => $this->getHeader($headers, 'From'),
                'date' => $this->getHeader($headers, 'Date'),
                'body' => $this->extractBody($message->getPayload())
            ];

            return $email;
        } catch (\Exception $e) {
            Log::error("Failed to get email content for ID {$messageId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract email body content
     */
    private function extractBody($payload): string
    {
        $body = '';

        if ($payload->getBody() && $payload->getBody()->getData()) {
            $body = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        } elseif ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain' && $part->getBody()->getData()) {
                    $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                    break;
                }
            }
        }

        return $body;
    }

    /**
     * Get header value by name
     */
    private function getHeader(array $headers, string $name): string
    {
        foreach ($headers as $header) {
            if (strtolower($header->getName()) === strtolower($name)) {
                return $header->getValue();
            }
        }
        return '';
    }

    /**
     * Mark email as read
     */
    public function markAsRead(string $messageId): bool
    {
        try {
            $modifyRequest = new \Google\Service\Gmail\ModifyMessageRequest();
            $modifyRequest->setRemoveLabelIds(['UNREAD']);
            
            $this->service->users_messages->modify($this->userId, $messageId, $modifyRequest);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark email as read: " . $e->getMessage());
            return false;
        }
    }
}
