<?php

namespace App\Services\PaymentNotification;

use App\Contracts\PaymentNotificationParserInterface;
use Illuminate\Support\Facades\Log;

class SMSParserService implements PaymentNotificationParserInterface
{
    private array $parsers = [];

    public function __construct()
    {
        $this->parsers = [
            'esewa' => new EsewaSMSParser(),
            'khalti' => new KhaltiSMSParser(),
            'bank' => new BankSMSParser(),
        ];
    }

    /**
     * Parse SMS content and extract transaction data
     */
    public function parse(string $content, string $source = 'sms'): ?array
    {
        $notificationType = $this->getNotificationType($content);
        
        if (!$notificationType || !isset($this->parsers[$notificationType])) {
            return null;
        }

        try {
            $parser = $this->parsers[$notificationType];
            $transactionData = $parser->parse($content, $source);
            
            if ($transactionData) {
                $transactionData['source'] = $source;
                $transactionData['notification_type'] = $notificationType;
                $transactionData['parsed_at'] = now();
            }

            return $transactionData;
        } catch (\Exception $e) {
            Log::error("SMS parsing failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if content is a valid payment notification
     */
    public function isValidNotification(string $content): bool
    {
        return $this->getNotificationType($content) !== null;
    }

    /**
     * Get notification type based on content
     */
    public function getNotificationType(string $content): ?string
    {
        $content = strtolower($content);

        // eSewa SMS patterns
        if (str_contains($content, 'esewa') || 
            str_contains($content, 'e-sewa') ||
            preg_match('/esewa.*payment|payment.*esewa/', $content)) {
            return 'esewa';
        }

        // Khalti SMS patterns
        if (str_contains($content, 'khalti') || 
            preg_match('/khalti.*payment|payment.*khalti/', $content)) {
            return 'khalti';
        }

        // Bank SMS patterns
        if (preg_match('/\b(?:nmb|nabil|himalayan|machhapuchhre)\b/i', $content) ||
            preg_match('/\b(?:debit|credit|transaction)\b.*\b(?:account|balance)\b/i', $content) ||
            preg_match('/A\/C\s*\*+\d+/i', $content) ||
            preg_match('/Rs\.?\s*[0-9,]+\.?[0-9]*\s*(?:debit|credit)/i', $content)) {
            return 'bank';
        }

        return null;
    }
}

/**
 * eSewa SMS Parser
 */
class EsewaSMSParser
{
    public function parse(string $content, string $source): ?array
    {
        $patterns = [
            'amount' => '/Rs\.?\s*([0-9,]+\.?[0-9]*)/i',
            'merchant' => '/to\s+([^.]+?)(?:\s+successful|\.|$)/i',
            'transaction_id' => '/transaction\s*(?:id|no)\.?\s*:?\s*([A-Z0-9]+)/i',
            'date' => '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
            'time' => '/(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM)?)/i'
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
            'amount' => $this->parseAmount($data['amount']),
            'merchant' => $data['merchant'] ?? 'Unknown Merchant',
            'transaction_id' => $data['transaction_id'] ?? null,
            'date' => $this->parseDate($data['date'] ?? null),
            'time' => $data['time'] ?? null,
            'description' => $this->generateDescription($data),
            'type' => 'expense'
        ];
    }

    private function parseAmount(string $amount): float
    {
        return (float) str_replace(',', '', $amount);
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateDescription(array $data): string
    {
        return "Payment to {$data['merchant']} via eSewa";
    }
}

/**
 * Khalti SMS Parser
 */
class KhaltiSMSParser
{
    public function parse(string $content, string $source): ?array
    {
        $patterns = [
            'amount' => '/Rs\.?\s*([0-9,]+\.?[0-9]*)/i',
            'merchant' => '/to\s+([^.]+?)(?:\s+successful|\.|$)/i',
            'transaction_id' => '/transaction\s*(?:id|no)\.?\s*:?\s*([A-Z0-9]+)/i',
            'date' => '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
            'time' => '/(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM)?)/i'
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
            'amount' => $this->parseAmount($data['amount']),
            'merchant' => $data['merchant'] ?? 'Unknown Merchant',
            'transaction_id' => $data['transaction_id'] ?? null,
            'date' => $this->parseDate($data['date'] ?? null),
            'time' => $data['time'] ?? null,
            'description' => $this->generateDescription($data),
            'type' => 'expense'
        ];
    }

    private function parseAmount(string $amount): float
    {
        return (float) str_replace(',', '', $amount);
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateDescription(array $data): string
    {
        return "Payment to {$data['merchant']} via Khalti";
    }
}

/**
 * Bank SMS Parser
 */
class BankSMSParser
{
    public function parse(string $content, string $source): ?array
    {
        $patterns = [
            'amount' => '/Rs\.?\s*([0-9,]+\.?[0-9]*)/i',
            'merchant' => '/at\s+([^.]+?)(?:\s+on|\.|$)/i',
            'account' => '/A\/C\s*\*+(\d+)/i',
            'date' => '/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})/',
            'time' => '/(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM)?)/i',
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
            'amount' => $this->parseAmount($data['amount']),
            'merchant' => $data['merchant'] ?? 'Unknown Merchant',
            'account' => $data['account'] ?? null,
            'date' => $this->parseDate($data['date'] ?? null),
            'time' => $data['time'] ?? null,
            'balance' => isset($data['balance']) ? $this->parseAmount($data['balance']) : null,
            'description' => $this->generateDescription($data),
            'type' => 'expense'
        ];
    }

    private function parseAmount(string $amount): float
    {
        return (float) str_replace(',', '', $amount);
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') ?:
                   \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateDescription(array $data): string
    {
        return "Payment to {$data['merchant']} via Bank";
    }
}
