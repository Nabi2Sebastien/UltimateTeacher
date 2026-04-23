@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-bug"></i> Débogage des modèles</h4>
                    <a href="{{ route('file-explorer.index') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-folder-open"></i> Explorer les fichiers
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="modelSelect" class="form-label">Sélectionner un modèle</label>
                            <select id="modelSelect" class="form-select">
                                <option value="">Choisir un modèle...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label><br>
                            <button id="testModel" class="btn btn-primary" disabled>
                                <i class="fas fa-search"></i> Tester le modèle
                            </button>
                            <button id="testExtraction" class="btn btn-warning" disabled>
                                <i class="fas fa-file-text"></i> Tester l'extraction
                            </button>
                        </div>
                    </div>

                    <div id="results" style="display: none;">
                        <hr>
                        <h5>Résultats</h5>
                        <div id="resultsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let models = @json($models ?? []);

document.addEventListener('DOMContentLoaded', function() {
    populateModelSelect();
});

function populateModelSelect() {
    const select = document.getElementById('modelSelect');
    select.innerHTML = '<option value="">Choisir un modèle...</option>';
    
    console.log('Modèles chargés:', models); // Debug
    
    models.forEach((model, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = model.name;
        select.appendChild(option);
    });
}

document.getElementById('modelSelect').addEventListener('change', function() {
    const testModelBtn = document.getElementById('testModel');
    const testExtractionBtn = document.getElementById('testExtraction');
    
    testModelBtn.disabled = this.value === '';
    testExtractionBtn.disabled = this.value === '';
});

document.getElementById('testModel').addEventListener('click', function() {
    const modelKey = document.getElementById('modelSelect').value;
    if (!modelKey) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Test...';
    
    fetch(`/debug/model/${modelKey}`)
        .then(response => response.json())
        .then(data => {
            displayResults('Test du modèle', data);
        })
        .catch(error => {
            displayResults('Erreur', {error: error.message});
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-search"></i> Tester le modèle';
        });
});

document.getElementById('testExtraction').addEventListener('click', function() {
    const modelKey = document.getElementById('modelSelect').value;
    if (!modelKey) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Extraction...';
    
    fetch(`/debug/extraction/${modelKey}`)
        .then(response => response.json())
        .then(data => {
            displayResults('Test d\'extraction', data);
        })
        .catch(error => {
            displayResults('Erreur', {error: error.message});
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-file-text"></i> Tester l\'extraction';
        });
});

function displayResults(title, data) {
    const resultsDiv = document.getElementById('results');
    const content = document.getElementById('resultsContent');
    
    let html = `<h6>${title}</h6>`;
    
    if (data.error) {
        html += `
            <div class="alert alert-danger">
                <strong>Erreur:</strong> ${data.error}
                ${data.error_type ? `<br><strong>Type:</strong> ${data.error_type}` : ''}
                ${data.file ? `<br><strong>Fichier:</strong> ${data.file}:${data.line}` : ''}
            </div>
        `;
    }
    
    if (data.model_info) {
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Informations du modèle</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>Nom:</strong></td><td>${data.model_info.name}</td></tr>
                        <tr><td><strong>Chemin:</strong></td><td><code>${data.model_info.path}</code></td></tr>
                        <tr><td><strong>Validé:</strong></td><td>${data.model_info.validated ? 'Oui' : 'Non'}</td></tr>
                    </table>
                </div>
            </div>
        `;
    }
    
    if (data.file_exists !== undefined) {
        const fileStatus = data.file_exists ? 
            '<span class="badge bg-success">Oui</span>' : 
            '<span class="badge bg-danger">Non</span>';
        
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6>État du fichier</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>Fichier existe:</strong></td><td>${fileStatus}</td></tr>
                        <tr><td><strong>Taille:</strong></td><td>${data.file_size || 'N/A'}</td></tr>
                        <tr><td><strong>Lisible:</strong></td><td>${data.file_readable ? 'Oui' : 'Non'}</td></tr>
                        ${data.file_extension ? `<tr><td><strong>Extension:</strong></td><td>${data.file_extension}</td></tr>` : ''}
                        ${data.mime_type ? `<tr><td><strong>Type MIME:</strong></td><td>${data.mime_type}</td></tr>` : ''}
                    </table>
                </div>
            </div>
        `;
    }
    
    if (data.success !== undefined) {
        const successBadge = data.success ? 
            '<span class="badge bg-success">Succès</span>' : 
            '<span class="badge bg-danger">Échec</span>';
        
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Résultat de l'extraction ${successBadge}</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>Longueur du texte:</strong></td><td>${data.text_length || 0} caractères</td></tr>
                        <tr><td><strong>Temps d'exécution:</strong></td><td>${data.execution_time || 'N/A'}</td></tr>
                        <tr><td><strong>Mémoire utilisée:</strong></td><td>${data.memory_used || 'N/A'}</td></tr>
                        <tr><td><strong>Pic mémoire:</strong></td><td>${data.peak_memory || 'N/A'}</td></tr>
                    </table>
                    ${data.text_preview ? `
                        <h6>Aperçu du texte:</h6>
                        <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${data.text_preview}</pre>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    if (data.memory_usage !== undefined) {
        html += `
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Informations système</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>Utilisation mémoire:</strong></td><td>${data.memory_usage} bytes</td></tr>
                        <tr><td><strong>Max execution time:</strong></td><td>${data.max_execution_time}s</td></tr>
                    </table>
                </div>
            </div>
        `;
    }
    
    content.innerHTML = html;
    resultsDiv.style.display = 'block';
}
</script>
@endpush
