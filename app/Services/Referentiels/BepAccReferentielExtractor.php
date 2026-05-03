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

            if (preg_match('/^MODULE\s+N(?:[Â°Âºo]|Ã‚Â°|Ã‚Âº)?\s*0*([\d\w]+)\s*:\s*(.+)$/ui', $line, $matches)) {
                $this->pushParsedModule($modules, $currentModule);
                $currentModule = $this->makeEmptyModule('M' . $matches[1], $matches[2]);
                $pendingField = null;

                continue;
            }

            if (preg_match('/^MODULES?\s+0*([0-9]+(?:\.[0-9]+)?|[A-Z][0-9]+)(?:\s+SUITE)?\s*:\s*(.+)$/ui', $line, $matches)) {
                $this->pushParsedModule($modules, $currentModule);
                $currentModule = $this->makeEmptyModule('M' . $matches[1], $matches[2]);
                $pendingField = null;

                continue;
            }

            if (preg_match('/^SOUS[\s-]*MODULES?\s+0*([0-9]+)\s*\.?\s*0*([0-9]+)(?:\s*[:\s]\s*(.+))?\s*$/ui', $line, $matches)) {
                $subModuleNumber = $matches[1] . '.' . $matches[2];
                $subModuleTitle = $matches[3] ?? '';
                $parentCode = 'M' . $matches[1];
                $parentModuleTitle = $this->findParentModuleTitle($modules, $currentModule, $parentCode);

                $this->pushParsedModule($modules, $currentModule);
                $currentModule = $this->makeEmptyModule('M' . $subModuleNumber, $subModuleTitle);
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

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:TITRE DU MODULE|INTITULÃ‰ DU MODULE|INTITULE DU MODULE)(?:\s*\d+)?\s*:\s*(.+)$/ui', $line, $matches)) {
                if ($currentModule && !empty($currentModule['title'])) {
                    $this->pushParsedModule($modules, $currentModule);
                    $currentModule = null;
                }

                if (!$currentModule) {
                    $currentModule = $this->makeEmptyModule('M' . (count($modules) + 1));
                }

                $currentModule['title'] = $matches[1];

                continue;
            }

            if (!$currentModule) {
                continue;
            }

            if (preg_match('/(?:Code du module|CODE DU MODULE|CODE)\s*:\s*([A-Z0-9][A-Z0-9\s-]*?)(?:\s+Dur(?:Ã©e|ee|e)\s*:|\s*$)/ui', $line, $matches)) {
                $currentModule['code'] = $matches[1];
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:DUR(?:Ã‰|E|EE)(?:E)?(?:\s+DU\s+MODULE)?|DURÃ‰E\s+DU\s+MODULE|DUREE\s+DU\s+MODULE)\s*:\s*(\d+)/ui', $line, $matches)) {
                $currentModule['duration'] = (int) $matches[1];
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?NIVEAU\s*:\s*(.+?)(?:\s+Volume|\s*$)/ui', $line, $matches)) {
                $currentModule['level'] = $matches[1];
                $pendingField = 'level';
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?D(?:Ã‰|E)MARCHES?\s+P(?:Ã‰|E)DAGOGIQUES?\s*:?\s*(.*)$/ui', $line, $matches)) {
                $currentModule['pedagogical_approach'] = $matches[1];
                $pendingField = 'pedagogical_approach';
            }

            if (preg_match('/^(?:\d+\s*[-.]?\s*)?TYPE(?:S)?\s+D[\'â€™]?(?:Ã‰|E)PREUVE\s*:?\s*(.*)$/ui', $line, $matches)) {
                $currentModule['assessment_type'] = $matches[1];
                $pendingField = 'assessment_type';
            }
        }

        $this->pushParsedModule($modules, $currentModule);

        return $modules;
    }

    private function findParentModuleTitle(array $modules, ?array $currentModule, string $parentCode): ?string
    {
        foreach ($modules as $module) {
            if (($module['code'] ?? '') === $parentCode) {
                return $module['title'] ?? null;
            }
        }

        if ($currentModule !== null && ($currentModule['code'] ?? '') === $parentCode) {
            return $currentModule['title'] ?? null;
        }

        return null;
    }

    private function isFieldContinuation(string $line): bool
    {
        if ($line === '' || preg_match('/^MODULES?\s+/ui', $line)) {
            return false;
        }

        if (preg_match('/^Inspection de l[\'â€™]?enseignement.*\bPage\s+\d+\b/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:Page\s+\d+|[IVX]+\.\s+TABLEAU|[IVX]+\.\s+DESCRIPTION|BIBLIOGRAPHIE|NB\s*:)/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:TITRE DU MODULE|INTITULÃ‰ DU MODULE|INTITULE DU MODULE|CODE|CODE DU MODULE|Code du module|DUR(?:Ã‰|E|EE)E|DURÃ‰E DU MODULE|DUREE DU MODULE|NIVEAU|OBJECTIF VISE|PLACE DANS LE REFERENTIEL|RÃ”LE ET IMPORTANCE|ROLE ET IMPORTANCE|CONTENUS ESSENTIELS|TYPE(?:S)?\s+D|D(?:Ã‰|E)MARCHES?\s+P)/ui', $line)) {
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
        $value = preg_replace('/Inspection de l[\'â€™]?enseignement.*?\bPage\s+\d+\b\s*/ui', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(['d\'?uvre', 'D\'?uvre'], ["d'oeuvre", "D'oeuvre"], $value);
        $value = preg_replace('/,\s*,+/', ', ', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B,;");
    }

    private function makeEmptyModule(?string $code = null, ?string $title = null): array
    {
        return [
            'code' => $this->normalizeInlineText((string) $code),
            'parent_module' => null,
            'title' => $this->normalizeInlineText((string) $title),
            'duration' => null,
            'level' => null,
            'teacher_profile' => null,
            'pedagogical_approach' => null,
            'assessment_type' => null,
        ];
    }

    private function normalizeParsedModule(array $module): array
    {
        $module['code'] = $this->normalizeInlineText((string) ($module['code'] ?? ''));
        $module['parent_module'] = $this->normalizeInlineText((string) ($module['parent_module'] ?? ''));
        $module['title'] = $this->normalizeInlineText((string) ($module['title'] ?? ''));
        $module['level'] = $this->normalizeInlineText((string) ($module['level'] ?? ''));
        $module['pedagogical_approach'] = $this->normalizeInlineText((string) ($module['pedagogical_approach'] ?? ''));
        $module['assessment_type'] = $this->normalizeInlineText((string) ($module['assessment_type'] ?? ''));

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
