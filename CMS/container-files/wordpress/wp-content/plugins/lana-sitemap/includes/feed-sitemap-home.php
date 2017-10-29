<?php
/**
 * Lana Sitemap Template for displaying an XML Sitemap feed
 */
defined( 'ABSPATH' ) or die();

global $lana_sitemap;

/** start output */
echo $lana_sitemap->headers();

$document = $lana_sitemap->xml_document();

$urlset = $document->createElement( 'urlset' );
$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
$urlset->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instanc' );
$urlset->setAttribute( 'xsi:schemaLocation', implode( "\n", array(
	'http://www.sitemaps.org/schemas/sitemap/0.9',
	'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
) ) );

/**
 * Get dates
 * @var $last_modified
 * @var $last_activity_age
 */
$last_modified     = lana_sitemap_get_last_date( 'gmt' );
$last_activity_age = ( gmdate( 'U' ) - mysql2date( 'U', $last_modified ) );

foreach ( $lana_sitemap->get_home_urls() as $home_urls ) {

	$url = $document->createElement( 'url' );

	$loc            = $document->createElement( 'loc' );
	$loc->nodeValue = esc_url( $home_urls );
	$url->appendChild( $loc );

	$lastmod            = $document->createElement( 'lastmod' );
	$lastmod->nodeValue = mysql2date( 'Y-m-d\TH:i:s+00:00', $last_modified, false );
	$url->appendChild( $lastmod );

	$changefreq = $document->createElement( 'changefreq' );

	if ( ( $last_activity_age / 86400 ) < 1 ) {
		$changefreq->nodeValue = 'hourly';
	} else if ( ( $last_activity_age / 86400 ) < 7 ) {
		$changefreq->nodeValue = 'daily';
	} else {
		$changefreq->nodeValue = 'weekly';
	}

	$url->appendChild( $changefreq );

	$priority            = $document->createElement( 'priority' );
	$priority->nodeValue = '1.0';
	$url->appendChild( $priority );

	$urlset->appendChild( $url );
}

$document->appendChild( $urlset );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();