<?php

declare(strict_types=1);

namespace herbie\plugins\markdown;

/**
 * Protects raw HTML blocks from being parsed as markdown.
 *
 * A CommonMark parser ends an HTML block at the first empty line and parses the rest as
 * markdown, which breaks HTML blocks that contain empty lines. Therefore such blocks are
 * replaced by a placeholder before parsing and the original markup is restored afterwards.
 */
final class RawHtmlProtector
{
    /**
     * Private use area (U+E000 and U+E001), so the placeholder can't be a part of the content.
     */
    private const string PLACEHOLDER_START = "\xee\x80\x80";

    private const string PLACEHOLDER_END = "\xee\x80\x81";

    /**
     * Elements that start a raw HTML block in CommonMark, if the start tag stands on a
     * line of its own. That is type 1 to type 6 of the CommonMark specification.
     */
    private const array HTML_BLOCK_TAGS = [
        'address', 'article', 'aside', 'base', 'basefont', 'blockquote', 'body', 'caption', 'center',
        'col', 'colgroup', 'dd', 'details', 'dialog', 'dir', 'div', 'dl', 'dt', 'fieldset', 'figcaption',
        'figure', 'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head',
        'header', 'hr', 'html', 'iframe', 'legend', 'li', 'link', 'main', 'menu', 'menuitem', 'nav',
        'noframes', 'ol', 'optgroup', 'option', 'p', 'param', 'section', 'source', 'summary', 'table',
        'tbody', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'track', 'ul',
    ];

    /**
     * Elements whose content is kept as it is by HTML renderers.
     */
    private const array LITERAL_TAGS = ['pre', 'script', 'style', 'textarea'];

    /** @var array<int, string> */
    private array $blocks = [];

    /**
     * Replaces all raw HTML blocks by placeholders.
     */
    public function protect(string $markdown): string
    {
        $this->blocks = [];

        $lines = explode("\n", $markdown);
        $count = count($lines);
        $result = [];
        $inFence = false;

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            // Fenced code blocks are never protected: their content (including
            // HTML tags) must reach the Markdown parser unchanged.
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line) === 1) {
                $inFence = !$inFence;
                $result[] = $line;
                continue;
            }
            if ($inFence) {
                $result[] = $line;
                continue;
            }

            $tag = $this->getBlockTag($line);
            if ($tag === null) {
                $result[] = $line;
                continue;
            }

            $end = $this->findBlockEnd($lines, $i, $tag);
            if ($end === null) {
                $result[] = $line;
                continue;
            }

            $block = implode("\n", array_slice($lines, $i, $end - $i + 1));
            $this->blocks[] = $block;
            $result[] = self::PLACEHOLDER_START . (count($this->blocks) - 1) . self::PLACEHOLDER_END;
            $i = $end;
        }

        return implode("\n", $result);
    }

    /**
     * Restores the raw HTML blocks. A placeholder, that was wrapped by the parser
     * to a paragraph, is replaced including the paragraph tags.
     */
    public function restore(string $html): string
    {
        $pattern = sprintf(
            '#(?:<p>\s*)?%s(\d+)%s(?:\s*</p>)?#',
            self::PLACEHOLDER_START,
            self::PLACEHOLDER_END,
        );

        return (string)preg_replace_callback(
            $pattern,
            fn(array $matches): string => $this->blocks[(int)$matches[1]] ?? '',
            $html,
        );
    }

    /**
     * Returns the tag name, if the line starts a block element.
     */
    /**
     * Returns the tag name, if the line starts a block element.
     *
     * A line that is indented by four spaces or a tab is an indented code block, so
     * its content must not be protected. Otherwise a code sample, that shows HTML,
     * would be rendered as HTML.
     */
    private function getBlockTag(string $line): ?string
    {
        if (preg_match('#^(?: {4}|\t)#', $line) === 1) {
            return null;
        }

        $tags = array_merge(self::HTML_BLOCK_TAGS, self::LITERAL_TAGS);
        if (preg_match(sprintf('#^ {0,3}<(%s)(?:\s[^>]*)?>[ \t]*$#i', implode('|', $tags)), $line, $matches) === 1) {
            return strtolower($matches[1]);
        }
        return null;
    }

    /**
     * Finds the line with the closing tag of the block. Nested elements of the same type are counted,
     * but the content of pre, script, style and textarea is not parsed as HTML.
     *
     * @param string[] $lines
     */
    private function findBlockEnd(array $lines, int $start, string $tag): ?int
    {
        $open = sprintf('#<(?:%1$s)(?:\s[^>]*)?>#i', $tag);
        $close = sprintf('#</%1$s>#i', $tag);

        $depth = 1;
        for ($i = $start + 1, $count = count($lines); $i < $count; $i++) {
            if ($this->startsWithLiteral($lines[$i])) {
                $i = $this->findLiteralEnd($lines, $i);
                continue;
            }
            $depth += (int)preg_match_all($open, $lines[$i]);
            $depth -= (int)preg_match_all($close, $lines[$i]);
            if ($depth <= 0) {
                return $i;
            }
        }

        return null;
    }

    private function startsWithLiteral(string $line): bool
    {
        $pattern = sprintf('#^[ \t]*<(?:%s)(?:\s[^>]*)?>#i', implode('|', self::LITERAL_TAGS));
        return preg_match($pattern, $line) === 1;
    }

    /**
     * @param string[] $lines
     */
    private function findLiteralEnd(array $lines, int $start): int
    {
        $pattern = sprintf('#</(?:%s)>#i', implode('|', self::LITERAL_TAGS));
        for ($i = $start, $count = count($lines); $i < $count; $i++) {
            if (preg_match($pattern, $lines[$i]) === 1) {
                return $i;
            }
        }
        return $start;
    }
}
