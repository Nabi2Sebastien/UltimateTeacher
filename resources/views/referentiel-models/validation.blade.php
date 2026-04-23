@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-check-circle"></i> Validation et Comparaison de Modèles</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="validationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="validation-tab" data-bs-toggle="tab" data-bs-target="#validation" type="button" role="tab">
                                <i class="fas fa-check"></i> Validation
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comparison-tab" data-bs-toggle="tab" data-bs-target="#comparison" type="button" role="tab">
                                <i class="fas fa-balance-scale"></i> Comparaison
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                <i class="fas fa-chart-bar"></i> Rapports
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="validationTabsContent">
                        <!-- Onglet de Validation -->
                        <div class="tab-pane fade show active" id="validation" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h5>Sélectionner un modèle à valider</h5>
                                    <select id="modelSelect" class="form-select">
                                        <option value="">Choisir un modèle...</option>
                                    </select>
                                    
                                    <div class="mt-3">
                                        <button id="loadModelData" class="btn btn-primary" disabled>
                                            <i class="fas fa-upload"></i> Charger les données
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div id="modelInfo" class="alert alert-info" style="display: none;">
                                        <h6>Informations du modèle</h6>
                                        <div id="modelInfoContent"></div>
                                    </div>
                                </div>
                            </div>

                            <div id="validationPanel" style="display: none;">
                                <hr>
                                <h5>Validation des colonnes</h5>
                                <div id="columnsValidation"></div>
                                
                                <div class="mt-4">
                                    <button id="validateModel" class="btn btn-success">
                                        <i class="fas fa-check"></i> Valider le modèle
                                    </button>
                                    <button id="generateReport" class="btn btn-info">
                                        <i class="fas fa-file-alt"></i> Générer un rapport
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet de Comparaison -->
                        <div class="tab-pane fade" id="comparison" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5>Sélectionner les modèles à comparer</h5>
                                    <div id="modelCheckboxes"></div>
                                    
                                    <div class="mt-3">
                                        <button id="compareModels" class="btn btn-primary" disabled>
                                            <i class="fas fa-balance-scale"></i> Comparer les modèles
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="comparisonResults" style="display: none;">
                                <hr>
                                <h5>Résultats de la comparaison</h5>
                                <div id="comparisonContent"></div>
                            </div>
                        </div>

                        <!-- Onglet de Rapports -->
                        <div class="tab-pane fade" id="reports" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5>Rapports de validation</h5>
                                    <div id="reportsList"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour le rapport -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rapport de validation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reportContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let models = [];
let currentModelData = null;

// Charger la liste des modèles au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadModels();
});

function loadModels() {
    fetch('/referentiel-models')
        .then(response => response.json())
        .then(data => {
            models = data.models || [];
            populateModelSelect();
            populateModelCheckboxes();
            loadReports();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des modèles:', error);
        });
}

function populateModelSelect() {
    const select = document.getElementById('modelSelect');
    select.innerHTML = '<option value="">Choisir un modèle...</option>';
    
    models.forEach((model, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = `${model.name} (${model.validated ? 'Validé' : 'Non validé'})`;
        select.appendChild(option);
    });
}

