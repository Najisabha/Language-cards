<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared print layout math for deck and level print views.
 */
final class PrintLayout
{
    /** @var array<string, array{0: float, 1: float}> */
    public const PAPER_DIMENSIONS_MM = [
        'A4' => [210.0, 297.0],
        'A3' => [420.0, 297.0],
        'A2' => [420.0, 594.0],
        'A1' => [841.0, 594.0],
        'A0' => [841.0, 1189.0],
    ];

    private const CARD_WIDTH_MM = 60.6666667;

    private const CARD_HEIGHT_MM = 89.6666667;

    private const PAGE_PADDING_MM = 10.0;

    private const CARD_GAP_MM = 4.0;

    private const CARD_BORDER_WIDTH_MM = 0.3;

    private const FRONT_FONT_SIZE_PX = 28.0;

    private const BACK_FONT_SIZE_PX = 14.0;

    /** @var array<string, array{min: int, max: int}|array{min: float, max: float}> */
    public const CUSTOM_LIMITS = [
        'rows' => ['min' => 1, 'max' => 20],
        'cols' => ['min' => 1, 'max' => 20],
        'page_padding_mm' => ['min' => 0.0, 'max' => 50.0],
        'card_gap_mm' => ['min' => 0.0, 'max' => 30.0],
        'border_width_mm' => ['min' => 0.0, 'max' => 5.0],
        'front_font_size_px' => ['min' => 8.0, 'max' => 96.0],
        'back_font_size_px' => ['min' => 6.0, 'max' => 60.0],
    ];

    public const CUSTOM_BORDER_STYLES = ['solid', 'dashed', 'dotted', 'double', 'none'];

    public const FRONT_TEXT_ROTATIONS = ['none', '90', '-90'];

    public const UNIFIED_BG_MODES = ['solid', 'gradient'];

    public const GRADIENT_DIRECTIONS = ['135deg', '45deg', 'to right', 'to left', 'to bottom', 'to top'];

    /**
     * @return array<string, mixed>
     */
    public static function validateQuery(Request $request): array
    {
        $rowsLimits = self::CUSTOM_LIMITS['rows'];
        $colsLimits = self::CUSTOM_LIMITS['cols'];
        $paddingLimits = self::CUSTOM_LIMITS['page_padding_mm'];
        $gapLimits = self::CUSTOM_LIMITS['card_gap_mm'];
        $borderLimits = self::CUSTOM_LIMITS['border_width_mm'];
        $frontFontLimits = self::CUSTOM_LIMITS['front_font_size_px'];
        $backFontLimits = self::CUSTOM_LIMITS['back_font_size_px'];

        return $request->validate([
            'paper_size' => ['nullable', Rule::in(array_keys(self::PAPER_DIMENSIONS_MM))],
            'mode' => ['nullable', Rule::in(['default', 'custom'])],
            'rows' => ['nullable', 'integer', 'min:'.$rowsLimits['min'], 'max:'.$rowsLimits['max']],
            'cols' => ['nullable', 'integer', 'min:'.$colsLimits['min'], 'max:'.$colsLimits['max']],
            'page_padding_mm' => ['nullable', 'numeric', 'min:'.$paddingLimits['min'], 'max:'.$paddingLimits['max']],
            'card_gap_mm' => ['nullable', 'numeric', 'min:'.$gapLimits['min'], 'max:'.$gapLimits['max']],
            'border_width_mm' => ['nullable', 'numeric', 'min:'.$borderLimits['min'], 'max:'.$borderLimits['max']],
            'border_style' => ['nullable', Rule::in(self::CUSTOM_BORDER_STYLES)],
            'unify_backgrounds' => ['nullable', 'in:0,1,true,false,on'],
            'unify_with_back' => ['nullable', 'in:0,1,true,false,on'],
            'unified_bg_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'unified_bg_mode' => ['nullable', Rule::in(self::UNIFIED_BG_MODES)],
            'unified_bg_color_1' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'unified_bg_color_2' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'unified_bg_direction' => ['nullable', Rule::in(self::GRADIENT_DIRECTIONS)],
            'front_font_size_px' => ['nullable', 'numeric', 'min:'.$frontFontLimits['min'], 'max:'.$frontFontLimits['max']],
            'back_font_size_px' => ['nullable', 'numeric', 'min:'.$backFontLimits['min'], 'max:'.$backFontLimits['max']],
            'front_text_rotate' => ['nullable', Rule::in(self::FRONT_TEXT_ROTATIONS)],
        ]);
    }

