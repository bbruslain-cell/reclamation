<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PilotageTableExport implements FromView, WithTitle, WithEvents, ShouldAutoSize
{
    public function __construct(
        private readonly array $sections,
        private readonly string $title,
        private readonly ?string $sheetTitle = null
    ) {
    }

    public function view(): View
    {
        return view('exports.pilotage.tables', [
            'title' => $this->title,
            'sheetTitle' => $this->title(),
            'sections' => $this->sections,
            'forPdf' => false,
        ]);
    }

    public function title(): string
    {
        $title = trim((string) ($this->sheetTitle ?: $this->title));
        $title = preg_replace('/[\\\\\\/*?:\\[\\]]/', '-', $title) ?: 'Export';

        return mb_substr($title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow < 1) {
                    return;
                }

                $fullRange = 'A1:'.$highestColumn.$highestRow;
                $sheet->getStyle($fullRange)->getAlignment()->setVertical('top');
                $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFB8C3CF');

                $sheet->getStyle('A1:'.$highestColumn.'1')->getFont()->setBold(true)->setSize(14);

                foreach (range(1, $highestRow) as $row) {
                    $firstCell = (string) $sheet->getCell('A'.$row)->getValue();

                    if ($firstCell === $this->title) {
                        continue;
                    }

                    if (str_starts_with($firstCell, 'Suivi') || str_starts_with($firstCell, 'RECLAMATIONS') || str_starts_with($firstCell, 'Repartition')) {
                        $sheet->getStyle('A'.$row.':'.$highestColumn.$row)->getFont()->setBold(true);
                        continue;
                    }

                    $rowRange = 'A'.$row.':'.$highestColumn.$row;
                    $values = $sheet->rangeToArray($rowRange, null, true, false)[0] ?? [];
                    $filledCount = collect($values)->filter(fn ($value) => $value !== null && $value !== '')->count();

                    if ($filledCount >= 2 && $row <= $highestRow) {
                        $isLikelyHeader = $row > 1 && collect($values)
                            ->filter(fn ($value) => is_string($value) && $value !== '')
                            ->every(fn ($value) => mb_strtoupper((string) $value) === (string) $value);

                        if ($isLikelyHeader) {
                            $sheet->getStyle($rowRange)->getFont()->setBold(true);
                            $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9EFF6');
                        }
                    }

                    if (is_string($firstCell) && str_contains(mb_strtoupper($firstCell), 'TOTAL')) {
                        $sheet->getStyle($rowRange)->getFont()->setBold(true);
                        $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F6F9');
                    }
                }
            },
        ];
    }
}
