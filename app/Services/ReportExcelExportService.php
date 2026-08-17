<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Support\BelgianDateTime;
use DateTimeImmutable;
use RuntimeException;
use ZipArchive;

final class ReportExcelExportService
{
    public const MIME_TYPE =
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(
        private readonly SettingsService $settings
    ) {
    }

    /**
     * @param array<string, mixed> $report
     */
    public function export(array $report): string
    {
        $event = $report['event'] ?? null;

        if (!$event instanceof Event) {
            throw new RuntimeException(
                'Het vergoedingsrapport bevat geen geldig evenement.'
            );
        }

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'aefs-report-'
        );

        if ($temporaryFile === false) {
            throw new RuntimeException(
                'Het tijdelijke Excel-bestand kon niet worden aangemaakt.'
            );
        }

        try {
            $archive = new ZipArchive();
            $opened = $archive->open(
                $temporaryFile,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            );

            if ($opened !== true) {
                throw new RuntimeException(
                    'Het Excel-archief kon niet worden aangemaakt.'
                );
            }

            try {
                foreach ($this->parts($report) as $path => $contents) {
                    if (!$archive->addFromString($path, $contents)) {
                        throw new RuntimeException(
                            'Een onderdeel van de Excel-export kon niet worden toegevoegd.'
                        );
                    }
                }
            } finally {
                $archive->close();
            }

            $contents = file_get_contents($temporaryFile);

            if ($contents === false || $contents === '') {
                throw new RuntimeException(
                    'De Excel-export kon niet worden gelezen.'
                );
            }

            return $contents;
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    public function filename(array $report): string
    {
        $event = $report['event'] ?? null;

        if (!$event instanceof Event) {
            return 'vrijwilligersvergoedingen.xlsx';
        }

        $parts = [
            'vrijwilligersvergoedingen',
            $event->titel,
        ];
        $groupLabel = trim(
            (string) ($report['selected_group_label'] ?? '')
        );

        if ($groupLabel !== '') {
            $parts[] = $groupLabel;
        }

        return $this->slug(implode('-', $parts)) . '.xlsx';
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return array<string, string>
     */
    private function parts(array $report): array
    {
        $createdAt = (new DateTimeImmutable())->format(DATE_ATOM);

        return [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->packageRelationshipsXml(),
            'docProps/app.xml' => $this->appPropertiesXml(),
            'docProps/core.xml' => $this->corePropertiesXml($createdAt),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' =>
                $this->workbookRelationshipsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $this->worksheetXml($report),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function worksheetXml(array $report): string
    {
        $event = $report['event'];
        $dates = array_values($report['dates'] ?? []);
        $sections = array_values($report['sections'] ?? []);
        $totalColumnIndex = 4 + count($dates);
        $totalColumn = $this->columnName($totalColumnIndex);
        $lastDateColumn = $this->columnName(
            3 + max(1, count($dates))
        );
        $rows = [];
        $merges = [];
        $sectionTotalRows = [];
        $rowNumber = 1;

        $rows[] = $this->row(
            $rowNumber,
            [
                $this->stringCell(
                    'A' . $rowNumber,
                    'Vrijwilligersvergoedingen',
                    1
                ),
            ],
            28
        );
        $merges[] = 'A1:' . $totalColumn . '1';
        $rowNumber++;

        $rows[] = $this->metadataRow(
            $rowNumber,
            'Evenement',
            $event->titel,
            $totalColumn,
            $merges
        );
        $rowNumber++;

        $groupLabel = trim(
            (string) ($report['selected_group_label'] ?? '')
        );
        $rows[] = $this->metadataRow(
            $rowNumber,
            'Groep',
            $event->werktMetGroepen
                ? ($groupLabel !== '' ? $groupLabel : 'Alle groepen')
                : 'Niet van toepassing',
            $totalColumn,
            $merges
        );
        $rowNumber++;

        $rows[] = $this->metadataRow(
            $rowNumber,
            'Periode',
            $event->displayDate(),
            $totalColumn,
            $merges
        );
        $rowNumber++;

        $rows[] = $this->metadataRow(
            $rowNumber,
            'Aangemaakt',
            BelgianDateTime::formatDateTime(new DateTimeImmutable()),
            $totalColumn,
            $merges
        );
        $rowNumber++;

        $headerCells = [
            $this->stringCell('A' . $rowNumber, 'Naam', 4),
            $this->stringCell('B' . $rowNumber, 'Voornaam', 4),
            $this->stringCell('C' . $rowNumber, 'Rekeningnummer', 4),
        ];

        foreach ($dates as $index => $date) {
            $headerCells[] = $this->stringCell(
                $this->columnName(4 + $index) . $rowNumber,
                BelgianDateTime::formatDate((string) $date),
                4
            );
        }

        $headerCells[] = $this->stringCell(
            $totalColumn . $rowNumber,
            'Totaal',
            4
        );
        $rows[] = $this->row($rowNumber, $headerCells, 30);
        $headerRow = $rowNumber;
        $rowNumber++;

        foreach ($sections as $sectionIndex => $section) {
            if ($sectionIndex > 0) {
                $rows[] = $this->row($rowNumber, [], 8);
                $rowNumber++;
            }

            $sectionLabel = (string) (
                $section['label'] ?? 'Vrijwilligers'
            );
            $rows[] = $this->row(
                $rowNumber,
                [
                    $this->stringCell(
                        'A' . $rowNumber,
                        $sectionLabel,
                        8
                    ),
                ],
                24
            );
            $merges[] = 'A' . $rowNumber
                . ':' . $totalColumn . $rowNumber;
            $rowNumber++;

            $memberStartRow = $rowNumber;

            foreach (($section['members'] ?? []) as $member) {
                $memberCells = [
                    $this->stringCell(
                        'A' . $rowNumber,
                        (string) ($member['last_name'] ?? ''),
                        5
                    ),
                    $this->stringCell(
                        'B' . $rowNumber,
                        (string) ($member['first_name'] ?? ''),
                        5
                    ),
                    $this->stringCell(
                        'C' . $rowNumber,
                        (string) ($member['bank_account'] ?? ''),
                        6
                    ),
                ];

                foreach ($dates as $index => $date) {
                    $day = $member['days'][$date] ?? [];
                    $cellReference = $this->columnName(4 + $index)
                        . $rowNumber;

                    if ((int) ($day['shift_count'] ?? 0) > 0) {
                        $memberCells[] = $this->numberCell(
                            $cellReference,
                            ((int) ($day['amount_cents'] ?? 0)) / 100,
                            7
                        );
                    } else {
                        $memberCells[] = $this->emptyCell(
                            $cellReference,
                            7
                        );
                    }
                }

                $memberCells[] = $this->formulaCell(
                    $totalColumn . $rowNumber,
                    'SUM(D' . $rowNumber
                        . ':' . $lastDateColumn . $rowNumber . ')',
                    ((int) ($member['total_cents'] ?? 0)) / 100,
                    7
                );
                $rows[] = $this->row($rowNumber, $memberCells, 22);
                $rowNumber++;
            }

            $memberEndRow = max($memberStartRow, $rowNumber - 1);
            $totalRow = $rowNumber;
            $sectionTotalRows[] = $totalRow;
            $totalCells = [
                $this->stringCell(
                    'A' . $totalRow,
                    'Totaal ' . $sectionLabel,
                    9
                ),
            ];
            $merges[] = 'A' . $totalRow . ':C' . $totalRow;

            foreach ($dates as $index => $date) {
                $column = $this->columnName(4 + $index);
                $totalCells[] = $this->formulaCell(
                    $column . $totalRow,
                    'SUM(' . $column . $memberStartRow
                        . ':' . $column . $memberEndRow . ')',
                    ((int) ($section['day_totals'][$date] ?? 0)) / 100,
                    10
                );
            }

            $totalCells[] = $this->formulaCell(
                $totalColumn . $totalRow,
                'SUM(D' . $totalRow
                    . ':' . $lastDateColumn . $totalRow . ')',
                ((int) ($section['total_cents'] ?? 0)) / 100,
                10
            );
            $rows[] = $this->row($totalRow, $totalCells, 24);
            $rowNumber++;
        }

        if (count($sectionTotalRows) > 1) {
            $rows[] = $this->row($rowNumber, [], 8);
            $rowNumber++;
            $grandTotalRow = $rowNumber;
            $grandTotalCells = [
                $this->stringCell(
                    'A' . $grandTotalRow,
                    'Algemeen totaal',
                    9
                ),
            ];
            $merges[] = 'A' . $grandTotalRow . ':C' . $grandTotalRow;

            foreach ($dates as $index => $date) {
                $column = $this->columnName(4 + $index);
                $grandTotalCells[] = $this->formulaCell(
                    $column . $grandTotalRow,
                    $this->sumCellsFormula(
                        $column,
                        $sectionTotalRows
                    ),
                    array_sum(
                        array_map(
                            static fn(array $section): int => (int) (
                                $section['day_totals'][$date] ?? 0
                            ),
                            $sections
                        )
                    ) / 100,
                    10
                );
            }

            $grandTotalCells[] = $this->formulaCell(
                $totalColumn . $grandTotalRow,
                $this->sumCellsFormula(
                    $totalColumn,
                    $sectionTotalRows
                ),
                ((int) ($report['total_cents'] ?? 0)) / 100,
                10
            );
            $rows[] = $this->row(
                $grandTotalRow,
                $grandTotalCells,
                26
            );
            $rowNumber++;
        }

        $noteRow = $rowNumber + 1;
        $rows[] = $this->row(
            $noteRow,
            [
                $this->stringCell(
                    'A' . $noteRow,
                    'Vertrouwelijk: dit bestand bevat bankrekeningnummers van leden.',
                    11
                ),
            ],
            22
        );
        $merges[] = 'A' . $noteRow
            . ':' . $totalColumn . $noteRow;

        $mergeXml = implode(
            '',
            array_map(
                fn(string $reference): string => '<mergeCell ref="'
                    . $this->xml($reference) . '"/>',
                $merges
            )
        );
        $columns = [
            '<col min="1" max="1" width="22" customWidth="1"/>',
            '<col min="2" max="2" width="19" customWidth="1"/>',
            '<col min="3" max="3" width="30" customWidth="1"/>',
        ];

        if ($dates !== []) {
            $columns[] = '<col min="4" max="'
                . (3 + count($dates))
                . '" width="14" customWidth="1"/>';
        }

        $columns[] = '<col min="' . $totalColumnIndex
            . '" max="' . $totalColumnIndex
            . '" width="16" customWidth="1"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:' . $totalColumn . $noteRow . '"/>'
            . '<sheetViews><sheetView showGridLines="0" workbookViewId="0">'
            . '<pane xSplit="3" ySplit="' . $headerRow
            . '" topLeftCell="D' . ($headerRow + 1)
            . '" activePane="bottomRight" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . implode('', $columns) . '</cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<mergeCells count="' . count($merges) . '">'
            . $mergeXml . '</mergeCells>'
            . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    /**
     * @param string[] $merges
     */
    private function metadataRow(
        int $rowNumber,
        string $label,
        string $value,
        string $totalColumn,
        array &$merges
    ): string {
        $merges[] = 'B' . $rowNumber
            . ':' . $totalColumn . $rowNumber;

        return $this->row(
            $rowNumber,
            [
                $this->stringCell(
                    'A' . $rowNumber,
                    $label,
                    2
                ),
                $this->stringCell(
                    'B' . $rowNumber,
                    $value,
                    3
                ),
            ],
            22
        );
    }

    /**
     * @param string[] $cells
     */
    private function row(
        int $number,
        array $cells,
        ?int $height = null
    ): string {
        $heightAttributes = $height !== null
            ? ' ht="' . $height . '" customHeight="1"'
            : '';

        return '<row r="' . $number . '"'
            . $heightAttributes . '>'
            . implode('', $cells)
            . '</row>';
    }

    private function stringCell(
        string $reference,
        string $value,
        int $style
    ): string {
        return '<c r="' . $reference . '" s="' . $style
            . '" t="inlineStr"><is><t xml:space="preserve">'
            . $this->xml($value)
            . '</t></is></c>';
    }

    private function emptyCell(
        string $reference,
        int $style
    ): string {
        return '<c r="' . $reference . '" s="' . $style . '"/>';
    }

    private function numberCell(
        string $reference,
        float $value,
        int $style
    ): string {
        return '<c r="' . $reference . '" s="' . $style . '"><v>'
            . number_format($value, 2, '.', '')
            . '</v></c>';
    }

    private function formulaCell(
        string $reference,
        string $formula,
        float $cachedValue,
        int $style
    ): string {
        return '<c r="' . $reference . '" s="' . $style . '"><f>'
            . $this->xml($formula)
            . '</f><v>'
            . number_format($cachedValue, 2, '.', '')
            . '</v></c>';
    }

    /**
     * @param int[] $rows
     */
    private function sumCellsFormula(
        string $column,
        array $rows
    ): string {
        return 'SUM(' . implode(
            ',',
            array_map(
                static fn(int $row): string => $column . $row,
                $rows
            )
        ) . ')';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function slug(string $value): string
    {
        $ascii = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : $value;
        $ascii = is_string($ascii) ? $ascii : $value;
        $slug = strtolower(
            trim(
                (string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii),
                '-'
            )
        );

        return substr(
            $slug !== '' ? $slug : 'vrijwilligersvergoedingen',
            0,
            120
        );
    }

    private function xml(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function packageRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>'
            . $this->xml($this->settings->applicationName())
            . '</Application>'
            . '<DocSecurity>0</DocSecurity>'
            . '<ScaleCrop>false</ScaleCrop>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Werkbladen</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>Vergoedingen</vt:lpstr></vt:vector></TitlesOfParts>'
            . '<Company>'
            . $this->xml($this->settings->organizationName())
            . '</Company>'
            . '<LinksUpToDate>false</LinksUpToDate>'
            . '<SharedDoc>false</SharedDoc>'
            . '<HyperlinksChanged>false</HyperlinksChanged>'
            . '<AppVersion>1.0</AppVersion>'
            . '</Properties>';
    }

    private function corePropertiesXml(string $createdAt): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Vrijwilligersvergoedingen</dc:title>'
            . '<dc:creator>'
            . $this->xml($this->settings->applicationName())
            . '</dc:creator>'
            . '<cp:lastModifiedBy>'
            . $this->xml($this->settings->applicationName())
            . '</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">'
            . $this->xml($createdAt)
            . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">'
            . $this->xml($createdAt)
            . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Vergoedingen" sheetId="1" r:id="rId1"/></sheets>'
            . '<calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/>'
            . '</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="[$€-x-euro2] #,##0.00"/></numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFC8102E"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="12">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="3" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normaal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
