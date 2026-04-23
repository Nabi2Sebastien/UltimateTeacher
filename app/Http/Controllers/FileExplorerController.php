<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileExplorerController extends Controller
{
    public function index(Request $request)
    {
        $path = $request->get('path', 'C:\\');
        $path = rtrim($path, '\\/');
        
        // Sécurité : vérifier que le chemin est valide
        if (!$this->isValidPath($path)) {
            $path = 'C:\\';
        }
        
        try {
            $directories = [];
            $files = [];
            
            if (is_dir($path)) {
                $items = scandir($path);
                
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    
                    $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                    
                    if (is_dir($fullPath)) {
                        $directories[] = [
                            'name' => $item,
                            'path' => $fullPath,
                            'type' => 'directory',
                            'size' => '-',
                            'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                        ];
                    } elseif (is_file($fullPath)) {
                        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                        
                        // Filtrer pour les fichiers PDF et Word
                        if (in_array($extension, ['pdf', 'doc', 'docx'])) {
                            $files[] = [
                                'name' => $item,
                                'path' => $fullPath,
                                'type' => $extension,
                                'size' => $this->formatFileSize(filesize($fullPath)),
                                'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                            ];
                        }
                    }
                }
                
                // Trier par nom
                usort($directories, fn($a, $b) => strcasecmp($a['name'], $b['name']));
                usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            }
            
            return view('file-explorer.index', [
                'currentPath' => $path,
                'parentPath' => dirname($path),
                'directories' => $directories,
                'files' => $files,
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'accès au répertoire: ' . $e->getMessage());
        }
    }
    
    public function createModel(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string',
            'description' => 'nullable|string',
        ]);
        
        $modelsFile = base_path('referentiel_test_models.php');
        $models = [];
        
        if (file_exists($modelsFile)) {
            $models = require $modelsFile;
        }
        
        $newModel = [
            'name' => $request->name,
            'path' => $request->path,
            'validated' => false,
            'description' => $request->description,
            'created_at' => now()->toISOString(),
        ];
        
        $models[] = $newModel;
        
        $this->saveModels($models);
        
        return redirect()->route('referentiel-models.index')
            ->with('success', 'Modèle créé avec succès depuis l\'explorateur de fichiers');
    }
    
    private function isValidPath($path)
    {
        // Sécurité de base : éviter les chemins relatifs et certains caractères dangereux
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Autoriser les lecteurs Windows et chemins absolus
        if (preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return true;
        }
        
        // Éviter les chemins comme ../../../etc/passwd
        if (strpos($path, '..') !== false) {
            return false;
        }
        
        return is_dir($path);
    }
    
    private function formatFileSize($bytes)
    {
        if ($bytes === false) {
            return 'Inconnu';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
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
