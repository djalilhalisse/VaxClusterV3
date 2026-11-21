<?php
// Minimal .xlsx writer (plain PHP: no Composer package and no PHP extension needed).
// Builds a styled "Patients" sheet and a "Summary" sheet, then zips the XML parts itself.

// Cell style indexes (see xl_styles_xml)
const XL_TITLE = 1, XL_SUB = 2, XL_HEAD = 3, XL_SEC = 13, XL_KPI = 14, XL_KPIP = 15, XL_KPIL = 16,
      XL_TH = 17, XL_TL = 18, XL_TC = 19, XL_TP = 20, XL_THL = 21;

function xl_col($i) { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) { $s = chr(65 + ($i - 1) % 26) . $s; } return $s; }

function xl_esc($s) {
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string)$s);
    return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function xl_s($ref, $text, $style) { return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . xl_esc($text) . '</t></is></c>'; }
function xl_n($ref, $num, $style) { return '<c r="' . $ref . '" s="' . $style . '"><v>' . $num . '</v></c>'; }
function xl_b($ref, $style) { return '<c r="' . $ref . '" s="' . $style . '"/>'; }
function xl_inc(array &$a, $k) { $k = trim((string)$k); if ($k === '') { $k = 'Non renseigné'; } $a[$k] = ($a[$k] ?? 0) + 1; }
function xl_rank(array $a) { uksort($a, function ($x, $y) use ($a) { return ($a[$y] <=> $a[$x]) ?: strcmp((string)$x, (string)$y); }); return $a; }

function xl_zip(array $files) {
    $out = ''; $cd = ''; $n = 0; $t = getdate();
    $dosTime = ($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1);
    $dosDate = (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];
    foreach ($files as $name => $data) {
        $crc = crc32($data); $size = strlen($data); $method = 0; $comp = $data;
        if (function_exists('gzdeflate')) { $d = gzdeflate($data, 6); if ($d !== false && strlen($d) < $size) { $method = 8; $comp = $d; } }
        $csize = strlen($comp); $offset = strlen($out);
        $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0) . $name . $comp;
        $cd .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $dosTime, $dosDate, $crc, $csize, $size, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        $n++;
    }
    return $out . $cd . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($cd), strlen($out), 0);
}

function xl_styles_xml() {
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="0.0%"/></numFmts>
<fonts count="11">
<font><sz val="11"/><color rgb="FF0C1A33"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
<font><i/><sz val="10"/><color rgb="FF62708C"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
<font><sz val="10"/><color rgb="FF0C1A33"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="10"/><color rgb="FF0C1A33"/><name val="Calibri"/><family val="2"/></font>
<font><sz val="10"/><color rgb="FFB4234A"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="20"/><color rgb="FF2F6BFF"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="9"/><color rgb="FF62708C"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="13"/><color rgb="FF0B1730"/><name val="Calibri"/><family val="2"/></font>
<font><sz val="10"/><color rgb="FF93A2C4"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="8">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF0B1730"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF2F6BFF"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFF3F6FB"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFDE8EC"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFE8EFFF"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="4">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFE1E7F2"/></left><right style="thin"><color rgb="FFE1E7F2"/></right><top style="thin"><color rgb="FFE1E7F2"/></top><bottom style="thin"><color rgb="FFE1E7F2"/></bottom><diagonal/></border>
<border><left style="thin"><color rgb="FFFFFFFF"/></left><right style="thin"><color rgb="FFFFFFFF"/></right><top style="thin"><color rgb="FFFFFFFF"/></top><bottom style="thin"><color rgb="FFFFFFFF"/></bottom><diagonal/></border>
<border><left/><right/><top/><bottom style="medium"><color rgb="FF2F6BFF"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="22">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
<xf numFmtId="0" fontId="2" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
<xf numFmtId="0" fontId="3" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="6" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="10" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="10" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="9" fillId="0" borderId="3" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="3" fontId="7" fillId="7" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="7" fillId="7" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="8" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="3" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
<xf numFmtId="3" fontId="4" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="4" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
}

