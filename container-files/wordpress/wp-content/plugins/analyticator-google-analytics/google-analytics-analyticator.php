<?php

/**
 *
 * @link              analyticator.com
 * @since             1.0.0
 * @package           Google_Analytics_Analyticator
 *
 * Plugin Name:       Google Analytics by Analyticator
 * Plugin URI:        analyticator.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.1
 * Author:            Analyticator.com
 * Author URI:        analyticator.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       google-analytics-analyticator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//delete_option( 'analyticator_com_activation_status' );
//echo get_option( 'analyticator_com_activation_status' ); // disabled
//update_option( 'analyticator_com_activation_status' ); // active

//DEFINE SOME USEFUL CONSTANTS
	define( 'ANALYTICATOR__GA_PLUGIN_VER', '1.0.1' );
	define( 'ANALYTICATOR__GA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'ANALYTICATOR__GA_ROOT_PATH',  dirname( __FILE__ ) . '/' );
	define( 'ANALYTICATOR__GA_PLUGINS_URL', plugins_url( '', __FILE__ ) );
	define( 'ANALYTICATOR__GA_PLUGINS_BASENAME', plugin_basename(__FILE__) );
	define( 'ANALYTICATOR__GA_PLUGIN_FILE', __FILE__ );

	if ( file_exists ( ANALYTICATOR__GA_PLUGIN_DIR . '/includes/start/start.php' ) ) {
		include_once( ANALYTICATOR__GA_PLUGIN_DIR . '/includes/start/start.php' );
	}

/**
 * The code that runs during plugin activation.
 */
function activate_google_analytics_analyticator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-google-analytics-analyticator-activator.php';
	Google_Analytics_Analyticator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_google_analytics_analyticator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-google-analytics-analyticator-deactivator.php';
	Google_Analytics_Analyticator_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_google_analytics_analyticator' );
register_deactivation_hook( __FILE__, 'deactivate_google_analytics_analyticator' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-google-analytics-analyticator.php';


/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_google_analytics_analyticator() {

	$plugin = new Google_Analytics_Analyticator();
	$plugin->run();

}
run_google_analytics_analyticator();

/**
* Create the Object of Remote Notification.
*
* @since  1.0.0
*/
if (!class_exists('TAV_Remote_Notification_Client')) {
  require( ANALYTICATOR__GA_ROOT_PATH . 'includes/class-remote-notification-client.php' );
}
$notification = new TAV_Remote_Notification_Client( 2, '00d6c68fdbcf2ecb', 'http://analyticator.com?post_type=notification' );
function _analyticator_google_analytics_uninstallation(){

  $tracking = get_option( 'analyticator_com_activation_status' );

      if ( $tracking !== false ) {
        $user = wp_get_current_user();
        $url =  "http://analyticator.com/";
        
        $body = array(
          'email'             => $user->user_email,
          'website'           => get_site_url(),
          'action'            => 'Uninstall',
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
register_uninstall_hook(__FILE__, '_analyticator_google_analytics_uninstallation' );
