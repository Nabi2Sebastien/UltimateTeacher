<?php

namespace App\Services\Referentiels;

use Smalot\PdfParser\Parser as PdfParser;

class BepAccReferentielExtractor
{
    public function extractFromPdf(string $path): array
    {
        return $this->parseModulesFromText($this->extractTextFromPdf($path));
    }

    public function extractTextFromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    public function parseModulesFromText(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $modules = [];
        $currentModule = null;
        $pendingField = null;
        $insideModulesSection = false;
        $hasModulesSectionHeading = preg_match('/^\s*VI[.\-]\s*MODULES DE FORMATION\s*$/uim', $text) === 1;

        foreach ($lines as $line) {
            $line = $this->normalizeInlineText($line);

            if ($line === '') {
                continue;
            }

            if ($currentModule && $pendingField === 'bibliographies' && $this->isBibliographyContinuation($line)) {
                $currentModule['bibliographies'] = $this->appendBibliographyLine($currentModule['bibliographies'] ?? [], $line);

                continue;
            }

            if ($pendingField === 'bibliographies') {
                $pendingField = null;
            }

            if ($currentModule && preg_match('/^(?:[IVX]+|\d+(?:\.\d+)?)\s*[\.\-\)]?\s*BIBLIOGRAPHIE\s*:?\s*(.*)$/ui', $line, $matches)) {
                $pendingField = 'bibliographies';

                if (!blank($matches[1] ?? '')) {
                    $currentModule['bibliographies'] = $this->appendBibliographyLine($currentModule['bibliographies'] ?? [], $matches[1]);
                }

                continue;
            }

            if (preg_match('/^\s*VI[.\-]\s*MODULES DE FORMATION\s*$/ui', $line)) {
                $insideModulesSection = true;

                continue;
            }

            if ($hasModulesSectionHeading && $insideModulesSection && preg_match('/^\s*IX[.\-]\s*TABLEAU DE REPARTITION.*$/ui', $line)) {
                break;
            }

            if ($hasModulesSectionHeading && !$insideModulesSection) {
                continue;
            }

            if (preg_match('/\.{4,}\s*\d+$/', $line)) {
                continue;
            }

            if (preg_match('/^MODULE\s+N(?:[Â°Âºo]|Â°|Ã‚Âº)?\s*0*([\d\w]+)(?:\s*[:\s]\s*(.+))?$/ui', $line, $matches)) {
                $this->pushParsedModule($modules, $currentModule);
                $title = $matches[2] ?? '';
                $duration = null;
                if (preg_match('/\(?\s*(\d+)\s*(?:[hH]|heures?)\s*\)?$/ui', $title, $durMatches)) {
                    $duration = (int) $durMatches[1];
                    $title = trim(str_replace($durMatches[0], '', $title));
                }
                $currentModule = $this->makeEmptyModule($matches[1], $title);
                if ($duration) $currentModule['duration'] = $duration;
                $pendingField = null;

                continue;
            }

            if (preg_match('/^MODULES?\s+0*([0-9]+(?:\.[0-9]+)?|[A-Z][0-9]+)(?:\s+SUITE)?(?:\s*[:\s]\s*(.+))?$/ui', $line, $matches)) {
                $this->pushParsedModule($modules, $currentModule);
                $title = $matches[2] ?? '';
                $duration = null;
                if (preg_match('/\(?\s*(\d+)\s*(?:[hH]|heures?)\s*\)?$/ui', $title, $durMatches)) {
                    $duration = (int) $durMatches[1];
                    $title = trim(str_replace($durMatches[0], '', $title));
                }
                $currentModule = $this->makeEmptyModule($matches[1], $title);
                if ($duration) $currentModule['duration'] = $duration;
                $pendingField = null;

                continue;
            }

            if (preg_match('/^SOUS[\s-]*MODULES?\s+0*([0-9]+)\s*\.?\s*0*([0-9]+)(?:\s*[:\s]\s*(.+))?\s*$/ui', $line, $matches)) {
                $subModuleNumber = $matches[1] . '.' . $matches[2];
                $subModuleTitle = $matches[3] ?? '';
                
                $duration = null;
                if (preg_match('/\(?\s*(\d+)\s*(?:[hH]|heures?)\s*\)?$/ui', $subModuleTitle, $durMatches)) {
                    $duration = (int) $durMatches[1];
                    $subModuleTitle = trim(str_replace($durMatches[0], '', $subModuleTitle));
                }

                $parentCode = $matches[1];
                $parentModuleTitle = $this->findParentModuleTitle($modules, $currentModule, $parentCode);

                $this->pushParsedModule($modules, $currentModule);
                $currentModule = $this->makeEmptyModule($subModuleNumber, $subModuleTitle);
                if ($duration) $currentModule['duration'] = $duration;
                $currentModule['parent_module'] = $parentModuleTitle;
                $pendingField = null;

                continue;
            }

            if ($pendingField && $this->isFieldContinuation($line)) {
                $currentModule[$pendingField] = $pendingField === 'level'
                    ? $this->normalizeInlineText(trim(($currentModule[$pendingField] ?? '') . ' ' . $line))
                    : $this->appendModuleValue($currentModule[$pendingField] ?? null, $line);

                continue;
            }

            $pendingField = null;

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:TITRE DU MODULE|INTITULÉ DU MODULE|INTITULE DU MODULE)(?:\s*\d+)?\s*:\s*(.+)$/ui', $line, $matches)) {
                if ($currentModule && !empty($currentModule['title'])) {
                    $this->pushParsedModule($modules, $currentModule);
                    $currentModule = null;
                }

                if (!$currentModule) {
                    $existingNumero = null;
                    $searchedTitle = $this->normalizeInlineText($matches[1]);
                    foreach ($modules as $mod) {
                        if (strcasecmp($mod['title'] ?? '', $searchedTitle) === 0) {
                            $existingNumero = $mod['numero'];
                            break;
                        }
                    }

                    if ($existingNumero) {
                        $currentModule = $this->makeEmptyModule($existingNumero, $searchedTitle);
                    } else {
                        $maxNumero = 0;
                        foreach ($modules as $mod) {
                            if (is_numeric($mod['numero'] ?? null)) {
                                $maxNumero = max($maxNumero, (int) $mod['numero']);
                            }
                        }
                        $currentModule = $this->makeEmptyModule((string) ($maxNumero + 1));
                    }
                }

                $currentModule['title'] = $matches[1];

                continue;
            }

