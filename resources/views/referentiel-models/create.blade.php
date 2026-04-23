@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Créer un modèle de référentiel</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('referentiel-models.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">Nom du modèle</label>
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                       name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="path" class="col-md-4 col-form-label text-md-end">Chemin du fichier</label>
                            <div class="col-md-6">
                                <input id="path" type="text" class="form-control @error('path') is-invalid @enderror" 
                                       name="path" value="{{ old('path') }}" required>
                                @error('path')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="form-text">
                                    Ex: {{ base_path('public/storage/referentiels/mon_fichier.pdf') }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="description" class="col-md-4 col-form-label text-md-end">Description</label>
                            <div class="col-md-6">
                                <textarea id="description" class="form-control @error('description') is-invalid @enderror" 
                                          name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="validate_now" name="validate_now">
                                    <label class="form-check-label" for="validate_now">
                                        Valider ce modèle maintenant
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Créer le modèle
                                </button>
                                <a href="{{ route('referentiel-models.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h6><i class="fas fa-info-circle"></i> Aide</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Chemin du fichier:</strong> Indiquez le chemin complet vers votre fichier PDF ou Word.
                    </p>
                    <p class="mb-2">
                        <strong>Exemples de chemins:</strong>
                    </p>
                    <ul class="mb-0">
                        <li><code>{{ base_path('public/storage/referentiels/mon_referentiel.pdf') }}</code></li>
                        <li><code>{{ base_path('public/storage/referentiels/referentiel.docx') }}</code></li>
                        <li><code>C:\laragon\www\UltimateTeacher\public\storage\referentiels\fichier.pdf</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
