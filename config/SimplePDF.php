<?php
/**
 * SimplePDF - a tiny, dependency-free PDF writer.
 *
 * The project has no PDF library (no composer.json/vendor, no
 * FPDF/TCPDF/Dompdf anywhere) which is why paySlip.php's "Download PDF"
 * button and report.php's export never produced anything. Pulling in a
 * full library isn't necessary for simple text layouts like a payslip
 * or a tabular report - a valid single-page PDF is only a few hundred
 * bytes of well-known PDF syntax. This class writes that directly.
 *
 * Supports left-aligned text lines with basic font size and simple
 * left-column/right-column rows (for label: value tables). Uses the
 * standard Helvetica font, which every PDF viewer has built in, so no
 * font embedding is needed.
 */
declare(strict_types=1);

class SimplePDF
{
    private array $lines = [];
    private int $y = 780;
    private const PAGE_WIDTH = 612;  // US Letter, points
    private const PAGE_HEIGHT = 792;
    private const LEFT_MARGIN = 50;
    private const RIGHT_MARGIN = 562;

    public function addTitle(string $text): void
    {
        $this->writeLine($text, self::LEFT_MARGIN, 18, true);
        $this->y -= 10;
    }

    public function addSubtitle(string $text): void
    {
        $this->writeLine($text, self::LEFT_MARGIN, 11, false);
    }

    public function addSpacer(int $points = 14): void
    {
        $this->y -= $points;
    }

    public function addRule(): void
    {
        $this->lines[] = sprintf('%.2f %.2f m %.2f %.2f l S', self::LEFT_MARGIN, $this->y, self::RIGHT_MARGIN, $this->y);
        $this->y -= 14;
    }

    public function addRow(string $label, string $value): void
    {
        $this->writeLine($label, self::LEFT_MARGIN, 11, false);
        $this->writeLine($value, self::LEFT_MARGIN + 260, 11, false, true);
    }

    public function addLine(string $text, int $size = 11, bool $bold = false): void
    {
        $this->writeLine($text, self::LEFT_MARGIN, $size, $bold);
    }

    private function writeLine(string $text, int $x, int $size, bool $bold, bool $sameLine = false): void
    {
        $font = $bold ? '/F2' : '/F1';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $this->lines[] = sprintf(
            'BT %s %d Tf %d %.2f Td (%s) Tj ET',
            $font,
            $size,
            $x,
            $this->y,
            $escaped
        );
        if (!$sameLine) {
            $this->y -= ($size + 8);
        }
    }

    /**
     * Build the PDF bytes and stream them as a download.
     */
    public function output(string $filename): void
    {
        $content = implode("\n", $this->lines);
        $streamLength = strlen($content);

        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . " " . self::PAGE_HEIGHT . "] "
            . "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Length {$streamLength} >>\nstream\n{$content}\nendstream\nendobj\n";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[6] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
}
