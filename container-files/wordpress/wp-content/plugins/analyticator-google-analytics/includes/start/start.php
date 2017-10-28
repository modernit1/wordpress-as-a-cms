<?php
	
function analyticator_start_page() {
	add_submenu_page(
		null,
		__('Activate', 'google-analytics-analyticator'),
		__('Activate', 'google-analytics-analyticator'),
		'manage_options',
		'analyticator-start',
		'analyticator_display_start_page'
	);
}
add_action( 'admin_menu', 'analyticator_start_page' );

function analyticator_display_start_page() {
		
	wp_enqueue_style('analyticator_start_css', ANALYTICATOR__GA_PLUGINS_URL . '/includes/start/start.min.css', false, ANALYTICATOR__GA_PLUGIN_VER );
	wp_enqueue_script('analyticator_start_js', ANALYTICATOR__GA_PLUGINS_URL . '/includes/start/start.min.js', false, ANALYTICATOR__GA_PLUGIN_VER, true );
		
	$user = wp_get_current_user();
	$name = empty( $user->user_firstname ) ? $user->display_name : $user->user_firstname;
	$email = $user->user_email;
	$site_link = '<a href="' . get_site_url() . '">'. get_site_url() . '</a>';
	$website = get_site_url();
	
	echo '<form method="post" action="' . admin_url( '/admin.php?page=analyticator-start' ) . '">';
		echo '<div id="analyticator-logo-wrapper">';
			echo '<div id="analyticator-logo-wrapper-inner">';
				//echo '<img id="analyticator-logo-text" src="' . ANALYTICATOR__GA_PLUGINS_URL . '/admin/images/icon-128x128.png' . '">';
			echo '</div>';
		echo '</div>';
		
		echo "<input type='hidden' name='fname' value='$name'>";
		echo "<input type='hidden' name='email' value='$email'>";
		
		echo '<div id="analyticator-start">';
			echo '<h1>' . __( 'Welcome to Google Analytics by Analyticator', 'google-analytics-analyticator' ) . '</h1>';
			
			echo '<div id="analyticator-start-main" class="analyticator-start-box">';
				echo '<p id="analyticator-start-main-text">' .  sprintf ( __( 'Hello %2$s,%4$s Google Analytics by Analyticator needs to connect %1$s %3$s to <strong>api.analyticator.com</strong> %1$s With this opt-in, You allow non-sensitive diagnostic tracking,%4$s Features updates notifications and occasional emails.', 'google-analytics-analyticator' ), '<br>', '<strong>' . $name . '</strong>', '<strong>' . $website . '</strong>', '<br>' ) . '</p>';
				echo "<button type='submit' id='analyticator-ga-submit-btn' class='analyticator-ga-button button button-primary' name='analyticator-ga-submit-optin' >" . __( 'Connect Google Analytics by Analyticator', 'google-analytics-analyticator') . "</button><br>";
				echo "<button type='submit' id='analyticator-ga-optout-btn' name='analyticator-ga-submit-optout' >" . __( 'Skip This Step', 'google-analytics-analyticator') . "</button>";
			echo '</div>';
			
			echo '<div id="analyticator-start-permissions" class="analyticator-start-box">';
				echo '<a id="analyticator-start-permissions-toggle" href="#" >' . __( 'What permission is being granted?', 'google-analytics-analyticator' ) . '</a>';
				echo '<div id="analyticator-start-permissions-dropdown" style="display: none;">';
					echo '<h3>' .  __( 'Your Website Info', 'google-analytics-analyticator' ) . '</h3>';
					echo '<p>' .  __( 'Your URL, WordPress version, plugins & themes. This data lets us make sure this plugin always stays compatible with the most popular plugins and themes.', 'google-analytics-analyticator' ) . '</p>';
					
					echo '<h3>' .  __( 'Your Info', 'google-analytics-analyticator' ) . '</h3>';
					echo '<p>' .  __( 'Your name and email.', 'google-analytics-analyticator' ) . '</p>';
					
					echo '<h3>' .  __( 'Plugin Usage', 'google-analytics-analyticator' ) . '</h3>';
					echo '<p>' .  __( "How you use this plugin's features and settings. This is limited to usage data. It does not include any of your sensitive Google Analytics data, such as traffic. This data helps us learn which features are most popular, so we can improve the plugin further.", 'google-analytics-analyticator' ) . '</p>';				
				echo '</div>';
			echo '</div>';
			

		echo '</div>';
	
	echo '</form>';
	
	echo '<div id="analyticator-start-footer">';
		echo '<a target="_blank" href="http://analyticator.com/terms-and-conditions/">' . _x( 'Terms & Conditions', 'as in terms and conditions', 'google-analytics-analyticator' ) . '</a> | <a target="_blank" href="http://analyticator.com/privacy-policy/">' . _x( 'Privacy Policy', 'as in privacy policy', 'google-analytics-analyticator' ) . '</a>';
	echo '</div>';
}

function analyticator_start_redirects() {

	if ( isset( $_POST['analyticator-ga-submit-optout'] ) ) {
		update_option( 'analyticator_com_activation_status', 'disabled' );
		wp_redirect( admin_url( '/options-general.php?page=analyticator_settings_page' ) );
		exit;
	} else if ( isset( $_POST['analyticator-ga-submit-optin'] ) ) {
		update_option( 'analyticator_com_activation_status', 'active' );

		$user = wp_get_current_user();
        $url =  "http://analyticator.com/";
        
        $body = array(
          'email'             => $user->user_email,
          'name'			  => empty( $user->user_firstname ) ? $user->display_name : $user->user_firstname,
          'website'           => get_site_url(),
          'action'            => 'Connect',
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
	
		wp_redirect( admin_url( '/options-general.php?page=analyticator_settings_page' ) );
		exit;
	}
	
	$status = get_option( 'analyticator_com_activation_status' );
	if ( empty($status) && isset( $_GET['page'] ) && $_GET['page'] === 'analyticator_settings_page' ) {
        wp_redirect( admin_url( '/admin.php?page=analyticator-start' ) );
		exit;
    }

}
add_action('admin_init', 'analyticator_start_redirects');

