<?php

/**
 * Script dedie au modele "Referentiel BEP ACC".
 *
 * Usage:
 *   php test_bep_acc.php "C:\chemin\vers\RFC BEP ACC termine - Copie.pdf"
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\Referentiels\BepAccReferentielExtractor;

$filePath = $argv[1] ?? 'c:\DOSSIERS_STAGE_GONSE\COURS_ENS\FICHES\Mes referentiels\Admin Commerciale-Telecommunication\RFC BEP ACC terminé - Copie.pdf';

echo "=== Extraction BEP ACC ===\n";
echo "Fichier: {$filePath}\n";
echo 'Existe: ' . (is_file($filePath) ? 'OUI' : 'NON') . "\n\n";

if (!is_file($filePath)) {
    exit(1);
}

$moduleColumns = [
    'referentiel_id',
    'code',
    'parent_module',
    'title',
    'duration',
    'level',
    'teacher_profile',
    'pedagogical_approach',
    'assessment_type',
];

try {
    $extractor = new BepAccReferentielExtractor();
    $modules = $extractor->extractFromPdf($filePath);

    echo 'Modules extraits: ' . count($modules) . "\n\n";

    foreach ($modules as $index => $module) {
        echo 'Module #' . ($index + 1) . "\n";

        foreach ($moduleColumns as $column) {
            $value = $module[$column] ?? null;

            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            echo "- {$column}: " . (($value === null || $value === '') ? 'NULL' : $value) . "\n";
        }

        echo "\n";
    }
} catch (Throwable $e) {
    echo 'Erreur: ' . $e->getMessage() . "\n";
    echo 'Fichier: ' . $e->getFile() . "\n";
    echo 'Ligne: ' . $e->getLine() . "\n";
    exit(1);
}
