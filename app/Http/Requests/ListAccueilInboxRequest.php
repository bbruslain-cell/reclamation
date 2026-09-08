<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class ListAccueilInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255', new SafePublicText()],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'type_code' => ['nullable', 'string', 'max:80'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id_direction'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function searchTerm(): string
    {
        return trim((string) ($this->validated('search') ?? ''));
    }

    public function sortBy(): string
    {
        return (string) ($this->validated('sort_by') ?? 'date_soumission');
    }

    public function sortDirection(): string
    {
        return (string) ($this->validated('sort_dir') ?? 'desc');
    }

    public function typeCode(): string
    {
        return (string) ($this->validated('type_code') ?? '');
    }

    public function directionId(): int
    {
        $directionId = $this->validated('direction_id');

        return $directionId !== null ? (int) $directionId : 0;
    }

    public function dateFrom(): string
    {
        return (string) ($this->validated('date_from') ?? '');
    }

    public function dateTo(): string
    {
        return (string) ($this->validated('date_to') ?? '');
    }
}
