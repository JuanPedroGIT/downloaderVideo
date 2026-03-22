<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Document\Exception\ConversionException;
use App\Domain\Document\Exception\InvalidDocumentException;
use App\Domain\Document\Exception\MergeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles document conversion (DOCX → PDF) and PDF merging.
 *
 * Flow:
 *   1. Validate each uploaded file (type + size).
 *   2. Save each file to an isolated /tmp directory.
 *   3. Convert any .docx file to PDF via LibreOffice --headless.
 *   4. Normalize existing PDFs to v1.4 via Ghostscript (required by FPDI).
 *   5. Merge all PDFs into one using FPDI.
 *   6. Return the path of the merged file (caller is responsible for cleanup).
 */
final class DocumentService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'docx'];
    private const MAX_FILE_SIZE_MB   = 50;
    private const MAX_FILE_SIZE      = self::MAX_FILE_SIZE_MB * 1024 * 1024;

    /**
     * Processes uploaded files and returns the path to the final merged PDF.
     *
     * @param UploadedFile[] $files Ordered list of uploaded files.
     *
     * @throws InvalidDocumentException on validation failure.
     * @throws ConversionException      when LibreOffice cannot convert a file.
     * @throws MergeException           when FPDI cannot merge PDFs.
     */
    public function process(array $files): string
    {
        if (empty($files)) {
            throw new InvalidDocumentException('At least one file is required.');
        }

        $tempDir  = $this->createTempDir();
        $pdfPaths = [];

        try {
            foreach ($files as $file) {
                $this->validateFile($file);

                $savedPath = $this->saveUploadedFile($file, $tempDir);
                $ext       = strtolower($file->getClientOriginalExtension());

                $pdfPaths[] = $ext === 'docx'
                    ? $this->convertDocxToPdf($savedPath, $tempDir)
                    : $this->normalizePdf($savedPath, $tempDir);
            }

            return count($pdfPaths) === 1
                ? $pdfPaths[0]
                : $this->mergePdfs($pdfPaths, $tempDir);

        } catch (\Throwable $e) {
            $this->cleanupDir($tempDir);
            throw $e;
        }
    }

    /**
     * Recursively removes a temporary directory.
     * Call this AFTER the HTTP response has been fully streamed.
     */
    public function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->cleanupDir($item) : @unlink($item);
        }

        @rmdir($dir);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidDocumentException(
                "Upload error on \"{$file->getClientOriginalName()}\": {$file->getErrorMessage()}"
            );
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new InvalidDocumentException(
                "File \"{$file->getClientOriginalName()}\" exceeds the " . self::MAX_FILE_SIZE_MB . " MB limit."
            );
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidDocumentException(
                "Unsupported file \"{$file->getClientOriginalName()}\". Allowed types: " . implode(', ', self::ALLOWED_EXTENSIONS) . '.'
            );
        }
    }

    private function saveUploadedFile(UploadedFile $file, string $dir): string
    {
        $filename  = uniqid('doc_', true) . '.' . strtolower($file->getClientOriginalExtension());
        $savedFile = $file->move($dir, $filename);

        return $savedFile->getRealPath();
    }

    /**
     * Converts a .docx file to PDF using LibreOffice in headless mode.
     * Output file lands in the same directory as the source.
     */
    private function convertDocxToPdf(string $docxPath, string $outputDir): string
    {
        $cmd = sprintf(
            'libreoffice --headless --norestore --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($outputDir),
            escapeshellarg($docxPath),
        );

        $output  = shell_exec($cmd);
        $pdfPath = $outputDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';

        if (!file_exists($pdfPath)) {
            throw new ConversionException(
                'LibreOffice failed to convert "' . basename($docxPath) . '". Output: ' . ($output ?? 'none')
            );
        }

        return $pdfPath;
    }

    /**
     * Uses Ghostscript to downgrade a PDF to version 1.4.
     * FPDI requires PDF ≤ 1.4; LibreOffice and most modern tools generate 1.5+.
     * Falls back silently to the original path if gs is unavailable.
     */
    private function normalizePdf(string $pdfPath, string $tempDir): string
    {
        $outputPath = $tempDir . '/' . uniqid('norm_', true) . '.pdf';

        $cmd = sprintf(
            'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>&1',
            escapeshellarg($outputPath),
            escapeshellarg($pdfPath),
        );

        shell_exec($cmd);

        return file_exists($outputPath) ? $outputPath : $pdfPath;
    }

    /**
     * Merges multiple PDF files (all must be v1.4) into a single PDF using FPDI.
     *
     * @param string[] $pdfPaths
     */
    private function mergePdfs(array $pdfPaths, string $tempDir): string
    {
        $fpdi = new Fpdi();
        $fpdi->SetAutoPageBreak(false);

        foreach ($pdfPaths as $pdfPath) {
            try {
                $pageCount = $fpdi->setSourceFile($pdfPath);

                for ($page = 1; $page <= $pageCount; $page++) {
                    $tplIdx = $fpdi->importPage($page);
                    $size   = $fpdi->getTemplateSize($tplIdx);
                    $orient = ($size['width'] > $size['height']) ? 'L' : 'P';

                    $fpdi->AddPage($orient, [$size['width'], $size['height']]);
                    $fpdi->useTemplate($tplIdx);
                }
            } catch (\Throwable $e) {
                throw new MergeException(
                    'Failed to import "' . basename($pdfPath) . '": ' . $e->getMessage()
                );
            }
        }

        $outputPath = $tempDir . '/merged_' . date('Ymd_His') . '.pdf';
        $fpdi->Output('F', $outputPath);

        if (!file_exists($outputPath)) {
            throw new MergeException('Failed to write merged PDF to disk.');
        }

        return $outputPath;
    }

    private function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/docs_' . bin2hex(random_bytes(8));

        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create temp directory: {$dir}");
        }

        return $dir;
    }
}
