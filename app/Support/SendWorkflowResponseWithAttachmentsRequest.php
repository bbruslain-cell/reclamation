<?php

namespace App\Support;

use App\Http\Requests\SendWorkflowResponseRequest;

class SendWorkflowResponseWithAttachmentsRequest extends SendWorkflowResponseRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['pieces_jointes.*'] = [
            'nullable',
            'file',
            'max:4096',
            'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $rules;
    }

    public function messages(): array
    {
        $messages = parent::messages();
        $messages['pieces_jointes.*.mimetypes'] = 'Format de fichier non autorisé. Utilisez PDF, JPG, PNG, Word ou Excel.';

        return $messages;
    }
}
