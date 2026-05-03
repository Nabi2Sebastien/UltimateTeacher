<?php
$line1 = "2. DURÉE : 52 heures";
$line2 = "3. NIVEAU : Première Année";
$line3 = "NIVEAU BEP";
$line4 = "DUREE 52 H";

$output = "";

if (preg_match('/(?:DUR(?:É|E|EE)(?:E)?|VOLUME HORAIRE|TEMPS).*?(?:\s*[:\-]\s*|\s+)(\d+)\s*(?:[hH]|heures?|Heures?)/ui', $line1, $matches)) {
    $output .= "DURATION 1 MATCH: " . $matches[1] . "\n";
}
if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:NIVEAU|CLASSE)(?:[^:\-]*)(?:\s*[:\-]\s*|\s+)(.+?)(?:\s+Volume|\s*$)/ui', $line2, $matches)) {
    $output .= "LEVEL 1 MATCH: " . $matches[1] . "\n";
}
if (preg_match('/^(?:\d+\s*[-.]?\s*)?(?:NIVEAU|CLASSE)(?:[^:\-]*)(?:\s*[:\-]\s*|\s+)(.+?)(?:\s+Volume|\s*$)/ui', $line3, $matches)) {
    $output .= "LEVEL 2 MATCH: " . $matches[1] . "\n";
}
if (preg_match('/(?:DUR(?:É|E|EE)(?:E)?|VOLUME HORAIRE|TEMPS).*?(?:\s*[:\-]\s*|\s+)(\d+)\s*(?:[hH]|heures?|Heures?)/ui', $line4, $matches)) {
    $output .= "DURATION 2 MATCH: " . $matches[1] . "\n";
}
echo $output;
