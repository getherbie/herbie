# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Static architecture analysis with Deptrac (`deptrac.php`, `composer deptrac`), which is part of `composer test`. It ensures that the core does not depend on plugins and that plugins only use the public extension API.
- New `spaceless` Twig filter. It replaces the Twig filter of the same name that is deprecated since Twig 3.12 and keeps the output of existing templates unchanged.

### Changed
- **Breaking:** Minimum PHP version raised from 8.0 to 8.3. Supported versions: 8.3, 8.4, 8.5, 8.6.
- Version bumped to 3.0.0 (new major line).
- **Breaking:** `psr/container` raised from `^1.1` to `^2.0` (PSR-11 2.0). `herbie\Container::get()` now declares the return type `mixed`. Plugins that implement `Psr\Container\ContainerInterface` themselves must add `mixed` as the return type of `get()`, and plugins that require `psr/container ^1.x` must widen their constraint.
- Contact plugin now uses PHPMailer for SMTP delivery; SMTP settings are configured under `plugins.contact.smtp`.
- **Breaking:** System, Composer and local plugins are activated with one list, the config option `enabledPlugins`. The option `enabledSysPlugins` is removed, move its names to `enabledPlugins`. Plugins are resolved by name, no matter where they come from, and loaded in the order of the list. Before, system plugins were always loaded first, then Composer plugins and then local plugins. If two plugins define the same Twig function, filter or test, the plugin listed last wins. A name in `enabledPlugins` that matches no plugin, or a plugin with an incomplete config, is logged as a warning instead of being skipped silently. If a plugin name exists as system, Composer and local plugin, the local plugin takes precedence over the Composer plugin and the Composer plugin over the system plugin. The virtual plugins `CORE` (first) and `LOCAL_EXT`, `APP_EXT` and `SYS_INFO` (last) keep their position.
- **Breaking:** Plugins are marked with the new attribute `#[herbie\Plugin('name')]` and found by scanning the PHP files in the plugin directory (one level deep, with the tokenizer, nothing is executed). The file `config.php` of a plugin is optional and only returns default values, the settings `apiVersion`, `pluginName`, `pluginClass` and `pluginPath` are removed from it. Plugin classes that are not autoloadable, i.e. local plugins, are loaded by Herbie, a `require_once` in `config.php` is no longer needed. Each plugin entry in the config has the new key `pluginFile`, the key `apiVersion` is gone. Outside of debug mode the discovery is cached in `site/runtime/cache/system/plugins.php`. The cache is renewed if a plugin directory or a Composer plugin is added, removed or updated, otherwise `clear-files` clears it. New classes `herbie\Plugin` (the attribute) and `herbie\PluginDiscovery`.
- **Breaking:** The packages of the plugins `imagine`, `markdown`, `rst` and `textile` are optional. Install the package of each plugin that you enable: `imagine/imagine`, `league/commonmark`, `doctrine/rst-parser` and `netcarver/textile`. `imagine/imagine` moves from `require` to `suggest`. The argument `requires` of the attribute `#[herbie\Plugin]` declares the packages a plugin needs, e.g. `#[Plugin('markdown', requires: ['league/commonmark' => '^2.10'])]`. They are checked when the plugin is enabled, a missing package or a wrong version throws an exception that says what to install. The plugins `markdown`, `rst` and `textile` no longer log an error and return the text unchanged if their package is missing. `composer/semver` is a new requirement.
- **Breaking:** The global functions `herbie\load_plugin_config()`, `herbie\load_plugin_configs()` and `herbie\load_composer_plugin_configs()` are removed, `herbie\PluginDiscovery` replaces them.
- **Breaking:** The plugins `simplecontact` and `simplesearch` are now part of Herbie as system plugins and are renamed to `contact` and `search` (`herbie\plugins\contact\ContactPlugin`, `herbie\plugins\search\SearchPlugin`). The composer packages `getherbie/plugin-simplecontact` and `getherbie/plugin-simplesearch` are replaced by Herbie, remove them from your `composer.json`. The names change everywhere: in `enabledPlugins`, in the config (`plugins.contact.*`, `plugins.search.*`), in the Twig functions (`contact()`, `search_form()` and `search_results()`), in the translation categories and in the CSS classes (`.contact*`, `.plugin-search-*`). The settings are no longer wrapped in a `config` key, `plugins.simplecontact.config.recipient` is now `plugins.contact.recipient`, the same applies to `template` and to the settings of `search` (`formTemplate`, `resultsTemplate`, `useCache`). A custom template path must not point to `@vendor/getherbie/plugin-simple*/templates` anymore, the defaults are now `@plugin/contact/templates` and `@plugin/search/templates`.
- **Breaking:** The plugin `rest` is renamed to `rst`, this applies to the name in `enabledPlugins`, the config (`plugins.rst.*`), the Twig filter and function `rst`, the class `herbie\plugins\rst\RstPlugin` and the page format. `page.format` is now `rst` instead of `rest`. Files with the extension `.rst` are recognized as before.
- **Breaking:** The suffix `Sys` is removed from the class names of the system plugins: `DummyPlugin`, `ImaginePlugin`, `MarkdownPlugin`, `TextilePlugin` (before `…SysPlugin`). The namespace `herbie\sysplugins` is renamed to `herbie\plugins` and the alias `@sysplugin` to `@plugin` (the Twig namespace `sysplugin` to `plugin`).
- **Breaking:** Abstract plugin base classes get the prefix `Abstract`: `herbie\Plugin` is now `herbie\AbstractPlugin` (plugins must `extend AbstractPlugin`) and `herbie\sysplugins\ParserPlugin` is now `herbie\plugins\AbstractParserPlugin`. The name `herbie\Plugin` is now the attribute that marks a plugin.
- **Breaking:** The markdown plugin uses `league/commonmark` instead of `erusev/parsedown` / `erusev/parsedown-extra`. The new option `plugins.markdown.flavor` selects the markdown dialect: `commonmark` (default), `gfm` or `markdown-extra`. An unknown value falls back to `commonmark`. Options for the CommonMark environment are passed with `plugins.markdown.commonmark`.
- `time_format()` now uses PHP's `date()` format syntax instead of the deprecated `strftime()` (strftime removed in PHP 9.0). **Breaking:** the function is removed, use `date_format()` instead.
- **Breaking:** `Alias::get()` resolves only a leading alias and only as a whole segment. An unknown alias throws an `InvalidArgumentException` instead of being returned unchanged; previously `strtr()` replaced every occurrence anywhere in the string, so `@siteother/x` resolved to the path of `@site`. Paths without a leading alias are still returned unchanged. The Twig tests `file_readable` and `file_writable` share this method, so they raise an exception for an unknown alias instead of evaluating to `false`.
- PSR-17 factory selection in `ContainerBuilder` deduplicated via `preferredPsr17Package()`.
- Page files are sorted naturally instead of lexicographically, so `10-foo.md` follows `9-foo.md` and `2-foo.md`. Number prefixes no longer need to be zero-padded.
- `UTF8_BOM` constant is now defined at file scope in `FlatFilePagePersistence`.

