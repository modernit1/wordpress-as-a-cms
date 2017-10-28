<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       analyticator.com
 * @since      1.0.0
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/admin
 * @author     Analyticator <support@analyticator.com>
 */
class Google_Analytics_Analyticator_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action( 'admin_notices', array( $this, '_admin_notices' ) );
  		add_action( 'admin_enqueue_scripts', array( $this, '_deactivation_survey' ) );
		add_action( 'wp_ajax_analyticator_ga_deactivate_survey', array( $this, 'analyticator_ga_deactivate_survey_ajax' ) );


	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Google_Analytics_Analyticator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Google_Analytics_Analyticator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/google-analytics-analyticator-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Google_Analytics_Analyticator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Google_Analytics_Analyticator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/google-analytics-analyticator-admin.js', array( 'jquery' ), $this->version, false );

	}

	function _plugin_action_links( $links ) {
		
		$url = admin_url('options-general.php?page=analyticator_settings_page');
		
		$new_links = array(
			'configure' => "<a href='$url' >" . __('Setup Google Analytics', 'google-analytics-analyticator' ) . '</a>'
		);
		
		$links = array_merge( $new_links, $links );
	
		return $links;
		
	}

		//ADD NAG IF NO GA TRACKING CODE IS SET
	function _admin_notices() {
		$options = get_option( 'analyticator_basics', true );
 
		if ( empty( $options['google-analytics-ua-code-id'] ) ) {
			$url = admin_url( 'options-general.php?page=analyticator_settings_page' );
		
			echo '<div id="ga-analyticator-setup-notice" class="notice notice-success is-dismissible" style="padding-bottom: 8px; padding-top: 8px;">';
				//echo '<img style="float:left; margin-right: 16px;" height="120" width="120" src="' . ANALYTICATOR__GA_PLUGINS_URL . '/admin/images/icon-128x128.png' . '">';
				echo '<p><strong>' . __( "Thank you for installing Google Analytics by Analyticator.", 'google-analytics-analyticator' ) . '</strong></p>';
				echo '<p>' . __( "Ready to get started?", 'google-analytics-analyticator' ) . '</p>';
				echo "<a href='$url' type='button' class='button button-primary' style='margin-top: 25px;'>" . __( 'Set up Google Analytics', 'google-analytics-analyticator' ) . "</a> ";
				echo '<br style="clear:both">';
			echo '</div>';
		}
	
	}


	function _deactivation_survey( $hook ) {

		if ( $hook === 'plugins.php' ) {
			
			ob_start(); ?>
			
			<div id="analyticator-survey" style="position: fixed; left: 232px; top: 191px; border: 1px solid #979797; background-color: white; z-index: 9999; padding: 12px; max-width: 669px;">
				<h3 style="font-size: 14px; border-bottom: 1px solid #979797; padding-bottom: 8px; margin-top: 0;"><?php _e( 'Sorry to see you go', 'google-analytics-analyticator' ) ?></h3>
				<p><?php _e( 'Hi, I\'m the creator of Google Analytics by Analyticator. Thanks so much for giving my plugin a try. I’m sorry that you didn’t love it.', 'google-analytics-analyticator' ) ?>
				</p>
				<p><?php _e( 'I have a quick question that I hope you’ll answer to help us make Google Analytics by Fatcat Apps better: what made you deactivate?', 'google-analytics-analyticator' ) ?>
				</p>
				<p><?php _e( 'You can leave me a message below. I’d really appreciate it.', 'google-analytics-analyticator' ) ?>
				</p>
				
				<p><textarea style='width: 100%;' id='analyticator-survey-textarea' placeholder='<?php _e( 'What made you deactivate?', 'google-analytics-analyticator' ) ?>'></textarea></p>
				
				<div style='float: right;' id='analyticator-survey-nav'>
					<button style='margin-right: 5px;' type='button' class='button button-secondary' id='analyticator-survey-skip'><?php _e( 'Skip', 'google-analytics-analyticator' ) ?></button>
					<button type='button' class='button button-primary' id='analyticator-survey-send'><?php _e( 'Send Feedback & Deactivate', 'google-analytics-analyticator' ) ?></button>
				</div>
			
			</div>
			
			<?php
				
			$html = ob_get_clean();
			
			$data = array(
				'html' => $html,
				'nonce' => wp_create_nonce( 'analyticator_ga_deactivate_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			);

			wp_enqueue_script('analyticator_ga_deactivation_survey_js', ANALYTICATOR__GA_PLUGINS_URL . '/admin/js/google-analytics-analyticator-survey.js', false, ANALYTICATOR__GA_PLUGIN_VER, true );
			wp_localize_script( 'analyticator_ga_deactivation_survey_js', "analyticator_ga_survey", $data );
		}
	    
	}

	function analyticator_ga_deactivate_survey_ajax() {
	
		$msg = esc_textarea( $_REQUEST['msg'] );
		$nonce = $_REQUEST['nonce'];
		$nonceVerified = wp_verify_nonce( $nonce, 'analyticator_ga_deactivate_nonce') == 1;

		if ( $nonceVerified && !empty( $msg ) ) {
			
			
			$user = wp_get_current_user();
	        $url =  "http://analyticator.com/";
	        
	        $body = array(
	          'email'             => $user->user_email,
	          'website'           => get_site_url(),
	          'action'            => 'Feedback',
	          'reason'            => 7,
	          'reason_detail'     => $msg,
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

			wp_send_json_success( $msg );

		}
		wp_send_json_error( $msg );

	}

}
