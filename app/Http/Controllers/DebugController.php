<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function testModel($modelKey)
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
        
        $debug = [
            'model_info' => $model,
            'file_exists' => file_exists($model['path']),
            'file_size' => file_exists($model['path']) ? filesize($model['path']) : 'N/A',
            'file_readable' => is_readable($model['path']),
            'memory_usage' => memory_get_usage(true),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
        
        if (file_exists($model['path'])) {
            $debug['file_extension'] = pathinfo($model['path'], PATHINFO_EXTENSION);
            $debug['mime_type'] = mime_content_type($model['path']);
        }
        
        return response()->json($debug);
    }
    
    public function testExtraction($modelKey)
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
            $controller = new ReferentielController();
            
            $extractMethod = new \ReflectionMethod(ReferentielController::class, 'extractTextFromPdf');
            $extractMethod->setAccessible(true);
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $text = (string) $extractMethod->invoke($controller, $model['path']);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            return response()->json([
                'success' => true,
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 500),
                'execution_time' => ($endTime - $startTime) . 's',
                'memory_used' => ($endMemory - $startMemory) . ' bytes',
                'peak_memory' => memory_get_peak_usage(true),
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
