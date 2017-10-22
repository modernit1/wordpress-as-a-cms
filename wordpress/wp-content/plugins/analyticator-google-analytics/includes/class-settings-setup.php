<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('Analyticator_Settings_Setup' ) ):
class Analyticator_Settings_Setup {

    private $settings_api;

    function __construct() {
        $this->settings_api = new Analyticator_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {

        add_options_page( 
            __( 'Setup Google Analytics', 'google-analytics-analyticator' ),
            __( 'Setup Google Analytics', 'google-analytics-analyticator' ),
            'manage_options',
            'analyticator_settings_page',
            array( $this, 'plugin_page' )
        );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'analyticator_basics',
                'title' => __( 'Basic Settings', 'google-analytics-analyticator' ),
                'desc' => ''
            ),
            array(
                'id'    => 'analyticator_advanced',
                'title' => __( 'Advanced Settings', 'google-analytics-analyticator' ),
                'desc' => ''
            )
        );
        return $sections;
    }

    function fetch_all_roles() {

        $roles = get_editable_roles();
        foreach ( $roles as $role ) {
            $options[strtolower($role['name'])] = $role['name'];
        }
        return $options;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'analyticator_basics' => array(
                array(
                    'name'              => 'google-analytics-ua-code-id',
                    'label'             => __( 'Google analytics ID', 'google-analytics-analyticator' ),
                    'desc'              => __( '<a target="_blank" href="https://support.google.com/analytics/answer/1032385?hl=en">What is my Google Analytics ID?</a>', 'google-analytics-analyticator' ),
                    'placeholder'       => __( 'e.g UA-12345678-1', 'google-analytics-analyticator' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'    => 'user-roles',
                    'label'   => __( 'Ignore users', 'google-analytics-analyticator' ),
                    'desc'    => __( 'Users of the role you select will NOT be tracked and ignored. So, if you select Author, all Authors will be ignored.', 'google-analytics-analyticator' ),
                    'type'    => 'multicheck',
                    'default' => array('administrator' => 'Administrator'),
                    'options' => $this->fetch_all_roles(),
                ),
            ),
            'analyticator_advanced' => array(
                array(
                    'name'  => 'anonymize-ip-address',
                    'label' => __( 'Anonymize IP addresses', 'google-analytics-analyticator' ),
                    'desc'  => __( 'This adds _anonymizeIp, telling Google Analytics to anonymize the information sent by the tracker objects by removing the last octet of the IP address prior to its storage.', 'google-analytics-analyticator' ),
                    'type'  => 'checkbox'
                ),
                 array(
                    'name'  => 'force-ssl-traffic',
                    'label' => __( 'Force Analytics Traffic Over SSL', 'google-analytics-analyticator' ),
                    'desc'  => __( 'If your site is HTTPS based, Analytics traffic will always go over SSL. If you have an insecure site, but wish Analytics traffic to still be secure, use this option.', 'google-analytics-analyticator' ),
                    'type'  => 'checkbox'
                ),
                  array(
                    'name'  => 'track-user-id',
                    'label' => __( 'Track User ID', 'google-analytics-analyticator' ),
                    'desc'  => __( 'Enable User-ID tracking.', 'google-analytics-analyticator' ),
                    'type'  => 'checkbox'
                ),
                   array(
                    'name'  => 'demographic-interest-tracking',
                    'label' => __( 'Demographic & Interest Tracking', 'google-analytics-analyticator' ),
                    'desc'  => __( 'Check this setting to add the Demographics and Remarketing features to your Google Analytics tracking code.', 'google-analytics-analyticator' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'  => 'cross-domain-setup',
                    'label' => __( 'Setup Cross-domain Tracking', 'google-analytics-analyticator' ),
                    'desc'  => __( 'Check this setting to enable Cross-domain tracking.', 'google-analytics-analyticator' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'name'        => 'custom-js-code',
                    'label'       => __( 'Custom JS Code', 'wedevs' ),
                    'desc'        => __( 'Not for the average user: this allows you to add a line of code, to be added before the _trackPageview call.', 'wedevs' ),
                    'placeholder' => __( 'Custom JS Code', 'wedevs' ),
                    'type'        => 'textarea'
                ),
                // array(
                //     'name'  => 'opt-in-usage',
                //     'label' => __( 'Allow usage tracking', 'google-analytics-analyticator' ),
                //     'desc'  => __( 'Opt-in and Allow us to track how this plugin is used and help us make the plugin better.', 'google-analytics-analyticator' ),
                //     'type'  => 'checkbox'
                // ),
            )
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';
        echo "<h1>Google Analytics by Analyticator: Settings</h1><br /><br />";
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
endif;
