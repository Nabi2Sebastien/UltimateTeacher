@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Détails du modèle: {{ $model['name'] }}</h4>
                    <div>
                        <a href="{{ route('referentiel-models.edit', $modelKey) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="{{ route('referentiel-models.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Informations générales</h5>
                            <table class="table table-striped">
                                <tr>
                                    <td><strong>Nom:</strong></td>
                                    <td>{{ $model['name'] }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fichier:</strong></td>
                                    <td>
                                        <code>{{ basename($model['path']) }}</code>
                                        <br><small class="text-muted">{{ $model['path'] }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Statut:</strong></td>
                                    <td>
                                        @if (($model['validated'] ?? false))
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Validé
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-clock"></i> En attente de validation
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @if (!empty($model['description']))
                                    <tr>
                                        <td><strong>Description:</strong></td>
                                        <td>{{ $model['description'] }}</td>
                                    </tr>
                                @endif
                                @if (!empty($model['code_comment']))
                                    <tr>
                                        <td><strong>Commentaire de code:</strong></td>
                                        <td><code>{{ $model['code_comment'] }}</code></td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-chart-bar"></i> Statistiques d'extraction</h5>
                            
                            @if (isset($extractedData['error']))
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Erreur d'extraction:</strong><br>
                                    {{ $extractedData['error'] }}
                                </div>
                            @else
                                <table class="table table-striped">
                                    <tr>
                                        <td><strong>Modules extraits:</strong></td>
                                        <td>
                                            <span class="badge bg-primary">{{ $extractedData['total_modules'] ?? 0 }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Colonnes détectées:</strong></td>
                                        <td>
                                            <span class="badge bg-info">{{ count($extractedData['columns_found'] ?? []) }}</span>
                                            / 8
                                        </td>
                                    </tr>
                                </table>

                                <h6 class="mt-3"><i class="fas fa-columns"></i> Colonnes disponibles</h6>
                                <div class="row">
                                    @php
                                        $allColumns = ['referentiel_id', 'code', 'title', 'duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'];
                                        $foundColumns = $extractedData['columns_found'] ?? [];
                                    @endphp
                                    
                                    @foreach ($allColumns as $column)
                                        <div class="col-6 mb-2">
                                            @if (in_array($column, $foundColumns))
                                                <div class="alert alert-success py-2 mb-1">
                                                    <i class="fas fa-check"></i> {{ $column }}
                                                </div>
                                            @else
                                                <div class="alert alert-secondary py-2 mb-1">
                                                    <i class="fas fa-times"></i> {{ $column }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!isset($extractedData['error']) && isset($extractedData['modules']) && count($extractedData['modules']) > 0)
                        <hr>
                        
                        <h5><i class="fas fa-list"></i> Modules extraits ({{ count($extractedData['modules']) }})</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Titre</th>
                                        <th>Durée</th>
                                        <th>Niveau</th>
                                        <th>Profil enseignant</th>
                                        <th>Approche pédagogique</th>
                                        <th>Type d'évaluation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($extractedData['modules'] as $index => $module)
                                        <tr>
                                            <td><strong>{{ $index + 1 }}</strong></td>
                                            <td>
                                                @if (!empty($module['code']))
                                                    <span class="badge bg-info">{{ $module['code'] }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $module['title'] ?? '-' }}</strong>
                                            </td>
                                            <td>
                                                @if (!empty($module['duration']))
                                                    <span class="badge bg-primary">{{ $module['duration'] }}h</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $module['level'] ?? '-' }}</td>
                                            <td>{{ Str::limit($module['teacher_profile'] ?? '-', 20) }}</td>
                                            <td>{{ Str::limit($module['pedagogical_approach'] ?? '-', 25) }}</td>
                                            <td>{{ Str::limit($module['assessment_type'] ?? '-', 25) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (isset($extractedData['text_preview']))
                            <details class="mt-4">
                                <summary class="h5">
                                    <i class="fas fa-file-alt"></i> Aperçu du texte extrait
                                </summary>
                                <div class="mt-3">
                                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">{{ $extractedData['text_preview'] }}</pre>
                                </div>
                            </details>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
