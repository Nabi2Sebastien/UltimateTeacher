<?php

namespace App\Http\Controllers;

use App\Models\Bibliographie;
use App\Models\Module;
use App\Models\Referentiel;
use App\Services\Referentiels\BepAccReferentielExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\PreserveText;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ReferentielController extends Controller
{
    public function index()
    {
        $referentiels = Referentiel::latest()->get();

        return view('settings', compact('referentiels'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:formation,evaluation',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('referentiels', $fileName, 'public');

        $referentiel = Referentiel::create([
            'title' => $request->title,
            'status' => $request->status,
            'file_path' => $filePath,
        ]);

        $this->extractModules($referentiel);

        return redirect()->route('settings.index')->with('success', 'Referentiel ajoute et modules extraits avec succes.');
    }

    private function extractModules(Referentiel $referentiel): void
    {
        $path = storage_path('app/public/' . $referentiel->file_path);

        if (!file_exists($path)) {
            return;
        }

        try {
            $text = $this->extractTextFromDocument($path, $referentiel->file_path);

            if (blank($text)) {
                return;
            }

            $modules = $this->isBepAccReferentiel($referentiel)
                ? app(BepAccReferentielExtractor::class)->parseModulesFromText($text)
                : $this->parseModulesFromText($text);

            foreach ($modules as $module) {
                $bibliographies = $module['bibliographies'] ?? [];
                unset($module['bibliographies']);

                $createdModule = $referentiel->modules()->create($module);

                $bibliographyRows = [];

                foreach ($bibliographies as $bibliographie) {
                    $bibliographyRows[] = array_merge(is_array($bibliographie) ? [
                        'author' => $bibliographie['author'] ?? null,
                        'title' => $bibliographie['title'] ?? null,
                        'publisher' => $bibliographie['publisher'] ?? null,
                        'year' => $bibliographie['year'] ?? null,
                        'pages' => $bibliographie['pages'] ?? null,
                        'raw_text' => $bibliographie['raw_text'] ?? null,
                    ] : [
                        'raw_text' => (string) $bibliographie,
                    ], [
                        'module_id' => $createdModule->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($bibliographyRows !== []) {
                    $createdModule->bibliographies()->insert($bibliographyRows);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erreur lors de l extraction du document', [
                'referentiel_id' => $referentiel->id,
                'file_path' => $referentiel->file_path,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function extractTextFromDocument(string $path, string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractTextFromPdf($path),
            'docx' => $this->extractTextFromDocx($path),
            'doc' => $this->extractTextFromLegacyWord($path),
            default => throw new \RuntimeException("Type de fichier non supporte: {$extension}"),
        };
    }

    private function isBepAccReferentiel(Referentiel $referentiel): bool
    {
        $value = mb_strtolower($referentiel->title . ' ' . basename($referentiel->file_path));

        return str_contains($value, 'bep acc')
            || (str_contains($value, 'rfc') && str_contains($value, 'acc'));
    }

    private function extractTextFromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    private function extractTextFromDocx(string $path): string
    {
        if (class_exists(\ZipArchive::class)) {
            try {
                return $this->extractTextFromWord($path, 'Word2007');
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        return $this->extractTextFromDocxXml($path);
    }

    private function extractTextFromWord(string $path, string $reader): string
    {
        $document = IOFactory::load($path, $reader);
        $chunks = [];

        foreach ($document->getSections() as $section) {
            $this->collectWordText($section, $chunks);
        }

        $lines = array_filter(
            array_map(static fn ($line) => trim((string) $line), $chunks),
            static fn ($line) => $line !== ''
        );

        return implode("\n", $lines);
    }

    private function extractTextFromDocxXml(string $path): string
    {
        $quotedPath = str_replace("'", "''", $path);
        $script = <<<'PS'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$ProgressPreference = 'SilentlyContinue'
$zipPath = '__DOCX_PATH__'
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $entry = $archive.GetEntry('word/document.xml')
    if ($null -eq $entry) { exit 1 }
    $reader = New-Object System.IO.StreamReader($entry.Open())
    try {
        [xml]$xml = $reader.ReadToEnd()
    } finally {
        $reader.Dispose()
    }
} finally {
    $archive.Dispose()
}
$ns = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
$ns.AddNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main')
$paragraphs = $xml.SelectNodes('//w:p', $ns)
foreach ($p in $paragraphs) {
    $texts = $p.SelectNodes('.//w:t', $ns)
    if ($texts.Count -eq 0) { continue }
    $line = (($texts | ForEach-Object { $_.'#text' }) -join '').Trim()
    if ($line -ne '') { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8; Write-Output $line }
}
PS;
        $script = str_replace('__DOCX_PATH__', $quotedPath, $script);

        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $command = sprintf('powershell -NoProfile -EncodedCommand %s', escapeshellarg($encoded));

        $output = [];
        $exitCode = null;
        exec($command, $output, $exitCode);

        $output = array_values(array_filter(
            $output,
            static fn ($line) => !str_starts_with(trim((string) $line), '#< CLIXML')
                && !preg_match('/^\s*<Objs\b/i', (string) $line)
                && !preg_match('/^\s*<Obj\b/i', (string) $line)
                && !preg_match('/^\s*<TN\b/i', (string) $line)
                && !preg_match('/^\s*<MS\b/i', (string) $line)
                && !preg_match('/^\s*<I64\b/i', (string) $line)
                && !preg_match('/^\s*<PR\b/i', (string) $line)
                && !preg_match('/^\s*<AV>/i', (string) $line)
                && !preg_match('/^\s*</', (string) $line)
        ));

        if ($exitCode !== 0 || empty($output)) {
            throw new \RuntimeException('Impossible d extraire le contenu du fichier DOCX.');
        }

        return trim(implode("\n", $output));
    }

    private function extractTextFromLegacyWord(string $path): string
    {
        $text = $this->extractTextWithAntiword($path);

        if (!blank($text)) {
            return $text;
        }

        $text = $this->extractTextFromWord($path, 'MsDoc');

        return $this->looksLikeGarbledText($text) ? '' : $text;
    }

    private function extractTextWithAntiword(string $path): ?string
    {
        $binary = $this->findAntiwordBinary();

        if (!$binary) {
            return null;
        }

        $command = sprintf('"%s" %s 2>NUL', $binary, escapeshellarg($path));
        $output = [];
        $exitCode = null;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            return null;
        }

        $text = implode("\n", $output);
        $encoding = mb_detect_encoding($text, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';

        return trim(mb_convert_encoding($text, 'UTF-8', $encoding));
    }

    private function findAntiwordBinary(): ?string
    {
        $candidates = array_filter([
            env('ANTIWORD_PATH'),
            base_path('bin/antiword.exe'),
            'C:\\laragon\\bin\\git\\mingw64\\bin\\antiword.exe',
            'C:\\Program Files\\Antiword\\antiword.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function looksLikeGarbledText(string $text): bool
    {
        if (blank($text)) {
            return true;
        }

        preg_match_all('/[A-Za-zÀ-ÿ]/u', $text, $latinMatches);
        preg_match_all('/[^\x00-\x7F]/u', $text, $nonAsciiMatches);

        $latinCount = count($latinMatches[0]);
        $nonAsciiCount = count($nonAsciiMatches[0]);

        return $latinCount > 0 && $nonAsciiCount > ($latinCount * 2);
    }

    private function collectWordText(mixed $element, array &$chunks): void
    {
        if ($element instanceof Section || $element instanceof TextRun || $element instanceof ListItemRun || $element instanceof Cell) {
            foreach ($element->getElements() as $child) {
                $this->collectWordText($child, $chunks);
            }

            return;
        }

        if ($element instanceof Table) {
            foreach ($element->getRows() as $row) {
                $this->collectWordText($row, $chunks);
            }

            return;
        }

        if ($element instanceof Row) {
            foreach ($element->getCells() as $cell) {
                $this->collectWordText($cell, $chunks);
            }

            return;
        }

        if ($element instanceof Text || $element instanceof PreserveText || $element instanceof Title) {
            $chunks[] = $element->getText();

            return;
        }

        if ($element instanceof TextBreak) {
            $chunks[] = '';

            return;
        }

        if ($element instanceof AbstractElement && method_exists($element, 'getText')) {
            $chunks[] = (string) $element->getText();
        }
    }

    private function parseModulesFromText(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $modules = [];
        $currentModule = null;
        $pendingField = null;
        $moduleCounter = 0;
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

            if (preg_match('/^MODULE\s+N(?:[°ºo]|Â°|Âº)?\s*0*([\d\w]+)(?:\s*[:\s]\s*(.+))?$/ui', $line, $matches)) {
                $this->pushParsedModule($modules, $currentModule);
                $title = $matches[2] ?? '';
                $duration = null;
                if (preg_match('/\(?\s*(\d+)\s*(?:[hH]|heures?)\s*\)?$/ui', $title, $durMatches)) {
                    $duration = (int) $durMatches[1];
                    $title = trim(str_replace($durMatches[0], '', $title));
                }
                $currentModule = $this->makeEmptyModule('M' . $matches[1], $title);
                if ($duration) $currentModule['duration'] = $duration;
                $pendingField = null;
                $moduleCounter = is_numeric($matches[1]) ? (int) $matches[1] : $moduleCounter;

                continue;
            }

            // MODULE X Suite is used in some PDFs to indicate continuation of a module
            // We should ignore it if it's just a section header without a title
            if (preg_match('/^MODULES?\s+0*([0-9]+)(?:\s+SUITE)?$/ui', $line, $matches)) {
                // Check if the next non-empty line is a SOUS-MODULE - if so, skip this line
                // because the sub-module will create the proper entry
                $skipModuleSuite = false;
                foreach ($lines as $nextLine) {
                    $nextLineNormalized = $this->normalizeInlineText($nextLine);
                    if ($nextLineNormalized === '') continue;
                    if (preg_match('/^SOUS[\s-]*MODULES?\s+/ui', $nextLineNormalized)) {
                        $skipModuleSuite = true;
                    }
                    break;
                }
                if (!$skipModuleSuite) {
                    $this->pushParsedModule($modules, $currentModule);
                    $currentModule = $this->makeEmptyModule('M' . $matches[1]);
                    $pendingField = null;
                    $moduleCounter = is_numeric($matches[1]) ? (int) $matches[1] : $moduleCounter;
                }

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
                $currentModule = $this->makeEmptyModule('M' . $matches[1], $title);
                if ($duration) $currentModule['duration'] = $duration;
                $pendingField = null;
                $moduleCounter = is_numeric($matches[1]) ? (int) $matches[1] : $moduleCounter;

                continue;
            }

            // Detection des sous-modules: SOUS-MODULE X.Y : Titre ou SOUS-MODULE X.Y Titre
            // Pattern flexible pour capturer SOUS-MODULE X.Y avec titre optionnel
            if (preg_match('/^SOUS[\s-]*MODULES?\s+0*([0-9]+)\s*\.?\s*0*([0-9]+)(?:\s*[:\s]\s*(.+))?\s*$/ui', $line, $matches)) {
                $subModuleNumber = $matches[1] . '.' . $matches[2];
                $subModuleTitle = $matches[3] ?? '';
                
                $duration = null;
                if (preg_match('/\(?\s*(\d+)\s*(?:[hH]|heures?)\s*\)?$/ui', $subModuleTitle, $durMatches)) {
                    $duration = (int) $durMatches[1];
                    $subModuleTitle = trim(str_replace($durMatches[0], '', $subModuleTitle));
                }

                $parentNumber = $matches[1];
                $parentCode = 'M' . $parentNumber;

                // Trouver le module parent pour recuperer son titre
                $parentModuleTitle = null;
                foreach ($modules as $mod) {
                    if (($mod['code'] ?? '') === $parentCode) {
                        $parentModuleTitle = $mod['title'] ?? null;
                        break;
                    }
                }
                if ($parentModuleTitle === null && $currentModule !== null && ($currentModule['code'] ?? '') === $parentCode) {
                    $parentModuleTitle = $currentModule['title'] ?? null;
                }

                $this->pushParsedModule($modules, $currentModule);
                $currentModule = $this->makeEmptyModule('M' . $subModuleNumber, $subModuleTitle);
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
                    $existingCode = null;
                    $searchedTitle = $this->normalizeInlineText($matches[1]);
                    foreach ($modules as $mod) {
                        if (strcasecmp($mod['title'] ?? '', $searchedTitle) === 0) {
                            $existingCode = $mod['code'];
                            break;
                        }
                    }

                    if ($existingCode) {
                        $currentModule = $this->makeEmptyModule($existingCode, $searchedTitle);
                        if (preg_match('/^M(\d+)$/', $existingCode, $codeMatch)) {
                            $moduleCounter = (int) $codeMatch[1];
                        }
                    } else {
                        $maxNumero = 0;
                        foreach ($modules as $mod) {
                            $code = $mod['code'] ?? '';
                            if (preg_match('/^M(\d+)$/', $code, $codeMatch)) {
                                $maxNumero = max($maxNumero, (int) $codeMatch[1]);
                            }
                        }
                        $moduleCounter = $maxNumero + 1;
                        $currentModule = $this->makeEmptyModule('M' . $moduleCounter);
                    }
                }

                $currentModule['title'] = $matches[1];

                continue;
            }

            if (!$currentModule) {
                continue;
            }

            if (preg_match('/(?:Code du module|CODE DU MODULE|CODE)\s*:\s*([A-Z0-9][A-Z0-9\s-]*?)(?:\s+Dur(?:ée|ee|e)\s*:|\s*$)/ui', $line, $matches)) {
                $currentModule['code'] = $matches[1];
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

        if (count($modules) === 0) {
            $fallbackModules = $this->parseRmiModules($lines);

            if (count($fallbackModules) > 0) {
                return $fallbackModules;
            }

            $fallbackModules = $this->parseCpgeModules($lines);

            if (count($fallbackModules) > 0) {
                return $fallbackModules;
            }

            return $this->parseSectionModules($lines);
        }


        // Déduplication des modules par leur code pour éviter les répétitions (ex: "MODULE 1 SUITE")
        $uniqueModules = [];
        foreach ($modules as $module) {
            $code = $module['code'] ?? null;
            if ($code) {
                if (!isset($uniqueModules[$code])) {
                    $uniqueModules[$code] = $module;
                } else {
                    // Fusionner les champs si le module est répété
                    foreach (['duration', 'level', 'teacher_profile', 'pedagogical_approach', 'assessment_type'] as $field) {
                        if (empty($uniqueModules[$code][$field]) && !empty($module[$field])) {
                            $uniqueModules[$code][$field] = $module[$field];
                        } elseif (!empty($uniqueModules[$code][$field]) && !empty($module[$field]) && $uniqueModules[$code][$field] !== $module[$field]) {
                            // Ajouter les informations supplémentaires si elles sont différentes
                            if (!str_contains($uniqueModules[$code][$field], $module[$field])) {
                                $uniqueModules[$code][$field] .= ', ' . $module[$field];
                            }
                        }
                    }
                }
            } else {
                $uniqueModules[] = $module;
            }
        }

        $finalModules = array_values($uniqueModules);
        
        usort($finalModules, function ($a, $b) {
            $codeA = $a['code'] ?? '';
            $codeB = $b['code'] ?? '';
            
            if ($codeA === '' && $codeB === '') return 0;
            if ($codeA === '') return 1;
            if ($codeB === '') return -1;
            
            // On enlève les caractères non numériques comme le 'M' pour le tri naturel
            $numA = preg_replace('/[^0-9.]/', '', $codeA);
            $numB = preg_replace('/[^0-9.]/', '', $codeB);
            
            return version_compare($numA, $numB);
        });

        return $finalModules;
    }

    private function parseRmiModules(array $lines): array
    {
        // Modele RMI : format en blocs "Module" suivi du titre puis de la ligne "X heures".
        $modules = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = $this->normalizeInlineText($lines[$i]);

            if (!preg_match('/^Module$/ui', $line)) {
                continue;
            }

            $title = null;
            $duration = null;

            for ($j = $i + 1; $j < min($i + 12, $count); $j++) {
                $candidate = $this->normalizeInlineText($lines[$j]);

                if ($candidate === '') {
                    continue;
                }

                if ($title === null && !preg_match('/^(Durée|Coefficient|Objets de formation|Connaissances|BIBLIOGRAPHIE|DEMARCHE PEDAGOGIQUE)/ui', $candidate)) {
                    $title = $candidate;
                    continue;
                }

                if (preg_match('/^(\d+)\s*heures?$/ui', $candidate, $matches)) {
                    $duration = (int) $matches[1];
                }

                if ($title !== null && $duration !== null) {
                    break;
                }
            }

            if ($title === null) {
                continue;
            }

            $module = $this->makeEmptyModule('M' . (count($modules) + 1), $title);
            $module['duration'] = $duration;
            $modules[] = $this->normalizeParsedModule($module);
        }

        return $modules;
    }

    private function parseCpgeModules(array $lines): array
    {
        // Modele CPGE : format "Semestre N" puis "Intitule (X h)".
        $modules = [];
        $seen = [];
        $currentSemester = null;

        foreach ($lines as $line) {
            $line = $this->normalizeInlineText($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^Semestre\s+(\d+)/ui', $line, $matches)) {
                $currentSemester = 'Semestre ' . $matches[1];
                continue;
            }

            if (!preg_match('/^(.+?)\s*\((\d+)\s*(?:h|H|heures?)\s*\)$/u', $line, $matches)) {
                continue;
            }

            $title = $this->normalizeInlineText($matches[1]);
            $duration = (int) $matches[2];

            if (preg_match('/^Semestre\b/ui', $title)) {
                continue;
            }

            if (mb_strlen($title) < 5) {
                continue;
            }

            $key = mb_strtolower($title . '|' . $duration);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $module = $this->makeEmptyModule('M' . (count($modules) + 1), $title);
            $module['duration'] = $duration;
            $module['level'] = $currentSemester;
            $modules[] = $this->normalizeParsedModule($module);
        }

        return $modules;
    }

    private function parseSectionModules(array $lines): array
    {
        // Modele par sections numerotees : "N. Intitule" + "Classe de ..." + "Volume horaire".
        $modules = [];
        $currentModule = null;
        $moduleCounter = 0;

        foreach ($lines as $index => $rawLine) {
            $line = $this->normalizeInlineText($rawLine);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(\d+)\.\s+(.+)$/u', $line, $matches)) {
                $lookaheadHasClass = false;

                for ($i = $index + 1; $i <= min($index + 4, count($lines) - 1); $i++) {
                    $nextLine = $this->normalizeInlineText($lines[$i]);

                    if ($nextLine !== '' && preg_match('/:\s*Classe de\s+.+$/ui', $nextLine)) {
                        $lookaheadHasClass = true;
                        break;
                    }
                }

                if (!$lookaheadHasClass) {
                    continue;
                }

                $this->pushParsedModule($modules, $currentModule);
                $moduleCounter = (int) $matches[1];
                $currentModule = $this->makeEmptyModule('M' . $moduleCounter, $matches[2]);

                continue;
            }

            if (!$currentModule) {
                continue;
            }

            if (preg_match('/^(.+?)\s*:\s*Classe de\s+(.+)$/ui', $line, $matches)) {
                if (blank($currentModule['title'])) {
                    $currentModule['title'] = $matches[1];
                }

                $currentModule['level'] = 'Classe de ' . $matches[2];

                continue;
            }

            if (preg_match('/^Volume horaire\s*:\s*(\d+)\s*heures?\s*\/?\s*semaine/ui', $line, $matches)) {
                $currentModule['duration'] = (int) $matches[1];

                continue;
            }

            if (preg_match('/^Objectif[s]?\s+g[ée]n[ée]ral(?:ux)?\s*:\s*(.+)$/ui', $line, $matches)) {
                $currentModule['pedagogical_approach'] = $this->appendModuleValue($currentModule['pedagogical_approach'] ?? null, $matches[1]);

                continue;
            }

            if (preg_match('/^Paragraphedeliste\|/ui', $rawLine)) {
                $currentModule['pedagogical_approach'] = $this->appendModuleValue(
                    $currentModule['pedagogical_approach'] ?? null,
                    preg_replace('/^Paragraphedeliste\|/ui', '', $rawLine) ?? $rawLine
                );
            }
        }

        $this->pushParsedModule($modules, $currentModule);

        return $modules;
    }

    private function isFieldContinuation(string $line): bool
    {
        if ($line === '' || preg_match('/^MODULES?\s+/ui', $line)) {
            return false;
        }

        // Modele RFC BEP ACC termine - Copie.pdf : certaines en-tetes PDF se collent au contenu utile.
        if (preg_match('/^Inspection de l[\'’]?enseignement.*\bPage\s+\d+\b/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:Page\s+\d+|[IVX]+\.\s+TABLEAU|[IVX]+\.\s+DESCRIPTION|BIBLIOGRAPHIE|NB\s*:)/ui', $line)) {
            return false;
        }

        if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:TITRE DU MODULE|INTITULÉ DU MODULE|INTITULE DU MODULE|CODE|CODE DU MODULE|Code du module|DUR(?:É|E|EE)E|DURÉE DU MODULE|DUREE DU MODULE|NIVEAU|OBJECTIF VISE|PLACE DANS LE REFERENTIEL|RÔLE ET IMPORTANCE|ROLE ET IMPORTANCE|CONTENUS ESSENTIELS|TYPE(?:S)?\s+D|D(?:É|E)MARCHES?\s+P)/ui', $line)) {
            return false;
        }

        return !preg_match('/^\d+\s*[-.]?\s*[A-ZÀ-ÿ]/u', $line);
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
        // Modele RFC BEP ACC termine - Copie.pdf : normalisation des caracteres parasites et suppression des en-tetes incrustes.
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

        if ($module['code'] !== '') {
            $module['code'] = preg_replace('/\s+/', ' ', $module['code']) ?? $module['code'];
        } else {
            $module['code'] = null;
        }

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

    public function destroy(Referentiel $referentiel)
    {
        if (Storage::disk('public')->exists($referentiel->file_path)) {
            Storage::disk('public')->delete($referentiel->file_path);
        }

        $referentiel->delete();

        return redirect()->route('settings.index')->with('success', 'Referentiel supprime avec succes.');
    }

    public function update(Request $request, Referentiel $referentiel)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:formation,evaluation',
        ]);

        $referentiel->update([
            'title' => $request->title,
            'status' => $request->status,
        ]);

        return redirect()->route('settings.index')->with('success', 'Referentiel modifie avec succes.');
    }

    public function getModules(Referentiel $referentiel)
    {
        return response()->json(
            $referentiel->modules()
                ->withCount('bibliographies')
                ->get()
        );
    }

    public function storeModule(Request $request, Referentiel $referentiel)
    {
        $request->validate([
            'numero' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'level' => 'nullable|string|max:255',
            'teacher_profile' => 'nullable|string|max:255',
            'pedagogical_approach' => 'nullable|string|max:255',
            'assessment_type' => 'nullable|string|max:255',
        ]);

        $module = $referentiel->modules()->create([
            'numero' => $request->numero,
            'code' => $request->code,
            'title' => $request->title,
            'duration' => $request->duration,
            'level' => $request->level,
            'teacher_profile' => $request->teacher_profile,
            'pedagogical_approach' => $request->pedagogical_approach,
            'assessment_type' => $request->assessment_type,
        ]);

        return response()->json($module, 201);
    }

    public function updateModule(Request $request, Module $module)
    {
        $request->validate([
            'numero' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'level' => 'nullable|string|max:255',
            'teacher_profile' => 'nullable|string|max:255',
            'pedagogical_approach' => 'nullable|string|max:255',
            'assessment_type' => 'nullable|string|max:255',
        ]);

        $module->update([
            'numero' => $request->numero,
            'code' => $request->code,
            'title' => $request->title,
            'duration' => $request->duration,
            'level' => $request->level,
            'teacher_profile' => $request->teacher_profile,
            'pedagogical_approach' => $request->pedagogical_approach,
            'assessment_type' => $request->assessment_type,
        ]);

        return response()->json($module);
    }

    public function getBibliographies(Module $module)
    {
        return response()->json($module->bibliographies()->latest()->get());
    }

    public function storeBibliographie(Request $request, Module $module)
    {
        $data = $request->validate([
            'author' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:50',
            'pages' => 'nullable|string|max:100',
            'raw_text' => 'nullable|string',
        ]);

        $bibliographie = $module->bibliographies()->create($data);

        return response()->json($bibliographie, 201);
    }

    public function updateBibliographie(Request $request, Bibliographie $bibliographie)
    {
        $data = $request->validate([
            'author' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:50',
            'pages' => 'nullable|string|max:100',
            'raw_text' => 'nullable|string',
        ]);

        $bibliographie->update($data);

        return response()->json($bibliographie);
    }

    public function destroyBibliographie(Bibliographie $bibliographie)
    {
        $bibliographie->delete();

        return response()->json(['success' => true]);
    }

    public function extract(Request $request, Referentiel $referentiel)
    {
        $extension = strtolower(pathinfo($referentiel->file_path, PATHINFO_EXTENSION));

        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            $message = 'Fichier non supporte pour l extraction automatique. (PDF, DOC et DOCX uniquement)';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return redirect()->route('settings.index')->withErrors([$message]);
        }

        $referentiel->modules()->delete();
        $this->extractModules($referentiel);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Modules extraits avec succes.']);
        }

        return redirect()->route('settings.index')->with('success', 'Modules extraits avec succes depuis le document.');
    }
}