/** Build the workbook from patient rows (assoc arrays; antecedents/side_effects separated by "|"). Returns the .xlsx bytes. */
function xlsx_patients_workbook(array $rows) {
    $now = gmdate('d/m/Y H:i') . ' UTC';
    $total = count($rows);

    // ---------- Sheet 1: Patients ----------
    // [header, width, type, field]  types: B bold name, L left, C centre, N number (centre), A list, F side effects
    $cols = [
        ['Numéro d’identité', 22, 'L', 'id_patient'], ['Nom', 24, 'B', 'nom'], ['Sexe', 11, 'C', 'sexe'], ['Âge', 7, 'N', 'age'],
        ['Adresse', 30, 'L', 'adresse'], ['Téléphone', 16, 'L', 'tel'], ['Vaccin', 18, 'L', 'type_vaccin'], ['N° de lot', 14, 'C', 'num_lot'],
        ['Date de péremption', 16, 'C', 'date_peremption'], ['Lieu de vaccination', 24, 'L', 'lieu_vaccination'], ['Date de vaccination', 16, 'C', 'date_vaccination'],
        ['Heure', 10, 'C', 'heure_vaccination'], ['Site d’injection', 16, 'L', 'site'], ['Dose', 14, 'C', 'dose'],
        ['Antécédents médicaux', 32, 'A', 'antecedents'], ['Effets secondaires déclarés', 38, 'F', 'side_effects'], ['Date d’ajout', 19, 'C', 'createdate'], ['Saison', 14, 'C', 'season'],
    ];
    $nc = count($cols);
    $lastCol = xl_col($nc - 1);
    $lastRow = max(4, 4 + $total);
    $base = ['L' => 4, 'C' => 6, 'B' => 8, 'N' => 6, 'A' => 4, 'F' => 4];

    $x = '<row r="1" ht="34" customHeight="1">' . xl_s('A1', 'VaxCluster – Export des patients', XL_TITLE);
    for ($c = 1; $c < $nc; $c++) { $x .= xl_b(xl_col($c) . '1', XL_TITLE); }
    $x .= '</row><row r="2" ht="22" customHeight="1">' . xl_s('A2', "Généré le $now  ·  $total patient" . ($total === 1 ? '' : 's'), XL_SUB);
    for ($c = 1; $c < $nc; $c++) { $x .= xl_b(xl_col($c) . '2', XL_SUB); }
    $x .= '</row><row r="3" ht="8" customHeight="1"/><row r="4" ht="34" customHeight="1">';
    foreach ($cols as $c => $col) { $x .= xl_s(xl_col($c) . '4', $col[0], XL_HEAD); }
    $x .= '</row>';

    $with = 0; $byV = []; $byG = []; $byD = []; $byS = []; $fx = [];
    foreach ($rows as $i => $r) {
        $rn = 5 + $i; $band = $i % 2;
        $x .= '<row r="' . $rn . '">';
        foreach ($cols as $c => $col) {
            $ref = xl_col($c) . $rn; $type = $col[2]; $v = trim((string)($r[$col[3]] ?? ''));
            if ($type === 'A' || $type === 'F') { $v = implode(', ', array_map('td', array_filter(array_map('trim', explode('|', $v)), 'strlen'))); }
            elseif (in_array($col[3], ['sexe', 'type_vaccin', 'dose', 'site', 'season'], true)) { $v = td($v); }
            elseif (in_array($col[3], ['date_peremption', 'date_vaccination', 'createdate'], true)) { $v = fdate($v); }
            if ($v === '') { $x .= xl_s($ref, '—', 11 + $band); continue; }
            if ($type === 'N' && is_numeric($v)) { $x .= xl_n($ref, $v + 0, 6 + $band); continue; }
            $style = ($type === 'F') ? 10 : $base[$type] + $band;
            $x .= xl_s($ref, $v, $style);
        }
        $x .= '</row>';
        // tallies for the Summary sheet
        xl_inc($byV, td($r['type_vaccin'] ?? '')); xl_inc($byG, td($r['sexe'] ?? '')); xl_inc($byD, td($r['dose'] ?? '')); xl_inc($byS, td($r['season'] ?? ''));
        $effects = array_filter(array_map('trim', explode('|', (string)($r['side_effects'] ?? ''))), 'strlen');
        if ($effects) { $with++; foreach ($effects as $e) { xl_inc($fx, td($e)); } }
    }

    $colsXml = '<cols>';
    foreach ($cols as $c => $col) { $colsXml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $col[1] . '" customWidth="1"/>'; }
    $colsXml .= '</cols>';

    $ns = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
    $sheet1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet ' . $ns . '>'
        . '<sheetPr><tabColor rgb="FF2F6BFF"/><pageSetUpPr fitToPage="1"/></sheetPr>'
        . '<dimension ref="A1:' . $lastCol . $lastRow . '"/>'
        . '<sheetViews><sheetView showGridLines="0" workbookViewId="0" tabSelected="1"><pane xSplit="2" ySplit="4" topLeftCell="C5" activePane="bottomRight" state="frozen"/><selection pane="topRight" activeCell="C1" sqref="C1"/><selection pane="bottomLeft" activeCell="A5" sqref="A5"/><selection pane="bottomRight" activeCell="C5" sqref="C5"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>' . $colsXml
        . '<sheetData>' . $x . '</sheetData>'
        . '<autoFilter ref="A4:' . $lastCol . $lastRow . '"/>'
        . '<mergeCells count="2"><mergeCell ref="A1:' . $lastCol . '1"/><mergeCell ref="A2:' . $lastCol . '2"/></mergeCells>'
        . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
        . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
        . '</worksheet>';

    // ---------- Sheet 2: Summary ----------
    $y = '<row r="1" ht="34" customHeight="1">' . xl_s('A1', 'VaxCluster – Synthèse', XL_TITLE) . xl_b('B1', XL_TITLE) . xl_b('C1', XL_TITLE) . '</row>';
    $y .= '<row r="2" ht="22" customHeight="1">' . xl_s('A2', "Généré le $now", XL_SUB) . xl_b('B2', XL_SUB) . xl_b('C2', XL_SUB) . '</row>';
    $y .= '<row r="3" ht="10" customHeight="1"/>';
    $y .= '<row r="4" ht="22" customHeight="1">' . xl_s('A4', 'Patients au total', XL_KPIL) . xl_s('B4', 'Avec effets secondaires déclarés', XL_KPIL) . xl_s('C4', 'Taux de déclaration', XL_KPIL) . '</row>';
    $y .= '<row r="5" ht="40" customHeight="1">' . xl_n('A5', $total, XL_KPI) . xl_n('B5', $with, XL_KPI) . xl_n('C5', $total ? round($with / $total, 4) : 0, XL_KPIP) . '</row>';

    $sections = [['Par vaccin', 'Vaccin', $byV], ['Par sexe', 'Sexe', $byG], ['Par dose', 'Dose', $byD],
                 ['Par saison de vaccination', 'Saison', $byS], ['Effets secondaires les plus déclarés', 'Effet secondaire', $fx]];
    $r = 7; $cf = ''; $prio = 1;
    foreach ($sections as $sec) {
        list($title, $label, $data) = $sec;
        $y .= '<row r="' . $r . '" ht="26" customHeight="1">' . xl_s('A' . $r, $title, XL_SEC) . xl_b('B' . $r, XL_SEC) . xl_b('C' . $r, XL_SEC) . '</row>'; $r++;
        $y .= '<row r="' . $r . '" ht="22" customHeight="1">' . xl_s('A' . $r, $label, XL_THL) . xl_s('B' . $r, 'Patients', XL_TH) . xl_s('C' . $r, '% des patients', XL_TH) . '</row>'; $r++;
        $first = $r;
        foreach (array_slice(xl_rank($data), 0, 10, true) as $k => $n) {
            $y .= '<row r="' . $r . '" ht="20" customHeight="1">' . xl_s('A' . $r, (string)$k, XL_TL) . xl_n('B' . $r, $n, XL_TC) . xl_n('C' . $r, $total ? round($n / $total, 4) : 0, XL_TP) . '</row>'; $r++;
        }
        if ($r > $first) {
            $cf .= '<conditionalFormatting sqref="C' . $first . ':C' . ($r - 1) . '"><cfRule type="dataBar" priority="' . $prio++ . '"><dataBar><cfvo type="num" val="0"/><cfvo type="num" val="1"/><color rgb="FF8FB0FF"/></dataBar></cfRule></conditionalFormatting>';
        } else {
            $y .= '<row r="' . $r . '">' . xl_s('A' . $r, 'Aucune donnée pour le moment', XL_TL) . xl_b('B' . $r, XL_TC) . xl_b('C' . $r, XL_TP) . '</row>'; $r++;
        }
        $r++; // spacer row
    }
    $sheet2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet ' . $ns . '>'
        . '<sheetPr><tabColor rgb="FF0B1730"/><pageSetUpPr fitToPage="1"/></sheetPr>'
        . '<dimension ref="A1:C' . ($r - 1) . '"/>'
        . '<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols><col min="1" max="1" width="40" customWidth="1"/><col min="2" max="2" width="26" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/></cols>'
        . '<sheetData>' . $y . '</sheetData>'
        . '<mergeCells count="2"><mergeCell ref="A1:C1"/><mergeCell ref="A2:C2"/></mergeCells>'
        . $cf
        . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
        . '<pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/>'
        . '</worksheet>';

    // ---------- Package ----------
    $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $files = [
        '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/></Types>',
        '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/></Relationships>',
        'docProps/core.xml' => $head . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>VaxCluster – Export des patients</dc:title><dc:creator>VaxCluster</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created></cp:coreProperties>',
        'xl/workbook.xml' => $head . '<workbook ' . $ns . '><bookViews><workbookView activeTab="0"/></bookViews><sheets><sheet name="Patients" sheetId="1" r:id="rId1"/><sheet name="Synthèse" sheetId="2" r:id="rId2"/></sheets><definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">Patients!$A$4:$' . $lastCol . '$' . $lastRow . '</definedName><definedName name="_xlnm.Print_Titles" localSheetId="0">Patients!$4:$4</definedName></definedNames></workbook>',
        'xl/_rels/workbook.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'xl/styles.xml' => xl_styles_xml(),
        'xl/worksheets/sheet1.xml' => $sheet1,
        'xl/worksheets/sheet2.xml' => $sheet2,
    ];
    return xl_zip($files);
}
