<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AcceptedEmailDomain implements ValidationRule
{
    /**
     * @param list<string> $acceptedTlds
     */
    public function __construct(private readonly array $acceptedTlds)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $pattern = "/^[A-Z0-9._%+\\-']+@(?:[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?\\.)+(?:".$this->acceptedEmailTldPattern().')$/i';

        if (preg_match($pattern, (string) $value) !== 1) {
            $fail('Veuillez saisir une adresse email valide avec un domaine complet, par exemple nom@example.com.');
        }
    }

    private function acceptedEmailTldPattern(): string
    {
        return implode('|', array_map(
            static fn (string $tld): string => preg_quote($tld, '/'),
            $this->acceptedTlds
        ));
    }
}
