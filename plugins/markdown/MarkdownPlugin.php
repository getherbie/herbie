<?php

declare(strict_types=1);

namespace herbie\plugins\markdown;

use herbie\Config;
use herbie\Plugin;
use herbie\plugins\AbstractParserPlugin;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Psr\Log\LoggerInterface;

#[Plugin('markdown', requires: ['league/commonmark' => '^2.10'])]
final class MarkdownPlugin extends AbstractParserPlugin
{
    /**
     * Available markdown flavours
     *
     * - commonmark: The specification at https://spec.commonmark.org/
     * - gfm: GitHub Flavored Markdown (CommonMark plus autolinks, tables, strikethrough, task lists)
     * - markdown-extra: Markdown Extra features on top of CommonMark
     */
    private const array FLAVORS = [
        'commonmark' => [],
        'gfm' => [GithubFlavoredMarkdownExtension::class],
        'markdown-extra' => [
            \League\CommonMark\Extension\Attributes\AttributesExtension::class,
            \League\CommonMark\Extension\DescriptionList\DescriptionListExtension::class,
            \League\CommonMark\Extension\Footnote\FootnoteExtension::class,
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
            \League\CommonMark\Extension\Table\TableExtension::class,
            \League\CommonMark\Extension\TaskList\TaskListExtension::class,
        ],
    ];

    private ?MarkdownConverter $converter = null;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger, 'plugins.markdown');
    }

    protected function getFormats(): array
    {
        return ['markdown', 'md'];
    }

    protected function getTwigName(): string
    {
        return 'markdown';
    }

    public function parse(string $value): string
    {
        return $this->parseSafely(
            $value,
            function (string $text): string {
                $protector = new RawHtmlProtector();
                $markdown = $protector->protect($text);
                $html = $this->getConverter()->convert($markdown)->getContent();
                return trim($protector->restore($html));
            },
        );
    }

    private function getConverter(): MarkdownConverter
    {
        if ($this->converter === null) {
            $environment = new Environment($this->getEnvironmentOptions());
            $environment->addExtension(new CommonMarkCoreExtension());

            foreach ($this->getExtensionClasses() as $class) {
                /** @var ExtensionInterface $extension */
                $extension = new $class();
                $environment->addExtension($extension);
            }

            $this->converter = new MarkdownConverter($environment);
        }
        return $this->converter;
    }

    /**
     * Returns the extension classes of the configured flavour
     *
     * @return string[]
     */
    private function getExtensionClasses(): array
    {
        $flavour = $this->config->getAsString('flavor', 'commonmark');
        if (!isset(self::FLAVORS[$flavour])) {
            $flavour = 'commonmark';
        }

        return self::FLAVORS[$flavour];
    }

    /**
     * Options for the CommonMark environment, they are passed as they are.
     * See https://commonmark.thephpleague.com/2.10/configuration/
     *
     * @return array<string, mixed>
     */
    private function getEnvironmentOptions(): array
    {
        /** @var array<string, mixed> $options */
        $options = $this->config->getAsArray('commonmark');

        // values have to be cast, because the configuration may come from a YAML file
        foreach ($options as $key => $value) {
            if ($key === 'html_input' && is_bool($value)) {
                $options[$key] = $value ? 'allow' : 'strip';
            }
        }

        return $options;
    }
}
