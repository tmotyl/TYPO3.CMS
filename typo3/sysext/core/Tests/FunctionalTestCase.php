<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2013 Robert Lemke (robert@typo3.org)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Base test case for functional tests.
 *
 * This class currently only inherits the base test case. However, it is recommended
 * to extend this class for unit test cases instead of the base test case because if,
 * at some point, specific behavior needs to be implemented for unit tests, your test cases
 * will profit from it automatically.
 *
 */
abstract class FunctionalTestCase extends BaseTestCase {

	/**
	 * name of a test database
	 *
	 * @var string
	 */
	protected $testDatabaseName = '';

	/**
	 * @var DatabaseConnection
	 */
	protected $testDatabaseConnection;

	/**
	 * @var DatabaseConnection
	 */
//	private $originalDatabaseConnection;

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name the name of a testcase
	 * @param array $data ?
	 * @param string $dataName ?
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->testDatabaseName = uniqid(strtolower(TYPO3_db . '_test_'));
	}

	public function setUp() {
//		$this->originalDatabaseConnection = $GLOBALS['TYPO3_DB'];
		unset($GLOBALS['TYPO3_DB']);
//		$GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
//		$GLOBALS['TYPO3_DB']->setDatabaseName($this->testDatabaseName);
//		$GLOBALS['TYPO3_DB']->setDatabaseUsername(TYPO3_db_username);
//		$GLOBALS['TYPO3_DB']->setDatabasePassword(TYPO3_db_password);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeTypo3DbGlobal();

		$this->testDatabaseConnection = $GLOBALS['TYPO3_DB'];

		if ($this->createTestDatabase()) {
			$this->testDatabaseConnection->setDatabaseName($this->testDatabaseName);

//			$GLOBALS['TYPO3_DB'] = $this->testDatabaseConnection;
			$this->createDatabaseStructure();
		} else {
			$this->markTestSkipped('Could not create test database');
		}




	}

	public function tearDown() {

		$this->dropDatabase();

//		$GLOBALS['TYPO3_DB'] = $this->originalDatabaseConnection;



	}

	/**
	 * Accesses the TYPO3 database instance and uses it to fetch the list of
	 * abailable databases. Then this function creates a test database (if none
	 * has been set up yet).
	 *
	 * @return boolean
	 *         TRUE if the database has been created successfully (or if there
	 *         already is a test database), FALSE otherwise
	 */
	protected function createTestDatabase() {
		$success = TRUE;

		$this->dropDatabase();
		if ($this->testDatabaseConnection->admin_query('CREATE DATABASE ' . $this->testDatabaseName) === FALSE) {
			$success = FALSE;
		}

		return $success;
	}

	/**
	 * Drops the test database.
	 *
	 * @return boolean //TODO: do we need a return value here?
	 *         TRUE if the database has been dropped successfully, FALSE otherwise
	 */
	protected function dropDatabase() {
		return ($this->testDatabaseConnection->admin_query('DROP DATABASE IF EXISTS ' . TYPO3_db . '_test_*') !== FALSE);
	}

	/**
	 * Sets the TYPO3 database instance to a test database.
	 *
	 * Note: This function does not back up the currenty TYPO3 database instance.
	 *
	 * @param string $databaseName
	 *        the name of the test database to use; if none is provided, the
	 *        name of the current TYPO3 database plus a suffix "_test" is used
	 *
	 * @return DatabaseConnection the test database
	 */
	protected function useTestDatabase($databaseName = NULL) {
		/** @var $db DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];

		if ($db->sql_select_db($databaseName ? $databaseName : $this->testDatabaseName) !== TRUE) {
			$this->markTestSkipped('This test is skipped because the test database is not available.');
		}

		return $db;
	}

	/**
	 * Imports the ext_tables.sql statements from the given extensions.
	 *
	 * @param array $extensions
	 *        keys of the extensions to import, may be empty
	 * @param boolean $importDependencies
	 *        whether to import dependency extensions on which the given extensions
	 *        depend as well
	 * @param array &$skipDependencies
	 *        keys of the extensions to skip, may be empty, will be modified
	 *
	 * @return void
	 */
	protected function importExtensions(
		array $extensions, $importDependencies = FALSE, array &$skipDependencies = array()
	) {
		$this->useTestDatabase();

		foreach ($extensions as $extensionName) {
			if (!ExtensionManagementUtility::isLoaded($extensionName)) {
				$this->markTestSkipped(
					'This test is skipped because the extension ' . $extensionName .
						' which was marked for import is not loaded on your system!'
				);
			} elseif (in_array($extensionName, $skipDependencies)) {
				continue;
			}

			$skipDependencies = array_merge($skipDependencies, array($extensionName));

			if ($importDependencies) {
				$dependencies = $this->findDependencies($extensionName);
				if (is_array($dependencies)) {
					$this->importExtensions($dependencies, TRUE, $skipDependencies);
				}
			}

			$this->importExtension($extensionName);
		}

		// TODO: The hook should be replaced by real clean up and rebuild the whole
		// "TYPO3_CONF_VARS" in order to have a clean testing environment.
		// hook to load additional files
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['importExtensions_additionalDatabaseFiles'] as $file) {
				$sqlFilename = GeneralUtility::getFileAbsFileName($file);
				$fileContent = GeneralUtility::getUrl($sqlFilename);

				$this->importDatabaseDefinitions($fileContent);
			}
		}
	}

	/**
	 * Gets the names of all tables in the database with the given name.
	 *
	 * @param string $databaseName
	 *        the name of the database from which to retrieve the table names,
	 *        if none is provided, the name of the current TYPO3 database plus a
	 *        suffix "_test" is used
	 *
	 * @return array<string>
	 *        the names of all tables in the database $databaseName, might be empty
	 */
	protected function getDatabaseTables($databaseName = NULL) {
		$db = $this->useTestDatabase($databaseName);

		$tableNames = array();

		$res = $db->sql_query('show tables');
		while ($row = $db->sql_fetch_row($res)) {
			$tableNames[] = $row[0];
		}

		return $tableNames;
	}

	/**
	 * Imports the ext_tables.sql file of the extension with the given name
	 * into the test database.
	 *
	 * @param string $extensionName
	 *        the name of the installed extension to import, must not be empty
	 *
	 * @return void
	 */
	private function importExtension($extensionName) {
		$sqlFilename = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extensionName) . 'ext_tables.sql');
		$fileContent = GeneralUtility::getUrl($sqlFilename);

		$this->importDatabaseDefinitions($fileContent);
	}

