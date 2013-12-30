<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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
***************************************************************/

require_once(dirname(__FILE__). '/IRREAbstract.php');

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic test helpers.
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
abstract class AbstractLocalization extends IRREAbstract {
	const COMMAND_LocalizeSynchronize = 'inlineLocalizeSynchronize';
	const COMMAND_LocalizeSynchronize_Localize = 'localize';
	const COMMAND_LocalizeSynchronize_Synchronize = 'synchronize';

	const VALUE_LocalizationMode_Keep = 'keep';
	const VALUE_LocalizationMode_Select = 'select';

	/**
	 * Asserts that accordant localizations exist.
	 *
	 * @param array $tables Table names with list of ids to be edited
	 * @param integer $languageId The sys_language_id
	 * @param boolean $expected Expected result of assertion
	 * @return void
	 */
	protected function assertLocalizations(array $tables, $languageId = self::VALUE_LanguageId, $expected = TRUE) {
		foreach ($tables as $tableName => $idList) {
			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$localization = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($tableName, $id, $languageId);
				$isLocalization = is_array($localization) && count($localization);
				$this->assertTrue(
					!($expected XOR $isLocalization),
					'Localization for ' . $tableName . ':' . $id . ($expected ? ' not' : '') . ' availabe'
				);
			}
		}
	}

	/**
	 * Gets the id of the localized record of a language parent.
	 *
	 * @param string $tableName
	 * @param integer $id
	 * @param integer $languageId
	 * @return boolean
	 */
	protected function getLocalizationId($tableName, $id, $languageId = self::VALUE_LanguageId) {
		$localization = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordLocalization($tableName, $id, $languageId);
		if (is_array($localization) && count($localization)) {
			return $localization[0]['uid'];
		}

		return FALSE;
	}
}

?>