<?php
declare(strict_types=1);

function now_iso(): string
{
    return date(DATE_ATOM);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'ae' => 'ae',
        'oe' => 'oe',
        'ue' => 'ue',
        'ss' => 'ss',
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'eintrag';
}

function normalize_whitespace(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
    return trim($text);
}

function normalize_search_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    return trim($text);
}

function excerpt(string $text, int $limit = 260): string
{
    $plain = preg_replace('/\s+/u', ' ', strip_tags(str_replace("\n", ' ', $text))) ?? '';
    $plain = trim($plain);
    if (mb_strlen($plain, 'UTF-8') <= $limit) {
        return $plain;
    }

    return rtrim(mb_substr($plain, 0, $limit, 'UTF-8')) . '...';
}
