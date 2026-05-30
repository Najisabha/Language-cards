<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CardAiSuggester
{
    /** @var array<string, mixed> */
    public function suggest(string $word, ?string $targetLanguageName = null): array
    {
        $word = trim($word);

        $apiKey = config('services.ai.api_key');
        $baseUrl = rtrim(config('services.ai.base_url'), '/');
        $model = config('services.ai.model');

        if (! $apiKey || ! $model) {
            throw new \RuntimeException('AI is not configured');
        }

        $languageLabel = $targetLanguageName
            ? trim($targetLanguageName)
            : 'the target language on the flashcard';

        $system = <<<TXT
You help build Arabic-first vocabulary flashcards. The front of each card shows a word in {$languageLabel}.
Respond with JSON only. Required keys: en_meaning, ar_meaning, explanation, icon.
Never return empty en_meaning or empty ar_meaning.
- en_meaning: write how to pronounce the front word using Arabic letters only (transliteration in Arabic script for learners). No Latin letters. Keep it short and natural (e.g. ويلكم for English "welcome").
- ar_meaning: Arabic translation or a concise Arabic gloss of the word
Keep text plain, learner-friendly, and brief.
For explanation, return one short Arabic example sentence (or a brief Arabic explanation) suitable for learners.
For icon return exactly one suitable emoji character only.
TXT;

        $user = 'Term on card front: '.$word;

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = Http::timeout((int) config('services.ai.timeout', 45))
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('AI HTTP error: '.$response->status());
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Empty AI response');
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid AI JSON');
        }

        $en = isset($decoded['en_meaning']) ? $this->normalizeString((string) $decoded['en_meaning']) : '';
        $ar = isset($decoded['ar_meaning']) ? $this->normalizeString((string) $decoded['ar_meaning']) : '';
        $explanation = isset($decoded['explanation']) ? $this->normalizeString((string) $decoded['explanation']) : '';
        $icon = isset($decoded['icon']) ? $this->normalizeIcon((string) $decoded['icon']) : '';

        if ($en === '' && $ar === '') {
            if ($this->containsArabic($word)) {
                $ar = $word;
            }
        } elseif ($ar === '' && $this->containsArabic($word)) {
            $ar = $word;
        }

        if ($explanation === '') {
            $explanation = $this->containsArabic($word)
                ? 'مثال: '.$word.' مفيد في سياق التعلم اليومي.'
                : 'مثال بالعربية: استخدم كلمة '.$word.' في جملة مفيدة.';
        }

        return [
            'en_meaning' => mb_substr($en, 0, 255),
            'ar_meaning' => mb_substr($ar, 0, 255),
            'explanation' => mb_substr($explanation, 0, 1000),
            'icon' => mb_substr($icon, 0, 16),
        ];
    }

    private function normalizeString(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function normalizeIcon(string $value): string
    {
        $value = preg_replace('/\s+/u', '', $value) ?? '';

        return trim($value);
    }

    private function containsArabic(string $value): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $value);
    }
}
