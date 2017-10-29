<?php
/**
 * Plugin Name: Lana Sitemap
 * Plugin URI: http://lana.codes/lana-product/lana-sitemap/
 * Description: XML and Google News Sitemaps.
 * Version: 1.0.3
 * Author: Lana Codes
 * Author URI: http://lana.codes/
 * Text Domain: lana-sitemap
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();
define( 'LANA_SITEMAP_VERSION', '1.0.3' );
define( 'LANA_SITEMAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LANA_SITEMAP_DIR_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'LANA_SITEMAP_NAME' ) ) {
	define( 'LANA_SITEMAP_NAME', 'sitemap.xml' );
}

if ( ! defined( 'LANA_SITEMAP_NEWS_NAME' ) ) {
	define( 'LANA_SITEMAP_NEWS_NAME', 'sitemap-news.xml' );
}

/**
 * Include
 * functions and classes
 */
include_once dirname( __FILE__ ) . '/lana-sitemap-functions.php';
include_once dirname( __FILE__ ) . '/includes/class-lana-sitemap.php';

/**
 * Initialize
 * Lana Sitemap
 */
$lana_sitemap = new Lana_Sitemap();
