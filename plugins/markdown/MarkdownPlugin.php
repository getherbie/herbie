<?php

declare(strict_types=1);

namespace herbie\plugins\markdown;

use herbie\Config;
use herbie\Plugin;
use herbie\plugins\AbstractParserPlugin;
use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Psr\Log\LoggerInterface;
use ReflectionClass;

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
        // @deprecated Kept for backwards compatibility with 3.0 configurations.
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
        $this->assertValidExtensions();
    }

    #[\Override]
    protected function getFormats(): array
    {
        return ['markdown', 'md'];
    }

    #[\Override]
    protected function getTwigName(): string
    {
        return 'markdown';
    }

    #[\Override]
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
     * Returns the extension classes of the configured flavour and the additional configured extensions
     *
     * @return string[]
     */
    private function getExtensionClasses(): array
    {
        $flavour = $this->config->getAsString('flavor', 'commonmark');
        if (!isset(self::FLAVORS[$flavour])) {
            $flavour = 'commonmark';
        }
        if ($flavour === 'markdown-extra') {
            $this->logger->warning(
                'The markdown flavor "markdown-extra" is deprecated; use "commonmark" or "gfm" instead.',
            );
        }

        /** @var array<mixed> $extensions */
        $extensions = array_merge(self::FLAVORS[$flavour], $this->config->getAsArray('extensions'));

        return array_values(array_unique(array_filter($extensions, is_string(...))));
    }

    private function assertValidExtensions(): void
    {
        foreach ($this->config->getAsArray('extensions') as $extension) {
            if (
                !is_string($extension)
                || !class_exists($extension)
                || !is_subclass_of($extension, ExtensionInterface::class)
            ) {
                $value = is_string($extension) ? $extension : (get_debug_type($extension));
                throw new InvalidArgumentException(sprintf('Invalid markdown extension class "%s".', $value));
            }
            $reflection = new ReflectionClass($extension);
            if (!$reflection->isInstantiable() || ($reflection->getConstructor()?->getNumberOfRequiredParameters() ?? 0) > 0) {
                throw new InvalidArgumentException(sprintf(
                    'Markdown extension class "%s" must be instantiable without constructor arguments.',
                    $extension,
                ));
            }
        }
    }

    /**
     * Options for the CommonMark environment, they are passed as they are.
     * See https://commonmark.thephpleague.com/2.x/configuration/
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
