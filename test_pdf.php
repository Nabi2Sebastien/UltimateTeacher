<?php

/**
 * Test d extraction des modules depuis des PDF, sans config obligatoire.
 *
 * 1) Placez des fichiers .pdf dans public/storage/referentiels/
 * 2) Lancez: php test_pdf.php
 *
 * Optionnel (sans code PHP) : public/storage/referentiels/fichiers_test.txt
 *    une ligne = un nom de fichier a tester (les lignes vides et #... sont ignorees)
 *
 * Avance : si referentiel_test_models.php retourne un tableau non vide,
 *    il remplace le scan automatique.
 */

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ReferentielController;

$referentielsDir = __DIR__ . '/public/storage/referentiels';
$modelsFile = __DIR__ . '/referentiel_test_models.php';

$referentielModels = [];

if (is_file($modelsFile)) {
    $referentielModels = require $modelsFile;
}

if (!is_array($referentielModels) || $referentielModels === []) {
    $referentielModels = buildReferentielModelsForTest($referentielsDir);
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

$controller = new ReferentielController();
$extractMethod = new ReflectionMethod(ReferentielController::class, 'extractTextFromPdf');
$extractMethod->setAccessible(true);
$parseMethod = new ReflectionMethod(ReferentielController::class, 'parseModulesFromText');
$parseMethod->setAccessible(true);

if ($referentielModels === []) {
    echo "Aucun PDF a tester.\n\n";
    echo "1) Deposez des fichiers .pdf dans:\n   {$referentielsDir}\n\n";
    echo "2) Optionnel: listez des noms de fichiers (un par ligne) dans\n";
    echo "   {$referentielsDir}/fichiers_test.txt\n\n";
    echo "3) Relancez: php test_pdf.php\n";
    exit(0);
}

foreach ($referentielModels as $model) {
    $files = [];

    if (!empty($model['path'])) {
        $files[] = $model['path'];
    }

    if (!empty($model['paths']) && is_array($model['paths'])) {
        foreach ($model['paths'] as $p) {
            if (is_string($p) && $p !== '') {
                $files[] = $p;
            }
        }
    }

    if (!empty($model['glob'])) {
        $files = array_merge($files, glob($model['glob']) ?: []);
    }

    if ($files === []) {
        echo "Modele: {$model['name']}\n";
        echo "Aucun fichier trouve.\n\n";
        continue;
    }

    foreach ($files as $filePath) {
        if (!is_file($filePath)) {
            echo "Modele: {$model['name']}\n";
            echo "Fichier introuvable (chemin: referentiel_test_models.php ou disque) :\n";
            echo $filePath . "\n\n";

            continue;
        }

        try {
            $text = (string) $extractMethod->invoke($controller, $filePath);
            $modules = (array) $parseMethod->invoke($controller, $text);

            echo "Modele: {$model['name']}\n";
            echo 'Fichier: ' . basename($filePath) . "\n";
            echo 'Modules extraits: ' . count($modules) . "\n";
            echo 'Colonnes table module: ' . implode(', ', $moduleColumns) . "\n\n";

            if (($model['validated'] ?? false) && !empty($model['code_comment'])) {
                echo "Modele valide -> commentaire de code associe:\n";
                echo $model['code_comment'] . "\n\n";
            }

            foreach ($modules as $index => $module) {
                echo 'Module #' . ($index + 1) . "\n";

                foreach ($moduleColumns as $column) {
                    $value = $module[$column] ?? null;

                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }

                    if ($value === null || $value === '') {
                        $value = 'NULL';
                    }

                    echo "- {$column}: {$value}\n";
                }

                echo "\n";
            }
        } catch (Throwable $e) {
            echo "Modele: {$model['name']}\n";
            echo 'Fichier: ' . basename($filePath) . "\n";
            echo 'Erreur: ' . $e->getMessage() . "\n\n";
        }
    }
}

/**
 * @return array<int, array{name: string, path: string, validated?: bool, code_comment?: string|null}>
 */
function buildReferentielModelsForTest(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $listFile = $dir . DIRECTORY_SEPARATOR . 'fichiers_test.txt';
    $models = [];

    if (is_file($listFile)) {
        $lines = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $line;

            if (!is_file($path)) {
                echo "[Attention] ignore (introuvable): {$line}\n";

                continue;
            }

            $models[] = [
                'name' => $line,
                'path' => $path,
                'validated' => false,
            ];
        }

        if ($models !== []) {
            echo "--- Test via " . basename($listFile) . " (" . count($models) . " fichier(s)) ---\n\n";
        }

        return $models;
    }

    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.pdf', GLOB_NOSORT) ?: [] as $path) {
        $models[] = [
            'name' => basename($path),
            'path' => $path,
            'validated' => false,
        ];
    }

    if ($models !== []) {
        echo '--- Tous les PDF du dossier (' . count($models) . " fichier(s)) ---\n\n";
    }

    return $models;
}