//	/**
//	 * Imports the data from the stddb tables.sql file.
//	 *
//	 * Example/intended usage:
//	 *
//	 * <pre>
//	 * public function setUp() {
//	 *   $this->createTestDatabase();
//	 *   $db = $this->useTestDatabase();
//	 *   $this->importStdDB();
//	 *   $this->importExtensions(array('cms', 'static_info_tables', 'templavoila'));
//	 * }
//	 * </pre>
//	 *
//	 * @return void
//	 */
//	protected function createDatabaseStructure() {
//		//TODO: FIXME!!
//		$sqlFilename = GeneralUtility::getFileAbsFileName(PATH_t3lib . 'stddb/tables.sql');
//		$fileContent = GeneralUtility::getUrl($sqlFilename);
//
//		$this->importDatabaseDefinitions($fileContent);
//
//		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
//			// make sure missing caching framework tables don't get into the way
//			$cacheTables = \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();
//			$this->importDatabaseDefinitions($cacheTables);
//		}
//	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	protected function createDatabaseStructure() {
		// Will load ext_localconf and ext_tables. This is pretty safe here since we are
		// in first install (database empty), so it is very likely that no extension is loaded
		// that could trigger a fatal at this point.
		$this->loadExtLocalconfDatabaseAndExtTables();

		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		/** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
		$expectedSchemaService = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService');

		// Raw concatenated ext_tables.sql and friends string
		$expectedSchemaString = $expectedSchemaService->getTablesDefinitionString(TRUE);
		$statements = $schemaMigrationService->getStatementArray($expectedSchemaString, TRUE);
		list($_, $insertCount) = $schemaMigrationService->getCreateTables($statements, TRUE);

		$fieldDefinitionsFile = $schemaMigrationService->getFieldDefinitions_fileContent($expectedSchemaString);
		$fieldDefinitionsDatabase = $schemaMigrationService->getFieldDefinitions_database();
		$difference = $schemaMigrationService->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $schemaMigrationService->getUpdateSuggestions($difference);

		$schemaMigrationService->performUpdateQueries($updateStatements['add'], $updateStatements['add']);
		$schemaMigrationService->performUpdateQueries($updateStatements['change'], $updateStatements['change']);
		$schemaMigrationService->performUpdateQueries($updateStatements['create_table'], $updateStatements['create_table']);

		foreach ($insertCount as $table => $count) {
			$insertStatements = $schemaMigrationService->getTableInsertStatements($statements, $table);
			foreach ($insertStatements as $insertQuery) {
				$insertQuery = rtrim($insertQuery, ';');
				$this->testDatabaseConnection->admin_query($insertQuery);
			}
		}

	}

	/**
	 * Some actions like the database analyzer and the upgrade wizards need additional
	 * bootstrap actions performed.
	 *
	 * Those actions can potentially fatal if some old extension is loaded that triggers
	 * a fatal in ext_localconf or ext_tables code! Use only if really needed.
	 *
	 * @return void
	 */
	protected function 	loadExtLocalconfDatabaseAndExtTables() {
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
			->loadTypo3LoadedExtAndExtLocalconf(FALSE)
//			->applyAdditionalConfigurationSettings()
//			->initializeTypo3DbGlobal()
			->loadExtensionTables(FALSE);
	}

	/**
	 * Imports the SQL definitions from a (ext_)tables.sql file.
	 *
	 * @param string $definitionContent
	 *        the SQL to import, must not be empty
	 *
	 * @return void
	 */
	private function importDatabaseDefinitions($definitionContent) {
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 4006000) {
			/* @var $install \TYPO3\CMS\Install\Service\SqlSchemaMigrationService */
			$install = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		} else {
			/* @var $install \TYPO3\CMS\Install\Service\SqlSchemaMigrationService */
			$install = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		}

		$fieldDefinitionsFile = $install->getFieldDefinitions_fileContent($definitionContent);
		if (empty($fieldDefinitionsFile)) {
			return;
		}

		// find statements to query
		$fieldDefinitionsDatabase = $install->getFieldDefinitions_fileContent($this->getTestDatabaseSchema());
		$diff = $install->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $install->getUpdateSuggestions($diff);

		$updateTypes = array('add', 'change', 'create_table');

		foreach ($updateTypes as $updateType) {
			if (array_key_exists($updateType, $updateStatements)) {
				foreach ((array) $updateStatements[$updateType] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
			}
		}
	}

	/**
	 * Returns an SQL dump of the test database.
	 *
	 * @return string SQL dump of the test databse, might be empty
	 */
	private function getTestDatabaseSchema() {
		$db = $this->useTestDatabase();
		$tables = $this->getDatabaseTables();

		// finds create statement for every table
		$linefeed = chr(10);

		$schema = '';
		$db->sql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
		foreach ($tables as $tableName) {
			$res = $db->sql_query('show create table ' . $tableName);
			$row = $db->sql_fetch_row($res);

			// modifies statement to be accepted by TYPO3
			$createStatement = preg_replace('/ENGINE.*$/', '', $row[1]);
			$createStatement = preg_replace(
				'/(CREATE TABLE.*\()/', $linefeed . '\\1' . $linefeed, $createStatement
			);
			$createStatement = preg_replace('/\) $/', $linefeed . ')', $createStatement);

			$schema .= $createStatement . ';';
		}

		return $schema;
	}

	/**
	 * Finds all direct dependencies of the extension with the key $extKey.
	 *
	 * @param string $extKey the key of an installed extension, must not be empty
	 *
	 * @return array<string>|NULL
	 *         the keys of all extensions on which the given extension depends,
	 *         will be NULL if the dependencies could not be determined
	 */
	private function findDependencies($extKey) {
		$path = GeneralUtility::getFileAbsFileName(ExtensionManagementUtility::extPath($extKey) . 'ext_emconf.php');
		$_EXTKEY = $extKey;
		include($path);

		$dependencies = $GLOBALS['EM_CONF'][$_EXTKEY]['constraints']['depends'];
		if (!is_array($dependencies)) {
			return NULL;
		}

		// remove php and typo3 extension (not real extensions)
		if (isset($dependencies['php'])) {
			unset($dependencies['php']);
		}
		if (isset($dependencies['typo3'])) {
			unset($dependencies['typo3']);
		}

		return array_keys($dependencies);
	}

	/**
	 * Imports a data set into the test database,
	 *
	 * @param string $path
	 *        the absolute path to the XML file containing the data set to load
	 *
	 * @return void
	 */
	protected function importDataSet($path) {
		$xml = simplexml_load_file($path);
		$db = $this->useTestDatabase();
		$foreignKeys = array();

		/** @var $table \SimpleXMLElement */
		foreach ($xml->children() as $table) {
			$insertArray = array();

			/** @var $column \SimpleXMLElement */
			foreach ($table->children() as $column) {
				$columnName = $column->getName();
				$columnValue = NULL;

				if (isset($column['ref'])) {
					list($tableName, $elementId) = explode('#', $column['ref']);
					$columnValue = $foreignKeys[$tableName][$elementId];
				} elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
					$columnValue = NULL;
				} else {
					$columnValue = $table->$columnName;
				}

				$insertArray[$columnName] = $columnValue;
			}

			$tableName = $table->getName();
			$db->exec_INSERTquery($tableName, $insertArray);

			if (isset($table['id'])) {
				$elementId = (string) $table['id'];
				$foreignKeys[$tableName][$elementId] = $db->sql_insert_id();
			}
		}
	}
}
?>