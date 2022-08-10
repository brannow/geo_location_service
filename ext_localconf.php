<?php

defined('TYPO3_MODE') or die();

// Register geo location cache
(function () {
    $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\CPSIT\GeoLocationService\Cache\GeoLocationCache::NAME]??[];
    if (!isset($cacheConfigurations['backend'])) {
        $cacheConfigurations['backend']
            = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
    }
    if (!isset($cacheConfigurations['options'])) {
        $cacheConfigurations['options'] = [
            'defaultLifetime' => \CPSIT\GeoLocationService\Cache\GeoLocationCache::DEFAULT_LIFETIME,
        ];
    }
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\CPSIT\GeoLocationService\Cache\GeoLocationCache::NAME] = $cacheConfigurations;
})();
