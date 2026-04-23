@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Modèles de Référentiels</h4>
                    <a href="{{ route('referentiel-models.validation') }}" class="btn btn-info">
                        <i class="fas fa-check-circle"></i> Validation
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (empty($models))
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>Aucun modèle de référentiel</h5>
                            <p class="text-muted">
                                Commencez par créer un modèle pour tester l'extraction de modules.
                            </p>
                            <a href="{{ route('referentiel-models.create') }}" class="btn btn-primary">
                                Créer un modèle
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Chemin</th>
                                        <th>Validé</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($models as $key => $model)
                                        <tr>
                                            <td>
                                                <strong>{{ $model['name'] }}</strong>
                                                @if (!empty($model['description']))
                                                    <br><small class="text-muted">{{ $model['description'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <code>{{ basename($model['path']) }}</code>
                                                <br><small class="text-muted">{{ $model['path'] }}</small>
                                            </td>
                                            <td>
                                                @if (($model['validated'] ?? false))
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Validé
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-clock"></i> En attente
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="extractColumns({{ $key }})">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </button>
                                                    <a href="{{ route('referentiel-models.edit', $key) }}" 
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('referentiel-models.destroy', $key) }}" 
                                                          method="POST" 
                                                          style="display: inline-block;"
                                                          onsubmit="return confirm('Êtes-vous sûr de supprimer ce modèle ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher les colonnes extraites -->
<div class="modal fade" id="columnsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Colonnes extraites du modèle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="columnsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function extractColumns(modelKey) {
    const modal = new bootstrap.Modal(document.getElementById('columnsModal'));
    const content = document.getElementById('columnsContent');
    
    // Afficher le spinner
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`/referentiel-models/${modelKey}/extract-columns`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur: ${data.error}
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle"></i> Informations du modèle</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Nom:</strong></td><td>${data.model.name}</td></tr>
                            <tr><td><strong>Fichier:</strong></td><td>${data.model.path}</td></tr>
                            <tr><td><strong>Validé:</strong></td><td>${data.model.validated ? 'Oui' : 'Non'}</td></tr>
                            <tr><td><strong>Modules trouvés:</strong></td><td>${data.modules_count}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-columns"></i> Colonnes détectées</h6>
                        <div class="row">
            `;
            
            const allColumns = ['referentiel_id', 'code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
            
            allColumns.forEach(column => {
                const found = data.columns_found.includes(column);
                const badgeClass = found ? 'bg-success' : 'bg-secondary';
                const icon = found ? 'fas fa-check' : 'fas fa-times';
                
                html += `
                    <div class="col-6 mb-2">
                        <span class="badge ${badgeClass}">
                            <i class="${icon}"></i> ${column}
                        </span>
                    </div>
                `;
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
            
            if (data.extracted_data.modules && data.extracted_data.modules.length > 0) {
                html += `
                    <hr>
                    <h6><i class="fas fa-list"></i> Aperçu des modules (${data.extracted_data.modules.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Titre</th>
                                    <th>Durée</th>
                                    <th>Niveau</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.extracted_data.modules.slice(0, 5).forEach((module, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${module.code || '-'}</td>
                            <td>${module.title || '-'}</td>
                            <td>${module.duration || '-'}</td>
                            <td>${module.level || '-'}</td>
                        </tr>
                    `;
                });
                
                if (data.extracted_data.modules.length > 5) {
                    html += `
                        <tr>
                            <td colspan="5" class="text-center">
                                <em>... et ${data.extracted_data.modules.length - 5} autres modules</em>
                            </td>
                        </tr>
                    `;
                }
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Erreur de chargement: ${error.message}
                </div>
            `;
        });
}
</script>
@endpush
