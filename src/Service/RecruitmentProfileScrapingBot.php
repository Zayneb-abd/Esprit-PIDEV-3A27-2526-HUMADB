<?php

namespace App\Service;

use App\Entity\OffreEmploi;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class RecruitmentProfileScrapingBot
{
    private const SEARCH_ENDPOINT = 'https://html.duckduckgo.com/html/';
    private const KEYWORD = 'symfony';

    /**
     * Public professional/recruitment-oriented sources queried through a site-filtered search.
     *
     * @var array<int, array{key:string,label:string,site_query:string}>
     */
    private const SOURCES = [
        ['key' => 'linkedin', 'label' => 'LinkedIn', 'site_query' => 'site:linkedin.com/in'],
        ['key' => 'github', 'label' => 'GitHub', 'site_query' => 'site:github.com'],
        ['key' => 'malt', 'label' => 'Malt', 'site_query' => 'site:malt.fr/profile'],
        ['key' => 'welcome', 'label' => 'Welcome to the Jungle', 'site_query' => 'site:welcometothejungle.com'],
    ];

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(HttpClient::create([
            'timeout' => 20,
            'max_redirects' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; HUMADB Goutte Scraper Bot/1.0)',
                'Accept-Language' => 'fr,en;q=0.8',
            ],
        ]));
    }

    /**
     * @return array<int, array{url:string,title:string,snippet:string,source:string,source_label:string}>
     */
    public function discoverProfilesForOffer(OffreEmploi $offreEmploi, int $limit = 8): array
    {
        $results = $this->discoverProfilesByQueries($this->buildQueries($offreEmploi), $limit);

        if ($results !== []) {
            return $results;
        }

        return $this->discoverSymfonyDeveloperProfiles($limit);
    }

    /**
     * Discover public Symfony developer profiles with broad fallback queries.
     *
     * @return array<int, array{url:string,title:string,snippet:string,source:string,source_label:string}>
     */
    public function discoverSymfonyDeveloperProfiles(int $limit = 8): array
    {
        $queries = [
            'Symfony developer',
            'Symfony PHP developer',
            'Développeur Symfony',
            'Symfony backend engineer',
            'Symfony consultant',
        ];

        return $this->discoverProfilesByQueries($queries, $limit);
    }

    /**
     * @return array{
     *     url:string,
     *     title:string,
     *     name:string,
     *     photo:?string,
     *     contact:array{email:?string,phone:?string,website:?string},
     *     description:string,
     *     preview:string,
     *     source:string,
     *     source_label:string
     * }
     */
    public function scrapeProfile(string $url, ?string $source = null, ?string $sourceLabel = null): array
    {
        $crawler = $this->client->request('GET', $url);
        $html = (string) $this->client->getResponse()->getContent();
        $visibleText = $this->extractVisibleText($html);

        $title = $this->extractMeta($crawler, 'property', 'og:title')
            ?? $this->extractMeta($crawler, 'name', 'twitter:title')
            ?? $this->safeText($crawler, 'title')
            ?? (string) (parse_url($url, \PHP_URL_HOST) ?: 'Profil public');

        $description = $this->extractMeta($crawler, 'property', 'og:description')
            ?? $this->extractMeta($crawler, 'name', 'description')
            ?? $this->truncate($visibleText, 220)
            ?? 'Aucune description publique exploitable trouvee.';

        $email = null;
        if (preg_match('/mailto:([^"\'>\s]+)/i', $html, $matches) === 1) {
            $email = trim($matches[1]);
        } elseif (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $visibleText, $matches) === 1) {
            $email = trim($matches[0]);
        }

        $phone = null;
        if (preg_match('/tel:([^"\'>\s]+)/i', $html, $matches) === 1) {
            $phone = trim(urldecode($matches[1]));
        } elseif (preg_match('/(?:\+?\d[\d\s\-\(\)]{7,}\d)/', $visibleText, $matches) === 1) {
            $phone = trim($matches[0]);
        }

        return [
            'url' => $url,
            'title' => $title,
            'name' => $this->normalizeProfileName($title),
            'photo' => $this->resolveUrl(
                $this->extractMeta($crawler, 'property', 'og:image')
                    ?? $this->extractMeta($crawler, 'name', 'twitter:image'),
                $url
            ),
            'contact' => [
                'email' => $email,
                'phone' => $phone,
                'website' => $url,
            ],
            'description' => trim($description) !== '' ? trim($description) : 'Aucune description publique exploitable trouvee.',
            'preview' => $this->truncate($visibleText, 350) ?? '',
            'source' => $source ?? 'public_web',
            'source_label' => $sourceLabel ?? 'Web public',
        ];
    }

    /**
     * Scrape multiple profile pages and keep only profiles mentioning Symfony.
     *
     * @param array<int, string> $urls
     * @return array<int, array{name:string,title:string,link:string}>
     */
    public function scrapeSymfonyDeveloperProfiles(array $urls): array
    {
        $results = [];
        $seen = [];

        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }

            try {
                $profile = $this->scrapeProfile($url, 'manual_url', 'URL manuelle');
            } catch (\Throwable) {
                continue;
            }

            if (!$this->containsSymfonyKeyword($profile)) {
                continue;
            }

            $link = $profile['url'];
            $dedupeKey = $this->dedupeKey($profile['name'], $profile['title'], $link);
            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;
            $results[] = [
                'name' => $profile['name'],
                'title' => $profile['title'],
                'link' => $link,
            ];
        }

        return $results;
    }

    /**
     * @param array{key:string,label:string,site_query:string} $source
     * @return array<int, array{url:string,title:string,snippet:string,source:string,source_label:string}>
     */
    private function searchSource(array $source, string $query, int $limit): array
    {
        try {
            $crawler = $this->client->request('GET', self::SEARCH_ENDPOINT, [
                'q' => trim($source['site_query'].' '.$query),
            ]);
        } catch (\Throwable) {
            return [];
        }

        $results = [];
        foreach ($crawler->filter('.result') as $node) {
            $resultCrawler = new Crawler($node);

            if (!$resultCrawler->filter('.result__a')->count()) {
                continue;
            }

            $href = html_entity_decode((string) $resultCrawler->filter('.result__a')->first()->attr('href'), \ENT_QUOTES | \ENT_HTML5);
            $url = $this->extractSearchResultTarget($href);
            if ($url === null || !$this->isAllowedPublicUrl($url)) {
                continue;
            }

            $host = (string) parse_url($url, \PHP_URL_HOST);
            if ($host === '' || str_contains($host, 'duckduckgo.com')) {
                continue;
            }

            $results[$url] = [
                'url' => $url,
                'title' => trim($resultCrawler->filter('.result__a')->text('')),
                'snippet' => trim($resultCrawler->filter('.result__snippet')->count() ? $resultCrawler->filter('.result__snippet')->text('') : ''),
                'source' => $source['key'],
                'source_label' => $source['label'],
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return array_values($results);
    }

    /**
     * @param array<int,string> $queries
     * @return array<int, array{url:string,title:string,snippet:string,source:string,source_label:string}>
     */
    private function discoverProfilesByQueries(array $queries, int $limit): array
    {
        $results = [];

        foreach (self::SOURCES as $source) {
            foreach ($queries as $query) {
                foreach ($this->searchSource($source, $query, $limit) as $candidate) {
                    $results[$candidate['url']] = $candidate;

                    if (count($results) >= $limit) {
                        return array_values($results);
                    }
                }
            }
        }

        return array_values($results);
    }

    /**
     * @return array<int,string>
     */
    private function buildQueries(OffreEmploi $offreEmploi): array
    {
        $title = trim((string) ($offreEmploi->getTitre() ?? ''));
        $department = trim((string) ($offreEmploi->getDepartement() ?? ''));
        $description = $this->extractSearchSnippet((string) ($offreEmploi->getDescription() ?? ''));

        $queries = array_filter([
            trim($title.' '.$department.' '.$description),
            trim($title.' '.$department.' CV resume profil'),
            trim($title.' '.$description.' recrutement'),
            trim($department.' '.$title.' developpeur ingenieur consultant'),
        ], static fn (string $query): bool => $query !== '');

        return array_values(array_unique($queries));
    }

    private function extractSearchResultTarget(string $candidate): ?string
    {
        if (str_starts_with($candidate, '//duckduckgo.com/l/?')) {
            $candidate = 'https:'.$candidate;
        }

        if (!str_contains($candidate, 'duckduckgo.com/l/?')) {
            return $candidate;
        }

        $query = parse_url($candidate, \PHP_URL_QUERY);
        if (!is_string($query)) {
            return null;
        }

        parse_str($query, $params);
        $target = $params['uddg'] ?? null;

        return is_string($target) && $target !== '' ? urldecode($target) : null;
    }

    private function isAllowedPublicUrl(string $url): bool
    {
        if (!filter_var($url, \FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array((string) parse_url($url, \PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function extractMeta(Crawler $crawler, string $attribute, string $value): ?string
    {
        $selector = sprintf('meta[%s="%s"]', $attribute, $value);
        if (!$crawler->filter($selector)->count()) {
            return null;
        }

        $content = $crawler->filter($selector)->first()->attr('content');

        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    private function safeText(Crawler $crawler, string $selector): ?string
    {
        if (!$crawler->filter($selector)->count()) {
            return null;
        }

        $text = trim($crawler->filter($selector)->first()->text(''));

        return $text !== '' ? $text : null;
    }

    private function extractVisibleText(string $html): string
    {
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $clean) ?? $clean;
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, \ENT_QUOTES | \ENT_HTML5);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function resolveUrl(?string $candidate, string $baseUrl): ?string
    {
        if ($candidate === null || trim($candidate) === '') {
            return null;
        }

        if (filter_var($candidate, \FILTER_VALIDATE_URL)) {
            return $candidate;
        }

        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        if (str_starts_with($candidate, '//')) {
            return $scheme.':'.$candidate;
        }

        if (str_starts_with($candidate, '/')) {
            return sprintf('%s://%s%s', $scheme, $host, $candidate);
        }

        $path = $baseParts['path'] ?? '/';
        $directory = rtrim((string) preg_replace('~/[^/]*$~', '/', $path), '/');

        return sprintf('%s://%s%s/%s', $scheme, $host, $directory, ltrim($candidate, '/'));
    }

    private function normalizeProfileName(string $title): string
    {
        $name = preg_split('/[|\-–•·]+/u', $title, 2)[0] ?? $title;
        $name = trim($name);

        return $name !== '' ? $name : 'Profil public';
    }

    private function extractSearchSnippet(string $description): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $description) ?? '');
        if ($normalized === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $normalized, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, 16));
    }

    private function truncate(string $text, int $limit): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(0, $limit - 3))).'...';
    }

    /**
     * @param array{title:string,description:string,preview:string} $profile
     */
    private function containsSymfonyKeyword(array $profile): bool
    {
        $haystack = mb_strtolower(trim(implode(' ', [
            $profile['title'] ?? '',
            $profile['description'] ?? '',
            $profile['preview'] ?? '',
        ])));

        return str_contains($haystack, self::KEYWORD);
    }

    private function dedupeKey(string $name, string $title, string $link): string
    {
        return mb_strtolower(trim($name)).'|'.mb_strtolower(trim($title)).'|'.mb_strtolower(trim($link));
    }
}
