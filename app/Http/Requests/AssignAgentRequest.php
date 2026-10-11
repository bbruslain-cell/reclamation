<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class AssignAgentRequest extends FormRequest
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
            'id_agent' => ['required', 'integer', 'exists:utilisateurs,id_utilisateur'],
            'commentaire' => ['nullable', 'string', 'max:2000', new SafePublicText()],
        ];
    }

    public function agentId(): int
    {
        return (int) $this->validated('id_agent');
    }

    public function comment(): ?string
    {
        $comment = trim((string) ($this->validated('commentaire') ?? ''));

        return $comment !== '' ? $comment : null;
    }
}
