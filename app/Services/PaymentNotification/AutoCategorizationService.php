<?php

namespace App\Services\PaymentNotification;

use App\Contracts\AutoCategorizationInterface;
use App\Models\Category;
use App\Models\MerchantCategoryMapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutoCategorizationService implements AutoCategorizationInterface
{
    private array $merchantKeywords = [];
    private array $categoryKeywords = [];

    public function __construct()
    {
        $this->loadMerchantMappings();
        $this->loadCategoryKeywords();
    }

    /**
     * Automatically categorize a transaction based on merchant and description
     */
    public function categorize(string $merchant, string $description, array $context = []): ?Category
    {
        $merchant = strtolower(trim($merchant));
        $description = strtolower(trim($description));
        $combinedText = $merchant . ' ' . $description;

        // Try exact merchant match first
        $exactMatch = $this->findExactMerchantMatch($merchant);
        if ($exactMatch) {
            return $exactMatch;
        }

        // Try keyword-based matching
        $keywordMatch = $this->findKeywordMatch($combinedText);
        if ($keywordMatch) {
            return $keywordMatch;
        }

        // Try fuzzy matching for similar merchants
        $fuzzyMatch = $this->findFuzzyMatch($merchant);
        if ($fuzzyMatch) {
            return $fuzzyMatch;
        }

        // Try ML-based categorization if available
        $mlMatch = $this->findMLMatch($combinedText, $context);
        if ($mlMatch) {
            return $mlMatch;
        }

        return null;
    }

    /**
     * Learn from user corrections to improve categorization
     */
    public function learn(string $merchant, string $description, Category $correctCategory): void
    {
        $merchant = strtolower(trim($merchant));
        
        try {
            // Store the mapping for future use
            MerchantCategoryMapping::updateOrCreate(
                ['merchant' => $merchant],
                [
                    'category_id' => $correctCategory->id,
                    'confidence' => 1.0,
                    'usage_count' => \DB::raw('usage_count + 1'),
                    'last_used' => now()
                ]
            );

            // Clear cache to refresh mappings
            Cache::forget('merchant_category_mappings');
            
            Log::info("Learned categorization: {$merchant} -> {$correctCategory->name}");
        } catch (\Exception $e) {
            Log::error("Failed to learn categorization: " . $e->getMessage());
        }
    }

    /**
     * Get confidence score for categorization
     */
    public function getConfidenceScore(string $merchant, string $description): float
    {
        $merchant = strtolower(trim($merchant));
        $description = strtolower(trim($description));
        $combinedText = $merchant . ' ' . $description;

        // Check exact merchant match
        $exactMatch = $this->findExactMerchantMatch($merchant);
        if ($exactMatch) {
            return 0.95; // High confidence for exact matches
        }

        // Check keyword match
        $keywordMatch = $this->findKeywordMatch($combinedText);
        if ($keywordMatch) {
            return 0.8; // Good confidence for keyword matches
        }

        // Check fuzzy match
        $fuzzyMatch = $this->findFuzzyMatch($merchant);
        if ($fuzzyMatch) {
            return 0.6; // Medium confidence for fuzzy matches
        }

        return 0.0; // No match found
    }

    /**
     * Find exact merchant match
     */
    private function findExactMerchantMatch(string $merchant): ?Category
    {
        $mapping = MerchantCategoryMapping::where('merchant', $merchant)->first();
        
        if ($mapping && $mapping->category) {
            return $mapping->category;
        }

        return null;
    }

    /**
     * Find keyword-based match
     */
    private function findKeywordMatch(string $text): ?Category
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->categoryKeywords as $categoryId => $keywords) {
            $score = $this->calculateKeywordScore($text, $keywords);
            
            if ($score > $bestScore && $score > 0.3) { // Minimum threshold
                $bestScore = $score;
                $bestMatch = Category::find($categoryId);
            }
        }

        return $bestMatch;
    }

    /**
     * Find fuzzy match for similar merchants
     */
    private function findFuzzyMatch(string $merchant): ?Category
    {
        $mappings = MerchantCategoryMapping::all();
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($mappings as $mapping) {
            $similarity = $this->calculateSimilarity($merchant, $mapping->merchant);
            
            if ($similarity > $bestSimilarity && $similarity > 0.7) { // 70% similarity threshold
                $bestSimilarity = $similarity;
                $bestMatch = $mapping->category;
            }
        }

        return $bestMatch;
    }

    /**
     * Find ML-based match (placeholder for future ML integration)
     */
    private function findMLMatch(string $text, array $context): ?Category
    {
        // This could integrate with your existing ML forecasting system
        // For now, return null as placeholder
        return null;
    }

    /**
     * Calculate keyword score
     */
    private function calculateKeywordScore(string $text, array $keywords): float
    {
        $score = 0;
        $totalKeywords = count($keywords);

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $score += 1;
            }
        }

        return $totalKeywords > 0 ? $score / $totalKeywords : 0;
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLength);
    }

    /**
     * Load merchant category mappings from database
     */
    private function loadMerchantMappings(): void
    {
        $this->merchantKeywords = Cache::remember('merchant_category_mappings', 3600, function () {
            return MerchantCategoryMapping::with('category')->get()->keyBy('merchant')->toArray();
        });
    }

    /**
     * Load category keywords
     */
    private function loadCategoryKeywords(): void
    {
        $this->categoryKeywords = [
            // Food & Dining
            1 => ['restaurant', 'cafe', 'food', 'dining', 'pizza', 'burger', 'coffee', 'tea', 'lunch', 'dinner', 'breakfast'],
            
            // Transportation
            2 => ['taxi', 'bus', 'petrol', 'fuel', 'gas', 'uber', 'transport', 'parking', 'toll', 'metro'],
            
            // Shopping
            3 => ['store', 'shop', 'mall', 'market', 'clothing', 'fashion', 'electronics', 'grocery', 'supermarket'],
            
            // Healthcare
            4 => ['hospital', 'clinic', 'pharmacy', 'medicine', 'doctor', 'medical', 'health', 'dental'],
            
            // Entertainment
            5 => ['movie', 'cinema', 'theater', 'game', 'entertainment', 'music', 'sports', 'gym', 'fitness'],
            
            // Utilities
            6 => ['electricity', 'water', 'internet', 'phone', 'mobile', 'utility', 'bill', 'rent'],
            
            // Education
            7 => ['school', 'college', 'university', 'education', 'book', 'course', 'tuition', 'library'],
            
            // Travel
            8 => ['hotel', 'flight', 'travel', 'ticket', 'booking', 'vacation', 'trip', 'airline'],
            
            // Insurance
            9 => ['insurance', 'premium', 'policy', 'claim', 'coverage'],
            
            // Investment
            10 => ['investment', 'stock', 'mutual fund', 'savings', 'deposit', 'withdrawal']
        ];
    }

    /**
     * Get suggested categories for a merchant
     */
    public function getSuggestedCategories(string $merchant, string $description): array
    {
        $suggestions = [];
        $combinedText = strtolower($merchant . ' ' . $description);

        foreach ($this->categoryKeywords as $categoryId => $keywords) {
            $score = $this->calculateKeywordScore($combinedText, $keywords);
            
            if ($score > 0.1) { // Lower threshold for suggestions
                $category = Category::find($categoryId);
                if ($category) {
                    $suggestions[] = [
                        'category' => $category,
                        'score' => $score,
                        'confidence' => $this->getConfidenceScore($merchant, $description)
                    ];
                }
            }
        }

        // Sort by score descending
        usort($suggestions, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($suggestions, 0, 3); // Return top 3 suggestions
    }

    /**
     * Add new merchant mapping
     */
    public function addMerchantMapping(string $merchant, $category, float $confidence = 0.8): void
    {
        try {
            // Handle both Category object and category ID
            $categoryId = is_object($category) ? $category->id : $category;
            
            MerchantCategoryMapping::updateOrCreate(
                ['merchant' => strtolower(trim($merchant))],
                [
                    'category_id' => $categoryId,
                    'confidence' => $confidence,
                    'usage_count' => 1,
                    'last_used' => now()
                ]
            );

            Cache::forget('merchant_category_mappings');
            $categoryName = is_object($category) ? $category->name : Category::find($category)->name;
            Log::info("Added merchant mapping: {$merchant} -> {$categoryName}");
        } catch (\Exception $e) {
            Log::error("Failed to add merchant mapping: " . $e->getMessage());
        }
    }

    /**
     * Get merchant mapping statistics
     */
    public function getMappingStatistics(): array
    {
        $totalMappings = MerchantCategoryMapping::count();
        $recentMappings = MerchantCategoryMapping::where('last_used', '>=', now()->subDays(30))->count();
        $highConfidenceMappings = MerchantCategoryMapping::where('confidence', '>=', 0.8)->count();

        return [
            'total_mappings' => $totalMappings,
            'recent_mappings' => $recentMappings,
            'high_confidence_mappings' => $highConfidenceMappings,
            'accuracy_rate' => $totalMappings > 0 ? ($highConfidenceMappings / $totalMappings) * 100 : 0
        ];
    }
}
