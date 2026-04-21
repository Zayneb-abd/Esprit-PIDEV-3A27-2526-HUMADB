<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FacebookJobPublisher
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ?string $pageId,
        private readonly ?string $pageAccessToken,
        private readonly ?string $graphApiVersion = null,
        private readonly ?string $publicBaseUrl = null,
        private readonly ?string $graphBaseUrl = 'https://graph.facebook.com',
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->normalize($this->pageId) !== null
            && $this->normalize($this->pageAccessToken) !== null;
    }

    /**
     * @return array{id:string,link:?string,message:string}
     */
    public function publishOffer(OffreEmploi $offreEmploi): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('La configuration Facebook Pages API est incomplete.');
        }

        $message = $this->buildMessage($offreEmploi);
        $payload = [
            'message' => $message,
            'access_token' => (string) $this->normalize($this->pageAccessToken),
        ];

        $publicUrl = $this->buildPublicOfferUrl($offreEmploi);
        if ($publicUrl !== null) {
            $payload['link'] = $publicUrl;
        }

        $response = $this->httpClient->request('POST', $this->buildFeedEndpoint(), [
            'body' => $payload,
            'timeout' => 20,
        ]);

        $data = $response->toArray(false);
        if ($response->getStatusCode() >= 400 || !isset($data['id'])) {
            $errorMessage = (string) ($data['error']['message'] ?? 'Erreur inconnue Facebook.');
            throw new \RuntimeException($errorMessage);
        }

        $this->logger->info('Facebook offer publication created.', [
            'offer_id' => $offreEmploi->getId(),
            'facebook_post_id' => $data['id'],
            'page_id' => $this->normalize($this->pageId),
        ]);

        return [
            'id' => (string) $data['id'],
            'link' => $publicUrl,
            'message' => $message,
        ];
    }

    private function buildFeedEndpoint(): string
    {
        $baseUrl = rtrim((string) ($this->normalize($this->graphBaseUrl) ?? 'https://graph.facebook.com'), '/');
        $pageId = rawurlencode((string) $this->normalize($this->pageId));
        $version = $this->normalize($this->graphApiVersion);

        if ($version !== null) {
            return sprintf('%s/%s/%s/feed', $baseUrl, ltrim($version, '/'), $pageId);
        }

        return sprintf('%s/%s/feed', $baseUrl, $pageId);
    }

    private function buildMessage(OffreEmploi $offreEmploi): string
    {
        $title = trim((string) ($offreEmploi->getTitre() ?? 'Offre d emploi'));
        $department = trim((string) ($offreEmploi->getDepartement() ?? 'Departement non precise'));
        $contract = trim((string) ($offreEmploi->getTypeContrat() ?? 'Contrat a definir'));
        $openings = $offreEmploi->getNombrePostes() ?? 0;
        $description = trim((string) ($offreEmploi->getDescription() ?? ''));
        $summary = $this->summarize($description, 280);

        $parts = [
            'Nous recrutons chez HUMA.',
            sprintf('Poste: %s', $title),
            sprintf('Departement: %s', $department),
            sprintf('Contrat: %s', $contract),
            $openings > 0 ? sprintf('Postes ouverts: %d', $openings) : null,
            $summary !== '' ? sprintf('Mission: %s', $summary) : null,
            'Consultez l offre et candidatez en ligne.',
            '#Recrutement #Emploi #HUMA',
        ];

        return implode("\n", array_values(array_filter($parts, static fn (?string $value): bool => $value !== null && $value !== '')));
    }

    private function buildPublicOfferUrl(OffreEmploi $offreEmploi): ?string
    {
        if ($offreEmploi->getId() === null) {
            return null;
        }

        $baseUrl = $this->normalize($this->publicBaseUrl);
        if ($baseUrl !== null) {
            $path = $this->router->generate('client_job_show', ['id' => $offreEmploi->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

            return rtrim($baseUrl, '/').$path;
        }

        return $this->router->generate('client_job_show', ['id' => $offreEmploi->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function summarize(string $text, int $limit): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($normalized === '') {
            return '';
        }

        if (mb_strlen($normalized) <= $limit) {
            return $normalized;
        }

        return rtrim(mb_substr($normalized, 0, max(0, $limit - 3))).'...';
    }

    private function normalize(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
