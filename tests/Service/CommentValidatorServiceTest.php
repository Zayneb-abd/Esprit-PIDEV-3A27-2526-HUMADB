<?php

namespace App\Tests\Service;

use App\Service\CommentValidatorService;
use PHPUnit\Framework\TestCase;

class CommentValidatorServiceTest extends TestCase
{
    private CommentValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new CommentValidatorService();
    }

    public function testValidateCommentWithNoBadWords(): void
    {
        $result = $this->validator->validateComment('Ceci est un commentaire correct');
        
        $this->assertTrue($result['is_valid']);
        $this->assertFalse($result['is_censored']);
        $this->assertEmpty($result['bad_words_found']);
        $this->assertEquals('Ceci est un commentaire correct', $result['censored_content']);
        $this->assertEquals('', $result['message']);
    }

    public function testValidateCommentWithBadWords(): void
    {
        $result = $this->validator->validateComment('Ceci est un commentaire idiot');
        
        $this->assertFalse($result['is_valid']);
        $this->assertTrue($result['is_censored']);
        $this->assertNotEmpty($result['bad_words_found']);
        $this->assertStringContainsString('***', $result['censored_content']);
        $this->assertStringContainsString('inappropriés', $result['message']);
    }

    public function testAddCustomBadWords(): void
    {
        $this->validator->addCustomBadWords(['testword']);
        $list = $this->validator->getBadWordsList();
        
        $this->assertContains('testword', $list);
    }

    public function testIsBadWordReturnsTrue(): void
    {
        $this->assertTrue($this->validator->isBadWord('idiot'));
        $this->assertTrue($this->validator->isBadWord('IDIOT'));
        $this->assertTrue($this->validator->isBadWord('  idiot  '));
    }

    public function testIsBadWordReturnsFalse(): void
    {
        $this->assertFalse($this->validator->isBadWord('bonjour'));
        $this->assertFalse($this->validator->isBadWord(''));
    }

    public function testGetBadWordsList(): void
    {
        $list = $this->validator->getBadWordsList();
        
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
        $this->assertContains('idiot', $list);
        $this->assertContains('stupide', $list);
    }
}
