<?php

namespace App\Tests\Service;

use App\Service\MLPredictionService;
use PHPUnit\Framework\TestCase;

class MLPredictionServiceTest extends TestCase
{
    private MLPredictionService $mlPredictionService;

    protected function setUp(): void
    {
        $this->mlPredictionService = new MLPredictionService();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(MLPredictionService::class, $this->mlPredictionService);
    }

    public function testPredictPublicationEngagementReturnsArray(): void
    {
        $result = $this->mlPredictionService->predictPublicationEngagement('Test content');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testPredictCommentEngagementReturnsArray(): void
    {
        $result = $this->mlPredictionService->predictCommentEngagement('Test comment');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testPredictCongeProbabilityReturnsArray(): void
    {
        $result = $this->mlPredictionService->predictCongeProbability(1);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testSuggestBestPeriodReturnsArray(): void
    {
        $result = $this->mlPredictionService->suggestBestPeriod(1, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('requested_duration', $result);
        $this->assertArrayHasKey('suggestions', $result);
    }
}