function populateModelCheckboxes() {
    const container = document.getElementById('modelCheckboxes');
    container.innerHTML = '';
    
    models.forEach((model, index) => {
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" value="${index}" id="model${index}">
            <label class="form-check-label" for="model${index}">
                ${model.name} (${model.validated ? 'Validé' : 'Non validé'})
            </label>
        `;
        container.appendChild(div);
    });
    
    // Activer/désactiver le bouton de comparaison
    container.addEventListener('change', function() {
        const checked = container.querySelectorAll('input[type="checkbox"]:checked');
        document.getElementById('compareModels').disabled = checked.length < 2;
    });
}

document.getElementById('modelSelect').addEventListener('change', function() {
    const loadBtn = document.getElementById('loadModelData');
    loadBtn.disabled = this.value === '';
});

document.getElementById('loadModelData').addEventListener('click', function() {
    const modelKey = document.getElementById('modelSelect').value;
    if (!modelKey) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Chargement...';
    
    fetch(`/referentiel-models/${modelKey}/extract-columns`)
        .then(response => response.json())
        .then(data => {
            currentModelData = data;
            displayModelInfo(data);
            displayValidationPanel(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-upload"></i> Charger les données';
        });
});

function displayModelInfo(data) {
    const infoDiv = document.getElementById('modelInfo');
    const content = document.getElementById('modelInfoContent');
    
    content.innerHTML = `
        <p><strong>Nom:</strong> ${data.model.name}</p>
        <p><strong>Fichier:</strong> ${data.model.path}</p>
        <p><strong>Modules:</strong> ${data.modules_count}</p>
        <p><strong>Validé:</strong> ${data.model.validated ? 'Oui' : 'Non'}</p>
    `;
    
    infoDiv.style.display = 'block';
}

function displayValidationPanel(data) {
    const panel = document.getElementById('validationPanel');
    const container = document.getElementById('columnsValidation');
    
    const allColumns = ['referentiel_id', 'code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
    
    container.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Colonnes détectées</h6>
                <div class="row">
    `;
    
    allColumns.forEach(column => {
        const found = data.columns_found.includes(column);
        const badgeClass = found ? 'bg-success' : 'bg-secondary';
        const icon = found ? 'fas fa-check' : 'fas fa-times';
        
        container.innerHTML += `
            <div class="col-6 mb-2">
                <div class="card card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${column}</span>
                        <span class="badge ${badgeClass}">
                            <i class="${icon}"></i>
                        </span>
                    </div>
                    <div class="mt-2">
                        <select class="form-select form-select-sm" data-column="${column}">
                            <option value="keep" ${found ? 'selected' : ''}>Conserver</option>
                            <option value="remove" ${!found ? 'selected' : ''}>Supprimer</option>
                            <option value="modify">Modifier</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML += `
                </div>
            </div>
            <div class="col-md-6">
                <h6>Aperçu des modules</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Titre</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    if (data.extracted_data && data.extracted_data.modules) {
        data.extracted_data.modules.slice(0, 5).forEach((module, index) => {
            container.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${module.code || '-'}</td>
                    <td>${module.title || '-'}</td>
                    <td>${module.duration || '-'}</td>
                </tr>
            `;
        });
        
        if (data.extracted_data.modules.length > 5) {
            container.innerHTML += `
                <tr>
                    <td colspan="4" class="text-center">
                        <em>... et ${data.extracted_data.modules.length - 5} autres</em>
                    </td>
                </tr>
            `;
        }
    }
    
    container.innerHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    panel.style.display = 'block';
}

document.getElementById('validateModel').addEventListener('click', function() {
    if (!currentModelData) return;
    
    const modelKey = document.getElementById('modelSelect').value;
    const corrections = [];
    
    // Collecter les corrections
    document.querySelectorAll('#columnsValidation select').forEach(select => {
        const column = select.dataset.column;
        const action = select.value;
        
        corrections.push({
            column: column,
            action: action,
            new_value: action === 'modify' ? prompt(`Nouvelle valeur pour ${column}:`) : null
        });
    });
    
    fetch('/module-validation/validate-columns', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            model_key: modelKey,
            corrections: corrections,
            validate_model: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Modèle validé avec succès!');
            loadModels(); // Recharger la liste
        } else {
            alert('Erreur lors de la validation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
});

document.getElementById('compareModels').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('#modelCheckboxes input[type="checkbox"]:checked');
    const modelKeys = Array.from(checkboxes).map(cb => cb.value);
    
    if (modelKeys.length < 2) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Comparaison...';
    
    fetch('/module-validation/compare-models', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            model_keys: modelKeys
        })
    })
    .then(response => response.json())
    .then(data => {
        displayComparisonResults(data);
    })
    .catch(error => {
        console.error('Erreur:', error);
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-balance-scale"></i> Comparer les modèles';
    });
});

function displayComparisonResults(data) {
    const resultsDiv = document.getElementById('comparisonResults');
    const content = document.getElementById('comparisonContent');
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Statistiques</h6>
                        <p><strong>Modèles comparés:</strong> ${data.summary.total_models}</p>
                        <p><strong>Modèles validés:</strong> ${data.summary.validated_models}</p>
                        <p><strong>Colonnes cohérentes:</strong> ${data.summary.consistent_columns}/8</p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h6>Analyse des colonnes</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Colonne</th>
                                <th>Présence</th>
                                <th>Cohérence</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    Object.entries(data.column_analysis).forEach(([column, analysis]) => {
        const consistencyBadge = analysis.consistency ? 
            '<span class="badge bg-success">Cohérent</span>' : 
            '<span class="badge bg-warning">Incohérent</span>';
        
        content.innerHTML += `
            <tr>
                <td>${column}</td>
                <td>
                    <small>${analysis.found_in_models.join(', ') || 'Aucun'}</small>
                </td>
                <td>${consistencyBadge}</td>
            </tr>
        `;
    });
    
    content.innerHTML += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    resultsDiv.style.display = 'block';
}

function loadReports() {
    // Charger les rapports existants
    const reportsList = document.getElementById('reportsList');
    reportsList.innerHTML = '<p>Sélectionnez un modèle pour générer un rapport...</p>';
}

document.getElementById('generateReport').addEventListener('click', function() {
    const modelKey = document.getElementById('modelSelect').value;
    if (!modelKey) return;
    
    fetch(`/module-validation/${modelKey}/report`)
        .then(response => response.json())
        .then(data => {
            displayReport(data);
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
});

function displayReport(data) {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    const content = document.getElementById('reportContent');
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informations du modèle</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nom:</strong></td><td>${data.model_info.name}</td></tr>
                    <tr><td><strong>Validé:</strong></td><td>${data.model_info.validated ? 'Oui' : 'Non'}</td></tr>
                    <tr><td><strong>Date validation:</strong></td><td>${data.model_info.validation_date || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Statistiques d'extraction</h6>
                <table class="table table-sm">
                    <tr><td><strong>Modules:</strong></td><td>${data.extraction_stats.total_modules}</td></tr>
                    <tr><td><strong>Colonnes trouvées:</strong></td><td>${data.extraction_stats.columns_found.length}</td></tr>
                    <tr><td><strong>Score qualité:</strong></td><td>${data.quality_score}/100</td></tr>
                </table>
            </div>
        </div>
        
        <hr>
        
        <h6>Recommandations</h6>
        <ul>
            ${data.recommendations.map(rec => `<li>${rec}</li>`).join('')}
        </ul>
    `;
    
    modal.show();
}
</script>
@endpush
