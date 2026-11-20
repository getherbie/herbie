# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

## [3.0.0] - TBD

### Added
- Static architecture analysis with Deptrac (`deptrac.php`, `composer qa:check:deptrac`), which is part of `composer qa`. It ensures that the core does not depend on plugins and that plugins only use the public extension API.
- New `spaceless` Twig filter. It replaces the Twig filter of the same name that is deprecated since Twig 3.12 and keeps the output of existing templates unchanged.
- Code style checks with php-cs-fixer (PER-CS 3.0, replaces phpcs/PSR-12) and Twig-CS-Fixer, and a PHP lint script. All three run first in `composer qa` and in CI.
- Composer scripts are grouped under a `qa:*` prefix. `composer qa` runs everything, `qa:check:*` the static checks, `qa:test:*` the Codeception suites and `qa:fix:*` the fixable code-style rules.
- Theme conventions: templates, `@theme` assets and icons are resolved from the active theme with one parent theme as fallback, configured with `themeParent` (default: `default`). Theme assets are published under `/assets/themes/<theme>/`.
- New Twig function `html_attributes()` that serializes and escapes HTML attributes. Attributes with `null` or `false` values are omitted, `true` renders a boolean attribute, and names are escaped. `link_file`, `link_page`, `link_download`, `image`, `menu_breadcrumb` and `menu_pager` render their HTML with Twig snippets (`templates/snippets/`) and accept a `template` parameter for custom overrides.
- The contact form is protected by a signed double-submit CSRF token (stateless, no server session), a self-hosted ALTCHA proof-of-work, configurable honeypot fields, form-age checks and a per-IP rate limit. A sender copy can be enabled with `plugins.contact.copy.enabled`. The secrets `plugins.contact.csrf.secret` and `plugins.contact.captcha.secret` are required, the form fails to render if they are empty or still a placeholder. Mail is sent with PHPMailer, SMTP settings are under `plugins.contact.smtp`.
- The search plugin renders results from the rendered page content, highlights the search terms, requires all terms to match and supports an exact phrase search (`?exact=1`). Pages with `no_search: true` are excluded. Results are cached with `plugins.search.useCache`.
- Markdown fenced code blocks are protected from segment and HTML block parsing.

