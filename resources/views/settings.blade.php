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

    .table tbody tr:hover {
        background-color: #f5f8fa;
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }

</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Paramètres</h1>
</div>

<div class="settings-nav">
    <a href="{{ route('settings.index') }}" class="settings-nav-item active">Référentiels</a>
    <a href="{{ route('settings.modules') }}" class="settings-nav-item">Modules</a>
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

<div class="settings-container">
    <!-- Upload Form -->
    <div class="card">
        <h2 class="card-title">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            Ajouter un Référentiel
        </h2>
        
        <form action="{{ route('settings.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label" for="title">Nom du document</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="Ex: Référentiel Mathématiques 2026" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Type de référentiel</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="formation">Formation</option>
                    <option value="evaluation">Évaluation</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="file">Fichier (PDF ou Word)</label>
                <div class="file-drop-area" id="file-drop-area">
                    <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.36 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                    <span class="file-msg" id="file-msg">Glissez-déposez ou cliquez pour choisir</span>
                    <input type="file" id="file" name="file" class="file-input" accept=".pdf,.doc,.docx" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Importer le document
            </button>
        </form>
    </div>

    <!-- Documents List -->
    <div class="card">
        <h2 class="card-title">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            Vos Référentiels
        </h2>

        <div class="list-controls">
            <div style="flex-grow: 1;">
                <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un référentiel...">
            </div>
            <select id="sortSelect" class="form-control" style="width: auto;">
                <option value="date-desc">Plus récents</option>
                <option value="date-asc">Plus anciens</option>
                <option value="name-asc">Nom (A-Z)</option>
                <option value="name-desc">Nom (Z-A)</option>
                <option value="status-asc">Statut (Éval. > Form.)</option>
                <option value="status-desc">Statut (Form. > Éval.)</option>
            </select>
        </div>

        <ul class="docs-list" id="docsList">
            @forelse($referentiels as $ref)
                @php
                    $isPdf = pathinfo($ref->file_path, PATHINFO_EXTENSION) === 'pdf';
                    $iconClass = $isPdf ? 'pdf' : 'word';
                @endphp
                <li class="doc-item" data-title="{{ strtolower($ref->title) }}" data-date="{{ $ref->created_at->timestamp }}" data-status="{{ strtolower($ref->status) }}">
                    <div class="doc-info" id="doc-info-{{ $ref->id }}" style="flex-grow: 1;">
                        <div class="doc-icon {{ $iconClass }}">
                            @if($isPdf)
                            <!-- PDF Icon -->
                            <svg viewBox="0 0 24 24"><path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z"/></svg>
                            @else
                            <!-- Word Icon -->
                            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-2 16c-2.05 0-3.81-1.24-4.58-3h1.71c.63.9 1.68 1.5 2.87 1.5 1.19 0 2.24-.6 2.87-1.5h1.71c-.77 1.76-2.53 3-4.58 3zm0-10c-2.05 0-3.81 1.24-4.58 3h1.71c.63-.9 1.68-1.5 2.87-1.5 1.19 0 2.24.6 2.87 1.5h1.71c-.77-1.76-2.53-3-4.58-3z"/></svg>
                            @endif
                        </div>
                        <div class="doc-details">
                            <h4 style="display:flex; align-items:center;">
                                {{ $ref->title }}
                                <span class="badge badge-{{ $ref->status }}">
                                    {{ $ref->status === 'evaluation' ? 'Évaluation' : 'Formation' }}
                                </span>
                            </h4>
                            <p>Ajouté le {{ $ref->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', $ref->id) }}" method="POST" id="edit-form-{{ $ref->id }}" style="display: none; flex-grow: 1; margin: 0 15px;">
                        @csrf
                        @method('PUT')
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="title" value="{{ $ref->title }}" class="form-control" style="padding: 8px 12px; height: 35px;" required>
                            <select name="status" class="form-control" style="padding: 8px 12px; height: 35px; width: auto;" required>
                                <option value="formation" {{ $ref->status == 'formation' ? 'selected' : '' }}>Formation</option>
                                <option value="evaluation" {{ $ref->status == 'evaluation' ? 'selected' : '' }}>Évaluation</option>
                            </select>
                            <button type="submit" class="btn btn-primary" style="padding: 0 15px; height: 35px;">Ok</button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEdit({{ $ref->id }})" style="padding: 0 15px; height: 35px;">Annuler</button>
                        </div>
                    </form>

                    <div class="doc-actions" id="doc-actions-{{ $ref->id }}">
                        <button type="button" class="btn-icon btn-icon-edit" title="Modifier" onclick="toggleEdit({{ $ref->id }})">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.995.995 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </button>
                        <a href="{{ Storage::url($ref->file_path) }}" target="_blank" class="btn-icon btn-icon-view" title="Ouvrir">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        </a>
                        <form action="{{ route('settings.delete', $ref->id) }}" method="POST" onsubmit="return confirm('Supprimer ce document ?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-delete" title="Supprimer">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li style="text-align: center; color: #a1a5b7; padding: 30px;">
                    Aucun référentiel n'a été ajouté.
                </li>
            @endforelse
        </ul>
    </div>
</div>


<script>
    // File upload visual feedback
    const fileInput = document.getElementById('file');
    const fileMsg = document.getElementById('file-msg');
    const dropArea = document.getElementById('file-drop-area');

    fileInput.addEventListener('change', function(e) {
        if(this.files && this.files.length > 0) {
            fileMsg.textContent = this.files[0].name;
            dropArea.style.borderColor = 'var(--accent)';
            dropArea.style.backgroundColor = 'rgba(0, 158, 247, 0.05)';
        } else {
            fileMsg.textContent = 'Glissez-déposez ou cliquez pour choisir';
        }
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    // Search and Sort functionality
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const docsList = document.getElementById('docsList');
    
    function filterAndSort() {
        const query = searchInput.value.toLowerCase();
        const sortValue = sortSelect.value;
        
        let items = Array.from(docsList.querySelectorAll('.doc-item'));
        
        // Filter
        items.forEach(item => {
            const title = item.getAttribute('data-title');
            if (title.includes(query)) {
                item.classList.remove('item-hidden');
            } else {
                item.classList.add('item-hidden');
            }
        });
        
        // Sort visible items
        let visibleItems = items.filter(item => !item.classList.contains('item-hidden'));
        
        visibleItems.sort((a, b) => {
            if (sortValue === 'date-desc') {
                return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
            } else if (sortValue === 'date-asc') {
                return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
            } else if (sortValue === 'name-asc') {
                return a.getAttribute('data-title').localeCompare(b.getAttribute('data-title'));
            } else if (sortValue === 'name-desc') {
                return b.getAttribute('data-title').localeCompare(a.getAttribute('data-title'));
            } else if (sortValue === 'status-asc') {
                return a.getAttribute('data-status').localeCompare(b.getAttribute('data-status'));
            } else if (sortValue === 'status-desc') {
                return b.getAttribute('data-status').localeCompare(a.getAttribute('data-status'));
            }
            return 0;
        });
        
        // Append elements back in new order
        visibleItems.forEach(item => docsList.appendChild(item));
    }
    
    if(searchInput) searchInput.addEventListener('input', filterAndSort);
    if(sortSelect) sortSelect.addEventListener('change', filterAndSort);

    // Edit Toggle Functionality
    function toggleEdit(id) {
        const infoDiv = document.getElementById('doc-info-' + id);
        const actionsDiv = document.getElementById('doc-actions-' + id);
        const formDiv = document.getElementById('edit-form-' + id);
        
        if (formDiv.style.display === 'none') {
            infoDiv.style.display = 'none';
            actionsDiv.style.display = 'none';
            formDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'flex';
            actionsDiv.style.display = 'flex';
            formDiv.style.display = 'none';
        }
    }



    function handleModuleKeyPress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addModule();
        }
    }
</script>
@endsection
