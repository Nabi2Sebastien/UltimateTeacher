@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Modifier le modèle: {{ $model['name'] }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('referentiel-models.update', $modelKey) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Informations du modèle</h5>
                                
                                <div class="row mb-3">
                                    <label for="name" class="col-md-4 col-form-label text-md-end">Nom du modèle</label>
                                    <div class="col-md-8">
                                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                               name="name" value="{{ old('name', $model['name']) }}" required autofocus>
                                        @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="path" class="col-md-4 col-form-label text-md-end">Chemin du fichier</label>
                                    <div class="col-md-8">
                                        <input id="path" type="text" class="form-control @error('path') is-invalid @enderror" 
                                               name="path" value="{{ old('path', $model['path']) }}" required>
                                        @error('path')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <div class="form-text">
                                            Chemin complet vers le fichier PDF ou Word
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="description" class="col-md-4 col-form-label text-md-end">Description</label>
                                    <div class="col-md-8">
                                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" 
                                                  name="description" rows="3">{{ old('description', $model['description'] ?? '') }}</textarea>
                                        @error('description')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-8 offset-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="validated" name="validated" 
                                                   value="1" {{ ($model['validated'] ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="validated">
                                                <i class="fas fa-check-circle"></i> Modèle validé
                                            </label>
                                            <div class="form-text">
                                                Cochez si ce modèle est correct et peut être utilisé comme référence
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5>Colonnes extraites</h5>
                                
                                @if (isset($extractedData['error']))
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ $extractedData['error'] }}
                                    </div>
                                @else
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span><strong>Modules détectés:</strong> {{ $extractedData['total_modules'] ?? 0 }}</span>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshExtraction()">
                                                    <i class="fas fa-sync-alt"></i> Actualiser
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        @php
                                            $allColumns = ['referentiel_id', 'code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
                                            $foundColumns = $extractedData['columns_found'] ?? [];
                                        @endphp
                                        
                                        @foreach ($allColumns as $column)
                                            <div class="col-6 mb-2">
                                                <div class="card card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>{{ $column }}</span>
                                                        @if (in_array($column, $foundColumns))
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check"></i>
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-times"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <label for="code_comment" class="col-md-2 col-form-label text-md-end">Commentaire de code</label>
                            <div class="col-md-10">
                                <textarea id="code_comment" class="form-control @error('code_comment') is-invalid @enderror" 
                                          name="code_comment" rows="4" placeholder="// Commentaire décrivant ce modèle...">{{ old('code_comment', $model['code_comment'] ?? '') }}</textarea>
                                @error('code_comment')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="form-text">
                                    Commentaire qui sera ajouté au code lorsque ce modèle est utilisé
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les modifications
                                </button>
                                <a href="{{ route('referentiel-models.show', $modelKey) }}" class="btn btn-info">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="{{ route('referentiel-models.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if (!isset($extractedData['error']) && isset($extractedData['modules']) && count($extractedData['modules']) > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i> Aperçu des modules extraits</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Titre</th>
                                        <th>Durée</th>
                                        <th>Niveau</th>
                                        <th>Approche pédagogique</th>
                                        <th>Type d'évaluation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($extractedData['modules'] as $index => $module)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $module['code'] ?? '-' }}</td>
                                            <td>{{ $module['title'] ?? '-' }}</td>
                                            <td>{{ $module['duration'] ?? '-' }}</td>
                                            <td>{{ $module['level'] ?? '-' }}</td>
                                            <td>{{ Str::limit($module['pedagogical_approach'] ?? '-', 30) }}</td>
                                            <td>{{ Str::limit($module['assessment_type'] ?? '-', 30) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshExtraction() {
    // Recharger la page pour actualiser l'extraction
    window.location.reload();
}
</script>
@endpush
