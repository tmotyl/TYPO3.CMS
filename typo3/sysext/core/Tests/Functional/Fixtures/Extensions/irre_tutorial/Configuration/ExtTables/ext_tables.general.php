<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'pages',
	 array (
		'tx_irretutorial_hotels' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.tx_irretutorial_hotels',
			'config' => Array (
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
				),
			)
		),
	),
	1
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.doktype.div.irre, tx_irretutorial_hotels;;;;1-1-1'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'pages_language_overlay',
	 array (
		'tx_irretutorial_hotels' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.tx_irretutorial_hotels',
			'config' => Array (
				'type' => 'inline',
				'foreign_table' => 'tx_irretutorial_1nff_hotel',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => 1,
					'showAllLocalizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showRemovedLocalizationRecords' => 1,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
				),
			)
		),
	),
	1
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'pages_language_overlay',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:pages.doktype.div.irre, tx_irretutorial_hotels;;;;1-1-1'
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'tt_content',
	 array (
		'tx_irretutorial_flexform' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.tx_irretutorial_flexform',
			'config' => Array (
				'type' => 'flex',
				'ds' => array(
					'default' => 'FILE:EXT:irre_tutorial/Configuration/FlexForms/tt_content_flexform.xml',
				),
			)
		),
	),
	1
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
	'tt_content',
	'--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xml:tt_content.div.irre, tx_irretutorial_flexform;;;;1-1-1'
);
?>