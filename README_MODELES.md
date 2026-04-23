# Système de Modèles de Référentiels

Ce document décrit le système amélioré pour gérer les modèles de référentiels et valider l'extraction de modules.

## Fonctionnalités

### 1. Gestion des modèles
- **Création**: Ajouter de nouveaux modèles de référentiels
- **Visualisation**: Voir les détails et les colonnes extraites
- **Modification**: Mettre à jour les informations et valider les modèles
- **Suppression**: Supprimer les modèles non nécessaires

### 2. Extraction et validation
- **Extraction automatique**: Utilise le système existant du `ReferentielController`
- **Détection des colonnes**: Identifie automatiquement les colonnes présentes
- **Validation manuelle**: Permet de valider et corriger les extractions
- **Rapports de qualité**: Génère des rapports détaillés

### 3. Comparaison de modèles
- **Comparaison multi-modèles**: Compare les colonnes entre différents modèles
- **Analyse de cohérence**: Vérifie la cohérence des extractions
- **Statistiques**: Fournit des statistiques sur la qualité des modèles

## Routes disponibles

### Gestion des modèles
- `GET /referentiel-models` - Liste des modèles
- `GET /referentiel-models/create` - Formulaire de création
- `POST /referentiel-models` - Créer un modèle
- `GET /referentiel-models/{key}` - Voir un modèle
- `GET /referentiel-models/{key}/edit` - Modifier un modèle
- `PUT /referentiel-models/{key}` - Mettre à jour un modèle
- `DELETE /referentiel-models/{key}` - Supprimer un modèle
- `GET /referentiel-models/validation` - Page de validation
- `GET /referentiel-models/{key}/extract-columns` - Extraire les colonnes (API)

### Validation
- `POST /module-validation/validate-columns` - Valider les colonnes
- `POST /module-validation/compare-models` - Comparer des modèles
- `GET /module-validation/{key}/report` - Générer un rapport

## Structure des données

### Modèle de référentiel
```php
[
    'name' => 'Nom du modèle',
    'path' => '/chemin/vers/fichier.pdf',
    'validated' => false, // true si validé
    'description' => 'Description optionnelle',
    'code_comment' => 'Commentaire de code optionnel',
    'created_at' => '2024-01-01T00:00:00+00:00',
    'updated_at' => '2024-01-01T00:00:00+00:00',
]
```

### Colonnes de la table modules
- `referentiel_id` - ID du référentiel
- `code` - Code du module
- `title` - Titre du module
- `duration` - Durée en heures
- `level` - Niveau/Classe
- `teacher_profile` - Profil de l'enseignant
- `pedagogical_approach` - Approche pédagogique
- `assessment_type` - Type d'évaluation

## Utilisation

### 1. Créer un modèle
1. Allez dans `/referentiel-models/create`
2. Remplissez le formulaire avec:
   - Nom du modèle
   - Chemin vers le fichier PDF/Word
   - Description (optionnelle)
3. Cliquez sur "Créer le modèle"

### 2. Visualiser les colonnes extraites
1. Dans la liste des modèles, cliquez sur "Voir"
2. Les colonnes détectées s'affichent avec des badges:
   - Vert: colonne trouvée
   - Gris: colonne manquante

### 3. Valider un modèle
1. Allez dans `/referentiel-models/validation`
2. Sélectionnez un modèle dans la liste
3. Cliquez sur "Charger les données"
4. Vérifiez les colonnes et appliquez les corrections
5. Cliquez sur "Valider le modèle"

### 4. Comparer des modèles
1. Dans la page de validation, allez dans l'onglet "Comparaison"
2. Sélectionnez au moins 2 modèles
3. Cliquez sur "Comparer les modèles"
4. Analysez les résultats de cohérence

## Améliorations apportées

### Avant
- Script `test_pdf.php` basique
- Fichier `referentiel_test_models.php` manuel
- Pas d'interface web
- Validation manuelle complexe

### Après
- Interface web complète
- Gestion CRUD des modèles
- Validation assistée
- Comparaison multi-modèles
- Rapports de qualité
- Intégration avec le système existant

## Fichiers ajoutés/modifiés

### Nouveaux contrôleurs
- `app/Http/Controllers/ReferentielModelController.php`
- `app/Http/Controllers/ModuleValidationController.php`

### Nouvelles vues
- `resources/views/referentiel-models/index.blade.php`
- `resources/views/referentiel-models/create.blade.php`
- `resources/views/referentiel-models/edit.blade.php`
- `resources/views/referentiel-models/show.blade.php`
- `resources/views/referentiel-models/validation.blade.php`

### Routes
- Ajout des routes dans `routes/web.php`

## Prochaines améliorations possibles

1. **Import/Export**: Permettre d'importer/exporter des configurations de modèles
2. **Tests automatiques**: Intégrer des tests unitaires pour la validation
3. **Historique**: Garder un historique des modifications
4. **Notifications**: Alertes lorsque des modèles nécessitent une validation
5. **API REST**: API complète pour l'intégration externe
