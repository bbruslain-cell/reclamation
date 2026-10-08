<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PilotageChartImageRenderer
{
    public function slaBars(array $rows, string $labelKey): ?string
    {
        if (empty($rows) || ! extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $rowHeight = 58;
        $height = max(460, 150 + (count($rows) * $rowHeight));
        $left = 360;
        $right = 90;
        $top = 72;
        $chartWidth = $width - $left - $right;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = $this->gdColor($image, '#1c203d');
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $barBackground = $this->gdColor($image, '#eef4fb');
        imagefill($image, 0, 0, $white);

        for ($step = 0; $step <= 100; $step += 25) {
            $x = $left + (int) round(($step / 100) * $chartWidth);
            imageline($image, $x, $top - 24, $x, $height - 70, $grid);
            $this->drawGdText($image, $step.' %', 11, $x - 16, $height - 42, $muted);
        }

        foreach ($rows as $index => $row) {
            $y = $top + ($index * $rowHeight);
            $barY = $y + 12;
            $barHeight = 22;
            $filledWidth = (int) round($chartWidth * min(100, max(0, (float) $row['taux'])) / 100);
            $color = $this->gdColor($image, (string) $row['color']);

            $this->drawGdText($image, $this->truncateChartText((string) ($row[$labelKey] ?? '-'), 40), 13, 34, $barY + 17, $navy);
            imagefilledrectangle($image, $left, $barY, $left + $chartWidth, $barY + $barHeight, $barBackground);

            if ($filledWidth > 0) {
                imagefilledrectangle($image, $left, $barY, $left + $filledWidth, $barY + $barHeight, $color);
            }

            $this->drawGdText($image, number_format((float) $row['taux'], 1, ',', ' ').' %', 12, $left + $chartWidth + 18, $barY + 17, $navy, true);
        }

        return $this->pngDataUri($image);
    }

    public function demandRankingBars(array $rows): ?string
    {
        if (empty($rows) || ! extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $rowHeight = 58;
        $height = max(460, 150 + (count($rows) * $rowHeight));
        $left = 430;
        $right = 100;
        $top = 72;
        $chartWidth = $width - $left - $right;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = $this->gdColor($image, '#1c203d');
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $barBackground = $this->gdColor($image, '#eef4fb');
        imagefill($image, 0, 0, $white);

        $maxValue = max(1, (int) collect($rows)->max('total_demandes'));
        $maxAxis = max(1, (int) ceil($maxValue / 5) * 5);

        for ($step = 0; $step <= 5; $step++) {
            $value = (int) round(($maxAxis / 5) * $step);
            $x = $left + (int) round(($value / $maxAxis) * $chartWidth);
            imageline($image, $x, $top - 24, $x, $height - 70, $grid);
            $this->drawGdText($image, number_format($value, 0, ',', ' '), 11, $x - 16, $height - 42, $muted);
        }

        foreach ($rows as $index => $row) {
            $y = $top + ($index * $rowHeight);
            $barY = $y + 12;
            $barHeight = 22;
            $total = max(0, (int) ($row['total_demandes'] ?? 0));
            $filledWidth = (int) round($chartWidth * min($maxAxis, $total) / $maxAxis);
            $color = $this->gdColor($image, (string) ($row['color'] ?? '#3996d3'));

            $this->drawGdText($image, $this->truncateChartText((string) ($row['label'] ?? '-'), 48), 13, 34, $barY + 17, $navy);
            imagefilledrectangle($image, $left, $barY, $left + $chartWidth, $barY + $barHeight, $barBackground);

            if ($filledWidth > 0) {
                imagefilledrectangle($image, $left, $barY, $left + $filledWidth, $barY + $barHeight, $color);
            }

            $this->drawGdText($image, number_format($total, 0, ',', ' '), 12, $left + $chartWidth + 18, $barY + 17, $navy, true);
        }

        return $this->pngDataUri($image);
    }

    public function reclamationEvolution(array $data): ?string
    {
        $labels = $data['labels'] ?? [];
        $received = $data['recues'] ?? [];
        $closed = $data['cloturees'] ?? [];
        $open = $data['non_cloturees'] ?? [];

        if (empty($labels) || ! extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $height = 440;
        $left = 86;
        $right = 52;
        $top = 42;
        $bottom = 72;
        $chartWidth = $width - $left - $right;
        $chartHeight = $height - $top - $bottom;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $sky = $this->gdColor($image, '#3996d3');
        $leaf = $this->gdColor($image, '#8fc043');
        $amber = $this->gdColor($image, '#f59e0b');
        imagefill($image, 0, 0, $white);

        $maxValue = max(1, (int) max(array_merge($received, $closed, $open, [1])));
        $maxAxis = (int) (ceil($maxValue / 5) * 5);
        $pointCount = count($labels);
        $xStep = $pointCount > 1 ? $chartWidth / ($pointCount - 1) : 0;

        for ($step = 0; $step <= 5; $step++) {
            $value = (int) round(($maxAxis / 5) * $step);
            $y = $top + $chartHeight - (int) round(($value / $maxAxis) * $chartHeight);
            imageline($image, $left, $y, $width - $right, $y, $grid);
            $this->drawGdText($image, (string) $value, 11, 28, $y + 4, $muted);
        }

        $labelSkip = max(1, (int) ceil($pointCount / 7));
        foreach ($labels as $index => $label) {
            if ($index % $labelSkip !== 0 && $index !== $pointCount - 1) {
                continue;
            }

            $x = $pointCount > 1 ? $left + (int) round($index * $xStep) : $left + (int) round($chartWidth / 2);
            $this->drawGdText($image, $this->truncateChartText((string) $label, 14), 10, $x - 26, $height - 48, $muted);
        }

        $this->drawLineSeries($image, $received, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $sky);
        $this->drawLineSeries($image, $closed, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $leaf);
        $this->drawLineSeries($image, $open, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $amber);

        imagesetthickness($image, 1);
        imageline($image, $left, $top, $left, $top + $chartHeight, $grid);
        imageline($image, $left, $top + $chartHeight, $width - $right, $top + $chartHeight, $grid);

        return $this->pngDataUri($image);
    }

    private function drawLineSeries($image, array $values, int $pointCount, int $left, int $top, int $chartWidth, int $chartHeight, int $maxAxis, int $color): void
    {
        if ($pointCount <= 0) {
            return;
        }

        $points = [];
        $xStep = $pointCount > 1 ? $chartWidth / ($pointCount - 1) : 0;

        for ($index = 0; $index < $pointCount; $index++) {
            $value = (int) ($values[$index] ?? 0);
            $x = $pointCount > 1 ? $left + (int) round($index * $xStep) : $left + (int) round($chartWidth / 2);
            $y = $top + $chartHeight - (int) round((min($value, $maxAxis) / $maxAxis) * $chartHeight);
            $points[] = [$x, $y];
        }

        imagesetthickness($image, 4);
        for ($index = 1; $index < count($points); $index++) {
            imageline($image, $points[$index - 1][0], $points[$index - 1][1], $points[$index][0], $points[$index][1], $color);
        }

        foreach ($points as [$x, $y]) {
            imagefilledellipse($image, $x, $y, 12, 12, $color);
        }

        imagesetthickness($image, 1);
    }

    public function directionDonut(array $rows, int $total): ?string
    {
        if ($total <= 0 || empty($rows) || ! extension_loaded('gd')) {
            return null;
        }

        $size = 640;
        $center = (int) ($size / 2);
        $outerDiameter = 500;
        $innerDiameter = 245;
        $image = imagecreatetruecolor($size, $size);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $softBackground = imagecolorallocate($image, 245, 247, 251);
        imagefill($image, 0, 0, $white);
        imagefilledellipse($image, $center, $center, $outerDiameter + 34, $outerDiameter + 34, $softBackground);

        $startAngle = 270.0;
        $rowCount = count($rows);

        foreach ($rows as $row) {
            $rgb = $this->hexToRgb((string) ($row['color'] ?? '#3996d3'));
            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

            if ($rowCount === 1) {
                imagefilledellipse($image, $center, $center, $outerDiameter, $outerDiameter, $color);
                break;
            }

            $angle = (((int) ($row['total'] ?? 0)) / $total) * 360;
            $endAngle = $startAngle + $angle;
            $normalizedStart = fmod($startAngle, 360.0);
            $normalizedEnd = $normalizedStart + $angle;

            if ($normalizedEnd <= 360.0) {
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, (int) round($normalizedStart), (int) round($normalizedEnd), $color, IMG_ARC_PIE);
            } else {
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, (int) round($normalizedStart), 360, $color, IMG_ARC_PIE);
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, 0, (int) round($normalizedEnd - 360.0), $color, IMG_ARC_PIE);
            }

            $startAngle = $endAngle;
        }

        imagefilledellipse($image, $center, $center, $innerDiameter, $innerDiameter, $white);

        return $this->pngDataUri($image);
    }

    private function gdColor($image, string $hex): int
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);

        return imagecolorallocate($image, $red, $green, $blue);
    }

    private function drawGdText($image, string $text, int $size, int $x, int $y, int $color, bool $bold = false): void
    {
        $font = $this->chartFontPath($bold);

        if ($font) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);

            return;
        }

        $fallbackText = function_exists('iconv')
            ? (iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text)
            : $text;
        imagestring($image, min(5, max(1, (int) round($size / 3))), $x, max(0, $y - $size), $fallbackText, $color);
    }

    private function chartFontPath(bool $bold = false): ?string
    {
        $candidates = $bold ? [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
            'C:\\Windows\\Fonts\\arialbd.ttf',
        ] : [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && File::isFile($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function truncateChartText(string $text, int $length): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $length ? mb_substr($text, 0, max(1, $length - 1)).'…' : $text;
        }

        return strlen($text) > $length ? substr($text, 0, max(1, $length - 1)).'...' : $text;
    }

    private function pngDataUri($image): ?string
    {
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png ? 'data:image/png;base64,'.base64_encode($png) : null;
    }

    private function hexToRgb(string $hex): array
    {
        $clean = ltrim($hex, '#');
        if (strlen($clean) === 3) {
            $clean = $clean[0].$clean[0].$clean[1].$clean[1].$clean[2].$clean[2];
        }

        if (strlen($clean) !== 6 || ! ctype_xdigit($clean)) {
            return [57, 150, 211];
        }

        return [
            hexdec(substr($clean, 0, 2)),
            hexdec(substr($clean, 2, 2)),
            hexdec(substr($clean, 4, 2)),
        ];
    }
}