### Changed
- **Breaking:** Minimum PHP version raised from 8.0 to 8.3. Supported versions: 8.3, 8.4, 8.5, 8.6.
- Version bumped to 3.0.0 (new major line).
- **Breaking:** `psr/container` raised from `^1.1` to `^2.0` (PSR-11 2.0). `herbie\Container::get()` now declares the return type `mixed`. Plugins that implement `Psr\Container\ContainerInterface` themselves must add `mixed` as the return type of `get()`, and plugins that require `psr/container ^1.x` must widen their constraint.
- **Breaking:** All plugins (system, Composer, local) are activated with one list, `enabledPlugins`; `enabledSysPlugins` is removed. Plugins load in list order instead of system, Composer, local; `CORE` stays first, `LOCAL_EXT`, `APP_EXT` and `SYS_INFO` last. On duplicate Twig functions, filters or tests the last plugin wins. If a name exists in several sources, local beats Composer beats system. Unknown names and incomplete plugin configs are logged as warnings.
- **Breaking:** Plugins are marked with `#[herbie\Plugin('name')]` and found by scanning the PHP files in the plugin directory (one level deep, via tokenizer). `config.php` is optional and only returns defaults; `apiVersion`, `pluginName`, `pluginClass` and `pluginPath` are removed from it, each plugin entry gets `pluginFile`. Local plugins are loaded by Herbie, no `require_once` needed. Outside debug mode the discovery is cached in `site/runtime/cache/system/plugins.php`, renewed when plugins change and cleared by `clear-files`. New classes: `herbie\Plugin`, `herbie\PluginDiscovery`.
- **Breaking:** The packages of the plugins `imagine`, `markdown`, `rst` and `textile` are optional (`imagine/imagine` moves to `suggest`). For each enabled plugin install `imagine/imagine`, `league/commonmark`, `doctrine/rst-parser` or `netcarver/textile`. Plugins declare needs with `#[Plugin('markdown', requires: ['league/commonmark' => '^2.10'])]`; a missing or wrong package throws an exception that names what to install, instead of logging an error and returning the text unchanged. New requirement: `composer/semver`.
- **Breaking:** The global functions `herbie\load_plugin_config()`, `herbie\load_plugin_configs()` and `herbie\load_composer_plugin_configs()` are removed, `herbie\PluginDiscovery` replaces them.
- **Breaking:** `simplecontact` and `simplesearch` are now system plugins named `contact` and `search` (`herbie\plugins\contact\ContactPlugin`, `herbie\plugins\search\SearchPlugin`). Remove `getherbie/plugin-simplecontact` and `-simplesearch` from `composer.json`. The new names apply to `enabledPlugins`, config (`plugins.contact.*`, `plugins.search.*`), Twig functions (`contact()`, `search_form()`, `search_results()`), translation categories and CSS classes (`.contact*`, `.plugin-search-*`). The `config` wrapper is gone (`plugins.simplecontact.config.recipient` is now `plugins.contact.recipient`, likewise `template` and the search settings `formTemplate`, `resultsTemplate`, `useCache`). Template defaults are `@plugin/contact/templates` and `@plugin/search/templates`, not `@vendor/getherbie/plugin-simple*/templates`.
- **Breaking:** The plugin `rest` is renamed to `rst`, this applies to the name in `enabledPlugins`, the config (`plugins.rst.*`), the Twig filter and function `rst`, the class `herbie\plugins\rst\RstPlugin` and the page format. `page.format` is now `rst` instead of `rest`. Files with the extension `.rst` are recognized as before.
- **Breaking:** The suffix `Sys` is removed from the class names of the system plugins: `DummyPlugin`, `ImaginePlugin`, `MarkdownPlugin`, `TextilePlugin` (before `…SysPlugin`). The namespace `herbie\sysplugins` is renamed to `herbie\plugins` and the alias `@sysplugin` to `@plugin` (the Twig namespace `sysplugin` to `plugin`).
- **Breaking:** Abstract plugin base classes get the prefix `Abstract`: `herbie\Plugin` is now `herbie\AbstractPlugin` (plugins must `extend AbstractPlugin`) and `herbie\sysplugins\ParserPlugin` is now `herbie\plugins\AbstractParserPlugin`. The name `herbie\Plugin` is now the attribute that marks a plugin.
- **Breaking:** The markdown plugin uses `league/commonmark` instead of `erusev/parsedown` / `erusev/parsedown-extra`. The new option `plugins.markdown.flavor` selects the markdown dialect: `commonmark` (default), `gfm` or `markdown-extra`. An unknown value falls back to `commonmark`. Options for the CommonMark environment are passed with `plugins.markdown.commonmark`.
- **Breaking:** `Alias::get()` resolves only a leading alias and only as a whole segment. An unknown alias throws an `InvalidArgumentException` instead of being returned unchanged; previously `strtr()` replaced every occurrence anywhere in the string, so `@siteother/x` resolved to the path of `@site`. Paths without a leading alias are still returned unchanged. The Twig tests `path_readable` and `path_writable` share this method, so they raise an exception for an unknown alias instead of evaluating to `false`.
- PSR-17 factory selection in `ContainerBuilder` deduplicated via `preferredPsr17Package()`.
- Page files are sorted naturally instead of lexicographically: `2-foo.md`, `9-foo.md`, `10-foo.md` (before: `10-foo.md`, `2-foo.md`, `9-foo.md`). Number prefixes no longer need to be zero-padded.
- `UTF8_BOM` constant is now defined at file scope in `FlatFilePagePersistence`.
- **Breaking:** Twig renames. The filter `format_size` is now `format_bytes`, the function `link_media` is now `link_download`, and the tests `file_readable` and `file_writable` are now `path_readable` and `path_writable`.
- **Breaking:** Twig parameter changes. `link_file` and `link_download` take `attribs` before `info`. The parameters `delim`, `pos` and `info` are now `delimiter`, `position` and `show_info`, `menu_pager` takes `prefix` instead of `limit`, and the parameter `iterator` of `query` is now `data`. The `link_mail` template fallback is removed, errors are handled like in the other link functions.
- **Breaking:** The framework's CSS classes and tokens are prefixed with `herbie-` and `--herbie-`. Theme tokens (for example `--surface`) and vendor tokens (`--altcha-*`) stay unchanged. Custom CSS that targets the old names must be adapted.
- **Breaking:** An empty `theme` value is rejected with an error when the config is loaded. Before, templates, assets, icons and `css_classes()` interpreted it differently.
- **Breaking:** The contact field `antispam` is replaced by honeypot fields, see `plugins.contact.honeypots`.
- **Breaking:** The `test` option of the imagine plugin is replaced by the per-collection flag `cache` (default: `true`).
- The search plugin option `plugins.search.usePageCache` (previously unused) is replaced by `plugins.search.useCache`.
- The code uses PHP 8.3 features: readonly classes, constructor property promotion, typed class constants, `#[Override]`, `#[SensitiveParameter]` and `json_validate()`.
- `Page` gets the `SlugGenerator` from `PageFactory` instead of a static property. Static caches in `Application`, `FlatFilePagePersistence` and the data repositories are now instance state or removed.
- Dependencies upgraded: Twig 3.6 to 3.30, PHPStan 2, Slim, Imagine, Laminas, nyholm and netcarver packages. The default Docker image uses PHP 8.3. macOS tests in CI run only on release tags and on manual dispatch.

