<?php

namespace App\Contracts;

interface PaymentNotificationParserInterface
{
    /**
     * Parse payment notification content and extract transaction data
     *
     * @param string $content
     * @param string $source
     * @return array|null
     */
    public function parse(string $content, string $source = 'email'): ?array;

    /**
     * Check if the content is a valid payment notification
     *
     * @param string $content
     * @return bool
     */
    public function isValidNotification(string $content): bool;

    /**
     * Get the notification type (esewa, khalti, bank, etc.)
     *
     * @param string $content
     * @return string|null
     */
    public function getNotificationType(string $content): ?string;
}