    private static function castBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function settingsFromQuery(array $validated): array
    {
        $mode = $validated['mode'] ?? 'default';
        $paperSize = $validated['paper_size'] ?? 'A4';

        return $mode === 'custom'
            ? self::buildCustomPrintSettings($paperSize, $validated)
            : self::buildDefaultPrintSettings($paperSize);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function buildCustomPrintSettings(string $paperSize, array $input): array
    {
        [$pageWidth, $pageHeight] = self::PAPER_DIMENSIONS_MM[$paperSize];

        $rows = (int) ($input['rows'] ?? 3);
        $cols = (int) ($input['cols'] ?? 3);
        $padding = (float) ($input['page_padding_mm'] ?? self::PAGE_PADDING_MM);
        $gap = (float) ($input['card_gap_mm'] ?? self::CARD_GAP_MM);
        $borderWidth = (float) ($input['border_width_mm'] ?? self::CARD_BORDER_WIDTH_MM);
        $borderStyle = $input['border_style'] ?? 'solid';

        if ($borderStyle !== 'none' && $borderWidth <= 0) {
            $borderWidth = self::CARD_BORDER_WIDTH_MM;
        }

        $usableWidth = max(0.0, $pageWidth - 2 * $padding - max(0, $cols - 1) * $gap);
        $usableHeight = max(0.0, $pageHeight - 2 * $padding - max(0, $rows - 1) * $gap);

        $cardWidth = $cols > 0 ? $usableWidth / $cols : 0.0;
        $cardHeight = $rows > 0 ? $usableHeight / $rows : 0.0;

        $cssPageSize = self::formatMm($pageWidth).'mm '.self::formatMm($pageHeight).'mm';

        $unifyBg = self::castBool($input['unify_backgrounds'] ?? false);
        $unifyWithBack = self::castBool($input['unify_with_back'] ?? false);
        $bgMode = (string) ($input['unified_bg_mode'] ?? 'solid');
        if (! in_array($bgMode, self::UNIFIED_BG_MODES, true)) {
            $bgMode = 'solid';
        }

        $solidColor = (string) ($input['unified_bg_color'] ?? '#ffffff');
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $solidColor)) {
            $solidColor = '#ffffff';
        }

        $color1 = (string) ($input['unified_bg_color_1'] ?? '#a78bfa');
        $color2 = (string) ($input['unified_bg_color_2'] ?? '#f472b6');
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color1)) {
            $color1 = '#a78bfa';
        }
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color2)) {
            $color2 = '#f472b6';
        }
        $bgDirection = (string) ($input['unified_bg_direction'] ?? '135deg');
        if (! in_array($bgDirection, self::GRADIENT_DIRECTIONS, true)) {
            $bgDirection = '135deg';
        }

        $unifiedBgValue = $bgMode === 'gradient'
            ? "linear-gradient({$bgDirection}, {$color1}, {$color2})"
            : $solidColor;

        $frontFontLimits = self::CUSTOM_LIMITS['front_font_size_px'];
        $backFontLimits = self::CUSTOM_LIMITS['back_font_size_px'];
        $frontFont = (float) ($input['front_font_size_px'] ?? self::FRONT_FONT_SIZE_PX);
        $backFont = (float) ($input['back_font_size_px'] ?? self::BACK_FONT_SIZE_PX);
        $frontFont = max($frontFontLimits['min'], min($frontFontLimits['max'], $frontFont));
        $backFont = max($backFontLimits['min'], min($backFontLimits['max'], $backFont));

        $rotate = (string) ($input['front_text_rotate'] ?? 'none');
        if (! in_array($rotate, self::FRONT_TEXT_ROTATIONS, true)) {
            $rotate = 'none';
        }

        return [
            'paper_size' => $paperSize,
            'css_page_size' => $cssPageSize,
            'page_width_mm' => $pageWidth,
            'page_height_mm' => $pageHeight,
            'card_width_mm' => $cardWidth,
            'card_height_mm' => $cardHeight,
            'padding_mm' => $padding,
            'gap_mm' => $gap,
            'cols' => $cols,
            'rows' => $rows,
            'per_page' => $cols * $rows,
            'border_width_mm' => $borderWidth,
            'border_style' => $borderStyle,
            'unify_backgrounds' => $unifyBg,
            'unify_with_back' => $unifyWithBack,
            'unified_bg_mode' => $bgMode,
            'unified_bg_value' => $unifiedBgValue,
            'unified_bg_color' => $solidColor,
            'unified_bg_color_1' => $color1,
            'unified_bg_color_2' => $color2,
            'unified_bg_direction' => $bgDirection,
            'front_font_size_px' => $frontFont,
            'back_font_size_px' => $backFont,
            'front_text_rotate' => $rotate,
            'mode' => 'custom',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildDefaultPrintSettings(string $paperSize): array
    {
        [$cols, $rows] = self::gridFor($paperSize);
        [$pageWidth, $pageHeight] = self::PAPER_DIMENSIONS_MM[$paperSize];

        $cssPageSize = self::formatMm($pageWidth).'mm '.self::formatMm($pageHeight).'mm';

        return [
            'paper_size' => $paperSize,
            'css_page_size' => $cssPageSize,
            'page_width_mm' => $pageWidth,
            'page_height_mm' => $pageHeight,
            'card_width_mm' => self::CARD_WIDTH_MM,
            'card_height_mm' => self::CARD_HEIGHT_MM,
            'padding_mm' => self::PAGE_PADDING_MM,
            'gap_mm' => self::CARD_GAP_MM,
            'cols' => $cols,
            'rows' => $rows,
            'per_page' => $cols * $rows,
            'border_width_mm' => self::CARD_BORDER_WIDTH_MM,
            'border_style' => 'solid',
            'unify_backgrounds' => false,
            'unify_with_back' => false,
            'unified_bg_mode' => 'solid',
            'unified_bg_value' => '#ffffff',
            'unified_bg_color' => '#ffffff',
            'unified_bg_color_1' => '#a78bfa',
            'unified_bg_color_2' => '#f472b6',
            'unified_bg_direction' => '135deg',
            'front_font_size_px' => self::FRONT_FONT_SIZE_PX,
            'back_font_size_px' => self::BACK_FONT_SIZE_PX,
            'front_text_rotate' => 'none',
            'mode' => 'default',
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function gridFor(string $paperSize): array
    {
        return match ($paperSize) {
            'A4' => [3, 3],
            'A3' => [6, 3],
            'A2' => [6, 6],
            'A1' => [12, 6],
            'A0' => [12, 12],
        };
    }

    public static function formatMm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
