<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}


	// Adding Web>Info module for SEO management
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'B13\\SeoBasics\\Controller\\ModuleController',
		NULL,
		'LLL:EXT:seo_basics/Resources/Private/Language/locallang_be.xlf:moduleFunction.tx_seobasics_modfunc1',
		'function',
		'online'
	);
}