<?php
/** if uninstall not called from WordPress exit */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/** delete all taxonomy terms */
register_taxonomy( 'gn-genre', null );

$terms = get_terms( 'gn-genre', array( 'hide_empty' => false ) );

if ( is_array( $terms ) ) {
	foreach ( $terms as $term ) {
		wp_delete_term( $term->term_id, 'gn-genre' );
	}
}

/** remove plugin settings */
delete_option( 'lana_sitemap_version' );
delete_option( 'lana_sitemap_sitemaps' );
delete_option( 'lana_sitemap_post_types' );
delete_option( 'lana_sitemap_taxonomies' );
delete_option( 'lana_sitemap_news_sitemap' );
delete_option( 'lana_sitemap_ping' );
delete_option( 'lana_sitemap_robots' );
delete_option( 'lana_sitemap_urls' );
delete_option( 'lana_sitemap_custom_sitemaps' );
delete_option( 'lana_sitemap_domains' );
delete_option( 'lana_sitemap_news_tags' );

/** make rewrite rules update at the appropriate time */
delete_option( 'rewrite_rules' );

error_log( 'Lana Sitemap settings cleared before uninstall.' );