### Documentation
- The Twig pages no longer list Twig's built-in filters, functions, tags and tests. They link to the official Twig documentation and document only Herbie's own additions.
- The Contents chapter was revised: examples use the default layout extension, special pages set `layout: ""` and `content_type`, the page variable reference exists only once, and the Markdown page no longer duplicates the CommonMark option reference.
- The Layouts page now documents `.twig` layout files, the layout extension configured with `fileExtensions.layouts`, and rendering segments with `content.<id>|raw` instead of the outdated `content()` function.
- The remaining layout examples in the Contents chapter, the First Steps chapter and the cheat sheet follow the same conventions.
- The Indepth chapter now matches the implementation: route middleware regular expressions are not anchored automatically, query builder examples use the hash format for numeric values, the removed `visible` filter is no longer listed, `twigify` is documented, and the event descriptions are corrected.
- The cheat sheet uses the query builder's `order()` method instead of the non-existent `sortBy()`. The Indepth page files use the same `1-index.md` naming as the other chapters.
- The parameter of the Twig functions `url_abs` and `url_rel` is marked as required. It was documented with an empty default, but `UrlManager::createUrl()` has never resolved an empty route to the current route. The `TODO` in `createUrl()` promised exactly that, so it is replaced by a description of what the method does.
- The Plugins page now lists the optional Composer package of each system plugin and distinguishes system, Composer and local plugins. It no longer claims that system plugins have no dependencies. The plugin list no longer shows the author and the plugin version, since both add no useful information for the reader, and links Contact and Search to their GitHub pages. The links are left-aligned below the description and use a plain link style instead of a filled button. The unused `author` and `version` fields are removed from the plugin data.
- The icons in the plugin buttons inherit the text color, so they match the label in the light and the dark theme.

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

### Security
- `FileCache` restricts deserialization to scalar/array values (`allowed_classes => false`).
- `handle_internal_webserver_assets()` prevents path traversal outside the base directory.
- `FlatFilePagePersistence::findById()` restricts access to the page root.
- Contact SMTP credentials are kept out of version control: `site/config/main.dist.php` is the tracked template, `main.php` is gitignored and filled via CI secrets.

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

[Unreleased]: https://github.com/getherbie/herbie/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/getherbie/herbie/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/getherbie/herbie/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/getherbie/herbie/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/getherbie/herbie/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/getherbie/herbie/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/getherbie/herbie/compare/v0.5.0...v1.0.0
