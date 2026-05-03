<?php

// Script pour réextraire tous les modules des référentiels
// Usage: php reextract-modules.php [referentiel_id]

require_once __DIR__ . '/bootstrap/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Referentiel;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$referentielId = $argv[1] ?? null;

if (!$referentielId) {
    echo "Usage: php reextract-modules.php [referentiel_id]\n";
    echo "\nRéférentials disponibles:\n";
    Referentiel::all()->each(function ($ref) {
        echo "  ID {$ref->id}: {$ref->title} (PDF: " . ($ref->is_pdf ? 'oui' : 'non') . ")\n";
    });
    exit(1);
}

$referentiel = Referentiel::find($referentielId);

if (!$referentiel) {
    echo "Référentiel {$referentielId} non trouvé.\n";
    exit(1);
}

echo "Suppression des modules existants pour '{$referentiel->title}'...\n";
DB::table('modules')->where('referentiel_id', $referentiel->id)->delete();

echo "Extraction des modules du PDF...\n";
$extractor = new \App\Services\Referentiels\BepAccReferentielExtractor();
$modules = $extractor->extractFromPdf($referentiel->file_path);

echo "Insertion de " . count($modules) . " modules...\n";

foreach ($modules as $module) {
    $bibliographies = $module['bibliographies'] ?? [];
    unset($module['bibliographies']);

    $createdModule = $referentiel->modules()->create($module);

    $bibliographyRows = [];
    foreach ($bibliographies as $bibliographie) {
        $data = is_array($bibliographie) ? [
            'author' => $bibliographie['author'] ?? null,
            'title' => $bibliographie['title'] ?? null,
            'publisher' => $bibliographie['publisher'] ?? null,
            'year' => $bibliographie['year'] ?? null,
            'pages' => $bibliographie['pages'] ?? null,
            'raw_text' => $bibliographie['raw_text'] ?? null,
        ] : [
            'raw_text' => (string) $bibliographie,
        ];

        $bibliographyRows[] = array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    if ($bibliographyRows !== []) {
        $createdModule->bibliographies()->insert($bibliographyRows);
    }
}

echo "✓ Extraction terminée avec succès!\n";
echo "\nRésumé:\n";
Referentiel::find($referentielId)->modules()->withCount('bibliographies')->get()->each(function ($mod) {
    echo "  • {$mod->title}: {$mod->bibliographies_count} livre(s)\n";
});
