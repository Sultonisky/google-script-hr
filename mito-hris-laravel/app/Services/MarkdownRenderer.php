<?php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;

class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Convert a Markdown string to safe HTML.
     *
     * Raw HTML from user input is stripped (not rendered).
     * Unsafe links (e.g. javascript:) are disallowed.
     * Returns an empty string when $content is null or blank.
     */
    public function render(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        return $this->converter
            ->convert($content)
            ->getContent();
    }
}
