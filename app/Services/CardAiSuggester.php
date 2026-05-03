<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CardAiSuggester
{
    /** @var array<string, mixed> */
    public function suggest(string $word): array
    {
        $word = trim($word);

        $apiKey = config('services.ai.api_key');
        $baseUrl = rtrim(config('services.ai.base_url'), '/');
        $model = config('services.ai.model');

        if (! $apiKey || ! $model) {
            throw new \RuntimeException('AI is not configured');
        }

        $system = <<<'TXT'
You help build bilingual (English/Arabic) vocabulary flashcards. The user enters a term that may be in English or Arabic.
Respond with JSON only. Required keys: en_meaning, ar_meaning, explanation, icon.
Never return empty en_meaning or empty ar_meaning.
If the term is English:
- en_meaning: short English gloss/definition
- ar_meaning: Arabic translation or concise Arabic gloss
If the term is Arabic:
- en_meaning: English translation or concise English gloss
- ar_meaning: short Arabic gloss (can reuse the original Arabic term when already clear)
Keep meanings plain text, learner-friendly, and brief.
For explanation, return one short Arabic example sentence (or a brief Arabic explanation) suitable for learners.
For icon return exactly one suitable emoji character only.
TXT;

        $user = 'Term: '.$word;

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
            } else {
                $en = $word;
            }
        } elseif ($en === '') {
            $en = $word;
        } elseif ($ar === '') {
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
