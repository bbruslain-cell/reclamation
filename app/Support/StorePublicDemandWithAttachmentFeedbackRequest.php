<?php

namespace App\Support;

use App\Http\Requests\StorePublicDemandRequest;

class StorePublicDemandWithAttachmentFeedbackRequest extends StorePublicDemandRequest
{
    public function messages(): array
    {
        $messages = parent::messages();
        $messages['piece_jointe.max'] = 'Fichier trop lourd. Maximum autorisé : 3,5 Mo.';

        return $messages;
    }
}
