<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist-based HTML sanitizer for rich-text content produced by the Quill
 * editor. Even though Quill emits fairly clean markup, NEVER trust client HTML —
 * this strips any tag/attribute/URL scheme not explicitly permitted, defeating
 * stored-XSS. Output of sanitize() is safe to render unescaped.
 */
final class HtmlSanitizer
{
    /** Allowed tags => allowed attributes for that tag. */
    private const ALLOWED = [
        'p'          => [],
        'br'         => [],
        'strong'     => [],
        'b'          => [],
        'em'         => [],
        'i'          => [],
        'u'          => [],
        's'          => [],
        'h1'         => [],
        'h2'         => [],
        'h3'         => [],
        'ul'         => [],
        'ol'         => [],
        'li'         => [],
        'blockquote' => [],
        'pre'        => [],
        'code'       => [],
        'a'          => ['href'],
        'span'       => [],
        // Image-by-URL only; data: URIs are rejected in isSafeUrl() to avoid
        // bloating the database with base64 blobs.
        'img'        => ['src', 'alt'],
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // Wrap in a single root element so $dom->documentElement is predictable.
        // The xml-encoding prolog is the canonical UTF-8 hack; it is built by
        // concatenation so the literal "<?" never appears in this source file
        // (which short_open_tag environments would otherwise mis-lex).
        $prolog = '<' . '?xml encoding="UTF-8"?' . '>';
        $dom->loadHTML(
            $prolog . '<div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            return '';
        }

        self::cleanChildren($root);

        $clean = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $clean .= $dom->saveHTML($child);
        }

        return trim($clean);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::cleanElement($child);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                // Drop comments, processing instructions, etc.
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private static function cleanElement(DOMElement $el): void
    {
        $tag = strtolower($el->nodeName);

        if (!array_key_exists($tag, self::ALLOWED)) {
            // Unknown tag: keep its (sanitized) text children, drop the wrapper.
            self::cleanChildren($el);
            while ($el->firstChild) {
                $el->parentNode?->insertBefore($el->firstChild, $el);
            }
            $el->parentNode?->removeChild($el);
            return;
        }

        $allowedAttrs = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (!in_array($name, $allowedAttrs, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }
            if (($name === 'href' || $name === 'src') && !self::isSafeUrl($attr->nodeValue)) {
                $el->removeAttribute($attr->nodeName);
            }
        }

        // Harden links opened in a new context.
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener noreferrer nofollow');
            $el->setAttribute('target', '_blank');
        }

        // An <img> with no valid src is useless — drop it.
        if ($tag === 'img' && !$el->hasAttribute('src')) {
            $el->parentNode?->removeChild($el);
            return;
        }
        if ($tag === 'img') {
            $el->setAttribute('loading', 'lazy');
        }

        self::cleanChildren($el);
    }

    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }
        // Allow relative URLs and http/https/mailto only.
        if (preg_match('#^(https?:|mailto:)#i', $url)) {
            return true;
        }
        return !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url); // no scheme => relative
    }

    /**
     * Strip ALL tags — used for excerpting rich text into a plain summary.
     */
    public static function toPlainText(string $html, int $limit = 160): string
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit - 1) . '…';
        }
        return $text;
    }
}
