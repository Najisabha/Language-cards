<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CardWebIconImageFetcher
{
    private const API = 'https://commons.wikimedia.org/w/api.php';

    /**
     * @return array{mime: string, image_base64: string}
     */
    public function fetch(string $word): array
    {
        $word = trim($word);
        if ($word === '') {
            throw new \InvalidArgumentException('Empty word');
        }

        $term = mb_substr($word, 0, 80);
        $headers = ['User-Agent' => $this->userAgent()];

        $search = Http::timeout(20)->withHeaders($headers)->get(self::API, [
            'action' => 'query',
            'format' => 'json',
            'list' => 'search',
            'srsearch' => $term,
            'srnamespace' => 6,
            'srlimit' => 15,
        ]);

        if (! $search->successful()) {
            throw new \RuntimeException('Search failed');
        }

        $items = $search->json('query.search');
        if (! is_array($items) || $items === []) {
            throw new \RuntimeException('No images found');
        }

        foreach ($items as $row) {
            $title = isset($row['title']) ? (string) $row['title'] : '';
            if ($title === '' || ! str_starts_with($title, 'File:')) {
                continue;
            }
            if ($this->isSvgTitle($title)) {
                continue;
            }

            $info = $this->imageInfo($title, $headers);
            if ($info === null) {
                continue;
            }

            ['url' => $imgUrl, 'mime' => $mime] = $info;
            $mime = (string) $mime;

            if (! str_starts_with($mime, 'image/')) {
                continue;
            }
            if (str_contains(strtolower($mime), 'svg')) {
                continue;
            }

            $binary = $this->downloadImage($imgUrl, $headers);
            if ($binary !== null) {
                return [
                    'mime' => $this->normalizeMime($mime),
                    'image_base64' => base64_encode($binary),
                ];
            }
        }

        throw new \RuntimeException('No usable image found');
    }

    private function userAgent(): string
    {
        $custom = config('services.icon_image_fetch.user_agent');
        if (is_string($custom) && $custom !== '') {
            return $custom;
        }

        return trim((string) config('app.name', 'App')).'/1.0 (educational flashcards; Laravel PHP)';
    }

    private function isSvgTitle(string $title): bool
    {
        return str_ends_with(strtolower($title), '.svg');
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{url: string, mime: string}|null
     */
    private function imageInfo(string $title, array $headers): ?array
    {
        $r = Http::timeout(20)->withHeaders($headers)->get(self::API, [
            'action' => 'query',
            'format' => 'json',
            'titles' => $title,
            'prop' => 'imageinfo',
            'iiprop' => 'url|thumburl|mime',
            'iiurlwidth' => 480,
        ]);

        if (! $r->successful()) {
            return null;
        }

        $pages = $r->json('query.pages');
        if (! is_array($pages)) {
            return null;
        }

        $page = reset($pages);
        if (! is_array($page) || ! empty($page['missing'])) {
            return null;
        }

        $ii = $page['imageinfo'][0] ?? null;
        if (! is_array($ii)) {
            return null;
        }

        $thumb = $ii['thumburl'] ?? null;
        $url = $ii['url'] ?? null;
        $mime = isset($ii['mime']) ? (string) $ii['mime'] : 'image/jpeg';
        $imgUrl = is_string($thumb) && $thumb !== '' ? $thumb : (is_string($url) ? $url : null);
        if ($imgUrl === null || $imgUrl === '') {
            return null;
        }

        return ['url' => $imgUrl, 'mime' => $mime];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function downloadImage(string $url, array $headers): ?string
    {
        $max = (int) config('services.icon_image_fetch.max_bytes', 2048 * 1024);

        $r = Http::timeout(35)->withHeaders($headers)->get($url);
        if (! $r->successful()) {
            return null;
        }

        $body = $r->body();
        if (strlen($body) > $max) {
            return null;
        }

        $ct = $r->header('Content-Type');
        if (is_string($ct) && stripos($ct, 'image') === false && stripos($ct, 'octet-stream') === false) {
            return null;
        }

        return $body;
    }

    private function normalizeMime(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        return match ($mime) {
            'image/jpg' => 'image/jpeg',
            'image/pjpeg' => 'image/jpeg',
            'image/x-png' => 'image/png',
            default => $mime,
        };
    }
}
