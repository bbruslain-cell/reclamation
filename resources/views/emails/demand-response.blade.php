<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ANBG - Réponse à votre réclamation</title>
</head>
<body style="margin:0; padding:0; background:#ffffff; color:#111827; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;">
        <tr>
            <td align="left" style="padding:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;">
                    <tr>
                        <td style="padding:0 0 16px; border-bottom:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:15px; line-height:1.6; font-weight:700;">
                                Agence Nationale des Bourses du Gabon
                            </p>
                            <p style="margin:4px 0 0; font-size:14px; line-height:1.6; color:#4b5563;">
                                Cellule Réclamation
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 0 0;">
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                @if (!empty($recipientName))
                                    Bonjour {{ $recipientName }},
                                @else
                                    Bonjour,
                                @endif
                            </p>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                Nous faisons suite à votre réclamation enregistrée sur la plateforme de l'ANBG.
                                Une réponse a été apportée à votre dossier.
                            </p>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                <strong>Numéro de suivi :</strong> {{ $trackingNumber }}<br>
                                <strong>Objet de la réclamation :</strong> {{ $subjectLabel }}
                            </p>

                            <p style="margin:0 0 8px; font-size:15px; line-height:1.7; font-weight:700;">
                                Réponse de la Cellule Réclamation :
                            </p>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                {!! nl2br(e($responseContent)) !!}
                            </p>

                            @if (($attachmentCount ?? 0) > 0)
                                <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                    {{ $attachmentCount === 1 ? 'Une pièce jointe est incluse' : $attachmentCount.' pièces jointes sont incluses' }} dans cet email.
                                </p>
                            @endif

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                                Pour tout complément d'information, veuillez conserver ce numéro de suivi dans vos échanges avec la Cellule Réclamation.
                            </p>

                            <p style="margin:0; font-size:15px; line-height:1.7;">
                                Cordialement,<br>
                                Cellule Réclamation<br>
                                Agence Nationale des Bourses du Gabon
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