            if (!$currentModule) {
                continue;
            }

            if (preg_match('/(?:Code du module|CODE DU MODULE|CODE)\s*:\s*([A-Z0-9][A-Z0-9\s-]*?)(?:\s+Dur(?:Ã©e|ee|e)\s*:|\s*$)/ui', $line, $matches)) {
                $currentModule['code'] = null; // Ignore code for Bep Acc
            }

            if (preg_match('/(?:DUR(?:É|E|EE)(?:E)?|VOLUME HORAIRE|TEMPS).*?(?:\s*[:\-]\s*|\s+)(\d+)\s*(?:[hH]|heures?|Heures?)/ui', $line, $matches)) {
                $currentModule['duration'] = (int) $matches[1];
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:NIVEAU|CLASSE)(?:[^:\-]*)(?:\s*[:\-]\s*|\s+)(.+?)(?:\s+Volume|\s*$)/ui', $line, $matches)) {
                $currentModule['level'] = $matches[1];
                $pendingField = 'level';
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?D(?:É|E)MARCHES?\s+P(?:É|E)DAGOGIQUES?\s*:?\s*(.*)$/ui', $line, $matches)) {
                $currentModule['pedagogical_approach'] = $matches[1];
                $pendingField = 'pedagogical_approach';
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?TYPE(?:S)?\s+D[\'’]?(?:É|E)PREUVE\s*:?\s*(.*)$/ui', $line, $matches)) {
                $currentModule['assessment_type'] = $matches[1];
                $pendingField = 'assessment_type';
            }
        }

        $this->pushParsedModule($modules, $currentModule);

        // Déduplication des modules par leur numéro pour éviter les répétitions (ex: "MODULE 1 SUITE")
        $uniqueModules = [];
        foreach ($modules as $module) {
            $numero = $module['numero'] ?? null;
            if ($numero) {
                if (!isset($uniqueModules[$numero])) {
                    $uniqueModules[$numero] = $module;
                } else {
                    // Fusionner les champs si le module est répété
                    foreach (['duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'] as $field) {
                        if (empty($uniqueModules[$numero][$field]) && !empty($module[$field])) {
                            $uniqueModules[$numero][$field] = $module[$field];
                        } elseif (!empty($uniqueModules[$numero][$field]) && !empty($module[$field]) && $uniqueModules[$numero][$field] !== $module[$field]) {
                            // Ajouter les informations supplémentaires si elles sont différentes
                            if (!str_contains($uniqueModules[$numero][$field], $module[$field])) {
                                $uniqueModules[$numero][$field] .= ', ' . $module[$field];
                            }
                        }
                    }

                    if (!empty($module['bibliographies'])) {
                        $uniqueModules[$numero]['bibliographies'] = array_values(array_unique(array_merge(
                            $uniqueModules[$numero]['bibliographies'] ?? [],
                            $module['bibliographies']
                        )));
                    }
                }
            } else {
                $uniqueModules[] = $module;
            }
        }

        $finalModules = array_values($uniqueModules);

        foreach ($finalModules as &$module) {
            $module['bibliographies'] = $this->parseBibliographyEntries($module['bibliographies'] ?? []);
        }
        unset($module);
        
        usort($finalModules, function ($a, $b) {
            $numA = $a['numero'] ?? '';
            $numB = $b['numero'] ?? '';
            
            if ($numA === '' && $numB === '') return 0;
            if ($numA === '') return 1;
            if ($numB === '') return -1;
            
            // Nettoyage des caractères non numériques ou de ponctuation avant comparaison
            // ex: si numero est "1" ou "1.1" version_compare fonctionnera parfaitement
            return version_compare($numA, $numB);
        });

        return $finalModules;
    }

    private function findParentModuleTitle(array $modules, ?array $currentModule, string $parentCode): ?string
    {
        foreach ($modules as $module) {
            if (($module['numero'] ?? '') === $parentCode) {
                return $module['title'] ?? null;
            }
        }

        if ($currentModule !== null && ($currentModule['numero'] ?? '') === $parentCode) {
            return $currentModule['title'] ?? null;
        }

        return null;
    }

    private function isBibliographyContinuation(string $line): bool
    {
        if ($line === '') {
            return false;
        }

        // Stop bibliography section if we hit a numbered section header (e.g., "10.", "X.", etc.)
        if (preg_match('/^(?:\d+|[IVX]+)\s*[\.\-\)]\s*(?:[A-Z]|MODULE|TABLEAU|DESCRIPTION)/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:MODULES?|SOUS[\s-]*MODULES?|TITRE DU MODULE|CODE|DUR(?:Ã‰|E|EE)E|DUREE|NIVEAU|CLASSE|OBJECTIF|PLACE DANS LE REFERENTIEL|RÃƒâ€LE ET IMPORTANCE|ROLE ET IMPORTANCE|CONTENUS ESSENTIELS|TYPE(?:S)?\s+D|D(?:Ã‰|E)MARCHES?|TABLEAU DE REPARTITION)/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:Inspection de l[\'â€™]?enseignement|Page\s+\d+|NB\s*:)/ui', $line)) {
            return false;
        }

        return true;
    }

    private function appendBibliographyLine(array $bibliographies, string $line): array
    {
        $line = $this->normalizeInlineText($line);

        if ($line !== '') {
            $bibliographies[] = $line;
        }

        return $bibliographies;
    }

    private function parseBibliographyEntries(array $lines): array
    {
        $entries = [];
        
        // Joindre toutes les lignes avec un séparateur pour traiter le texte comme un bloc
        $fullText = implode(' ', array_map(fn ($line) => $this->normalizeInlineText($line), $lines));
        
        // Diviser sur les séparateurs de livres:
        // Les formats supportés:
        // 1. Numéros avec point: 1., 2., 3., 9., etc.
        // 2. Lettres avec point: a., b., c., d., etc.
        // 3. Tirets standards ou spéciaux: "- " ou "− " (tiret bas Unicode U+2212)
        // 4. Points-virgules suivis de tirets: "; - " ou "; − "
        // 5. Points-virgules seuls: "; "
        // Pattern: divise avant numéro/lettre suivi d'un point et espace,
        // ou avant un tiret (standard ou Unicode) suivi d'espace et majuscule,
        // ou avant un point-virgule
        $books = preg_split('/\s+(?=\d+\.\s+|[a-z]\.\s+|[\-−]\s+[A-Z]|;\s*[\-−]\s+|;\s+)/u', $fullText);
        
        foreach ($books as $book) {
            $book = $this->normalizeInlineText(trim($book));
            if ($book === '' || strlen($book) < 5) {
                continue;
            }
            
            // Supprimer les catégories (Windows, Traitement de textes, Tableurs, Internet, etc.)
            if (preg_match('/^(?:Windows|Traitement\s+de\s+textes?|Tableurs?|Internet|Applications?|Bureautique|Revues?\s+périodiques?)\s*[\-−]?\s*$/ui', $book)) {
                continue;
            }
            
            // Enlever les puces ou numéros/lettres initiales (1., a., b., c., etc. ou - ou −)
            $book = preg_replace('/^\d+\.\s+/', '', $book) ?? $book;
            $book = preg_replace('/^[a-z]\.\s+/', '', $book) ?? $book;
            $book = preg_replace('/^[\-−]\s+/', '', $book) ?? $book;
            $book = preg_replace('/^;\s*[\-−]?\s*/', '', $book) ?? $book;
            $book = $this->normalizeInlineText(trim($book));
            
            if ($book !== '' && strlen($book) > 5) {
                $entry = $this->parseBibliographyEntry([$book]);
                if (!blank($entry['raw_text'] ?? null)) {
                    $entries[] = $entry;
                }
            }
        }

        return array_values($entries);
    }

    private function parseBibliographyEntry(array $lines): array
    {
        $rawText = $this->normalizeInlineText(implode(', ', $lines));
        $entry = [
            'author' => null,
            'title' => null,
            'publisher' => null,
            'year' => null,
            'pages' => null,
            'raw_text' => $rawText,
        ];

        if (preg_match('/\b(19|20)\d{2}\b/u', $rawText, $matches, PREG_OFFSET_CAPTURE)) {
            $entry['year'] = $matches[0][0];
        }

        if (preg_match('/\b(\d+)\s*p(?:ages?)?\.?\b/ui', $rawText, $matches)) {
            $entry['pages'] = $matches[1];
        }

        $parts = array_values(array_filter(
            array_map(fn ($part) => $this->normalizeInlineText($part), explode(',', $rawText)),
            static fn ($part) => $part !== ''
        ));

        if (count($parts) >= 2) {
            $entry['author'] = $parts[0];
            $entry['title'] = $parts[1];
        }

        foreach ($parts as $part) {
            if (preg_match('/^(?:Edition|Editions|Ed\.?)\s+(.+)$/ui', $part, $matches)) {
                $publisher = $this->normalizeInlineText($matches[1]);
                $publisher = preg_replace('/\b(19|20)\d{2}\b.*$/u', '', $publisher) ?? $publisher;
                $entry['publisher'] = $this->normalizeInlineText($publisher);
                break;
            }
        }

        if (!$entry['publisher'] && count($parts) >= 3) {
            foreach ($parts as $part) {
                if (!preg_match('/\b(19|20)\d{2}\b/u', $part) && !preg_match('/\b\d+\s*p(?:ages?)?\.?\b/ui', $part) && $part !== $entry['author'] && $part !== $entry['title']) {
                    $entry['publisher'] = $part;
                    break;
                }
            }
        }

        return $entry;
    }

    private function isFieldContinuation(string $line): bool
    {
        if ($line === '' || preg_match('/^MODULES?\s+/ui', $line)) {
            return false;
        }

        if (preg_match('/^Inspection de l[\'’]?enseignement.*\bPage\s+\d+\b/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:Page\s+\d+|[IVX]+\.\s+TABLEAU|[IVX]+\.\s+DESCRIPTION|BIBLIOGRAPHIE|NB\s*:)/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:TITRE DU MODULE|INTITULÉ DU MODULE|INTITULE DU MODULE|CODE|CODE DU MODULE|Code du module|DUR(?:É|E|EE)E|DURÉE DU MODULE|DUREE DU MODULE|NIVEAU|OBJECTIF VISE|PLACE DANS LE REFERENTIEL|RÃ”LE ET IMPORTANCE|ROLE ET IMPORTANCE|CONTENUS ESSENTIELS|TYPE(?:S)?\s+D|D(?:É|E)MARCHES?\s+P)/ui', $line)) {
            return false;
        }

        return !preg_match('/^\d+\s*[-.]?\s*\p{Lu}/u', $line);
    }

    private function appendModuleValue(?string $currentValue, string $line): string
    {
        $line = $this->normalizeInlineText(preg_replace('/^\-\s*/', '', trim($line)) ?? trim($line));

        if (blank($currentValue)) {
            return $line;
        }

        return trim($currentValue . ', ' . $line, " \t\n\r\0\x0B,");
    }

    private function normalizeInlineText(?string $value): string
    {
        $value = trim((string) $value);
        $value = str_replace("\u{00A0}", ' ', $value);
        $value = str_replace(
            ["\u{0091}", "\u{0092}", "\u{0096}", "\u{0097}"],
            ["'", "'", '-', '-'],
            $value
        );
        $value = preg_replace('/Inspection de l[\'’]?enseignement.*?\bPage\s+\d+\b\s*/ui', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(['d\'?uvre', 'D\'?uvre'], ["d'oeuvre", "D'oeuvre"], $value);
        $value = preg_replace('/,\s*,+/', ', ', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B,;");
    }

    private function makeEmptyModule(?string $numero = null, ?string $title = null): array
    {
        return [
            'numero' => $this->normalizeInlineText((string) $numero),
            'code' => null,
            'parent_module' => null,
            'title' => $this->normalizeInlineText((string) $title),
            'duration' => null,
            'level' => null,
            'teacher_profile' => null,
            'pedagogical_approach' => null,
            'assessment_type' => null,
            'bibliographies' => [],
        ];
    }

    private function normalizeParsedModule(array $module): array
    {
        $module['numero'] = $this->normalizeInlineText((string) ($module['numero'] ?? ''));
        $module['code'] = $this->normalizeInlineText((string) ($module['code'] ?? ''));
        $module['parent_module'] = $this->normalizeInlineText((string) ($module['parent_module'] ?? ''));
        $module['title'] = $this->normalizeInlineText((string) ($module['title'] ?? ''));
        $module['level'] = $this->normalizeInlineText((string) ($module['level'] ?? ''));
        $module['pedagogical_approach'] = $this->normalizeInlineText((string) ($module['pedagogical_approach'] ?? ''));
        $module['assessment_type'] = $this->normalizeInlineText((string) ($module['assessment_type'] ?? ''));

        $module['numero'] = $module['numero'] !== '' ? $module['numero'] : null;
        $module['code'] = $module['code'] !== '' ? (preg_replace('/\s+/', ' ', $module['code']) ?? $module['code']) : null;
        $module['parent_module'] = $module['parent_module'] !== '' ? $module['parent_module'] : null;
        $module['title'] = $module['title'] !== '' ? $module['title'] : null;
        $module['level'] = $module['level'] !== '' ? $module['level'] : null;
        $module['pedagogical_approach'] = $module['pedagogical_approach'] !== '' ? $module['pedagogical_approach'] : null;
        $module['assessment_type'] = $module['assessment_type'] !== '' ? $module['assessment_type'] : null;

        return $module;
    }

    private function pushParsedModule(array &$modules, ?array $currentModule): void
    {
        if (!$currentModule) {
            return;
        }

        $currentModule = $this->normalizeParsedModule($currentModule);

        if (!empty($currentModule['title'])) {
            $modules[] = $currentModule;
        }
    }
}