### Documentation
- The Twig pages no longer list Twig's built-in filters, functions, tags and tests. They link to the official Twig documentation and document only Herbie's own additions.
- The Contents chapter was revised: examples use the default layout extension, special pages set `layout: ""` and `content_type`, the page variable reference exists only once, and the Markdown page no longer duplicates the CommonMark option reference.
- The Layouts page now documents `.twig` layout files, the layout extension configured with `fileExtensions.layouts`, and rendering segments with `content.<id>|raw` instead of the outdated `content()` function.
- The remaining layout examples in the Contents chapter, the First Steps chapter and the cheat sheet follow the same conventions.
- The Indepth chapter now matches the implementation: route middleware regular expressions are not anchored automatically, query builder examples use the hash format for numeric values, the removed `visible` filter is no longer listed, `twigify` is documented, and the event descriptions are corrected.
- The cheat sheet uses the query builder's `order()` method instead of the non-existent `sortBy()`. The Indepth page files use the same `1-index.md` naming as the other chapters.
- The parameter of the Twig functions `url_abs` and `url_rel` is marked as required. It was documented with an empty default, but `UrlManager::createUrl()` has never resolved an empty route to the current route. The `TODO` in `createUrl()` promised exactly that, so it is replaced by a description of what the method does.
- The Plugins page lists the optional Composer package of each system plugin and distinguishes system, Composer and local plugins. The list no longer shows author and version and links Contact and Search to GitHub, with plain links left-aligned below the description.
- The icons in the plugin buttons inherit the text color, so they match the label in the light and the dark theme.
- The documentation website was redesigned (hero, dark mode, mobile navigation, logo, favicon) and highlights Twig and Apache examples. The Recipes section and the System Info page were removed, the Themes and layouts, Middlewares, Extending, Markdown and Search pages were revised.

### Removed
- `herbie\plugins\markdown\HerbieParsedownExtra`, the PHP 8.3 compatibility workaround for ParsedownExtra.
- **Breaking:** The unused global functions `herbie\path_normalize()`, `herbie\defined_constants()` and `herbie\time_format()`. `time_format()` was an exact duplicate of `date_format()`, use that instead.
- **Breaking:** The global functions `herbie\defined_classes()`, `herbie\defined_functions()` and `herbie\get_callable_name()`. They were only used by `SystemInfoPlugin` and are now private methods of that class.

