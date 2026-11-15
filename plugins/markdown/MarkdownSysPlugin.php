<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use herbie\Config;
use herbie\events\RenderSegmentEvent;
use herbie\Plugin;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Psr\Log\LoggerInterface;
use Throwable;

final class MarkdownSysPlugin extends Plugin
{
    /**
     * Available markdown flavours
     *
     * - commonmark: The specification at https://spec.commonmark.org/
     * - gfm: GitHub Flavored Markdown (CommonMark plus autolinks, tables, strikethrough, task lists)
     * - markdown-extra: Markdown Extra features on top of CommonMark
     */
    private const FLAVORS = [
        'commonmark' => [],
        'gfm' => [GithubFlavoredMarkdownExtension::class],
        'markdown-extra' => [
            'League\CommonMark\Extension\Attributes\AttributesExtension',
            'League\CommonMark\Extension\DescriptionList\DescriptionListExtension',
            'League\CommonMark\Extension\Footnote\FootnoteExtension',
            'League\CommonMark\Extension\Strikethrough\StrikethroughExtension',
            'League\CommonMark\Extension\Table\TableExtension',
            'League\CommonMark\Extension\TaskList\TaskListExtension',
        ],
    ];

    private ?MarkdownConverter $converter = null;

    /**
     * MarkdownPlugin constructor.
     */
    public function __construct(
        private Config $config,
        LoggerInterface $logger
    ) {
        $this->config = $config->getAsConfig('plugins.markdown');
        if (!class_exists(Environment::class)) {
            $logger->error('Please install "league/commonmark" via composer');
        }
    }

    public function eventListeners(): array
    {
        return [
            [RenderSegmentEvent::class, [$this, 'onRenderSegment']]
        ];
    }

    public function twigFilters(): array
    {
        if (!$this->config->getAsBool('enableTwigFilter')) {
            return [];
        }
        return [
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }

    public function twigFunctions(): array
    {
        if (!$this->config->getAsBool('enableTwigFunction')) {
            return [];
        }
        return [
            ['markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]],
        ];
    }

    public function onRenderSegment(RenderSegmentEvent $event): void
    {
        if (in_array($event->getPage()->getFormat(), ['markdown', 'md'], true)) {
            $event->setSegment($this->parseMarkdown($event->getSegment()));
        }
    }

    public function parseMarkdown(string $string): string
    {
        try {
            $protector = new RawHtmlProtector();
            $markdown = $protector->protect($string);
            $html = $this->getConverter()->convert($markdown)->getContent();
            return trim($protector->restore($html));
        } catch (Throwable $e) {
            return $string;
        }
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
     * @return string[] Extension class names implementing ExtensionInterface
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
