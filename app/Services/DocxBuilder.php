<?php

namespace App\Services;

/**
 * Minimal Word (.docx) generator with zero dependencies (ZipArchive only).
 * Builds a valid Office Open XML package: title, headings, paragraphs,
 * bullet lists, fill-in fields, and simple tables.
 */
class DocxBuilder
{
    /** @var list<array<string, mixed>> */
    private array $blocks = [];

    public function title(string $text): static
    {
        $this->blocks[] = ['t' => 'title', 'text' => $text];

        return $this;
    }

    public function h1(string $text): static
    {
        $this->blocks[] = ['t' => 'h1', 'text' => $text];

        return $this;
    }

    public function h2(string $text): static
    {
        $this->blocks[] = ['t' => 'h2', 'text' => $text];

        return $this;
    }

    public function p(string $text, bool $bold = false): static
    {
        $this->blocks[] = ['t' => 'p', 'text' => $text, 'bold' => $bold];

        return $this;
    }

    public function bullet(string $text): static
    {
        $this->blocks[] = ['t' => 'bullet', 'text' => $text];

        return $this;
    }

    /** Fill-in line: "Label: ________" */
    public function field(string $label): static
    {
        $this->blocks[] = ['t' => 'field', 'text' => $label];

        return $this;
    }

    /** @param list<string> $headers @param list<list<string>> $rows */
    public function table(array $headers, array $rows): static
    {
        $this->blocks[] = ['t' => 'table', 'headers' => $headers, 'rows' => $rows];

        return $this;
    }

    public function spacer(): static
    {
        $this->blocks[] = ['t' => 'p', 'text' => '', 'bold' => false];

        return $this;
    }

    /**
     * Compile and return the raw .docx binary.
     */
    public function build(): string
    {
        $body = '';
        foreach ($this->blocks as $b) {
            $body .= match ($b['t']) {
                'title' => $this->para($b['text'], 'Title', 56, true),
                'h1' => $this->para($b['text'], 'Heading1', 32, true, '1F3864'),
                'h2' => $this->para($b['text'], 'Heading2', 26, true, '7A1222'),
                'bullet' => $this->bulletPara($b['text']),
                'field' => $this->para($b['text'].': '.str_repeat('_', 42), null, 22, false),
                'table' => $this->tableXml($b['headers'], $b['rows']),
                default => $this->para($b['text'], null, 22, (bool) ($b['bold'] ?? false)),
            };
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="12240" w:h="15840"/>'
            .'<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720"/></w:sectPr>'
            .'</w:body></w:document>';

        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'docx').'.zip';
        // tempnam creates the file; ZipArchive::CREATE needs a non-existing path.
        @unlink($tmp);
        if ($zip->open($tmp, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Could not create temp docx file.');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $this->styles());
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        return $binary === false ? '' : $binary;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function para(string $text, ?string $style, int $size, bool $bold, ?string $color = null): string
    {
        $pPr = $style ? '<w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr>' : '';
        $rPr = '<w:rPr><w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>'
            .($bold ? '<w:b/><w:bCs/>' : '')
            .($color ? '<w:color w:val="'.$color.'"/>' : '')
            .'</w:rPr>';

        return '<w:p>'.$pPr.'<w:r>'.$rPr
            .'<w:t xml:space="preserve">'.$this->esc($text).'</w:t>'
            .'</w:r></w:p>';
    }

    private function bulletPara(string $text): string
    {
        return '<w:p><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>'
            .'<w:t xml:space="preserve">•  '.$this->esc($text).'</w:t>'
            .'</w:r></w:p>';
    }

    /** @param list<string> $headers @param list<list<string>> $rows */
    private function tableXml(array $headers, array $rows): string
    {
        $cell = function (string $text, bool $header): string {
            $shd = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="7A1222"/>' : '';
            $color = $header ? '<w:color w:val="FFFFFF"/>' : '';
            $b = $header ? '<w:b/><w:bCs/>' : '';

            return '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/>'
                .$shd.'</w:tcPr><w:p><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/>'
                .$b.$color.'</w:rPr><w:t xml:space="preserve">'
                .$this->esc($text).'</w:t></w:r></w:p></w:tc>';
        };

        $borders = '<w:tblBorders><w:top w:val="single" w:sz="6" w:color="B0B0B0"/>'
            .'<w:left w:val="single" w:sz="6" w:color="B0B0B0"/>'
            .'<w:bottom w:val="single" w:sz="6" w:color="B0B0B0"/>'
            .'<w:right w:val="single" w:sz="6" w:color="B0B0B0"/>'
            .'<w:insideH w:val="single" w:sz="6" w:color="B0B0B0"/>'
            .'<w:insideV w:val="single" w:sz="6" w:color="B0B0B0"/></w:tblBorders>';

        $xml = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'.$borders.'</w:tblPr>';
        $xml .= '<w:tr>'.implode('', array_map(fn ($h) => $cell($h, true), $headers)).'</w:tr>';
        foreach ($rows as $row) {
            $xml .= '<w:tr>'.implode('', array_map(fn ($c) => $cell((string) $c, false), $row)).'</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function styles(): string
    {
        $font = '<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr>'.$font.'</w:rPr></w:rPrDefault></w:styles>';
    }
}
