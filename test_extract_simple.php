<?php
// Script d'extraction simplifié avec sortie vers fichier
ob_start();

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ReferentielController;

$filePath = 'c:\laragon\www\UltimateTeacher\public\storage\referentiels\1776989177_RFC BEP ACC terminé - Copie.pdf';

echo "=== Test d'extraction ===\n";
echo "Fichier: $filePath\n";
echo "Existe: " . (file_exists($filePath) ? "OUI" : "NON") . "\n";

if (!file_exists($filePath)) {
    $output = ob_get_clean();
    file_put_contents(__DIR__ . '/test_output.txt', $output);
    echo $output;
    exit(1);
}

try {
    $controller = new ReferentielController();
    
    $extractMethod = new ReflectionMethod(ReferentielController::class, 'extractTextFromPdf');
    $extractMethod->setAccessible(true);
    
    $parseMethod = new ReflectionMethod(ReferentielController::class, 'parseModulesFromText');
    $parseMethod->setAccessible(true);
    
    echo "Extraction du texte...\n";
    $text = (string) $extractMethod->invoke($controller, $filePath);
    
    echo "Texte extrait: " . strlen($text) . " caracteres\n\n";
    
    echo "Parsing des modules...\n";
    $modules = (array) $parseMethod->invoke($controller, $text);
    
    echo "Modules trouves: " . count($modules) . "\n\n";
    
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
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
}

$output = ob_get_clean();
file_put_contents(__DIR__ . '/test_output.txt', $output);
echo $output;