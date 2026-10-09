<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

/** Produces genuine OOXML using the untouched official narrative package. */
class AccomplishmentDocumentService
{
    public const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const REL = 'http://schemas.openxmlformats.org/package/2006/relationships';
    public const PARTICULARS = ['Activity', 'Brief Description', 'Sustainable Development Goals', 'Persons Involved/ Participants', 'No. of Participants', 'Date/Day/ Venue/ Time', 'Expenses', 'Problems Encountered', 'Recommendations'];
    public const SDGS = [1 => 'No Poverty', 2 => 'Zero Hunger', 3 => 'Good Health and Well-being', 4 => 'Quality Education', 5 => 'Gender Equality', 6 => 'Clean Water and Sanitation', 7 => 'Affordable and Clean Energy', 8 => 'Decent Work and Economic Growth', 9 => 'Industry, Innovation and Infrastructure', 10 => 'Reduced Inequalities', 11 => 'Sustainable Cities and Communities', 12 => 'Responsible Consumption and Production', 13 => 'Climate Action', 14 => 'Life Below Water', 15 => 'Life on Land', 16 => 'Peace, Justice and Strong Institutions', 17 => 'Partnerships for the Goals'];

    public function templatePath(string $kind = 'narrative'): string
    {
        return storage_path('Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/'.($kind === 'particulars' ? 'Particulars of the Accomplishments (1).docx' : 'Accomplishment Report_Template.docx'));
    }

    public static function sdgLabels(array $goals): string
    {
        $labels = [];
        foreach ($goals as $goal) {
            $text = is_scalar($goal) ? trim((string) $goal) : '';
            if (preg_match('/^(?:SDG\s*)?(\d{1,2})(?:\s*[-:.].*)?$/i', $text, $match) && isset(self::SDGS[(int) $match[1]])) {
                $labels[] = 'SDG '.(int) $match[1].' — '.self::SDGS[(int) $match[1]];
            } elseif ($text !== '') {
                $labels[] = $text;
            }
        }
        return implode("\n", $labels);
    }

