<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class ListChefInboxRequest extends FormRequest
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
        ];
    }

    public function searchTerm(): string
    {
        return trim((string) ($this->validated('search') ?? ''));
    }
}
