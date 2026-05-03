<?php

// Fichier à placer à la racine de ton projet (même niveau que artisan)
// Lancer avec : php test-brevo.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Envoi en cours...\n";

try {
    Mail::raw('Test Brevo depuis Laravel OK', function ($m) {
        $m->to('bbruslain@gmail.com')
          ->subject('Test Laravel + Brevo');
    });

    echo "Email envoye avec succes !\n";
} catch (\Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}
