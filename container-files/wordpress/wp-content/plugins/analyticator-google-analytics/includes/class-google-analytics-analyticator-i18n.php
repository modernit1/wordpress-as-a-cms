<?php

/**
 *
 * @link       analyticator.com
 * @since      1.0.0
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/includes
 */

/**
 *
 * @since      1.0.0
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/includes
 * @author     Analyticator <support@analyticator.com>
 */
class Google_Analytics_Analyticator_i18n {


	/**
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'google-analytics-analyticator',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
