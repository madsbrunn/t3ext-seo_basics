<?php

namespace B13\SeoBasics\Frontend;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2014 Benjamin Mack <benni@typo3.org>
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
 * ************************************************************* */

/**
 * @author	Benjamin Mack (benni@typo3.org)
 * @subpackage	tx_seobasics
 *
 * This package includes all functions for generating XML sitemaps
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class Hook {

	/**
	 * Hook function for cleaning output XHTML
	 * hooks on "class.tslib_fe.php:2946"
	 * page.config.tx_seo.sourceCodeFormatter.indentType = space
	 * page.config.tx_seo.sourceCodeFormatter.indentAmount = 16
	 *
	 * @param       array           hook parameters
	 * @param       object          Reference to parent object (TSFE-obj)
	 * @return      void
	 */
	public function processOutputHook(&$feObj, $ref) {

		if ($GLOBALS['TSFE']->type != 0) {
			return;
		}

		$configuration = $GLOBALS['TSFE']->config['config']['tx_seo.']['sourceCodeFormatter.'];

		// disabled for this page type
		if (isset($configuration['enable']) && $configuration['enable'] == '0') {
			return;
		}

		$indentAmount = MathUtility::forceIntegerInRange($configuration['indentAmount'], 1, 100);

		// use the "space" character as a indention type
		if ($configuration['indentType'] == 'space') {
			$indentElement = ' ';
			// use any character from the ASCII table
		} else {
			$indentTypeIsNumeric = FALSE;

			$indentTypeIsNumeric = MathUtility::canBeInterpretedAsInteger($configuration['indentType']);

			if ($indentTypeIsNumeric) {
				$indentElement = chr($configuration['indentType']);
			} else {
				// use tab by default
				$indentElement = "\t";
			}
		}

		$indention = '';

		for ($i = 1; $i <= $indentAmount; $i++) {
			$indention .= $indentElement;
		}


		$spltContent = explode("\n", $ref->content);
		$level = 0;



		$cleanContent = array();
		$textareaOpen = false;
		foreach ($spltContent as $lineNum => $line) {
			$line = trim($line);
			if (empty($line))
				continue;
			$out = $line;

			// ugly strpos => TODO: use regular expressions
			// starts with an ending tag
			if (strpos($line, '</div>') === 0 || (strpos($line, '<div') !== 0 && strpos($line, '</div>') === strlen($line) - 6) || strpos($line, '</html>') === 0 || strpos($line, '</body>') === 0 || strpos($line, '</head>') === 0 || strpos($line, '</ul>') === 0)
				$level--;


			if (strpos($line, '<textarea') !== false) {
				$textareaOpen = true;
			}

			// add indention only if no textarea is open
			if (!$textareaOpen) {
				for ($i = 0; $i < $level; $i++) {
					$out = $indention . $out;
				}
			}

			if (strpos($line, '</textarea>') !== false) {
				$textareaOpen = false;
			}

			// starts with an opening <div>, <ul>, <head> or <body>
			if ((strpos($line, '<div') === 0 && strpos($line, '</div>') !== strlen($line) - 6) || (strpos($line, '<body') === 0 && strpos($line, '</body>') !== strlen($line) - 7) || (strpos($line, '<head') === 0 && strpos($line, '</head>') !== strlen($line) - 7) || (strpos($line, '<ul') === 0 && strpos($line, '</ul>') !== strlen($line) - 5))
				$level++;


			$cleanContent[] = $out;
		}

		$ref->content = implode("\n", $cleanContent);
	}

}
