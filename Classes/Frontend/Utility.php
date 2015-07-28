<?php

namespace B13\SeoBasics\Frontend;

/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Benjamin Mack <benni@typo3.org>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @author		Benjamin Mack (benni@typo3.org)
 * @subpackage	tx_seobasics
 *
 * This package includes all hook implementations.
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Utility {

	/**
	 * returns the URL for the current webpage
	 */
	public function getCanonicalUrl($content, $conf) {

		if ($GLOBALS['TSFE']->page['tx_seo_canonicaltag']) {
			$url = $GLOBALS['TSFE']->page['tx_seo_canonicaltag'];
		} else {
			$pageId = $GLOBALS['TSFE']->id;
			$pageType = $GLOBALS['TSFE']->type;

			if ($GLOBALS['TSFE']->MP) {
				$mountPointInUse = TRUE;
				list($originalMountPage, $targetMountPage) = explode('-', $GLOBALS['TSFE']->MP);
				$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] = 0;
				$MP = $GLOBALS['TSFE']->MP;
				$GLOBALS['TSFE']->MP = '';
			}

			$configuration = array(
				'parameter' => $pageId . ',' . $pageType,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'method' => 'GET',
					'exclude' => 'MP'
				),
				'forceAbsoluteUrl' => 1
			);
			$url = $GLOBALS['TSFE']->cObj->typoLink_URL($configuration);
			$url = $GLOBALS['TSFE']->baseUrlWrap($url);

			if ($mountPointInUse) {
				$GLOBALS['TSFE']->MP = $MP;
				$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] = 1;
			}

		}

		if ($url) {
			$urlParts = parse_url($url);
			$scheme = $urlParts['scheme'];
			if (isset($conf['useDomain'])) {
				if ($conf['useDomain'] == 'current') {
					$domain = GeneralUtility::getIndpEnv('HTTP_HOST');
				} else {
					$domain = $conf['useDomain'];
				}
				if (!$scheme) {
					$scheme = 'http';
				}
    			$url =  $scheme . '://' . $domain . $urlParts['path'];
			} elseif (!$urlParts['scheme']) {
				$pageWithDomains = $GLOBALS['TSFE']->findDomainRecord();
				// get first domain record of that page
				$allDomains = $GLOBALS['TSFE']->sys_page->getRecordsByField(
					'sys_domain',
					'pid', $pageWithDomains,
					'AND redirectTo = ""' . $GLOBALS['TSFE']->sys_page->enableFields('sys_domain'),
					'',
					'sorting ASC'
				);
				if (count($allDomains)) {
					$domain = (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://');
					$domain = $domain . $allDomains[0]['domainName'];
					$domain = rtrim($domain, '/') . '/' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
				} else {
					$domain = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
				}
				$url = rtrim($domain, '/') . '/' . ltrim($url, '/');
			}
				// remove everything after the ?
			list($url, ) = explode('?', $url);
		}
		return $url;
	}

}
