<?php

namespace App\Http\Controllers;


use App\Services\CardWebIconImageFetcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardAiIconImageController extends Controller
{
    public function __invoke(Request $request, CardWebIconImageFetcher $fetcher): JsonResponse
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:120'],
        ]);

        try {
            $result = $fetcher->fetch($validated['word']);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('Card web icon fetch failed', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'تعذر العثور على صورة مناسبة من الويكيميديا. جرّب كلمة أخرى بالإنجليزية، أو ارفع صورة يدوياً.',
            ], 502);
        }
    }
}
