<?php

namespace App\Http\Requests;

use App\Rules\SafePublicText;
use Illuminate\Foundation\Http\FormRequest;

class SendWorkflowResponseRequest extends FormRequest
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
            'contenu_reponse' => ['required', 'string', 'min:20', new SafePublicText()],
            'pieces_jointes' => ['nullable', 'array', 'max:5'],
            'pieces_jointes.*' => ['nullable', 'file', 'max:4096', 'mimetypes:application/pdf,image/jpeg,image/png'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contenu_reponse.required' => 'La réponse est requise.',
            'contenu_reponse.min' => 'La réponse doit contenir au moins 20 caractères.',
            'pieces_jointes.array' => 'Les pièces jointes doivent être envoyées sous forme de liste.',
            'pieces_jointes.max' => 'Vous ne pouvez pas joindre plus de 5 fichiers.',
            'pieces_jointes.*.file' => 'Chaque pièce jointe doit être un fichier valide.',
            'pieces_jointes.*.max' => 'Chaque pièce jointe ne doit pas dépasser 4 Mo.',
            'pieces_jointes.*.mimetypes' => 'Format de fichier non autorisé. Utilisez PDF, JPG ou PNG.',
        ];
    }

    public function responseContent(): string
    {
        return trim((string) $this->validated('contenu_reponse'));
    }
}
