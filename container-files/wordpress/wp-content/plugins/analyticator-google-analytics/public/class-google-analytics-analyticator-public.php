<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       analyticator.com
 * @since      1.0.0
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Google_Analytics_Analyticator
 * @subpackage Google_Analytics_Analyticator/public
 * @author     Analyticator <support@analyticator.com>
 */
class Google_Analytics_Analyticator_Public {

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

	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->settings = new Analyticator_Settings_API();

		//INSERT SCRIPT
		add_action('wp_head', array( $this, '_add_script') );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/google-analytics-analyticator-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/google-analytics-analyticator-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add GA JS code in the head of the page.
	 */

	function _add_script() {

		$roles     = wp_get_current_user()->roles;
		$options   = get_option( 'analyticator_basics', true );
		$id        = empty ( $options['google-analytics-ua-code-id'] ) ? '' : $options['google-analytics-ua-code-id'];
		$exclude   = empty ( $options['user-roles'] ) ? array() : $options['user-roles'];
		$do_script = count( array_intersect( array_map( 'strtolower', $roles), array_map( 'strtolower', $exclude ) ) ) == 0;

		if ( !empty( $options['google-analytics-ua-code-id'] ) && $do_script ) {
			
			$GA_ID     = $options['google-analytics-ua-code-id'];
			
			ob_start(); ?>
			<!-- This site uses the Google Analytics by Analyticator plugin version <?php echo $this->version; ?> - http://www.analyticator.com/ -->
			<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

			<?php 
			if ( 'on' === $this->settings->get_option( 'cross-domain-setup', 'analyticator_advanced' ) ) {
				echo "ga('create', '{$GA_ID}', 'auto', {'allowLinker': true});\n\t\t\t";
				echo "ga('require', 'linker');\n\t\t\t";

				if ( $this->settings->get_option( 'custom-js-code', 'analyticator_advanced' ) ) {
					echo $this->settings->get_option( 'custom-js-code', 'analyticator_advanced' );
				}
			} else {
				echo "ga('create', '{$GA_ID}', 'auto');\n\t\t\t";
			}

			if ( 'on' === $this->settings->get_option( 'anonymize-ip-address', 'analyticator_advanced' ) ) {
				echo "ga('set', 'anonymizeIp', true);\n\t\t\t";
			}

			if ( 'on' === $this->settings->get_option( 'force-ssl-traffic', 'analyticator_advanced' ) ) {
				echo "ga('set', 'forceSSL', true);\n\t\t\t";
			}

			if ( 'on' === $this->settings->get_option( 'track-user-id', 'analyticator_advanced' ) && is_user_logged_in() ) {
				echo "ga('set', 'userId', " . esc_html( get_current_user_id() ) . ');';
			}

			if ( 'on' === $this->settings->get_option( 'demographic-interest-tracking', 'analyticator_advanced' ) ) {
				echo "ga('require', 'displayfeatures');\n\t\t\t";
			}
			?>
			ga('send', 'pageview');

			</script>
			
			<?php
			echo ob_get_clean();
		}

		if ( ! $do_script ) {

			echo "<!-- This site uses the Google Analytics by Analyticator plugin version " . $this->version . " - http://www.analyticator.com/ -->";
			echo "<!-- @Webmaster, normally you will find the Google Analytics tracking code here, but you are in the disabled user groups. To change this, navigate to Plugin Settings -> (Ignore users) -->";

		}

		echo "<!-- / Google Analytics by Analyticator -->";
	}

}
