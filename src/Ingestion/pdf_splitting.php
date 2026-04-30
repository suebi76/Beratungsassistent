<?php
declare(strict_types=1);

function pdf_split_default_pages_per_part(?int $pageCount = null, int $bytes = 0): int
{
    if ($pageCount !== null && $pageCount >= 300) {
        return 25;
    }
    if ($bytes >= LARGE_PDF_BYTES) {
        return 25;
    }
    return 50;
}

function pdf_split_normalize_pages_per_part(int $pagesPerPart): int
{
    return max(PDF_SPLIT_MIN_PAGES_PER_PART, min(PDF_SPLIT_MAX_PAGES_PER_PART, $pagesPerPart));
}

function pdf_split_part_filename(string $originalName, int $partNumber, int $pageStart, int $pageEnd, ?int $pageCount = null): string
{
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    $pageWidth = max(3, strlen((string) max(1, $pageCount ?? $pageEnd)));
    $partWidth = 3;

    return sprintf(
        '%s_teil-%s_seiten-%s-%s.pdf',
        $base,
        str_pad((string) max(1, $partNumber), $partWidth, '0', STR_PAD_LEFT),
        str_pad((string) max(1, $pageStart), $pageWidth, '0', STR_PAD_LEFT),
        str_pad((string) max(1, $pageEnd), $pageWidth, '0', STR_PAD_LEFT)
    );
}

function pdf_split_plan(string $originalName, int $pageCount, int $pagesPerPart): array
{
    $pageCount = max(0, $pageCount);
    $pagesPerPart = pdf_split_normalize_pages_per_part($pagesPerPart);

    $parts = [];
    if ($pageCount > 0) {
        $partNumber = 1;
        for ($pageStart = 1; $pageStart <= $pageCount; $pageStart += $pagesPerPart) {
            $pageEnd = min($pageCount, $pageStart + $pagesPerPart - 1);
            $parts[] = [
                'file' => pdf_split_part_filename($originalName, $partNumber, $pageStart, $pageEnd, $pageCount),
                'part' => $partNumber,
                'page_start' => $pageStart,
                'page_end' => $pageEnd,
            ];
            $partNumber++;
        }
    }

    return [
        'original_file' => basename($originalName),
        'pages_per_part' => $pagesPerPart,
        'page_count' => $pageCount,
        'part_count' => count($parts),
        'parts' => $parts,
    ];
}

function pdf_split_manifest(string $originalName, ?string $originalSha256, int $pageCount, int $pagesPerPart): array
{
    $plan = pdf_split_plan($originalName, $pageCount, $pagesPerPart);
    return [
        'original_file' => basename($originalName),
        'original_sha256' => $originalSha256,
        'created_at' => now_iso(),
        'pages_per_part' => $plan['pages_per_part'],
        'page_count' => $plan['page_count'],
        'parts' => $plan['parts'],
    ];
}

function pdf_estimate_page_count_from_file(string $path): ?int
{
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $content = @file_get_contents($path);
    if (!is_string($content) || $content === '') {
        return null;
    }

    return pdf_estimate_page_count_from_content($content);
}

function pdf_estimate_page_count_from_content(string $content): ?int
{
    $matches = [];
    $count = preg_match_all('~\/Type\s*\/Page\b~', $content, $matches);
    if (!is_int($count) || $count <= 0) {
        return null;
    }

    return $count;
}

function pdf_split_advice_for_file(string $originalName, int $bytes, ?string $path = null): array
{
    $pageCount = $path !== null ? pdf_estimate_page_count_from_file($path) : null;
    $largePdf = $bytes >= LARGE_PDF_BYTES || ($pageCount !== null && $pageCount >= LARGE_PDF_PAGE_WARNING_THRESHOLD);
    $pagesPerPart = pdf_split_default_pages_per_part($pageCount, $bytes);
    $plan = $pageCount !== null ? pdf_split_plan($originalName, $pageCount, $pagesPerPart) : null;
    $example = $plan !== null && ($plan['parts'][0]['file'] ?? '') !== ''
        ? (string) $plan['parts'][0]['file']
        : pdf_split_part_filename($originalName, 1, 1, $pagesPerPart, null);

    $advice = [
        'large_pdf' => $largePdf,
        'page_count_estimate' => $pageCount,
        'page_count_method' => $pageCount !== null ? 'heuristic' : 'unknown',
        'pages_per_part_recommendation' => $pagesPerPart,
        'part_count_estimate' => $plan['part_count'] ?? null,
        'example_first_file' => $example,
    ];
    $advice['message'] = pdf_split_advice_message($advice);

    return $advice;
}

function pdf_split_advice_message(array $advice): string
{
    if (empty($advice['large_pdf'])) {
        return '';
    }

    $pagesPerPart = (int) ($advice['pages_per_part_recommendation'] ?? pdf_split_default_pages_per_part());
    $example = (string) ($advice['example_first_file'] ?? '');
    $pageCount = $advice['page_count_estimate'] ?? null;
    $partCount = $advice['part_count_estimate'] ?? null;

    if (is_int($pageCount) && $pageCount > 0 && is_int($partCount) && $partCount > 0) {
        return 'Große PDF erkannt (ca. ' . $pageCount . ' Seiten). Falls der Webhost oder der KI-Anbieter die Verarbeitung abbricht, teilen Sie die Datei in '
            . $partCount . ' Teile mit je ' . $pagesPerPart . ' Seiten. Erster logischer Dateiname: ' . $example . '.';
    }

    return 'Große PDF erkannt. Falls die Verarbeitung abbricht, teilen Sie die Datei lokal in Teile mit etwa '
        . $pagesPerPart . ' Seiten. Beispiel für den ersten Dateinamen: ' . $example . '.';
}

function pdf_split_document_metadata(array $advice): array
{
    return [
        'large_pdf' => (bool) ($advice['large_pdf'] ?? false),
        'page_count_estimate' => $advice['page_count_estimate'] ?? null,
        'page_count_method' => (string) ($advice['page_count_method'] ?? 'unknown'),
        'pages_per_part_recommendation' => (int) ($advice['pages_per_part_recommendation'] ?? pdf_split_default_pages_per_part()),
        'part_count_estimate' => $advice['part_count_estimate'] ?? null,
        'example_first_file' => (string) ($advice['example_first_file'] ?? ''),
    ];
}
