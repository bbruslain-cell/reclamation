<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class CancelAssignmentRequest extends FormRequest
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
            'commentaire' => ['nullable', 'string', 'max:2000', new SafePublicText()],
        ];
    }

    public function comment(): ?string
    {
        $comment = trim((string) ($this->validated('commentaire') ?? ''));

        return $comment !== '' ? $comment : null;
    }
}
