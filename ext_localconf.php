<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// adding tx_seo_titletag,tx_seo_canonicaltag and tx_seo_noindex to the pageOverlayFields so it is recognized when fetching the overlay fields
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_seo_titletag,tx_seo_canonicaltag,tx_seo_noindex';

$extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['seo_basics']);

	// registering hook for correct indenting of output
if ($extconf['sourceFormatting'] == '1') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['tx_seobasics'] = 'B13\\SeoBasics\\Frontend\\Hook->processOutputHook';
}

	// registering sitemap.xml for each hierachy of configuration to realurl (meaning to every website in a multisite installation)
if ($extconf['xmlSitemap'] == '1') {
	$realurl = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'];
	if (is_array($realurl))	{
		foreach ($realurl as $host => $cnf) {
			// we won't do anything with string pointer (e.g. example.org => www.example.org)
			if (!is_array($realurl[$host])) {
				continue;
			}

			if (!isset($realurl[$host]['fileName'])) {
				$realurl[$host]['fileName'] = array();
			}
			$realurl[$host]['fileName']['index']['sitemap.xml']['keyValues']['type'] = 776;
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'] = $realurl;
	}
}
