<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PilotageTableExport implements FromView
{
    public function __construct(
        private readonly array $sections,
        private readonly string $title
    ) {
    }

    public function view(): View
    {
        return view('exports.pilotage.tables', [
            'title' => $this->title,
            'sections' => $this->sections,
            'forPdf' => false,
        ]);
    }
}
