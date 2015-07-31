<?php

namespace B13\SeoBasics\Controller;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2011 Benjamin Mack <benni@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * ************************************************************* */

/**
 * SEO Management extension
 *
 * @author	Benjamin Mack <benni@typo3.org>
 * @package TYPO3
 * @subpackage tx_seobasics
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

class ModuleController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	// load languages
	var $sysLanguages;
	var $sysHasLangs = false;
	var $langOnly = false; // if a language is selected
	var $langOverlays = array();
	var $pathCaches = array();
	var $extKey = 'seo_basics';
	// Internal, dynamic:
	var $searchResultCounter = 0;


	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:seo_basics/Resources/Private/Language/locallang.xlf');
	}

	/**
	 * Does some initial work for the page
	 *
	 * @return	array
	 */
	function init(&$pObj, $conf) {

		// load languages
		$trans = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider');
		$this->sysLanguages = $trans->getSystemLanguages($pObj->id, $GLOBALS['BACK_PATH']);

		// see if multiple languages exist in the system (array includes more than "0" (default) and "-1" (all))
		$this->sysHasLangs = (count($this->sysLanguages) > 2 ? true : false);
		parent::init($pObj, $conf);
	}

	/**
	 * Returns the menu array
	 *
	 * @return	array
	 */
	function modMenu() {
		$langs = $this->sysLanguages;
		ksort($langs);
		if (!$langs[-1]['title']) {
			$langs[-1]['title'] = 'Show All Languages';
		}
		foreach ($langs as $k => $v) {
			$langs[$k] = $v['title'];
		}

		return array(
			'depth' => array(
				0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
				1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
				2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
				3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
				99 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
			),
			'lang' => $langs,
			'hideShortcuts' => '',
			'hideSysFolders' => '',
			'hideNotInMenu' => '',
			'hideDisabled' => ''
		);
	}

	/**
	 * MAIN function for cache information
	 *
	 * @return	string		Output HTML for the module.
	 */
	function main() {

		$content = '';

		// specific language selection from form
		$depth = $this->pObj->MOD_SETTINGS['depth'];
		$langOnly = $this->pObj->MOD_SETTINGS['lang'];
		if ($langOnly != '' && $langOnly != '-1') {
			$this->langOnly = intval($langOnly);
		}

		$id = intval($this->pObj->id);
		if ($id) {

			// Add CSS
			$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '
				TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
				TABLE#tx-seobasics TD { vertical-align: top; }
			', $this->pObj->content);


			// Add Javascript
			$this->pObj->doc->JScode .= '<script type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/Js/mootools.v1.11.js"></script>';
			$this->pObj->doc->JScode .= '<script type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/Js/seobasics.js"></script>';

			// render depth selector
			$content = BackendUtility::getFuncMenu($id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth'], '');

			// if there are multiple languages, show dropdown to narrow it down.
			if ($this->sysHasLangs) {
				$content .= 'Display only language:&nbsp;';
				$content .= BackendUtility::getFuncMenu($id, 'SET[lang]', $this->pObj->MOD_SETTINGS['lang'], $this->pObj->MOD_MENU['lang'], '') . '<br/>';
			}

			$content .= BackendUtility::getFuncCheck($id, 'SET[hideShortcuts]', $this->pObj->MOD_SETTINGS['hideShortcuts'], '', '', 'id="SET[hideShortcuts]"');
			$content .= '<label for="SET[hideShortcuts]">Hide Shortcuts</label>&nbsp;&nbsp;';
			$content .= BackendUtility::getFuncCheck($id, 'SET[hideDisabled]', $this->pObj->MOD_SETTINGS['hideDisabled'], '', '', 'id="SET[hideDisabled]"');
			$content .= '<label for="SET[hideDisabled]">Hide Disabled Pages</label>&nbsp;&nbsp;<br/>';
			$content .= BackendUtility::getFuncCheck($id, 'SET[hideSysFolders]', $this->pObj->MOD_SETTINGS['hideSysFolders'], '', '', 'id="SET[hideSysFolders]"');
			$content .= '<label for="SET[hideSysfolders]">Hide System Folders</label>&nbsp;&nbsp;<br/>';
			$content .= BackendUtility::getFuncCheck($id, 'SET[hideNotInMenu]', $this->pObj->MOD_SETTINGS['hideNotInMenu'], '', '', 'id="SET[hideNotInMenu]"');
			$content .= '<label for="SET[hideNotInMenu]">Hide Not in menu</label>&nbsp;&nbsp;<br/>';

			// Save previous editing when submit was hit
			$this->saveChanges();


			// == Showing the tree ==
			// Initialize starting point (= $id) of page tree:
			$treeStartingRecord = BackendUtility::getRecord('pages', $id);
			BackendUtility::workspaceOL('pages', $treeStartingRecord);


			// Initialize tree object:
			$tree = GeneralUtility::makeInstance('t3lib_pageTree');
			$tree->addField('tx_seo_titletag', 1);
			$tree->addField('keywords', 1);
			$tree->addField('description', 1);
			$tree->addField('tx_seo_noindex', 1);
			if (ExtensionManagementUtility::isLoaded('realurl')) {
				$tree->addField('tx_realurl_pathsegment', 1);
			}
			$tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));

			// Creating top icon; the current page
			$HTML = IconUtility::getIconImage('pages', $treeStartingRecord, $GLOBALS['BACK_PATH'], 'align="top"');
			$tree->tree[] = array(
				'row' => $treeStartingRecord,
				'HTML' => $HTML
			);

			// Create the tree from starting point
			if ($depth > 0) {
				$tree->getTree($id, $depth, '');
			}


			// get all page IDs that will be displayed
			$pages = array();
			foreach ($tree->tree as $row) {
				$pages[] = $row['row']['uid'];
			}

			// load language overlays and path cache for all pages shown
			$uidList = $GLOBALS['TYPO3_DB']->cleanIntList(implode(',', $pages));
			$this->loadLanguageOverlays($uidList);
			if (ExtensionManagementUtility::isLoaded('realurl')) {
				$this->loadPathCache($uidList);
			}

			// Render information table
			$content .= $this->renderSaveButtons();
			$content .= $this->renderSEOTable($tree);
			$content .= $this->renderSaveButtons();
		}

		return $content;
	}

	/**
	 * Rendering the information
	 *
	 * @param	array		The Page tree data
	 * @return	string		HTML for the information table.
	 */
	function renderSEOTable($tree) {
		$cmd = GeneralUtility::_GP('cmd');
		$hideShortcuts = ($this->pObj->MOD_SETTINGS['hideShortcuts'] == '1' ? true : false);
		$hideDisabled = ($this->pObj->MOD_SETTINGS['hideDisabled'] == '1' ? true : false);
		$hideSysFolders = ($this->pObj->MOD_SETTINGS['hideSysFolders'] == '1' ? true : false);
		$hideNotInMenu = ($this->pObj->MOD_SETTINGS['hideNotInMenu'] == '1' ? true : false);


		// Traverse tree
		$output = '';
		$cc = 0;
		foreach ($tree->tree as $row) {
			$itemHtml = $row['HTML'];
			$item = $row['row'];
			$itemId = $item['uid'];

			// filter checkbox selections
			if ($hideDisabled && $item['hidden'] == 1) {
				continue;
			}
			if ($hideShortcuts && ($item['doktype'] == 3 || $item['doktype'] == 4 || $item['doktype'] == 199)) {
				continue;
			}
			if ($hideNotInMenu && $item['nav_hide'] == 1) {
				continue;
			}
			if ($hideSysFolders && $item['doktype'] == 254) {
				continue;
			}


			// load translations for this record
			$translations = (is_array($this->langOverlays[$itemId]) ? $this->langOverlays[$itemId] : '');

			// fill row with data
			$tCells = array();

			// multilanguage system
			$numRows = 1;
			if ($this->sysHasLangs) {
				$item['sys_language'] = 0;

				// see if a specific language (and not the default one) is chosen
				// overwrite the default values,
				if ($this->langOnly) {
					if (!is_array($translations[$this->langOnly]))
						continue;
					$item['uid'] = $translations[$this->langOnly]['uid'];
					$item['tx_seo_titletag'] = $translations[$this->langOnly]['tx_seo_titletag'];
					$item['keywords'] = $translations[$this->langOnly]['keywords'];
					$item['description'] = $translations[$this->langOnly]['description'];
					$item['sys_language'] = $this->langOnly;
					$item['tx_seo_noindex'] = $translations[$this->langOnly]['tx_seo_noindex'];
					$itemID = $item['uid'];
				}
				// if no specific language is chosen, display all translations of a page
				// in multirow format
				else if (is_array($translations)) {
					$numRows = count($translations) + 1;
				}
			}

			// render main language (or specifically selected language) row
			$item['pathcache'] = $this->pathCaches[$itemId][($this->langOnly ? $this->langOnly : 0)];


			// row title
			$cellAttrib = ($item['_CSSCLASS'] ? ' class="' . $item['_CSSCLASS'] . '"' : '');
			$rowTitle = '<td title="ID: ' . $itemId . '"' . $cellAttrib . ' nowrap="nowrap" ';
			if ($cmd == 'edit') {
				$rowTitle .= 'rowspan="' . ($numRows * 2) . '">' . $itemHtml . BackendUtility::getRecordTitle('pages', $item, true) . '</td>';
			} else {
				$rowTitle .= 'rowspan="' . $numRows . '">' . $itemHtml . BackendUtility::getRecordTitle('pages', $item, true) . '</td>';
			}


			$tRows = $this->renderRowContent($item, $rowTitle);

			// compile row
			foreach ($tRows as $singleRow) {
				$output.= '
					<tr class="bgColor-' . ($cc % 2 ? '20' : '10') . '">
						' . implode('
						', $singleRow) . '
					</tr>';
			}


			// display other translations now
			if ($numRows > 1) {
				foreach ($translations as $langId => $item) {
					$item['sys_language'] = $langId;
					$item['pathcache'] = $this->pathCaches[$itemId][$langId];
					$tRows = $this->renderRowContent($item);
					// compile row
					foreach ($tRows as $singleRow) {
						$output.= '
						<tr class="bgColor-' . ($cc % 2 ? '20' : '10') . '">
							' . implode('
							', $singleRow) . '
						</tr>';
					}
				}
			}
			$cc++;
		}

		// Create header:
		$tCells = array();
		$tCells[] = '<td>Title&nbsp;</td>';

		if ($this->sysHasLangs) {
			$tCells[] = '<td>Lang&nbsp;</td>';
		}

		if ($cmd != 'edit') {
			$tCells[] = '<td>URL&nbsp;Path</td>';
			$tCells[] = '<td>Title&nbsp;Tag</td>';
			$tCells[] = '<td>Keywords</td>';
			$tCells[] = '<td>Description</td>';
			$tCells[] = '<td>No index?</td>';
		} else {
			$tCells[] = '<td colspan="2">Title&nbsp;Tag</td>';
			$tCells[] = '<td colspan="2">Keywords</td>';
			$tCells[] = '<td>No index?</td>';
		}
		$output = '
			<tr class="bgColor5 tableheader t3-row-header">
				' . implode('
				', $tCells) . '
			</tr>' . $output;

		// Compile final table and return
		return '
		<table border="0" cellspacing="1" cellpadding="0" id="tx-seo_basics" class="lrPadding c-list typo3-dblist">' . $output . '
		</table>';
	}

	/**
	 *
	 * @param	list	The Page Uids
	 * @return	nothing
	 */
	function renderRowContent($item, $rowTitle = NULL) {
		$cmd = GeneralUtility::_GP('cmd');
		$row1 = array();
		$row2 = array();
		$row3 = array();

		if ($cmd != 'edit') {
			$row1[] = $item['pathcache'];
			$row1[] = $item['tx_seo_titletag'];
			$row1[] = $item['keywords'];
			$row1[] = $item['description'];
			$row1[] = $item['tx_seo_noindex'] ? 'Yes' : 'No';

			// before output, wrap each cell in tds
			foreach ($row1 as $k => $v) {
				$row1[$k] = '<td>' . htmlspecialchars($v) . '</td>';
			}
		} else {
			// display fields that can be edited
			$tbl = ($item['sys_language'] > 0 ? 'pages_language_overlay' : 'pages');
			$fName = 'tx_seo[' . $tbl . '][' . $item['uid'] . ']';

			$row1[] = '<td>Title-Tag:</td>'
					. '<td><input name="' . $fName . '[tx_seo_titletag]" value="' . htmlspecialchars($item['tx_seo_titletag']) . '" type="text" size="43" maxlength="100" autocomplete="off" class="seoTitleTag"/></td>'
					. '<td>Keywords:</td><td><input name="' . $fName . '[keywords]" value="' . htmlspecialchars($item['keywords']) . '" type="text" size="67" maxlength="180" autocomplete="off" class="seoKeywords"/></td>'
					. '<td rowspan="2"><input name="'.$fName.'[tx_seo_noindex]" value="1" type="checkbox"'.( $item['tx_seo_noindex'] ? ' checked="checked"' : '').'/></td>';

			$row2[] = '<td>Description:</td><td colspan="3"><input name="' . $fName . '[description]" value="' . htmlspecialchars($item['description']) . '" type="text" size="120" autocomplete="off" class="seoDescription"/><br/><br/></td>';

		}

		if ($this->sysHasLangs) {
			array_unshift($row1, '<td ' . ($cmd == 'edit' ? 'rowspan="2"' : '') . '>' . IconUtility::getSpriteIcon($this->sysLanguages[$item['sys_language']]['flagIcon']) . '</td>');
		}
		if ($rowTitle) {
			array_unshift($row1, $rowTitle);
		}
		return (count($row2) ? array($row1, $row2) : array($row1));
	}

	/**
	 * This function loads all language overlays that exist for pages
	 * an local array
	 * @param	array	The Page Uids
	 * @return	nothing
	 */
	function loadLanguageOverlays($uidList) {
		// no localization
		if (!$this->sysHasLangs) {
			return;
		}
		// if the main language is selected, don't load overlay records
		if ($this->langOnly === 0) {
			return;
		}

		// building where clause
		$where = ($this->langOnly ? ' AND sys_language_uid = ' . $this->langOnly : '');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, pid, sys_language_uid, title, tx_seo_titletag, tx_seo_noindex, keywords, description', 'pages_language_overlay', 'pid IN (' . $uidList . ') AND deleted = 0 ' . $where, '', 'pid ASC, sys_language_uid ASC'
		);

		// fill results into instance variable
		$this->langOverlays = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$item = array(
				'uid' => $row['uid'],
				'title' => $row['title'],
				'tx_seo_titletag' => $row['tx_seo_titletag'],
				'keywords' => $row['keywords'],
				'description' => $row['description'],
				'tx_seo_noindex' => $row['tx_seo_noindex']
			);
			$this->langOverlays[$row['pid']][$row['sys_language_uid']] = $item;
		}
	}

	/**
	 * Loads the path cache from the RealURL extension table to
	 * display it later in the table
	 * @param	list	The Page Uids
	 * @return	nothing
	 */
	function loadPathCache($uidList) {

		// building where clause
		$where = ($this->langOnly || $this->langOnly === 0 ? ' AND language_id = ' . $this->langOnly : '');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'page_id, language_id, pagepath, cache_id ', 'tx_realurl_pathcache', 'page_id IN (' . $uidList . ') ' . $where, '', 'language_id ASC, expire ASC'
		);

		// Traverse result:
		$this->pathCaches = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->pathCaches[$row['page_id']][$row['language_id']] = $row['pagepath'];
		}
	}

	/**
	 * Render edit / save buttons
	 *
	 * @return	string		HTML
	 */
	function renderSaveButtons() {
		$cmd = GeneralUtility::_GP('cmd');
		if ($cmd == 'edit') {
			$output = '<br/>
			<input type="submit" name="submit"  value="Save Changes" />
			<input type="button" value="Cancel" onclick="jumpToUrl(\'' . $this->linkToSelf() . '\');" / />
			<input type="hidden" name="cmd"     value="edit" />
			<input type="hidden" name="id"      value="' . htmlspecialchars($this->pObj->id) . '" /><br/><br/>';
		} else {
			$output = '<br/>
			<input type="button" value="Edit SEO fields" onclick="jumpToUrl(\'' . $this->linkToSelf('cmd=edit') . '\');" /><br/><br/>';
		}
		return $output;
	}

	/**
	 * Links to the module script and sets necessary parameters
	 *
	 * @param	string		Additional GET vars
	 * @return	string		script + query params
	 */
	function linkToSelf($addParams = '') {

		$langOnly = $this->langOnly;
		$hideShortcuts = ($this->pObj->MOD_SETTINGS['hideShortcuts'] == '1' ? true : false);
		$hideDisabled = ($this->pObj->MOD_SETTINGS['hideDisabled'] == '1' ? true : false);

		if ($addParams && strpos('&', $addParams) !== 0) {
			$addParams = '&' . $addParams;
		}
		if ($langOnly) {
			$addParams .= '&langOnly=' . $langOnly;
		}
		if ($hideShortcuts) {
			$addParams .= '&hideShortcuts=' . $hideShortcuts;
		}
		if ($hideDisabled) {
			$addParams .= '&hideDisabled=' . $hideDisabled;
		}
		return htmlspecialchars('index.php?id=' . $this->pObj->id . $addParams);
	}

	/**
	 * Will look for submitted SEO / page entries to save to DB
	 *
	 * @return	void
	 */
	function saveChanges() {
		$seoData = GeneralUtility::_POST('tx_seo');
		if (!is_array($seoData)) {
			return;
		}

		// run through every table (can only be "pages" or "pages_language_overlay")
		foreach ($seoData as $tbl => $res) {
			if ($tbl !== 'pages' && $tbl !== 'pages_language_overlay') {
				continue;
			}

			$emptyItems = array();

			// run through every item in the table
			foreach ($res as $uid => $item) {
				$uid = intval($uid);

				if (empty($item['tx_seo_titletag']) && empty($item['keywords']) && empty($item['description']) && $item['tx_seo_noindex'] === 0) {
					$emptyItems[] = $uid;
				}

				$fields = array(
					'tx_seo_titletag' => $item['tx_seo_titletag'],
					'keywords' => $item['keywords'],
					'description' => $item['description'],
					'tx_seo_noindex' => $item['tx_seo_noindex']
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tbl, 'uid = ' . $uid, $fields);
			}

			// set all items where all fields all empty at once to save time
			if (count($emptyItems)) {
				$uidList = $GLOBALS['TYPO3_DB']->cleanIntList(implode(',', $emptyItems));

				$fields = array(
					'tx_seo_titletag' => '',
					'keywords' => '',
					'description' => '',
					'tx_seo_noindex' => 0
 				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tbl, 'uid IN (' . $uidList . ')', $fields);
			}
		}
	}
}