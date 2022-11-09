<?php

$settings = [];

/**
 * Common settings
 */
$settings['language'] = 'de';
$settings['locale'] = 'de_DE.UTF-8';
$settings['charset'] = 'UTF-8';
$settings['theme'] = 'default';

/**
 * Paths
 */
$settings['paths'] = [];
$settings['paths']['app'] = 'APP_PATH';
$settings['paths']['data'] = 'SITE_PATH/data';
$settings['paths']['media'] = 'SITE_PATH/media';
$settings['paths']['pages'] = 'SITE_PATH/pages';
$settings['paths']['plugins'] = 'SITE_PATH/extend/plugins'; // TODO move config
$settings['paths']['site'] = 'SITE_PATH';
$settings['paths']['themes'] = 'SITE_PATH/themes';
$settings['paths']['web'] = 'WEB_PATH';

/**
 * URLs
 */
$settings['urls'] = [];
$settings['urls']['media'] = 'WEB_URL/media';
$settings['urls']['web'] = 'WEB_URL/';

/**
 * File extensions
 */
$settings['fileExtensions'] = [];
$settings['fileExtensions']['layouts'] = 'twig';
//$settings['fileExtensions']['mediaImages'] = 'ai,bmp,gif,ico,jpg,png,psd,svg,tiff';
//$settings['fileExtensions']['mediaDocuments'] = 'csv,doc,docx,md,pdf,ppt,rtf,xls,xlsx';
//$settings['fileExtensions']['mediaArchives'] = 'gz,gzip,tar,tgz,zip';
//$settings['fileExtensions']['mediaCode'] = 'css,html,js,json,xml';
//$settings['fileExtensions']['mediaVideos'] = 'avi,flv,mov,mp4,mv4,ogg,ogv,swf,webm';
//$settings['fileExtensions']['mediaAudio'] = 'aiff,m4a,midi,mp3,wav';
$settings['fileExtensions']['pages'] = 'htm,html,markdown,md,rss,rst,textile,txt,xml';

/**
 * Components
 */
$settings['components'] = [];

/**
 * Data Repository Component
 */
$settings['components']['dataRepository'] = [];
$settings['components']['dataRepository']['adapter'] = 'json';

/**
 * Download Middleware Component
 */
$settings['components']['downloadMiddleware'] = [];
$settings['components']['downloadMiddleware']['route'] = 'download';
$settings['components']['downloadMiddleware']['storagePath'] = '@site/media';

/**
 * PSR-16 File Cache Component
 */
$settings['components']['fileCache'] = [];
$settings['components']['fileCache']['path'] = '@site/runtime/cache/system';

/**
 * PSR-3 File Logger Component
 */
$settings['components']['fileLogger'] = [];
$settings['components']['fileLogger']['path'] = '@site/runtime/log/logger.log';
$settings['components']['fileLogger']['channel'] = 'herbie';
$settings['components']['fileLogger']['level'] = 'debug';

/**
 * Page Renderer Middleware Component
 */
$settings['components']['pageRendererMiddleware'] = [];
$settings['components']['pageRendererMiddleware']['cache'] = false;
$settings['components']['pageRendererMiddleware']['cacheTTL'] = 60 * 60 * 24;

/**
 * Twig Renderer Component
 */
$settings['components']['twigRenderer'] = [];
$settings['components']['twigRenderer']['autoescape'] = 'html';
$settings['components']['twigRenderer']['cache'] = false;
$settings['components']['twigRenderer']['charset'] = 'UTF-8';
$settings['components']['twigRenderer']['debug'] = false;
$settings['components']['twigRenderer']['strictVariables'] = false;

/**
 * URL-Manager Component
 */
$settings['components']['urlManager'] = [];
$settings['components']['urlManager']['niceUrls'] = false;
$settings['components']['urlManager']['rules'] = [];

/**
 * Plugin Configurations
 */
$settings['plugins'] = [];

/**
 * Core Plugin
 */
$settings['plugins']['CORE'] = [];
$settings['plugins']['CORE']['enableTwigInLayoutFilter'] = true;
$settings['plugins']['CORE']['enableTwigInSegmentFilter'] = true;

/**
 * Local Extensions Plugin
 */
$settings['plugins']['LOCAL_EXT'] = [];
$settings['plugins']['LOCAL_EXT']['pathApplicationMiddlewares'] = 'SITE_PATH/extend/middlewares_app';
$settings['plugins']['LOCAL_EXT']['pathConsoleCommands'] = 'SITE_PATH/extend/commands';
$settings['plugins']['LOCAL_EXT']['pathEventListeners'] = 'SITE_PATH/extend/events';
$settings['plugins']['LOCAL_EXT']['pathInterceptingFilters'] = 'SITE_PATH/extend/filters';
$settings['plugins']['LOCAL_EXT']['pathRouteMiddlewares'] = 'SITE_PATH/extend/middlewares_route';
$settings['plugins']['LOCAL_EXT']['pathTwigFilters'] = 'SITE_PATH/extend/twig_filters';
$settings['plugins']['LOCAL_EXT']['pathTwigGlobals'] = 'SITE_PATH/extend/twig_globals';
$settings['plugins']['LOCAL_EXT']['pathTwigFunctions'] = 'SITE_PATH/extend/twig_functions';
$settings['plugins']['LOCAL_EXT']['pathTwigTests'] = 'SITE_PATH/extend/twig_tests';

/**
 * Enabled Plugins
 */
$settings['enabledPlugins'] = '';

/**
 * Enabled System Plugins
 */
$settings['enabledSysPlugins'] = '';

return $settings;
