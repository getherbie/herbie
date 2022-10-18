<?php

$settings = [];

/**
 * Common settings
 */
$settings['language'] = 'de';
$settings['locale'] = 'de_DE.UTF-8';
$settings['charset'] = 'UTF-8';
$settings['theme'] = 'default';
$settings['niceUrls'] = false;

/**
 * Paths
 */
$paths = [];
$settings['paths']['app'] = 'APP_PATH';
$settings['paths']['data'] = 'SITE_PATH/data';
$settings['paths']['media'] = 'SITE_PATH/media';
$settings['paths']['pages'] = 'SITE_PATH/pages';
$settings['paths']['plugins'] = 'SITE_PATH/extend/plugins';
$settings['paths']['site'] = 'SITE_PATH';
$settings['paths']['themes'] = 'SITE_PATH/themes';
$settings['paths']['twigFilters'] = 'SITE_PATH/extend/twig_filters';
$settings['paths']['twigGlobals'] = 'SITE_PATH/extend/twig_globals';
$settings['paths']['twigFunctions'] = 'SITE_PATH/extend/twig_functions';
$settings['paths']['twigTests'] = 'SITE_PATH/extend/twig_tests';
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
$settings['fileExtensions']['media'] = [];
$settings['fileExtensions']['media']['images'] = 'ai,bmp,gif,ico,jpg,png,psd,svg,tiff';
$settings['fileExtensions']['media']['documents'] = 'csv,doc,docx,md,pdf,ppt,rtf,xls,xlsx';
$settings['fileExtensions']['media']['archives'] = 'gz,gzip,tar,tgz,zip';
$settings['fileExtensions']['media']['code'] = 'css,html,js,json,xml';
$settings['fileExtensions']['media']['videos'] = 'avi,flv,mov,mp4,mv4,ogg,ogv,swf,webm';
$settings['fileExtensions']['media']['audio'] = 'aiff,m4a,midi,mp3,wav';
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
$settings['components']['downloadMiddleware']['baseUrl'] = '/download/';
$settings['components']['downloadMiddleware']['storagePath'] = '@site/media';

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
 * URL-Matcher Component
 */
$settings['components']['urlMatcher'] = [];
$settings['components']['urlMatcher']['rules'] = [];

/**
 * VirtualCorePlugin Component
 */
$settings['components']['virtualCorePlugin'] = [];
$settings['components']['virtualCorePlugin']['enableTwigInLayoutFilter'] = true;
$settings['components']['virtualCorePlugin']['enableTwigInSegmentFilter'] = true;

/**
 * Plugin Configurations
 */
$settings['plugins'] = [];

/**
 * Enabled Plugins
 */
$settings['enabledPlugins'] = '';

/**
 * Enabled System Plugins
 */
$settings['enabledSysPlugins'] = '';

return $settings;
