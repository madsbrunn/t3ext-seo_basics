<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// Adding a static template TypoScript configuration from static/
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('seo_basics', 'Configuration/TypoScript', 'Metatags and XML Sitemap');

