<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

/**
 * Sanitizes HTML content to prevent XSS while keeping safe formatting tags.
 * Used for CMS pages, blog posts, FAQ answers, etc.
 */
class HtmlSanitizer
{
    /**
     * Allowed HTML tags for rich text content.
     */
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'ul', 'ol', 'li',
        'a',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'img',
        'div', 'span',
        'sup', 'sub',
    ];

    /**
     * Allowed attributes per tag.
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'div' => ['style', 'class', 'dir'],
        'span' => ['style', 'class'],
        'p' => ['style', 'class', 'dir'],
        'h1' => ['style', 'class'], 'h2' => ['style', 'class'], 'h3' => ['style', 'class'],
        'h4' => ['style', 'class'], 'h5' => ['style', 'class'], 'h6' => ['style', 'class'],
        'table' => ['style', 'class'],
        'blockquote' => ['class'],
    ];

    /**
     * Dangerous style properties to strip.
     */
    private const DANGEROUS_STYLE_PATTERNS = [
        '/expression\s*\(/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/behavior\s*:/i',
        '/-moz-binding\s*:/i',
        '/url\s*\(\s*["\']?\s*data:/i',
    ];

    /**
     * Sanitize HTML content, removing dangerous tags/attributes.
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // Remove dangerous tags AND their content before strip_tags
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
        $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*>.*?<\/embed>/is', '', $html);
        $html = preg_replace('/<form\b[^>]*>.*?<\/form>/is', '', $html);

        // Strip all remaining tags except allowed ones
        $allowedTagsString = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $html = strip_tags($html, $allowedTagsString);

        // Parse and clean attributes
        $html = self::cleanAttributes($html);

        // Remove any remaining javascript: URLs
        $html = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/vbscript\s*:/i', '', $html);

        return $html;
    }

    private static function cleanAttributes(string $html): string
    {
        // For each allowed tag, strip non-allowed attributes
        return preg_replace_callback(
            '/<(\w+)((?:\s+[^>]*)?)>/i',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attrs = $matches[2];

                if (! in_array($tag, self::ALLOWED_TAGS)) {
                    return '';
                }

                $allowedAttrs = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

                if (empty($allowedAttrs) || empty(trim($attrs))) {
                    return "<{$tag}>";
                }

                // Parse and filter attributes
                $cleanAttrs = '';
                preg_match_all('/(\w[\w-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrs, $attrMatches, PREG_SET_ORDER);

                foreach ($attrMatches as $attr) {
                    $attrName = strtolower($attr[1]);
                    $attrValue = $attr[2] ?? $attr[3] ?? $attr[4] ?? '';

                    if (! in_array($attrName, $allowedAttrs)) {
                        continue;
                    }

                    // Sanitize href/src - must be http(s), mailto, tel, or relative
                    if (in_array($attrName, ['href', 'src'])) {
                        $attrValue = trim($attrValue);
                        if (! preg_match('/^(https?:\/\/|mailto:|tel:|\/|#)/i', $attrValue)) {
                            continue;
                        }
                    }

                    // Sanitize style attribute
                    if ($attrName === 'style') {
                        foreach (self::DANGEROUS_STYLE_PATTERNS as $pattern) {
                            if (preg_match($pattern, $attrValue)) {
                                continue 2;
                            }
                        }
                    }

                    $attrValue = htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8', false);
                    $cleanAttrs .= " {$attrName}=\"{$attrValue}\"";
                }

                return "<{$tag}{$cleanAttrs}>";
            },
            $html,
        ) ?? $html;
    }
}
