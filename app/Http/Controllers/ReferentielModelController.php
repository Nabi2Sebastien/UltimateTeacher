<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ReferentielModelController extends Controller
{
    public function index()
    {
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }
        
        return view('referentiel-models.index', compact('models'));
    }
    
    public function show($modelKey)
    {
    public function validation()
    {
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }
        
        return view('referentiel-models.validation', compact('models'));
    }
    
        
    public function extractColumns($modelKey)
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
        $extractedData = $this->extractModelData($model);
        
        return response()->json([
            'model' => $model,
            'extracted_data' => $extractedData,
            'columns_found' => $extractedData['columns_found'] ?? [],
            'modules_count' => count($extractedData['modules'] ?? []),
        ]);
    }
    
    private function extractModelData($model)
    {
        try {
            $controller = new ReferentielController();
            
            $extractMethod = new \ReflectionMethod(ReferentielController::class, 'extractTextFromPdf');
            $extractMethod->setAccessible(true);
            $parseMethod = new \ReflectionMethod(ReferentielController::class, 'parseModulesFromText');
            $parseMethod->setAccessible(true);
            
            $filePath = $model['path'];
            
            if (!file_exists($filePath)) {
                return [
                    'error' => 'Fichier non trouvé',
                    'file_path' => $filePath,
                    'modules' => [],
                    'columns_found' => [],
                    'total_modules' => 0,
                ];
            }
            
            // Vérifier la taille du fichier pour éviter les timeouts
            $fileSize = filesize($filePath);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB max
                return [
                    'error' => 'Fichier trop volumineux (max 50MB)',
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'modules' => [],
                    'columns_found' => [],
                    'total_modules' => 0,
                ];
            }
            
            // Augmenter le timeout pour l'extraction
            $maxExecutionTime = ini_get('max_execution_time');
            set_time_limit(300); // 5 minutes
            
            $text = (string) $extractMethod->invoke($controller, $filePath);
            
            if (empty($text)) {
                return [
                    'error' => 'Texte vide extrait du fichier',
                    'file_path' => $filePath,
                    'modules' => [],
                    'columns_found' => [],
                    'total_modules' => 0,
                ];
            }
            
            $modules = (array) $parseMethod->invoke($controller, $text);
            
            // Restaurer le timeout original
            set_time_limit($maxExecutionTime);
            
            $moduleColumns = [
                'referentiel_id',
                'code',
                'title',
                'duration',
                'level',
                'teacher_profile',
                'pedagogical_approach',
                'assessment_type',
            ];
            
            $columnsFound = [];
            
            foreach ($modules as $module) {
                foreach ($moduleColumns as $column) {
                    if (isset($module[$column]) && $module[$column] !== null && $module[$column] !== '') {
                        $columnsFound[$column] = true;
                    }
                }
            }
            
            return [
                'modules' => $modules,
                'columns_found' => array_keys($columnsFound),
                'total_modules' => count($modules),
                'text_preview' => substr($text, 0, 1000) . (strlen($text) > 1000 ? '...' : ''),
            ];
            
        } catch (\Throwable $e) {
            // Restaurer le timeout original en cas d'erreur
            if (isset($maxExecutionTime)) {
                set_time_limit($maxExecutionTime);
            }
            
            \Log::error('Erreur lors de l\'extraction du modèle', [
                'model' => $model,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'error' => 'Erreur lors de l\'extraction: ' . $e->getMessage(),
                'file_path' => $model['path'] ?? 'inconnu',
                'modules' => [],
                'columns_found' => [],
                'total_modules' => 0,
                'error_type' => get_class($e),
            ];
        }
    }
    
    private function saveModels($models)
    {
        $modelsFile = base_path('referentiel_test_models.php');
        
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * Fichier de configuration des modèles de référentiels.\n";
        $content .= " * Généré automatiquement - ne pas éditer manuellement.\n";
        $content .= " */\n\n";
        $content .= "return " . var_export($models, true) . ";\n";
        
        file_put_contents($modelsFile, $content);
    }
}