### Fixed
- Local plugins are discovered in `site/extend/plugins` again. `paths.plugins` points to Herbie's own plugin directory since 3.0 and was also used as the local plugin directory, so system plugins were discovered twice and local plugins were ignored. Local plugins now use the new setting `paths.localPlugins`, whose default is `SITE_PATH/extend/plugins`. An overridden `paths.plugins` is respected when the system plugins are relocated.
- Raw HTML blocks in markdown pages are kept intact again. CommonMark ends an HTML block at the first empty line, so such blocks are protected before parsing. Indented code samples that contain HTML stay code blocks, as before.
- The error handler answered with an HTML error page for every request, including routes that serve XML such as a feed or a sitemap, so the client received a document with the wrong root element. Such routes now receive an XML error document. In all other cases the `Content-Type` header now describes the body that is actually sent. **Breaking:** `ErrorHandlerMiddleware` requires a `PageRepositoryInterface` and a `UrlManager` in the constructor.
- `UrlManager::parseRequest()` now caches the parsed request when a route rule matches.
- Cache directory is created with mode 0755 instead of 0644.
- Website tests create `site/config/main.php` from `main.dist.php` on startup so they run without a committed config.
- `site.created` returns the latest page creation date as documented. The defaults of the site variables `charset` and `locale` are corrected.
- `PageList::shuffle()` keeps the routes of the pages, and `Pagination` accepts an empty list and any iterable.
- The query builder supports array values in the hash format (OR conditions), applies `offset` after filtering, does not change the configured limit in terminal methods, and `count()` works without a prior `all()`.
- A middleware chain without an end throws a `LogicException` with a readable message instead of a `TypeError`.
- A `Finder` without configured extensions returns no files instead of every file in the page directory.
- Search results include hidden pages unless they are marked with `no_search`.
- Assets are republished when the source is newer than the copy, so edited stylesheets show up on the next request.
- The script URL is detected on the PHP 8.3 CLI server for paths such as `/atom.xml`.
- `YamlDataRepository` no longer shares its scanned files with other repositories, a `JsonDataRepository` could return YAML files.
- The frontmatter regex is non-greedy, and the markdown format is detected for `.md` files.
- The contact form no longer uses the removed `FILTER_SANITIZE_STRING`, sends plain text mail that keeps line breaks, redirects after submitting and throws an exception if the contact page is configured with `cached: true`.
- The `strftime()`-based time formatting was replaced with `date()`.

### Security
- `FileCache` restricts deserialization to scalar/array values (`allowed_classes => false`).
- `handle_internal_webserver_assets()` prevents path traversal outside the base directory.
- `FlatFilePagePersistence::findById()` restricts access to the page root.
- Contact SMTP credentials are kept out of version control: `site/config/main.dist.php` is the tracked template, `main.php` is gitignored and filled via CI secrets.
- Contact form: mail header injection is prevented, see Added for CSRF and bot protection.

## [2.2.0] - 2025-05-23

### Added
- New `twigify` Twig filter to render a string with Twig
- New `twigify` Twig function to render a string with Twig

## [2.1.0] - 2025-04-13

### Added
- Support for PHP 8.3
- Support for Docker and Docker Compose

### Fixed
- Failed WebsiteCest

## [2.0.0] - 2023-01-02

See https://github.com/getherbie/herbie/issues/82.

## [1.1.0] - 2018-10-27
- Remove Composer minimum-stability
- Bump php version requirement to 5.6
- Fix phpcs code issues

## [1.0.1] - 2016-09-24
### Added
- CHANGELOG.md
- Throw Exception if plugins path not exist
- Moved config, messages, plugins and system folders to src folder

## [1.0.0] - 2016-03-25
- Release of Version 1.0.0

## [0.x] - 2014-11-11 to 2016-03-10

The releases `v0.5.0`, `v0.5.1`, `v0.6.0`, `v0.6.1`, `v0.7.0`, `v0.8.0`, `v0.8.1`, `v0.8.2`, `v0.8.3`, `v0.8.4`, `v0.8.5` and `v0.8.6` predate this changelog, which was introduced with 1.0.1. See the [Git tags](https://github.com/getherbie/herbie/tags) for their history.

---

`Added` for new features.  
`Changed` for changes in existing functionality.  
`Deprecated` for once-stable features removed in upcoming releases.  
`Removed` for deprecated features removed in this release.  
`Fixed` for any bug fixes.  
`Security` to invite users to upgrade in case of vulnerabilities.

---

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

[Unreleased]: https://github.com/getherbie/herbie/compare/v3.0.0...HEAD
[3.0.0]: https://github.com/getherbie/herbie/compare/v2.2.0...v3.0.0
[2.2.0]: https://github.com/getherbie/herbie/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/getherbie/herbie/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/getherbie/herbie/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/getherbie/herbie/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/getherbie/herbie/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/getherbie/herbie/compare/v0.5.0...v1.0.0
[0.x]: https://github.com/getherbie/herbie/tags
