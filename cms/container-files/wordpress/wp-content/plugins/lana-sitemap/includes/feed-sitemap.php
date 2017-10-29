<?php
/**
 * XML Sitemap Index Feed Template
 */
defined( 'ABSPATH' ) or die();

global $lana_sitemap;

/** start output */
echo $lana_sitemap->headers();

$document = $lana_sitemap->xml_document( 'index' );

$sitemapindex = $document->createElement( 'sitemapindex' );
$sitemapindex->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
$sitemapindex->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instanc' );
$sitemapindex->setAttribute( 'xsi:schemaLocation', implode( "\n", array(
	'http://www.sitemaps.org/schemas/sitemap/0.9',
	'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
) ) );

$sitemap = $document->createElement( 'sitemap' );

$loc            = $document->createElement( 'loc' );
$loc->nodeValue = $lana_sitemap->get_index_url( 'home' );
$sitemap->appendChild( $loc );

$lastmod            = $document->createElement( 'lastmod' );
$lastmod->nodeValue = mysql2date( 'Y-m-d\TH:i:s+00:00', lana_sitemap_get_last_date( 'gmt' ), false );
$sitemap->appendChild( $lastmod );

$sitemapindex->appendChild( $sitemap );

/**
 * add rules for public post types
 * @var array $post_type
 */
foreach ( $lana_sitemap->have_post_types() as $post_type ) {
	$archive = isset( $post_type['archive'] ) ? $post_type['archive'] : '';

	foreach ( $lana_sitemap->get_archives( $post_type['name'], $archive ) as $m => $url ) {

		$sitemap = $document->createElement( 'sitemap' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = $url;
		$sitemap->appendChild( $loc );

		$lastmod            = $document->createElement( 'lastmod' );
		$lastmod->nodeValue = mysql2date( 'Y-m-d\TH:i:s+00:00', lana_sitemap_get_last_modified( 'gmt', $post_type['name'], $m ), false );
		$sitemap->appendChild( $lastmod );

		$sitemapindex->appendChild( $sitemap );
	}
}

/**
 * add rules for public taxonomies
 * @var array $taxonomy
 */
foreach ( $lana_sitemap->get_taxonomies() as $taxonomy ) {
	if ( wp_count_terms( $taxonomy, array( 'hide_empty' => true ) ) > 0 ) {

		$sitemap = $document->createElement( 'sitemap' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = $lana_sitemap->get_index_url( 'taxonomy', $taxonomy );
		$sitemap->appendChild( $loc );

		$lastmod_value = $lana_sitemap->get_lastmod( 'taxonomy', $taxonomy );

		if ( $lastmod_value ) {
			$lastmod            = $document->createElement( 'lastmod' );
			$lastmod->nodeValue = $lastmod_value;
			$sitemap->appendChild( $lastmod );
		}

		$sitemapindex->appendChild( $sitemap );
	}
}

/**
 * custom URLs sitemap
 * @var array $urls
 */
$urls = $lana_sitemap->get_urls();
if ( ! empty( $urls ) ) {

	$sitemap = $document->createElement( 'sitemap' );

	$loc            = $document->createElement( 'loc' );
	$loc->nodeValue = $lana_sitemap->get_index_url( 'custom' );
	$sitemap->appendChild( $loc );

	$sitemapindex->appendChild( $sitemap );
}

/**
 * custom sitemaps
 * @var array $custom_sitemaps
 */
$custom_sitemaps = $lana_sitemap->get_custom_sitemaps();
foreach ( $custom_sitemaps as $url ) {

	if ( empty( $url ) || ! $lana_sitemap->is_allowed_domain( $url ) ) {
		continue;
	}

	$sitemap = $document->createElement( 'sitemap' );

	$loc            = $document->createElement( 'loc' );
	$loc->nodeValue = esc_url( $url );
	$sitemap->appendChild( $loc );

	$sitemapindex->appendChild( $sitemap );
}

$document->appendChild( $sitemapindex );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();