<!DOCTYPE html>
<html>
<head>
    <title>Test Debug Modèles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-bug"></i> Test Debug - Modèles de Référentiels</h4>
            </div>
            <div class="card-body">
                <h6>Informations de débogage:</h6>
                <ul>
                    <li><strong>Fichier modèles:</strong> {{ base_path('referentiel_test_models.php') }}</li>
                    <li><strong>Fichier existe:</strong> {{ file_exists(base_path('referentiel_test_models.php')) ? 'Oui' : 'Non' }}</li>
                    <li><strong>Nombre de modèles:</strong> {{ count($models) }}</li>
                </ul>
                
                @if (!empty($models))
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
                                @foreach ($models as $index => $model)
                                    <tr>
                                        <td>{{ $index }}</td>
                                        <td><strong>{{ $model['name'] }}</strong></td>
                                        <td><code>{{ $model['path'] }}</code></td>
                                        <td>
                                            @if ($model['validated'] ?? false)
                                                <span class="badge bg-success">Oui</span>
                                            @else
                                                <span class="badge bg-secondary">Non</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="testModel({{ $index }})">
                                                <i class="fas fa-search"></i> Tester
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="diagnoseExtraction({{ $index }})">
                                                <i class="fas fa-stethoscope"></i> Diagnostic
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Aucun modèle trouvé dans le fichier.
                    </div>
                @endif
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Test rapide:</h6>
                        <a href="/referentiel-models" class="btn btn-success">
                            <i class="fas fa-list"></i> Voir les modèles (page normale)
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h6>Explorer les fichiers:</h6>
                        <a href="/file-explorer" class="btn btn-info">
                            <i class="fas fa-folder-open"></i> Explorateur de fichiers
                        </a>
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
    <script>
    const models = @json($models);
    
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
                    <h6><i class="fas fa-list"></i> Liste des modules extraits</h6>
                </div>
                <div class="card-body">
                    <p><span class="spinner-border spinner-border-sm"></span> Extraction des modules...</p>
                    <div id="extractionResults"></div>
                </div>
            </div>
        `;
        
        contentDiv.appendChild(extractionDiv);
        
        // Utiliser l'endpoint extractColumns qui retourne seulement les modules
        fetch(`/referentiel-models/${index}/extract-columns`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('extractionResults');
                
                if (data.error) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Erreur:</strong> ${data.error}
                        </div>
                    `;
                    return;
                }
                
                const modules = data.modules || [];
                const modulesCount = data.modules_count || 0;
                
                if (modulesCount === 0) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Aucun module trouvé dans le fichier.
                        </div>
                    `;
                } else {
                    // Analyser quelles colonnes ont des données
                    const allColumns = ['code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
                    const columnLabels = {
                        'code': 'Code',
                        'title': 'Titre',
                        'duration': 'Durée',
                        'level': 'Niveau',
                        'teacher_profile': 'Profil enseignant',
                        'pedagogical_approach': 'Approche pédagogique',
                        'assessment_type': 'Type d\'évaluation'
                    };
                    
                    // Trouver les colonnes qui ont des données
                    const columnsWithData = allColumns.filter(column => {
                        return modules.some(module => module[column] && module[column] !== '' && module[column] !== null);
                    });
                    
                    let html = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>${modulesCount} module(s) trouvé(s)</strong>
                            <br><small>Colonnes avec données: ${columnsWithData.map(col => columnLabels[col]).join(', ')}</small>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                    `;
                    
                    // Ajouter seulement les colonnes qui ont des données
                    columnsWithData.forEach(column => {
                        html += `<th>${columnLabels[column]}</th>`;
                    });
                    
                    html += `
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    modules.forEach((module, index) => {
                        html += `<tr><td><strong>${index + 1}</strong></td>`;
                        
                        // Ajouter seulement les données des colonnes qui existent
                        columnsWithData.forEach(column => {
                            const value = module[column];
                            if (column === 'title') {
                                html += `<td><strong>${value || '<em>Sans titre</em>'}</strong></td>`;
                            } else if (value && value !== '' && value !== null) {
                                html += `<td>${value}</td>`;
                            } else {
                                html += `<td><em>N/A</em></td>`;
                            }
                        });
                        
                        html += `</tr>`;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    resultsDiv.innerHTML = html;
                }
            })
            .catch(error => {
                document.getElementById('extractionResults').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erreur lors de l'extraction:</strong> ${error.message}
                    </div>
                `;
            });
    }
    
    function diagnoseExtraction(index) {
        const contentDiv = document.getElementById('resultsContent');
        
        // Ajouter une section pour le diagnostic
        const diagnosticDiv = document.createElement('div');
        diagnosticDiv.className = 'mt-3';
        diagnosticDiv.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-stethoscope"></i> Diagnostic complet de l'extraction</h6>
                </div>
                <div class="card-body">
                    <p><span class="spinner-border spinner-border-sm"></span> Diagnostic en cours...</p>
                    <div id="diagnosticResults"></div>
                </div>
            </div>
        `;
        
        contentDiv.appendChild(diagnosticDiv);
        
        fetch(`/extraction-diagnostic/${index}`)
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('diagnosticResults');
                
                let html = '';
                
                if (data.success) {
                    html += `
                        <div class="alert alert-success">
                            <strong>Diagnostic terminé avec succès!</strong>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Résumé:</h6>
                                <table class="table table-sm">
                                    <tr><td>Taille fichier:</td><td>${data.summary.file_size} bytes</td></tr>
                                    <tr><td>Temps extraction:</td><td>${data.summary.extraction_time}s</td></tr>
                                    <tr><td>Longueur texte:</td><td>${data.summary.text_length} caractères</td></tr>
                                    <tr><td>Temps parsing:</td><td>${data.summary.parse_time}s</td></tr>
                                    <tr><td>Modules trouvés:</td><td>${data.summary.module_count}</td></tr>
                                    <tr><td>Temps total:</td><td>${data.summary.total_time}s</td></tr>
                                </table>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="alert alert-danger">
                            <strong>Échec du diagnostic:</strong> ${data.error}
                        </div>
                    `;
                }
                
                html += '<h6>Étapes du diagnostic:</h6>';
                
                data.steps.forEach(step => {
                    const badgeClass = step.status === 'success' ? 'bg-success' : 
                                     step.status === 'error' ? 'bg-danger' : 
                                     step.status === 'warning' ? 'bg-warning' : 'bg-primary';
                    
                    html += `
                        <div class="card mb-2">
                            <div class="card-header py-2">
                                <span class="badge ${badgeClass}">${step.status}</span>
                                <strong>${step.step}</strong>
                            </div>
                            <div class="card-body py-2">
                                <p class="mb-1">${step.message}</p>
                                ${step.details ? `
                                    <details>
                                        <summary>Détails</summary>
                                        <pre class="bg-light p-2 mt-2" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(step.details, null, 2)}</pre>
                                    </details>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                
                resultsDiv.innerHTML = html;
            })
            .catch(error => {
                document.getElementById('diagnosticResults').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erreur lors du diagnostic:</strong> ${error.message}
                    </div>
                `;
            });
    }
    </script>
</body>
</html>
