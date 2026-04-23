<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtractionDiagnosticController extends Controller
{
    public function diagnose($modelKey)
    {
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }
        
        if (!isset($models[$modelKey])) {
            return response()->json(['error' => 'Modèle non trouvé'], 404);
        }
        
        $model = $models[$modelKey];
        
        $diagnostic = [
            'model' => $model,
            'steps' => [],
            'success' => false,
            'error' => null,
        ];
        
        try {
            // Étape 1: Vérification du fichier
            $diagnostic['steps'][] = [
                'step' => 'Vérification du fichier',
                'status' => 'processing',
                'message' => 'Vérification de l\'existence du fichier...',
            ];
            
            if (!file_exists($model['path'])) {
                $diagnostic['steps'][] = [
                    'step' => 'Vérification du fichier',
                    'status' => 'error',
                    'message' => 'Fichier non trouvé: ' . $model['path'],
                ];
                return response()->json($diagnostic);
            }
            
            $fileSize = filesize($model['path']);
            $diagnostic['steps'][] = [
                'step' => 'Vérification du fichier',
                'status' => 'success',
                'message' => "Fichier trouvé, taille: {$fileSize} bytes",
                'details' => [
                    'path' => $model['path'],
                    'size' => $fileSize,
                    'readable' => is_readable($model['path']),
                    'extension' => pathinfo($model['path'], PATHINFO_EXTENSION),
                ]
            ];
            
            // Étape 2: Test de la classe PDF Parser
            $diagnostic['steps'][] = [
                'step' => 'Test PDF Parser',
                'status' => 'processing',
                'message' => 'Test de la classe Smalot\PdfParser\Parser...',
            ];
            
            if (!class_exists('Smalot\PdfParser\Parser')) {
                $diagnostic['steps'][] = [
                    'step' => 'Test PDF Parser',
                    'status' => 'error',
                    'message' => 'Classe Smalot\PdfParser\Parser non trouvée',
                ];
                return response()->json($diagnostic);
            }
            
            $diagnostic['steps'][] = [
                'step' => 'Test PDF Parser',
                'status' => 'success',
                'message' => 'Classe PDF Parser disponible',
            ];
            
            // Étape 3: Tentative d'extraction du texte
            $diagnostic['steps'][] = [
                'step' => 'Extraction du texte',
                'status' => 'processing',
                'message' => 'Tentative d\'extraction du texte du PDF...',
            ];
            
            $startTime = microtime(true);
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($model['path']);
            $text = $pdf->getText();
            $endTime = microtime(true);
            
            $extractionTime = ($endTime - $startTime);
            $textLength = strlen($text);
            
            if ($textLength === 0) {
                $diagnostic['steps'][] = [
                    'step' => 'Extraction du texte',
                    'status' => 'warning',
                    'message' => 'Texte extrait vide',
                    'details' => [
                        'extraction_time' => $extractionTime . 's',
                        'text_length' => 0,
                    ]
                ];
            } else {
                $diagnostic['steps'][] = [
                    'step' => 'Extraction du texte',
                    'status' => 'success',
                    'message' => "Texte extrait avec succès: {$textLength} caractères en {$extractionTime}s",
                    'details' => [
                        'extraction_time' => $extractionTime . 's',
                        'text_length' => $textLength,
                        'preview' => substr($text, 0, 500),
                    ]
                ];
            }
            
            // Étape 4: Test du parsing des modules
            $diagnostic['steps'][] = [
                'step' => 'Parsing des modules',
                'status' => 'processing',
                'message' => 'Tentative de parsing des modules depuis le texte...',
            ];
            
            $controller = new \App\Http\Controllers\ReferentielController();
            $parseMethod = new \ReflectionMethod(\App\Http\Controllers\ReferentielController::class, 'parseModulesFromText');
            $parseMethod->setAccessible(true);
            
            $parseStartTime = microtime(true);
            $modules = $parseMethod->invoke($controller, $text);
            $parseEndTime = microtime(true);
            
            $parseTime = ($parseEndTime - $parseStartTime);
            $moduleCount = count($modules);
            
            if ($moduleCount === 0) {
                $diagnostic['steps'][] = [
                    'step' => 'Parsing des modules',
                    'status' => 'warning',
                    'message' => 'Aucun module trouvé dans le texte',
                    'details' => [
                        'parse_time' => $parseTime . 's',
                        'module_count' => 0,
                        'text_sample' => substr($text, 0, 1000),
                    ]
                ];
            } else {
                $diagnostic['steps'][] = [
                    'step' => 'Parsing des modules',
                    'status' => 'success',
                    'message' => "{$moduleCount} modules trouvés en {$parseTime}s",
                    'details' => [
                        'parse_time' => $parseTime . 's',
                        'module_count' => $moduleCount,
                        'first_module' => $modules[0] ?? null,
                    ]
                ];
            }
            
            $diagnostic['success'] = true;
            $diagnostic['summary'] = [
                'file_size' => $fileSize,
                'extraction_time' => $extractionTime,
                'text_length' => $textLength,
                'parse_time' => $parseTime,
                'module_count' => $moduleCount,
                'total_time' => ($endTime - $startTime) + $parseTime,
            ];
            
        } catch (\Throwable $e) {
            $diagnostic['steps'][] = [
                'step' => 'Erreur générale',
                'status' => 'error',
                'message' => $e->getMessage(),
                'details' => [
                    'error_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            ];
            
            $diagnostic['error'] = $e->getMessage();
        }
        
        return response()->json($diagnostic);
    }
    
    public function testTextExtraction($modelKey)
    {
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }
        
        if (!isset($models[$modelKey])) {
            return response()->json(['error' => 'Modèle non trouvé'], 404);
        }
        
        $model = $models[$modelKey];
        
        try {
            // Test simple d'extraction
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($model['path']);
            $text = $pdf->getText();
            
            return response()->json([
                'success' => true,
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 2000),
                'full_text' => $text, // Pour debug
            ]);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
