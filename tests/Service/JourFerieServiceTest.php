<?php

namespace App\Tests\Service;

use App\Service\JourFerieService;
use App\Repository\JourFerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use PHPUnit\Framework\TestCase;

class JourFerieServiceTest extends TestCase
{
    private JourFerieService $jourFerieService;
    private $httpClient;
    private $entityManager;
    private $repository;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(JourFerieRepository::class);
        
        $this->jourFerieService = new JourFerieService(
            $this->httpClient,
            $this->entityManager,
            $this->repository
        );
    }

    public function testFetchJoursFeriesFromAPIReturnsArray(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);
        
        $this->httpClient->method('request')->willReturn($response);
        
        $result = $this->jourFerieService->fetchJoursFeriesFromAPI(2025);
        
        $this->assertIsArray($result);
    }

    public function testFetchJoursFeriesFromAPIWithDefaultYear(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);
        
        $this->httpClient->method('request')->willReturn($response);
        
        $result = $this->jourFerieService->fetchJoursFeriesFromAPI((int)date('Y'));
        
        $this->assertIsArray($result);
    }

    public function testFetchJoursFeriesFromAPIWithCountryCode(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);
        
        $this->httpClient->method('request')->willReturn($response);
        
        $result = $this->jourFerieService->fetchJoursFeriesFromAPI(2025, 'FR');
        
        $this->assertIsArray($result);
    }
}
