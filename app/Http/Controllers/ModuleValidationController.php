<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ModuleValidationController extends Controller
{
    /**
     * Valide et corrige les colonnes extraites d'un modèle
     */
    public function validateColumns(Request $request)
    {
        $request->validate([
            'model_key' => 'required|integer',
            'corrections' => 'required|array',
            'corrections.*.column' => 'required|string',
            'corrections.*.action' => 'required|in:keep,remove,modify',
            'corrections.*.new_value' => 'nullable|string',
        ]);

        $modelKey = $request->model_key;
        $corrections = $request->corrections;

        // Charger le modèle
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }

        if (!isset($models[$modelKey])) {
            return response()->json(['error' => 'Modèle non trouvé'], 404);
        }

        $model = $models[$modelKey];
        
        // Appliquer les corrections
        $validationReport = $this->applyCorrections($model, $corrections);

        // Mettre à jour le modèle si validé
        if ($request->boolean('validate_model')) {
            $models[$modelKey]['validated'] = true;
            $models[$modelKey]['validation_date'] = now()->toISOString();
            $models[$modelKey]['validation_report'] = $validationReport;
            
            $this->saveModels($models);
        }

        return response()->json([
            'success' => true,
            'validation_report' => $validationReport,
            'model_validated' => $request->boolean('validate_model'),
        ]);
    }

    /**
     * Compare les colonnes entre plusieurs modèles
     */
    public function compareModels(Request $request)
    {
        $request->validate([
            'model_keys' => 'required|array|min:2',
            'model_keys.*' => 'integer',
        ]);

        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }

        $comparison = [];
        $allColumns = ['referentiel_id', 'code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];

        foreach ($request->model_keys as $key) {
            if (!isset($models[$key])) {
                continue;
            }

            $model = $models[$key];
            $extractedData = $this->extractModelData($model);
            
            $comparison[$key] = [
                'name' => $model['name'],
                'columns_found' => $extractedData['columns_found'] ?? [],
                'modules_count' => $extractedData['total_modules'] ?? 0,
                'validated' => $model['validated'] ?? false,
            ];
        }

        // Analyser les différences
        $columnAnalysis = [];
        foreach ($allColumns as $column) {
            $columnAnalysis[$column] = [
                'found_in_models' => [],
                'missing_in_models' => [],
                'consistency' => true,
            ];

            $hasColumn = false;
            foreach ($comparison as $key => $data) {
                if (in_array($column, $data['columns_found'])) {
                    $columnAnalysis[$column]['found_in_models'][] = $data['name'];
                    $hasColumn = true;
                } else {
                    $columnAnalysis[$column]['missing_in_models'][] = $data['name'];
                }
            }

            $columnAnalysis[$column]['consistency'] = count($columnAnalysis[$column]['found_in_models']) === count($comparison);
        }

        return response()->json([
            'comparison' => $comparison,
            'column_analysis' => $columnAnalysis,
            'summary' => [
                'total_models' => count($comparison),
                'validated_models' => count(array_filter($comparison, fn($m) => $m['validated'])),
                'consistent_columns' => count(array_filter($columnAnalysis, fn($c) => $c['consistency'])),
            ],
        ]);
    }

    /**
     * Génère un rapport de validation pour un modèle
     */
    public function generateValidationReport($modelKey)
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

        $report = [
            'model_info' => [
                'name' => $model['name'],
                'path' => $model['path'],
                'validated' => $model['validated'] ?? false,
                'validation_date' => $model['validation_date'] ?? null,
            ],
            'extraction_stats' => [
                'total_modules' => $extractedData['total_modules'] ?? 0,
                'columns_found' => $extractedData['columns_found'] ?? [],
                'missing_columns' => array_diff([
                    'referentiel_id', 'code', 'title', 'duration', 'level', 
                    'teacher_profile', 'pedagogical_approach', 'assessment_type'
                ], $extractedData['columns_found'] ?? []),
            ],
            'quality_score' => $this->calculateQualityScore($extractedData),
            'recommendations' => $this->generateRecommendations($extractedData),
        ];

        return response()->json($report);
    }

    private function applyCorrections($model, $corrections)
    {
        $report = [
            'corrections_applied' => [],
            'issues_fixed' => 0,
        ];

        foreach ($corrections as $correction) {
            $column = $correction['column'];
            $action = $correction['action'];
            $newValue = $correction['new_value'] ?? null;

            switch ($action) {
                case 'keep':
                    $report['corrections_applied'][] = "Colonne '{$column}' conservée telle quelle";
                    break;
                    
                case 'remove':
                    $report['corrections_applied'][] = "Colonne '{$column}' marquée pour suppression";
                    $report['issues_fixed']++;
                    break;
                    
                case 'modify':
                    $report['corrections_applied'][] = "Colonne '{$column}' modifiée avec: '{$newValue}'";
                    $report['issues_fixed']++;
                    break;
            }
        }

        return $report;
    }

    private function extractModelData($model)
    {
        $controller = new ReferentielController();
        
        try {
            $extractMethod = new \ReflectionMethod(ReferentielController::class, 'extractTextFromPdf');
            $extractMethod->setAccessible(true);
            $parseMethod = new \ReflectionMethod(ReferentielController::class, 'parseModulesFromText');
            $parseMethod->setAccessible(true);
            
            $filePath = $model['path'];
            
            if (!file_exists($filePath)) {
                return [
                    'error' => 'Fichier non trouvé',
                    'file_path' => $filePath,
                ];
            }
            
            $text = (string) $extractMethod->invoke($controller, $filePath);
            $modules = (array) $parseMethod->invoke($controller, $text);
            
            $moduleColumns = [
                'referentiel_id', 'code', 'title', 'duration', 'level',
                'teacher_profile', 'pedagogical_approach', 'assessment_type',
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
            ];
            
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'modules' => [],
                'columns_found' => [],
            ];
        }
    }

    private function calculateQualityScore($extractedData)
    {
        if (isset($extractedData['error'])) {
            return 0;
        }

        $score = 0;
        $maxScore = 100;

        // Points pour les modules extraits (max 40 points)
        $moduleCount = $extractedData['total_modules'] ?? 0;
        $score += min(40, $moduleCount * 4); // 4 points par module, max 40

        // Points pour les colonnes trouvées (max 60 points)
        $columnsFound = $extractedData['columns_found'] ?? [];
        $essentialColumns = ['title', 'code', 'duration'];
        $optionalColumns = ['level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];

        // Colonnes essentielles (30 points)
        foreach ($essentialColumns as $column) {
            if (in_array($column, $columnsFound)) {
                $score += 10;
            }
        }

        // Colonnes optionnelles (30 points)
        foreach ($optionalColumns as $column) {
            if (in_array($column, $columnsFound)) {
                $score += 7.5;
            }
        }

        return round($score, 1);
    }

    private function generateRecommendations($extractedData)
    {
        $recommendations = [];

        if (isset($extractedData['error'])) {
            $recommendations[] = "Corriger l'erreur d'extraction: " . $extractedData['error'];
            return $recommendations;
        }

        $columnsFound = $extractedData['columns_found'] ?? [];
        $moduleCount = $extractedData['total_modules'] ?? 0;

        if ($moduleCount === 0) {
            $recommendations[] = "Aucun module n'a été extrait. Vérifiez le format du document.";
        } elseif ($moduleCount < 3) {
            $recommendations[] = "Peu de modules extraits ({$moduleCount}). Le document pourrait contenir plus d'informations.";
        }

        $essentialColumns = ['title', 'code', 'duration'];
        foreach ($essentialColumns as $column) {
            if (!in_array($column, $columnsFound)) {
                $recommendations[] = "La colonne '{$column}' est manquante mais est essentielle.";
            }
        }

        $optionalColumns = ['level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
        $missingOptional = array_diff($optionalColumns, $columnsFound);
        if (!empty($missingOptional)) {
            $recommendations[] = "Colonnes optionnelles manquantes: " . implode(', ', $missingOptional);
        }

        if (empty($recommendations)) {
            $recommendations[] = "L'extraction semble complète et de bonne qualité.";
        }

        return $recommendations;
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
