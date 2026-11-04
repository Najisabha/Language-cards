<?php

namespace App\Support;

use App\Models\Card;
use App\Models\Category;
use App\Models\Deck;
use App\Models\Language;
use App\Models\Level;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LanguageExcelExchange
{
    private const DEFAULT_DECK_COLOR = '#6366f1';

    private const DEFAULT_FRONT_BG_TYPE = 'color';

    private const DEFAULT_FRONT_BG_VALUE = '#ffffff';

    /** @var list<string> */
    public const HEADERS = [
        'المستوى',
        'النوع',
        'الفئة',
        'الكلمة',
        'المعنى الإنجليزي',
        'المعنى العربي',
        'الشرح',
        'الأيقونة',
    ];

    public function download(Language $language): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($language);
        $filename = $this->filename($language);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadTemplate(Language $language): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('البطاقات');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $this->styleHeaderRow($sheet, count(self::HEADERS));

        $filename = 'قالب_'.$this->filename($language);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(Language $language, UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['الملف فارغ.'],
            ];
        }

        $headerRow = array_shift($rows);
        $columnMap = $this->mapHeaders(array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow ?? []));

        if (! isset($columnMap['level_name'], $columnMap['word'], $columnMap['deck_name'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['أعمدة مطلوبة مفقودة: يجب وجود «المستوى» و«النوع» و«الكلمة» في الصف الأول.'],
            ];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $seenInFile = [];

        foreach ($language->levels as $level) {
            $level->migrateLegacyWordSentenceDecks();
        }

        DB::transaction(function () use (
            $language,
            $rows,
            $columnMap,
            &$created,
            &$updated,
            &$skipped,
            &$errors,
            &$seenInFile
        ): void {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $levelName = trim((string) ($row[$columnMap['level_name']] ?? ''));
                $deckName = trim((string) ($row[$columnMap['deck_name']] ?? ''));
                $word = trim((string) ($row[$columnMap['word']] ?? ''));

                if ($levelName === '' || $deckName === '' || $word === '') {
                    $skipped++;
                    $errors[] = "الصف {$line}: المستوى والنوع والكلمة مطلوبة.";
                    continue;
                }

                if (in_array($deckName, [Deck::CATEGORY_WORDS, Deck::CATEGORY_SENTENCES], true)) {
                    $skipped++;
                    $errors[] = "الصف {$line}: اسم النوع «{$deckName}» غير مسموح.";
                    continue;
                }

                $normalizedWord = mb_strtolower($word, 'UTF-8');
                if (isset($seenInFile[$normalizedWord])) {
                    $skipped++;
                    $errors[] = "الصف {$line}: الكلمة «{$word}» مكررة داخل الملف.";
                    continue;
                }
                $seenInFile[$normalizedWord] = true;

                $categoryName = trim((string) ($row[$columnMap['category_name'] ?? -1] ?? ''));
                if ($categoryName === '') {
                    $categoryName = Deck::CATEGORY_WORDS;
                }

                $payload = [
                    'word' => mb_substr($word, 0, 120),
                    'front_bg_type' => self::DEFAULT_FRONT_BG_TYPE,
                    'front_bg_value' => self::DEFAULT_FRONT_BG_VALUE,
                    'en_meaning' => $this->nullableString($row[$columnMap['en_meaning'] ?? -1] ?? null, 255),
                    'ar_meaning' => $this->nullableString($row[$columnMap['ar_meaning'] ?? -1] ?? null, 255),
                    'explanation' => $this->nullableString($row[$columnMap['explanation'] ?? -1] ?? null, 1000),
                    'icon' => $this->sanitizeIconEmoji($row[$columnMap['icon'] ?? -1] ?? null),
                    'show_en' => true,
                    'show_ar' => true,
                    'show_explanation' => false,
                    'show_icon' => false,
                    'position' => 0,
                ];
                $payload['show_icon'] = $payload['icon'] !== null;

                $level = Level::query()->firstOrCreate(
                    [
                        'language_id' => $language->id,
                        'name' => mb_substr($levelName, 0, 50),
                    ],
                    [
                        'title' => null,
                        'position' => 0,
                    ]
                );

                $deck = Deck::query()->firstOrCreate(
                    [
                        'level_id' => $level->id,
                        'name' => mb_substr($deckName, 0, 120),
                    ],
                    [
                        'color' => self::DEFAULT_DECK_COLOR,
                        'description' => null,
                    ]
                );

                $category = Category::query()->firstOrCreate(
                    [
                        'deck_id' => $deck->id,
                        'name' => mb_substr($categoryName, 0, 120),
                    ],
                    [
                        'position' => $categoryName === Deck::CATEGORY_SENTENCES ? 2 : 1,
                        'description' => null,
                    ]
                );

                $existing = $this->findCardInLanguage($language->id, $normalizedWord);

                if ($existing) {
                    $existing->fill([
                        ...$payload,
                        'deck_id' => $deck->id,
                        'category_id' => $category->id,
                    ])->save();
                    $updated++;
                } else {
                    Card::create([
                        ...$payload,
                        'deck_id' => $deck->id,
                        'category_id' => $category->id,
                    ]);
                    $created++;
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'errors');
    }

    private function buildSpreadsheet(Language $language): Spreadsheet
    {
        $language->load(['levels' => fn ($q) => $q->orderBy('position')->orderBy('name')]);

        foreach ($language->levels as $level) {
            $level->migrateLegacyWordSentenceDecks();
        }

        $language->load([
            'levels' => fn ($q) => $q->orderBy('position')->orderBy('name')->with([
                'decks' => fn ($dq) => $dq->topics()->orderBy('id')->with([
                    'categories' => fn ($cq) => $cq->orderBy('position')->with([
                        'cards' => fn ($cardQuery) => $cardQuery->orderBy('position')->orderBy('id'),
                    ]),
                ]),
            ]),
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('البطاقات');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $this->styleHeaderRow($sheet, count(self::HEADERS));

        $rowNumber = 2;
        foreach ($language->levels as $level) {
            foreach ($level->decks as $deck) {
                foreach ($deck->categories as $category) {
                    foreach ($category->cards as $card) {
                        $sheet->fromArray([[
                            $level->name,
                            $deck->name,
                            $category->name,
                            $card->word,
                            $card->en_meaning,
                            $card->ar_meaning,
                            $card->explanation,
                            $card->iconEmoji() ?? $card->icon,
                        ]], null, 'A'.$rowNumber);
                        $rowNumber++;
                    }
                }
            }
        }

        foreach (range(1, count(self::HEADERS)) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function styleHeaderRow($sheet, int $columnCount): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
    }

    private function filename(Language $language): string
    {
        $safe = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', (string) ($language->code ?: $language->name)) ?: 'lang';

        return $safe.'_cards.xlsx';
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int>
     */
    private function mapHeaders(array $headers): array
    {
        $aliases = [
            'level_name' => ['المستوى', 'level', 'level_name'],
            'deck_name' => ['النوع', 'deck', 'deck_name', 'type', 'group'],
            'category_name' => ['الفئة', 'category', 'category_name'],
            'word' => ['الكلمة', 'word', 'front'],
            'en_meaning' => ['المعنى الإنجليزي', 'en_meaning', 'english', 'en'],
            'ar_meaning' => ['المعنى العربي', 'ar_meaning', 'arabic', 'ar'],
            'explanation' => ['الشرح', 'explanation'],
            'icon' => ['الأيقونة', 'الايقونة', 'icon'],
        ];

        $map = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }
            foreach ($aliases as $key => $names) {
                $normalizedNames = array_map(fn (string $name) => $this->normalizeHeader($name), $names);
                if (in_array($header, $normalizedNames, true) && ! isset($map[$key])) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value, ?int $max = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return $max === null ? $text : mb_substr($text, 0, $max);
    }

    private function sanitizeIconEmoji(mixed $value): ?string
    {
        $icon = $this->nullableString($value, 16);
        if ($icon === null) {
            return null;
        }

        if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/')) {
            return null;
        }

        return $icon;
    }

    private function findCardInLanguage(int $languageId, string $normalizedWord): ?Card
    {
        return Card::query()
            ->select('cards.*')
            ->join('categories', 'categories.id', '=', 'cards.category_id')
            ->join('decks', 'decks.id', '=', 'categories.deck_id')
            ->join('levels', 'levels.id', '=', 'decks.level_id')
            ->where('levels.language_id', $languageId)
            ->whereRaw('LOWER(TRIM(cards.word)) = ?', [$normalizedWord])
            ->with('deck')
            ->first();
    }
}
