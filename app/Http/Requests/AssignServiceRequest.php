<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class AssignServiceRequest extends FormRequest
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
            'id_direction' => ['required', 'integer', 'exists:directions,id_direction'],
            'id_service' => ['required', 'integer', 'exists:services,id_service'],
            'commentaire' => ['nullable', 'string', 'max:2000', new SafePublicText()],
        ];
    }

    public function directionId(): int
    {
        return (int) $this->validated('id_direction');
    }

    public function serviceId(): int
    {
        return (int) $this->validated('id_service');
    }

    public function comment(): ?string
    {
        $comment = trim((string) ($this->validated('commentaire') ?? ''));

        return $comment !== '' ? $comment : null;
    }
}
