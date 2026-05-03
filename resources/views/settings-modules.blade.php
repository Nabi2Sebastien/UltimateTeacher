@extends('layouts.app')

@section('title', 'Paramètres - Référentiels')

@push('styles')
<style>
    .settings-container {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }

    .card {
        background-color: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        padding: 30px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #181c32;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 0.95rem;
        font-weight: 500;
        color: #3f4254;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e4e6ef;
        border-radius: 8px;
        background-color: #f9f9f9;
        font-size: 0.95rem;
        transition: all 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent);
        background-color: #fff;
        box-shadow: 0 0 0 3px var(--accent-glow);
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background-color: var(--accent);
        color: white;
    }

    .btn-primary:hover {
        background-color: #008be5;
        box-shadow: 0 4px 10px var(--accent-glow);
    }

    .btn-danger {
        background-color: #f1416c;
        color: white;
    }

    .btn-danger:hover {
        background-color: #d9214e;
    }

    .file-drop-area {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 150px;
        border: 2px dashed #e4e6ef;
        border-radius: 12px;
        background-color: #f9f9f9;
        transition: all 0.3s;
        cursor: pointer;
    }

    .file-drop-area:hover, .file-drop-area.dragover {
        border-color: var(--accent);
        background-color: rgba(0, 158, 247, 0.05);
    }

    .file-drop-area svg {
        width: 40px;
        height: 40px;
        fill: #a1a5b7;
        margin-bottom: 10px;
    }

    .file-drop-area:hover svg {
        fill: var(--accent);
    }

    .file-msg {
        font-size: 0.95rem;
        color: #7e8299;
        font-weight: 500;
    }

    .file-input {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 100%;
        cursor: pointer;
        opacity: 0;
    }

    /* List styling */
    .docs-list {
        list-style: none;
        max-height: 350px;
        overflow-y: auto;
        padding-right: 10px;
        margin-right: -10px;
    }

    /* Personnalisation de la barre de défilement */
    .docs-list::-webkit-scrollbar {
        width: 6px;
    }

    .docs-list::-webkit-scrollbar-track {
        background: #f1f1f1; 
        border-radius: 4px;
    }
     
    .docs-list::-webkit-scrollbar-thumb {
        background: #c1c1c1; 
        border-radius: 4px;
    }

    .docs-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8; 
    }

    .doc-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        border: 1px solid #e4e6ef;
        border-radius: 8px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }

    .doc-item:hover {
        border-color: var(--accent);
        background-color: #fcfcfc;
    }

    .doc-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .doc-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background-color: rgba(0, 158, 247, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .doc-icon svg {
        width: 24px;
        height: 24px;
        fill: var(--accent);
    }

    .doc-icon.pdf {
        background-color: rgba(241, 65, 108, 0.1);
    }

    .doc-icon.pdf svg {
        fill: #f1416c;
    }

    .doc-icon.word {
        background-color: rgba(62, 151, 255, 0.1);
    }

    .doc-icon.word svg {
        fill: #3e97ff;
    }

    .doc-details h4 {
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: #181c32;
    }

    .doc-details p {
        font-size: 0.85rem;
        color: #7e8299;
    }

    .doc-actions {
        display: flex;
        gap: 10px;
    }

    .btn-icon {
        width: 35px;
        height: 35px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-icon-view {
        background-color: #f5f8fa;
        color: #5e6278;
    }

    .btn-icon-view:hover {
        background-color: #e4e6ef;
        color: var(--accent);
    }

    .btn-icon-delete {
        background-color: #fff5f8;
        color: #f1416c;
    }

    .btn-icon-delete:hover {
        background-color: #f1416c;
        color: white;
    }

    .btn-icon-edit {
        background-color: #fff8e5;
        color: #ffc700;
    }

    .btn-icon-edit:hover {
        background-color: #ffc700;
        color: white;
    }

    .btn-secondary {
        background-color: #f5f8fa;
        color: #5e6278;
        border: 1px solid #e4e6ef;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-secondary:hover {
        background-color: #e4e6ef;
    }

    .list-controls {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .item-hidden {
        display: none !important;
    }

    .settings-nav {
        display: flex;
        gap: 20px;
        border-bottom: 1px solid #e4e6ef;
        margin-bottom: 30px;
        position: sticky;
        top: 0;
        background-color: var(--bg-color);
        z-index: 99;
        padding: 10px 0;
        margin-top: -10px;
    }

    .settings-nav-item {
        padding: 10px 5px;
        color: #7e8299;
        font-weight: 500;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }

    .settings-nav-item:hover {
        color: var(--accent);
    }

    .settings-nav-item.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-left: 10px;
    }

    .badge-formation {
        background-color: #e8fff3;
        color: #50cd89;
    }

    .badge-evaluation {
        background-color: #e8f3ff;
        color: #3e97ff;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-success {
        background-color: #e8fff3;
        border: 1px solid #50cd89;
        color: #0b7a42;
    }

    .alert-danger {
        background-color: #fff5f8;
        border: 1px solid #f1416c;
        color: #9f1c3a;
    }

    /* Table styling */
    .table-responsive {
        overflow-x: auto;
        margin-bottom: 20px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e4e6ef;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .table th, .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e4e6ef;
        font-size: 0.9rem;
        background: white; /* Important for sticky */
    }

    .table th {
        background-color: #f9f9f9;
        font-weight: 600;
        color: #3f4254;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table tbody tr:hover td {
        background-color: #f5f8fa;
    }
    
    .table tbody tr.sub-module-row td {
        background-color: #f4f6f8;
    }

    .table tbody tr.sub-module-row:hover td {
        background-color: #eef1f5;
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Frozen columns configuration */
    .col-numero { position: sticky; z-index: 2; width: 60px; min-width: 60px; max-width: 60px; }
    .col-code { position: sticky; z-index: 2; width: 60px; min-width: 60px; max-width: 60px; }
    .col-title { position: sticky; z-index: 2; width: 180px; min-width: 180px; max-width: 180px; white-space: nowrap !important; overflow: hidden; text-overflow: ellipsis; cursor: help; }
    .col-level { position: sticky; z-index: 2; width: 70px; min-width: 70px; max-width: 70px; }
    .col-duration { position: sticky; z-index: 2; width: 70px; min-width: 70px; max-width: 70px; }
    .col-actions { position: sticky; z-index: 2; width: 60px; min-width: 60px; max-width: 60px; border-right: 2px solid #e4e6ef; box-shadow: 2px 0 5px rgba(0,0,0,0.02); text-align: center; }

    .table th.col-numero, .table th.col-code, .table th.col-title, .table th.col-level, .table th.col-duration, .table th.col-actions {
        z-index: 12;
        background-color: #f9f9f9;
    }

    /* Non-frozen columns */
    .col-pedagogy { min-width: 250px; }
    .col-assessment { min-width: 200px; }
    .col-profile { min-width: 200px; }

    /* Modal styling */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        animation: modalFadeIn 0.3s;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #e4e6ef;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #181c32;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #a1a5b7;
        transition: color 0.3s;
    }

    .modal-close:hover {
        color: #f1416c;
    }

    .modal-body {
        padding: 20px;
    }

</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Paramètres</h1>
</div>

<div class="settings-nav">
    <a href="{{ route('settings.index') }}" class="settings-nav-item">Référentiels</a>
    <a href="{{ route('settings.modules') }}" class="settings-nav-item active">Modules</a>
    <a href="#" class="settings-nav-item">Général</a>
    <a href="#" class="settings-nav-item">Utilisateurs</a>
    <a href="#" class="settings-nav-item">Sécurité</a>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul style="margin-left: 20px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="settings-container" style="display: block;">
<div class="card">
    <h2 class="card-title">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/></svg>
        Gestion des Modules
    </h2>
    
    <div style="margin-top: 20px;">
        <label class="form-label" for="referentielSelect">Sélectionner un Référentiel</label>
        <select id="referentielSelect" class="form-control" style="max-width: 400px;">
            <option value="" data-is-pdf="0">-- Choisir un référentiel --</option>
            @foreach($referentiels as $ref)
                <option value="{{ $ref->id }}" data-is-pdf="{{ pathinfo($ref->file_path, PATHINFO_EXTENSION) === 'pdf' ? '1' : '0' }}">{{ $ref->title }} ({{ $ref->status == 'evaluation' ? 'Éval.' : 'Form.' }})</option>
            @endforeach
        </select>
    </div>

    <div id="modulesContainer" style="display: none; margin-top: 30px; border-top: 1px solid #e4e6ef; padding-top: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600; margin: 0; color: #181c32;">Liste des Modules</h3>
            
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <!-- Champ de recherche -->
                <div style="position: relative;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="#a1a5b7" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" id="searchModule" class="form-control" placeholder="Rechercher..." style="width: 220px; height: 40px; padding: 0 15px 0 38px; font-size: 0.9rem; border-radius: 8px; margin: 0; box-sizing: border-box;" onkeyup="filterModules()">
                </div>
                
                <!-- Boutons d'action -->
                <button type="button" id="btnExtractModules" class="btn btn-primary" onclick="extractModulesAjax()" style="display: none; height: 40px; padding: 0 18px; font-size: 0.9rem; margin: 0; box-sizing: border-box;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="margin-right: 5px;"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                    Extraire
                </button>
                <button type="button" id="btnAddModule" class="btn btn-secondary" onclick="openAddModal()" style="display: none; height: 40px; padding: 0 18px; font-size: 0.9rem; background-color: #f5f8fa; color: #3e97ff; border-color: #e8f3ff; margin: 0; box-sizing: border-box;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="margin-right: 5px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Ajouter
                </button>
            </div>
        </div>
        
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table" id="modulesTable">
                <thead>
                    <tr>
                        <th class="col-numero">Numéro</th>
                        <th class="col-code">Code</th>
                        <th class="col-title">Titre</th>
                        <th class="col-level">Niveau</th>
                        <th class="col-duration">Durée</th>
                        <th class="col-actions">Actions</th>
                        <th class="col-pedagogy">Démarche pédagogique</th>
                        <th class="col-assessment">Épreuve</th>
                        <th class="col-profile">Profil Professeur</th>
                        <th class="col-bibliographie">Bibliographie</th>
                    </tr>
                </thead>
                <tbody id="modulesList">
                    <!-- Modules will be injected here via JS -->
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Ajout Module -->
<div id="addModuleModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter un module</h3>
            <button type="button" class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="text" id="newModuleNumero" class="form-control" placeholder="Numéro (ex: 1.1)">
            <input type="text" id="newModuleCode" class="form-control" placeholder="Code (ex: M101)">
            <input type="text" id="newModuleTitle" class="form-control" placeholder="Titre complet..." style="grid-column: 1 / -1;" required>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="number" id="newModuleDuration" class="form-control" placeholder="Durée" style="width: 100px;">
                <span style="color: #7e8299; font-weight: 500;">heures</span>
            </div>
            
            <input type="text" id="newModuleLevel" class="form-control" placeholder="Niveau (ex: BEP, BAC...)">
            
            <input type="text" id="newModuleProfile" class="form-control" placeholder="Profil Professeur" style="grid-column: 1 / -1;">
            
            <input type="text" id="newModulePedagogy" class="form-control" placeholder="Démarche pédagogique">
            <input type="text" id="newModuleAssessment" class="form-control" placeholder="Type d'épreuve">
            <input type="text" id="newModuleBibliographie" class="form-control" placeholder="Bibliographie" style="grid-column: 1 / -1;">
            
            <button type="button" class="btn btn-primary" onclick="addModule()" style="grid-column: 1 / -1; justify-content: center; margin-top: 10px;">Enregistrer le Module</button>
        </div>
    </div>
</div>

<!-- Modal Modification Module -->
<div id="editModuleModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier le module</h3>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="hidden" id="editModuleId">
            <input type="text" id="editModuleNumero" class="form-control" placeholder="Numéro (ex: 1.1)">
            <input type="text" id="editModuleCode" class="form-control" placeholder="Code (ex: M101)">
            <input type="text" id="editModuleTitle" class="form-control" placeholder="Titre complet..." style="grid-column: 1 / -1;" required>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="number" id="editModuleDuration" class="form-control" placeholder="Durée" style="width: 100px;">
                <span style="color: #7e8299; font-weight: 500;">heures</span>
            </div>
            
            <input type="text" id="editModuleLevel" class="form-control" placeholder="Niveau (ex: BEP, BAC...)">
            
            <input type="text" id="editModuleProfile" class="form-control" placeholder="Profil Professeur" style="grid-column: 1 / -1;">
            
            <input type="text" id="editModulePedagogy" class="form-control" placeholder="Démarche pédagogique">
            <input type="text" id="editModuleAssessment" class="form-control" placeholder="Type d'épreuve">
            <input type="text" id="editModuleBibliographie" class="form-control" placeholder="Bibliographie" style="grid-column: 1 / -1;">
            
            <button type="button" id="btnSubmitEditModule" class="btn btn-primary" onclick="submitEditModule()" style="grid-column: 1 / -1; justify-content: center; margin-top: 10px;">Enregistrer les modifications</button>
        </div>
    </div>
</div>

<script>


    // Modules Management Logic
    const referentielSelect = document.getElementById('referentielSelect');
    const modulesContainer = document.getElementById('modulesContainer');
    const modulesList = document.getElementById('modulesList');
    const newModuleTitle = document.getElementById('newModuleTitle');
    const btnExtractModules = document.getElementById('btnExtractModules');
    const btnAddModule = document.getElementById('btnAddModule');
    const addModuleModal = document.getElementById('addModuleModal');
    const editModuleModal = document.getElementById('editModuleModal');

    if(referentielSelect) {
        referentielSelect.addEventListener('change', function() {
            const refId = this.value;
            if (!refId) {
                modulesContainer.style.display = 'none';
                btnExtractModules.style.display = 'none';
                btnAddModule.style.display = 'none';
                return;
            }

            btnAddModule.style.display = 'inline-flex';

            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.getAttribute('data-is-pdf') === '1') {
                btnExtractModules.style.display = 'inline-flex';
            } else {
                btnExtractModules.style.display = 'none';
            }

            modulesContainer.style.display = 'block';
            fetchModules(refId);
        });
    }

    function extractModulesAjax() {
        const refId = referentielSelect.value;
        if (!refId) return;
        
        btnExtractModules.disabled = true;
        btnExtractModules.innerHTML = 'Extraction en cours...';

        fetch(`/settings/${refId}/extract`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btnExtractModules.disabled = false;
            btnExtractModules.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Extraire les modules';
            if (data.success) {
                alert(data.message || "Extraction terminée avec succès.");
                fetchModules(refId);
            } else {
                alert(data.message || "Erreur lors de l'extraction.");
            }
        })
        .catch(err => {
            console.error("Erreur d'extraction:", err);
            btnExtractModules.disabled = false;
            btnExtractModules.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Extraire les modules';
            alert("Erreur lors de la requête d'extraction.");
        });
    }

    function fetchModules(refId) {
        modulesList.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #a1a5b7; padding: 20px;">Chargement...</td></tr>';
        
        fetch(`/settings/${refId}/modules`)
            .then(response => response.json())
            .then(data => {
                modulesList.innerHTML = '';
                if (data.length === 0) {
                    modulesList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #a1a5b7; padding: 20px;">Aucun module trouvé pour ce référentiel.</td></tr>';
                    
                    // Enable extraction button
                    const selectedOption = referentielSelect.options[referentielSelect.selectedIndex];
                    if (selectedOption && selectedOption.getAttribute('data-is-pdf') === '1') {
                        btnExtractModules.style.display = 'inline-flex';
                        btnExtractModules.disabled = false;
                        btnExtractModules.style.backgroundColor = 'var(--accent)';
                        btnExtractModules.title = "Extraire les modules";
                    }
                    return;
                }
                
                // Disable extraction button because modules exist
                const selectedOption = referentielSelect.options[referentielSelect.selectedIndex];
                if (selectedOption && selectedOption.getAttribute('data-is-pdf') === '1') {
                    btnExtractModules.style.display = 'inline-flex';
                    btnExtractModules.disabled = true;
                    btnExtractModules.style.backgroundColor = '#d8d8d8';
                    btnExtractModules.title = "Modules déjà extraits. Supprimez-les pour recommencer.";
                }

                // Toujours afficher le numéro et le code pour faciliter l'édition
                const hasNumero = true;
                const hasCode = true;
                
                const thNumero = document.querySelector('th.col-numero');
                const thCode = document.querySelector('th.col-code');
                const thTitle = document.querySelector('th.col-title');
                const thLevel = document.querySelector('th.col-level');
                const thDuration = document.querySelector('th.col-duration');
                const thActions = document.querySelector('th.col-actions');

                let currentLeft = 0;
                
                if (hasNumero) {
                    thNumero.style.display = '';
                    thNumero.style.left = currentLeft + 'px';
                    currentLeft += 60;
                } else {
                    thNumero.style.display = 'none';
                }

                if (hasCode || (!hasCode && !hasNumero)) { // Show Code if both are missing just in case
                    thCode.style.display = '';
                    thCode.style.left = currentLeft + 'px';
                    currentLeft += 60;
                } else {
                    thCode.style.display = 'none';
                }

                thTitle.style.left = currentLeft + 'px';
                currentLeft += 180;
                
                thLevel.style.left = currentLeft + 'px';
                currentLeft += 70;
                
                thDuration.style.left = currentLeft + 'px';
                currentLeft += 70;
                
                thActions.style.left = currentLeft + 'px';

                data.forEach(module => {
                    const tr = document.createElement('tr');
                    tr.id = `module-row-${module.id}`;
                    
                    const isSubModule = (module.numero && module.numero.includes('.')) || (module.code && module.code.includes('.'));
                    if (isSubModule) {
                        tr.classList.add('sub-module-row');
                    }
                    
                    let html = '';
                    
                    if (hasNumero) {
                        html += `<td class="col-numero" style="left: ${thNumero.style.left}; font-weight: 600; color: #181c32;">${module.numero || '-'}</td>`;
                    }
                    if (hasCode || (!hasCode && !hasNumero)) {
                        html += `<td class="col-code" style="left: ${thCode.style.left}; font-weight: 600; color: var(--accent);">${module.code || '-'}</td>`;
                    }
                    
                    html += `
                        <td class="col-title" title="${module.title ? module.title.replace(/"/g, '&quot;') : '-'}" style="left: ${thTitle.style.left}; font-weight: 500; color: #181c32;">${module.title || '-'}</td>
                        <td class="col-level" style="left: ${thLevel.style.left}; color: #7e8299;">${module.level || '-'}</td>
                        <td class="col-duration" style="left: ${thDuration.style.left}; color: #7e8299;">${module.duration ? module.duration + 'h' : '-'}</td>
                        <td class="col-actions" style="left: ${thActions.style.left};">
                            <button class="btn-icon btn-icon-edit" title="Modifier" onclick='editModule(${JSON.stringify(module).replace(/'/g, "&#39;")})' style="display: inline-flex; margin: 0 auto;">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </button>
                        </td>
                        <td class="col-pedagogy" style="color: #7e8299;">${module.pedagogical_approach || '-'}</td>
                        <td class="col-assessment" style="color: #7e8299;">${module.assessment_type || '-'}</td>
                        <td class="col-profile" style="color: #7e8299;">${module.teacher_profile || '-'}</td>
                        <td class="col-bibliographie" style="color: #7e8299;">${module.bibliographie || '-'}</td>
                    `;
                    tr.innerHTML = html;
                    modulesList.appendChild(tr);
                });
            })
            .catch(err => {
                modulesList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #f1416c; padding: 20px;">Erreur de chargement des modules.</td></tr>';
            });
    }

    function editModule(module) {
        document.getElementById('editModuleId').value = module.id;
        document.getElementById('editModuleNumero').value = module.numero || '';
        document.getElementById('editModuleCode').value = module.code || '';
        document.getElementById('editModuleTitle').value = module.title || '';
        document.getElementById('editModuleDuration').value = module.duration || '';
        document.getElementById('editModuleLevel').value = module.level || '';
        document.getElementById('editModuleProfile').value = module.teacher_profile || '';
        document.getElementById('editModulePedagogy').value = module.pedagogical_approach || '';
        document.getElementById('editModuleAssessment').value = module.assessment_type || '';
        document.getElementById('editModuleBibliographie').value = module.bibliographie || '';
        
        editModuleModal.style.display = 'flex';
        setTimeout(() => document.getElementById('editModuleTitle').focus(), 100);
    }

    function closeEditModal() {
        editModuleModal.style.display = 'none';
    }

    function submitEditModule() {
        const moduleId = document.getElementById('editModuleId').value;
        const numero = document.getElementById('editModuleNumero').value.trim();
        const code = document.getElementById('editModuleCode').value.trim();
        const title = document.getElementById('editModuleTitle').value.trim();
        const duration = document.getElementById('editModuleDuration').value;
        const level = document.getElementById('editModuleLevel').value.trim();
        const profile = document.getElementById('editModuleProfile').value.trim();
        const pedagogy = document.getElementById('editModulePedagogy').value.trim();
        const assessment = document.getElementById('editModuleAssessment').value.trim();
        const bibliographie = document.getElementById('editModuleBibliographie').value.trim();

        if (!title) {
            alert("Le titre est obligatoire.");
            return;
        }

        const btnSubmit = document.getElementById('btnSubmitEditModule');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = "Enregistrement...";

        fetch(`/settings/modules/${moduleId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                numero: numero,
                code: code,
                title: title,
                duration: duration,
                level: level,
                teacher_profile: profile,
                pedagogical_approach: pedagogy,
                assessment_type: assessment,
                bibliographie: bibliographie
            })
        })
        .then(response => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = "Enregistrer les modifications";
            if(response.ok) {
                closeEditModal();
                fetchModules(referentielSelect.value);
            } else {
                alert("Erreur lors de la modification.");
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = "Enregistrer les modifications";
            alert("Erreur de connexion.");
        });
    }

    function addModule() {
        const refId = referentielSelect.value;
        const numero = document.getElementById('newModuleNumero').value.trim();
        const code = document.getElementById('newModuleCode').value.trim();
        const title = document.getElementById('newModuleTitle').value.trim();
        const duration = document.getElementById('newModuleDuration').value;
        const level = document.getElementById('newModuleLevel').value.trim();
        const profile = document.getElementById('newModuleProfile').value.trim();
        const pedagogy = document.getElementById('newModulePedagogy').value.trim();
        const assessment = document.getElementById('newModuleAssessment').value.trim();
        const bibliographie = document.getElementById('newModuleBibliographie').value.trim();

        if (!refId || !title) {
            alert("Le titre est obligatoire.");
            return;
        }

        // Reset inputs immediately
        document.getElementById('newModuleNumero').value = '';
        document.getElementById('newModuleCode').value = '';
        document.getElementById('newModuleTitle').value = '';
        document.getElementById('newModuleDuration').value = '';
        document.getElementById('newModuleLevel').value = '';
        document.getElementById('newModuleProfile').value = '';
        document.getElementById('newModulePedagogy').value = '';
        document.getElementById('newModuleAssessment').value = '';
        document.getElementById('newModuleBibliographie').value = '';
        
        document.getElementById('newModuleTitle').disabled = true;

        fetch(`/settings/${refId}/modules`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                numero: numero,
                code: code,
                title: title,
                duration: duration,
                level: level,
                teacher_profile: profile,
                pedagogical_approach: pedagogy,
                assessment_type: assessment,
                bibliographie: bibliographie
            })
        })
        .then(response => {
            document.getElementById('newModuleTitle').disabled = false;
            if(response.ok) {
                fetchModules(refId);
                closeAddModal();
            } else {
                alert("Erreur lors de l'ajout du module.");
            }
        })
        .catch(err => {
            document.getElementById('newModuleTitle').disabled = false;
            alert("Erreur de connexion internet.");
        });
    }

    function handleModuleKeyPress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addModule();
        }
    }

    function openAddModal() {
        addModuleModal.style.display = 'flex';
        setTimeout(() => document.getElementById('newModuleTitle').focus(), 100);
    }

    function closeAddModal() {
        addModuleModal.style.display = 'none';
        // Reset inputs on close
        document.getElementById('newModuleNumero').value = '';
        document.getElementById('newModuleCode').value = '';
        document.getElementById('newModuleTitle').value = '';
        document.getElementById('newModuleDuration').value = '';
        document.getElementById('newModuleLevel').value = '';
        document.getElementById('newModuleProfile').value = '';
        document.getElementById('newModulePedagogy').value = '';
        document.getElementById('newModuleAssessment').value = '';
        document.getElementById('newModuleBibliographie').value = '';
    }

    function filterModules() {
        const input = document.getElementById("searchModule");
        const filter = input.value.toLowerCase();
        const tbody = document.getElementById("modulesList");
        const trs = tbody.getElementsByTagName("tr");

        for (let i = 0; i < trs.length; i++) {
            // Ignore the "Aucun module trouvé" row
            if (trs[i].children.length === 1) continue;
            
            const titleCell = trs[i].getElementsByClassName("col-title")[0];
            const codeCell = trs[i].getElementsByClassName("col-code")[0]; // Also allow searching by code
            
            if (titleCell || codeCell) {
                const titleText = titleCell ? (titleCell.textContent || titleCell.innerText) : "";
                const codeText = codeCell ? (codeCell.textContent || codeCell.innerText) : "";
                
                if (titleText.toLowerCase().indexOf(filter) > -1 || codeText.toLowerCase().indexOf(filter) > -1) {
                    trs[i].style.display = "";
                } else {
                    trs[i].style.display = "none";
                }
            }       
        }
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target === addModuleModal) {
            closeAddModal();
        }
        if (e.target === editModuleModal) {
            closeEditModal();
        }
    });
</script>
@endsection
