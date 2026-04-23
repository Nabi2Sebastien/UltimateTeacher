@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-folder-open"></i> Explorateur de fichiers</h4>
                    <div>
                        <a href="{{ route('referentiel-models.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour aux modèles
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Navigation -->
                    <div class="mb-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('file-explorer.index', ['path' => 'C:\\']) }}">C:</a>
                                </li>
                                @php
                                    $pathParts = explode(DIRECTORY_SEPARATOR, trim($currentPath, DIRECTORY_SEPARATOR));
                                    $currentPathTemp = '';
                                @endphp
                                @foreach ($pathParts as $index => $part)
                                    @if ($part !== '')
                                        @php
                                            $currentPathTemp .= ($currentPathTemp ? DIRECTORY_SEPARATOR : '') . $part;
                                            $isLast = $index === count($pathParts) - 1;
                                        @endphp
                                        @if ($isLast)
                                            <li class="breadcrumb-item active" aria-current="page">{{ $part }}</li>
                                        @else
                                            <li class="breadcrumb-item">
                                                <a href="{{ route('file-explorer.index', ['path' => $currentPathTemp]) }}">{{ $part }}</a>
                                            </li>
                                        @endif
                                    @endif
                                @endforeach
                            </ol>
                        </nav>
                    </div>

                    <!-- Barre d'outils -->
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="GET" action="{{ route('file-explorer.index') }}">
                                    <div class="input-group">
                                        <input type="text" name="path" class="form-control" 
                                               value="{{ $currentPath }}" 
                                               placeholder="Entrez un chemin...">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i> Aller
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Navigation dans les fichiers PDF et Word uniquement
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des répertoires -->
                    @if (!empty($directories))
                        <h6><i class="fas fa-folder"></i> Répertoires</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom</th>
                                        <th>Modifié</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($parentPath && $parentPath !== $currentPath)
                                        <tr>
                                            <td colspan="3">
                                                <a href="{{ route('file-explorer.index', ['path' => $parentPath]) }}" 
                                                   class="text-decoration-none">
                                                    <i class="fas fa-level-up-alt"></i> ..
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach ($directories as $dir)
                                        <tr>
                                            <td>
                                                <a href="{{ route('file-explorer.index', ['path' => $dir['path']]) }}" 
                                                   class="text-decoration-none">
                                                    <i class="fas fa-folder text-warning"></i>
                                                    {{ $dir['name'] }}
                                                </a>
                                            </td>
                                            <td>{{ $dir['modified'] }}</td>
                                            <td>
                                                <a href="{{ route('file-explorer.index', ['path' => $dir['path']]) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-folder-open"></i> Ouvrir
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <!-- Liste des fichiers -->
                    @if (!empty($files))
                        <h6><i class="fas fa-file"></i> Fichiers PDF et Word</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Taille</th>
                                        <th>Modifié</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($files as $file)
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-{{ $file['type'] === 'pdf' ? 'pdf text-danger' : 'word text-primary' }}"></i>
                                                {{ $file['name'] }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $file['type'] === 'pdf' ? 'danger' : 'primary' }}">
                                                    {{ strtoupper($file['type']) }}
                                                </span>
                                            </td>
                                            <td>{{ $file['size'] }}</td>
                                            <td>{{ $file['modified'] }}</td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success"
                                                        onclick="createModelFromPath('{{ $file['path'] }}', '{{ $file['name'] }}')">
                                                    <i class="fas fa-plus"></i> Créer un modèle
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (empty($directories) && empty($files))
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5>Ce répertoire est vide</h5>
                            <p class="text-muted">
                                Aucun sous-répertoire ou fichier PDF/Word trouvé dans ce dossier.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour créer un modèle -->
<div class="modal fade" id="createModelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer un modèle de référentiel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('file-explorer.create-model') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalName" class="form-label">Nom du modèle</label>
                        <input type="text" class="form-control" id="modalName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="modalPath" class="form-label">Chemin du fichier</label>
                        <input type="text" class="form-control" id="modalPath" name="path" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="modalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="modalDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le modèle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function createModelFromPath(filePath, fileName) {
    // Extraire un nom de modèle à partir du nom de fichier
    let modelName = fileName.replace(/\.[^/]+$/, ""); // Enlever l'extension
    modelName = modelName.replace(/[-_]/g, ' '); // Remplacer les tirets et underscores
    modelName = modelName.charAt(0).toUpperCase() + modelName.slice(1); // Majuscule au début
    
    document.getElementById('modalName').value = modelName;
    document.getElementById('modalPath').value = filePath;
    document.getElementById('modalDescription').value = 'Modèle créé depuis: ' + fileName;
    
    const modal = new bootstrap.Modal(document.getElementById('createModelModal'));
    modal.show();
}

// Auto-soumission du formulaire de navigation
document.querySelector('form[method="GET"]').addEventListener('submit', function(e) {
    const pathInput = e.target.querySelector('input[name="path"]');
    let path = pathInput.value.trim();
    
    // Normaliser le chemin
    path = path.replace(/\//g, '\\');
    if (!path.endsWith('\\')) {
        path += '\\';
    }
    
    pathInput.value = path;
});
</script>
@endpush
