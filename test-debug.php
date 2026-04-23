<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Lire les modèles directement
$modelsFile = base_path('referentiel_test_models.php');
$models = [];

if (file_exists($modelsFile)) {
    $models = require $modelsFile;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Debug Modèles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4>Test Debug - Modèles de Référentiels</h4>
            </div>
            <div class="card-body">
                <h6>Informations de débogage:</h6>
                <ul>
                    <li><strong>Fichier modèles:</strong> <?php echo $modelsFile; ?></li>
                    <li><strong>Fichier existe:</strong> <?php echo file_exists($modelsFile) ? 'Oui' : 'Non'; ?></li>
                    <li><strong>Nombre de modèles:</strong> <?php echo count($models); ?></li>
                </ul>
                
                <?php if (!empty($models)): ?>
                    <h6>Modèles trouvés:</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nom</th>
                                    <th>Chemin</th>
                                    <th>Validé</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($models as $index => $model): ?>
                                    <tr>
                                        <td><?php echo $index; ?></td>
                                        <td><strong><?php echo htmlspecialchars($model['name']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($model['path']); ?></code></td>
                                        <td>
                                            <?php echo ($model['validated'] ?? false) ? 
                                                '<span class="badge bg-success">Oui</span>' : 
                                                '<span class="badge bg-secondary">Non</span>'; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="testModel(<?php echo $index; ?>)">
                                                <i class="fas fa-search"></i> Tester
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Aucun modèle trouvé dans le fichier.
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Test rapide:</h6>
                        <button class="btn btn-success" onclick="window.location.href='/referentiel-models'">
                            <i class="fas fa-list"></i> Voir les modèles (page normale)
                        </button>
                    </div>
                    <div class="col-md-6">
                        <h6>Explorer les fichiers:</h6>
                        <button class="btn btn-info" onclick="window.location.href='/file-explorer'">
                            <i class="fas fa-folder-open"></i> Explorateur de fichiers
                        </button>
                    </div>
                </div>
                
                <div id="testResults" style="display: none; margin-top: 20px;">
                    <h6>Résultats du test:</h6>
                    <div id="resultsContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <script>
    const models = <?php echo json_encode($models); ?>;
    
    function testModel(index) {
        const model = models[index];
        console.log('Test du modèle:', model);
        
        const resultsDiv = document.getElementById('testResults');
        const contentDiv = document.getElementById('resultsContent');
        
        contentDiv.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h6>Test du modèle: ${model.name}</h6>
                </div>
                <div class="card-body">
                    <p><strong>Chemin:</strong> <code>${model.path}</code></p>
                    <p><strong>Fichier existe:</strong> <span class="spinner-border spinner-border-sm"></span> Vérification...</p>
                    <div id="fileCheck"></div>
                </div>
            </div>
        `;
        
        resultsDiv.style.display = 'block';
        
        // Vérifier si le fichier existe
        fetch(`/debug/model/${index}`)
            .then(response => response.json())
            .then(data => {
                const fileCheckDiv = document.getElementById('fileCheck');
                
                if (data.error) {
                    fileCheckDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Erreur:</strong> ${data.error}
                        </div>
                    `;
                } else {
                    fileCheckDiv.innerHTML = `
                        <p><strong>Fichier existe:</strong> ${data.file_exists ? 'Oui' : 'Non'}</p>
                        <p><strong>Taille:</strong> ${data.file_size || 'N/A'}</p>
                        <p><strong>Type MIME:</strong> ${data.mime_type || 'N/A'}</p>
                        <p><strong>Extension:</strong> ${data.file_extension || 'N/A'}</p>
                        <button class="btn btn-warning" onclick="testExtraction(${index})">
                            <i class="fas fa-file-text"></i> Tester l'extraction
                        </button>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('fileCheck').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erreur de test:</strong> ${error.message}
                    </div>
                `;
            });
    }
    
    function testExtraction(index) {
        const contentDiv = document.getElementById('resultsContent');
        
        // Ajouter une section pour l'extraction
        const extractionDiv = document.createElement('div');
        extractionDiv.className = 'mt-3';
        extractionDiv.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h6>Test d'extraction</h6>
                </div>
                <div class="card-body">
                    <p><span class="spinner-border spinner-border-sm"></span> Extraction en cours...</p>
                    <div id="extractionResults"></div>
                </div>
            </div>
        `;
        
        contentDiv.appendChild(extractionDiv);
        
        fetch(`/debug/extraction/${index}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('extractionResults');
                
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Extraction réussie!</strong><br>
                            Texte extrait: ${data.text_length} caractères<br>
                            Temps: ${data.execution_time}<br>
                            Mémoire: ${data.memory_used}
                        </div>
                        <h6>Aperçu du texte:</h6>
                        <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto;">${data.text_preview}</pre>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Échec de l'extraction:</strong> ${data.error}<br>
                            <strong>Type:</strong> ${data.error_type}<br>
                            <strong>Fichier:</strong> ${data.file}:${data.line}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('extractionResults').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erreur lors du test:</strong> ${error.message}
                    </div>
                `;
            });
    }
    </script>
</body>
</html>
