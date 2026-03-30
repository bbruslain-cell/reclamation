<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reponse a votre demande</title>
</head>
<body style="margin:0; padding:24px; background:#f5f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:680px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden;">
        <div style="background:#1c203d; color:#ffffff; padding:24px;">
            <p style="margin:0; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; opacity:0.8;">ANBG CIQ</p>
            <h1 style="margin:10px 0 0; font-size:24px; line-height:1.3;">Reponse a votre demande {{ $trackingNumber }}</h1>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                @if(!empty($recipientName))
                    Bonjour {{ $recipientName }},
                @else
                    Bonjour,
                @endif
            </p>

            <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
                Une reponse a ete apportee a votre demande.
            </p>

            <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:0 0 16px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280;">Numero de suivi</p>
                <p style="margin:0 0 14px; font-size:16px; font-weight:700; color:#111827;">{{ $trackingNumber }}</p>

                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280;">Objet</p>
                <p style="margin:0; font-size:15px; color:#111827;">{{ $subjectLabel }}</p>
            </div>

            <div style="margin:0 0 16px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280;">Contenu de la reponse</p>
                <div style="background:#ffffff; border-left:4px solid #3996d3; padding:12px 14px; font-size:15px; line-height:1.7; color:#111827;">
                    {!! nl2br(e($responseContent)) !!}
                </div>
            </div>

            @if(($attachmentCount ?? 0) > 0)
                <p style="margin:0 0 16px; font-size:14px; line-height:1.7; color:#4b5563;">
                    Des pieces jointes sont incluses dans cet email.
                </p>
            @endif

            <p style="margin:0; font-size:14px; line-height:1.7; color:#4b5563;">
                Cordialement,<br>
                Cellule CIQ - ANBG
            </p>
        </div>
    </div>
</body>
</html>