    public function generate(array $packet, string $destination): void
    {
        if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
            throw new RuntimeException('Cannot create the Accomplishment Report directory.');
        }
        if (! copy($this->templatePath(), $destination)) {
            throw new RuntimeException('Cannot copy the official Accomplishment Report template.');
        }
        $zip = new ZipArchive;
        if ($zip->open($destination) !== true) {
            throw new RuntimeException('Cannot open the Accomplishment Report Word package.');
        }
        try {
            $document = $this->xml($zip->getFromName('word/document.xml'));
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('w', self::W);
            $body = $xpath->query('/w:document/w:body')->item(0);
            $section = $xpath->query('//w:sectPr')->item(0)->cloneNode(true);
            while ($body->firstChild) {
                $body->removeChild($body->firstChild);
            }
            $relationships = $this->xml($zip->getFromName('word/_rels/document.xml.rels'));
            $types = $this->xml($zip->getFromName('[Content_Types].xml'));
            $imageNumber = 0;
            $period = mb_strtoupper($packet['semester']).', ACADEMIC YEAR '.$packet['academic_year'];
            $content = $this->p($packet['organization'], true, 'Title').$this->p('ACCOMPLISHMENT & FINANCIAL REPORT', true, 'Title')
                .$this->p($period, true).$this->p('STUDENT ORGANIZATION ACCOMPLISHMENT REPORT', true)
                .$this->p('Generated: '.$packet['generated_at']).$this->sectionBreak($section, false, $document);
            $content .= $this->p('STUDENT ORGANIZATION ACCOMPLISHMENT REPORT', true, 'Heading1').$this->p($period, true)
                .$this->p('Name of Organization: '.$packet['organization']).$this->p('Classification: '.($packet['classification'] ?: '________________________'))
                .$this->p('PARTICULARS OF THE ACCOMPLISHMENTS', true, 'Heading2');
            $rows = [];
            foreach ($packet['reports'] as $report) {
                $rows[] = [$report['title'], $report['brief_description'], self::sdgLabels($report['sdg_goals']), $report['people_involved'],
                    'Total: '.$report['participants']."\nMale: ".$report['male_participants']."\nFemale: ".$report['female_participants'],
                    $report['date_label']."\n".$report['venue']."\n".$report['time_label'], 'PHP '.$report['financial']['total_expenses'], $report['problems_encountered'], $report['recommendations']];
            }
            $content .= $this->table(self::PARTICULARS, $rows, 15840, true).$this->signatories($packet['signatories'], true)
                .$this->sectionBreak($section, true, $document);
            foreach ($packet['reports'] as $index => $report) {
                if ($index > 0) {
                    $content .= $this->pageBreak();
                }
                $content .= $this->p('NARRATIVE REPORT', true, 'Heading1').$this->p($report['title'], true, 'Heading2')
                    .$this->p($packet['organization'].' — '.$period).$this->p('I. Background of the Activity', true, 'Heading2')
                    .$this->p('Title of the Activity: '.$report['title']).$this->p('Sponsor of the Activity: '.$report['sponsor'])
                    .$this->p('Date / Day / Venue / Time: '.$report['date_label'].' / '.$report['venue'].' / '.$report['time_label'])
                    .$this->p('Objectives: '.$report['objectives']).$this->p('Sustainable Development Goals: '.self::sdgLabels($report['sdg_goals']))
                    .$this->p('Number of Participants — Total: '.$report['participants'].'; Male: '.$report['male_participants'].'; Female: '.$report['female_participants'])
                    .$this->p('Persons Involved / Participants: '.$report['people_involved']).$this->p('II. Highlights of the Activity', true, 'Heading2')
                    .$this->p($report['narrative']).$this->p('A. Brief Overview of the Activity', true, 'Heading2').$this->p($report['brief_description'])
                    .$this->p('Problems Encountered: '.$report['problems_encountered']).$this->p('Recommendations: '.$report['recommendations']);
                foreach ($report['evidence'] as $image) {
                    $content .= $this->pageBreak().$this->p('B. Documentation — '.$report['title'], true, 'Heading2')
                        .$this->image($image, $zip, $relationships, $types, ++$imageNumber).$this->p($image['caption']).$this->p($image['name']);
                }
                $finance = $report['financial'];
                $content .= $this->pageBreak().$this->p('C. Financial Report / Expenses for the Activity', true, 'Heading2')
                    .$this->p($report['title'], true).$this->p('Collection', true)
                    .$this->financialTable($finance['collections']).$this->p('Total Collection: PHP '.$finance['total_collection'], true)
                    .$this->p('Expenses', true).$this->financialTable($finance['expenses'])
                    .$this->p('Total Expenses: PHP '.$finance['total_expenses'], true)
                    .$this->p('NET ACTIVITY COLLECTION (collections − expenses): PHP '.$finance['net_collection'], true)
                    .$this->p($finance['source_note']).$this->p('Financial snapshot recorded: '.$finance['recorded_at'])
                    .$this->p('ATTACHMENT — Receipt References / Supporting Evidence', true, 'Heading2');
                $references = [];
                foreach ($finance['expenses'] as $expense) {
                    $references[] = [$expense['posting_reference'], $expense['reference'] ?: 'No receipt reference recorded', $expense['particulars'], $expense['date'] ?? '', 'PHP '.$expense['total']];
                }
                $content .= $this->table(['Posting', 'Receipt No.', 'Particulars', 'Date', 'Total Cost'], $references, 9360);
                $content .= $this->signatories($report['signatories']);
                foreach ($finance['receipt_attachments'] ?? [] as $attachment) {
                    $content .= $this->pageBreak().$this->p('Supporting Receipt Scan — '.$report['title'], true, 'Heading2')
                        .$this->image($attachment, $zip, $relationships, $types, ++$imageNumber).$this->p($attachment['caption']).$this->p($attachment['name']);
                }
            }
            // A break paragraph can overflow onto an otherwise empty page after a full table.
            // Attach the break to the next heading so Word paginates the content, not an empty line.
            $content = str_replace($this->pageBreak().'<w:p><w:pPr>', '<w:p><w:pPr><w:pageBreakBefore/>', $content);
            $wrapper = $this->xml('<?xml version="1.0" encoding="UTF-8"?><root xmlns:w="'.self::W.'" xmlns:r="'.self::R.'" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'.$content.'</root>');
            foreach ($wrapper->documentElement->childNodes as $node) {
                $body->appendChild($document->importNode($node, true));
            }
            $body->appendChild($this->section($section, false, $document));
            foreach (['word/document.xml' => $document->saveXML(), 'word/_rels/document.xml.rels' => $relationships->saveXML(), '[Content_Types].xml' => $types->saveXML()] as $name => $xml) {
                if (! $zip->addFromString($name, $xml)) {
                    throw new RuntimeException('Cannot write the Word report XML.');
                }
            }
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('Cannot finalize the Accomplishment Report Word package.');
            }
        }
        $this->assertReadable($destination);
    }

    private function financialTable(array $rows): string
    {
        $values = array_map(fn (array $row): array => [($row['date'] ? $row['date']."\n" : '').$row['particulars'],
            (string) $row['quantity'], 'PHP '.$row['unit_cost'], 'PHP '.$row['total'], $row['reference'] ?: 'No receipt reference recorded'], $rows);
        return $this->table(['Particulars', 'Quantity', 'Unit Cost', 'Total Cost', 'Receipt No.'], $values, 9360);
    }

    private function signatories(array $names, bool $summary = false): string
    {
        $roles = $summary ? ['president' => 'Prepared by: President, Organization', 'adviser' => 'Noted by: Adviser, Organization', 'head' => 'Certified True and Correct: Head, Student Organization']
            : ['secretary' => 'Prepared by: Secretary, Organization', 'auditor' => 'Audited by: Auditor, Organization', 'president' => 'Noted by: President, Organization', 'adviser' => 'Noted by: Adviser, Organization', 'coordinator' => 'Noted by: OSO Coordinator, College', 'head' => 'Verified True and Correct: Head, Student Organization'];
        $content = '<w:tbl><w:tblPr><w:tblW w:w="'.($summary ? 15840 : 9360).'" w:type="dxa"/><w:tblLayout w:type="fixed"/></w:tblPr>';
        $cellWidth = $summary ? 5280 : 3120;
        $content .= '<w:tblGrid>'.str_repeat('<w:gridCol w:w="'.$cellWidth.'"/>', 3).'</w:tblGrid>';
        $index = 0;
        foreach ($roles as $key => $role) {
            if ($index % 3 === 0) {
                $content .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
            }
            $content .= '<w:tc><w:tcPr><w:tcW w:w="'.$cellWidth.'" w:type="dxa"/></w:tcPr>'
                .$this->p(trim((string) ($names[$key] ?? '')) ?: '________________________________', true)
                .$this->p($role).'</w:tc>';
            if (++$index % 3 === 0) {
                $content .= '</w:tr>';
            }
        }
        return $content.'</w:tbl>';
    }

    private function p(string $text, bool $bold = false, ?string $style = null): string
    {
        $runs = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $runs[] = '<w:r><w:rPr>'.($bold ? '<w:b/>' : '').'<w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">'.$this->escape($line).'</w:t></w:r>';
        }
        return '<w:p><w:pPr>'.($style ? '<w:pStyle w:val="'.$style.'"/>' : '').'<w:spacing w:after="120"/></w:pPr>'.implode('<w:r><w:br/></w:r>', $runs).'</w:p>';
    }

    private function table(array $headers, array $rows, int $width, bool $compact = false): string
    {
        $cellWidth = intdiv($width, count($headers));
        $content = '<w:tbl><w:tblPr><w:tblW w:w="'.$width.'" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $border) {
            $content .= '<w:'.$border.' w:val="single" w:sz="4" w:color="000000"/>';
        }
        $content .= '</w:tblBorders></w:tblPr><w:tblGrid>'.str_repeat('<w:gridCol w:w="'.$cellWidth.'"/>', count($headers)).'</w:tblGrid>';
        if ($rows === []) {
            $rows[] = array_pad(['No recorded activity-linked transactions'], count($headers), '');
        }
        foreach ([$headers, ...$rows] as $index => $row) {
            $content .= '<w:tr>'.($index === 0 ? '<w:trPr><w:tblHeader/></w:trPr>' : '');
            foreach ($row as $value) {
                $paragraph = $this->p((string) $value, $index === 0);
                if ($compact) {
                    $paragraph = str_replace('w:val="22"', 'w:val="16"', $paragraph);
                }
                $content .= '<w:tc><w:tcPr><w:tcW w:w="'.$cellWidth.'" w:type="dxa"/><w:vAlign w:val="top"/></w:tcPr>'.$paragraph.'</w:tc>';
            }
            $content .= '</w:tr>';
        }
        return $content.'</w:tbl>'.$this->p('');
    }

    private function image(array $image, ZipArchive $zip, DOMDocument $relationships, DOMDocument $types, int $number): string
    {
        $path = (string) ($image['path'] ?? '');
        if (! preg_match('~^accomplishment-evidence/[0-9]+/[0-9a-f-]{36}\.(jpg|png)$~D', $path, $match)) {
            throw new RuntimeException('The report contains an invalid evidence path.');
        }
        $absolute = Storage::disk('local')->path($path);
        $dimensions = @getimagesize($absolute);
        if (! $dimensions || ! in_array($dimensions[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || filesize($absolute) <= 0) {
            throw new RuntimeException('A published evidence image is missing or unreadable.');
        }
        $extension = $match[1];
        $target = 'media/accomplishment-'.$number.'.'.$extension;
        if (! $zip->addFile($absolute, 'word/'.$target)) {
            throw new RuntimeException('Cannot embed evidence in the Word report.');
        }
        $relationshipId = 'rIdAccomplishmentImage'.$number;
        $relationship = $relationships->createElementNS(self::REL, 'Relationship');
        $relationship->setAttribute('Id', $relationshipId);
        $relationship->setAttribute('Type', self::R.'/image');
        $relationship->setAttribute('Target', $target);
        $relationships->documentElement->appendChild($relationship);
        $typeExists = false;
        foreach ($types->getElementsByTagName('Default') as $type) {
            if ($type->getAttribute('Extension') === $extension) {
                $typeExists = true;
            }
        }
        if (! $typeExists) {
            $type = $types->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default');
            $type->setAttribute('Extension', $extension);
            $type->setAttribute('ContentType', $dimensions['mime']);
            $types->documentElement->appendChild($type);
        }
        $scale = min(5700000 / $dimensions[0], 7200000 / $dimensions[1]);
        $cx = (int) round($dimensions[0] * $scale);
        $cy = (int) round($dimensions[1] * $scale);
        return '<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="'.(1000 + $number).'" name="Evidence '.$number.'" descr="'.$this->escape($image['caption']).'"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="'.$number.'" name="'.$this->escape($image['name']).'"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="'.$relationshipId.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    private function section(DOMElement $original, bool $landscape, DOMDocument $document): DOMElement
    {
        $section = $original->cloneNode(true);
        foreach (['type', 'pgNumType'] as $name) {
            foreach (iterator_to_array($section->getElementsByTagNameNS(self::W, $name)) as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        $type = $document->createElementNS(self::W, 'w:type');
        $type->setAttributeNS(self::W, 'w:val', 'nextPage');
        $size = $section->getElementsByTagNameNS(self::W, 'pgSz')->item(0);
        $section->insertBefore($type, $size);
        $size->setAttributeNS(self::W, 'w:w', $landscape ? '18720' : '12240');
        $size->setAttributeNS(self::W, 'w:h', $landscape ? '12240' : '18720');
        $size->setAttributeNS(self::W, 'w:orient', $landscape ? 'landscape' : 'portrait');
        return $section;
    }

    private function sectionBreak(DOMElement $original, bool $landscape, DOMDocument $document): string
    {
        return '<w:p><w:pPr>'.$document->saveXML($this->section($original, $landscape, $document)).'</w:pPr></w:p>';
    }

    private function pageBreak(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xml(string|false $xml): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if ($xml === false || ! $document->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('The official Word package contains unreadable XML.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return $document;
    }

    public function assertReadable(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['report' => 'The native Word packet is missing or unreadable. Save it again.']);
        }
        try {
            $parts = [];
            foreach (['[Content_Types].xml', 'word/document.xml', 'word/_rels/document.xml.rels', 'word/styles.xml'] as $name) {
                $parts[$name] = $this->xml($zip->getFromName($name));
            }
            $document = $parts['word/document.xml'];
            if ($document->documentElement->namespaceURI !== self::W || $document->documentElement->localName !== 'document'
                || $document->getElementsByTagNameNS(self::W, 'body')->length !== 1) {
                throw new RuntimeException('The Word packet has no document body.');
            }
            $relationships = [];
            foreach ($parts['word/_rels/document.xml.rels']->getElementsByTagNameNS(self::REL, 'Relationship') as $relationship) {
                $relationships[$relationship->getAttribute('Id')] = $relationship;
                if ($relationship->getAttribute('TargetMode') !== 'External') {
                    $target = $relationship->getAttribute('Target');
                    $member = str_starts_with($target, '/') ? ltrim($target, '/') : 'word/'.$target;
                    $stat = $zip->statName($member);
                    if ($stat === false || $stat['size'] <= 0) {
                        throw new RuntimeException('An internal Word package attachment is missing.');
                    }
                }
            }
            foreach ($document->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip') as $image) {
                $id = $image->getAttributeNS(self::R, 'embed');
                if ($id !== '' && (! isset($relationships[$id]) || $relationships[$id]->getAttribute('Type') !== self::R.'/image')) {
                    throw new RuntimeException('A Word evidence image has no valid relationship.');
                }
            }
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['report' => 'The native Word packet is incomplete. Save it again.']);
        } finally {
            $zip->close();
        }
    }
}
