<?php

/**
 * Fired during plugin deactivation
 *
 * @link       analyticator.com
 * @since      1.0.0
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/includes
 * @author     Analyticator <support@analyticator.com>
 */
class Google_Analytics_Analyticator_Deactivator {

	/**
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		$tracking = get_option( 'analyticator_com_activation_status' );
      if ( $tracking !== false ) {
        $user = wp_get_current_user();
        $url =  "http://analyticator.com/";
        
        $body = array(
          'email'             => $user->user_email,
          'website'           => get_site_url(),
          'action'            => 'Deactivate',
          'reason'            => '',
          'reason_detail'     => '',
          'blog_language'     => get_bloginfo( 'language' ),
          'wordpress_version' => get_bloginfo( 'version' ),
          'plugin_version'    => ANALYTICATOR__GA_PLUGIN_VER,
          'plugin_name'       => 'Google Analytics by Analyticator Free',  
        );
        
        $args = array(
          'method'      => 'POST',
          'timeout'     => 5,
          'httpversion' => '1.0',
          'blocking'    => false,
          'headers'     => array(),
          'body' => $body,   
        );    
        
        $return = wp_remote_post( $url, $args );
        
        return true;
      }
		
		
	}

}
