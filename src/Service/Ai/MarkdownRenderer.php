<?php
declare(strict_types=1);

namespace App\Service\Ai;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Renders model answers (Markdown) to safe HTML.
 *
 * Raw HTML is stripped and unsafe links (javascript:, data:…) are removed, so the
 * output can be printed without h().
 */
class MarkdownRenderer
{
    private GithubFlavoredMarkdownConverter $converter;

    /**
     * Builds the converter with the safe configuration.
     */
    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Converts Markdown to sanitized HTML.
     *
     * @param string $markdown Model output.
     * @return string
     */
    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
