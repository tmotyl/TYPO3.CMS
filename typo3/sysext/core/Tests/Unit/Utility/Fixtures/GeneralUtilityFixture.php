<?php
namespace TYPO3\CMS\Core\Utility;


class GeneralUtilityFixture extends GeneralUtility {
	/** Disables CURLOT_FOLLOWLOCATION in self::getUrl() */
	const TESTOPTION_NOFOLLOWLOCATION = 1;
	/** Disables 'redirect_url' handling in curl information in self::getUrl() */
	const TESTOPTION_NOREDIRECTURL = 2;

	/**
	 * Options for GeneralUtility unit testing.
	 *
	 * @var array[]
	 */
	static public $testModeOptions = array();

	/**
	 * Sets test mode options for this. You must not use this outside unit tests!
	 *
	 * @param int $testModeOption
	 * @param mixed $value
	 * @return void
	 */
	static public function setTestModeOption($testModeOption, $value) {
		self::$testModeOptions[$testModeOption] = $value;
	}

	public static function mockCurlSetOpt() {
		if (!function_exists('TYPO3\CMS\Core\Utility\curl_setopt')) {
			function curl_setopt($ch, $option, $value) {
				if ($option === \CURLOPT_FOLLOWLOCATION) {
					if (GeneralUtilityFixture::$testModeOptions[GeneralUtilityFixture::TESTOPTION_NOFOLLOWLOCATION]) {
						return FALSE;
					}
				}
				return \curl_setopt($ch, $option, $value);
			}

			function curl_getinfo($ch) {
				$result = \curl_getinfo($ch);
				if (isset($result['redirect_url']) && GeneralUtilityFixture::$testModeOptions[GeneralUtilityFixture::TESTOPTION_NOREDIRECTURL]) {
					unset($result['redirect_url']);
				}
				return $result;
			}
		}

	}

	public static function getUrl($url, $includeHeader = 0, $requestHeaders = FALSE, &$report = NULL) {
		static::mockCurlSetOpt();
		return parent::getUrl($url, $includeHeader, $requestHeaders, $report);
	}

}