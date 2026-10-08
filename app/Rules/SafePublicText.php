<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafePublicText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $text = (string) $value;

        if (!mb_check_encoding($text, 'UTF-8')) {
            $fail($this->message($attribute));
            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text) === 1) {
            $fail($this->message($attribute));
            return;
        }

        if (preg_match('/[<>]|&(?:lt|gt);|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|on[a-z]+\s*=/iu', $text) === 1) {
            $fail($this->message($attribute));
            return;
        }

        if (preg_match('/--|\/\*|\*\/|\b(?:union\s+select|select\s+.+\s+from|insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+(?:table|database)|alter\s+table|truncate\s+table|(?:or|and)\s+\d+\s*=\s*\d+)\b/iu', $text) === 1) {
            $fail($this->message($attribute));
        }
    }

    private function message(string $attribute): string
    {
        $label = str_replace('_', ' ', $attribute);

        return "Le champ {$label} contient des caractères ou expressions non autorisés.";
    }
}
