<?php

namespace App\Contracts;

use App\Models\Category;

interface AutoCategorizationInterface
{
    /**
     * Automatically categorize a transaction based on merchant and description
     *
     * @param string $merchant
     * @param string $description
     * @param array $context
     * @return Category|null
     */
    public function categorize(string $merchant, string $description, array $context = []): ?Category;

    /**
     * Learn from user corrections to improve categorization
     *
     * @param string $merchant
     * @param string $description
     * @param Category $correctCategory
     * @return void
     */
    public function learn(string $merchant, string $description, Category $correctCategory): void;

    /**
     * Get confidence score for categorization
     *
     * @param string $merchant
     * @param string $description
     * @return float
     */
    public function getConfidenceScore(string $merchant, string $description): float;
}



