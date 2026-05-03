<?php

namespace App\Http\Controllers;

use App\Services\CardAiSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardAiSuggestController extends Controller
{
    public function __invoke(Request $request, CardAiSuggester $suggester): JsonResponse
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:120'],
        ]);

        if (! config('services.ai.api_key')) {
            return response()->json([
                'message' => 'ميزة الذكاء الاصطناعي غير مُعدّة. أضف AI_API_KEY في ملف البيئة.',
            ], 503);
        }

        try {
            $result = $suggester->suggest($validated['word']);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('Card AI suggest failed', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'تعذر الحصول على اقتراح من الخدمة. حاول لاحقًا.',
            ], 502);
        }
    }
}
