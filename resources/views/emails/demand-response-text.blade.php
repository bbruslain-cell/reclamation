@if (!empty($recipientName))
Bonjour {!! $recipientName !!},
@else
Bonjour,
@endif

Agence Nationale des Bourses du Gabon
Cellule Réclamations

Nous faisons suite à votre réclamation enregistrée sur la plateforme de l'ANBG.

Numéro de suivi : {!! $trackingNumber !!}
Objet de la réclamation : {!! $subjectLabel !!}

Réponse :
{!! $responseContent !!}

@if (($attachmentCount ?? 0) > 0)
{{ $attachmentCount === 1 ? 'Une pièce jointe est incluse' : $attachmentCount.' pièces jointes sont incluses' }} dans cet email.

@endif
Pour tout complément d'information, veuillez conserver ce numéro de suivi dans vos échanges avec la Cellule Réclamations.

Cordialement,
Cellule Réclamations
Agence Nationale des Bourses du Gabon
