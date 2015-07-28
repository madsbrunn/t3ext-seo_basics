<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// Adding title tag field to pages TCA
$tmpCol = array(
	'tx_seo_titletag' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:seo_basics/Resources/Private/Language/locallang_be.xlf:pages.titletag',
		'config' => Array (
			'type' => 'input',
			'size' => '70',
			'max' => '70',
			'eval' => 'trim'
		)
	),
	'tx_seo_canonicaltag' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:seo_basics/Resources/Private/Language/locallang_be.xlf:pages.canonicaltag',
		'config' => Array (
			'type' => 'input',
			'size' => '70',
			'max' => '70',
			'eval' => 'trim'
		)
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tmpCol, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', $tmpCol, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_seo_titletag;;;;, tx_seo_canonicaltag', 1, 'before:keywords');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages_language_overlay', 'tx_seo_titletag, tx_seo_canonicaltag, nav_title, tx_realurl_pathsegment;;;;', "4,5", 'after:subtitle');

$GLOBALS['TCA']['pages_language_overlay']['interface']['showRecordFieldList'] .= ',tx_seo_titletag, tx_seo_canonicaltag';
