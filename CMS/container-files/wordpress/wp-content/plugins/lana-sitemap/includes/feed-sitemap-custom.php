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
 * custom URLs sitemap
 * @var array $urls
 */
$urls = $lana_sitemap->get_urls();

foreach ( $urls as $url ) {

	if ( empty( $url[0] ) ) {
		continue;
	}

	if ( $lana_sitemap->is_allowed_domain( $url[0] ) ) {

		$url = $document->createElement( 'url' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = esc_url( $url[0] );
		$url->appendChild( $loc );

		$priority            = $document->createElement( 'priority' );
		$priority->nodeValue = ( isset( $url[1] ) && is_numeric( $url[1] ) ) ? $url[1] : '0.5';
		$url->appendChild( $priority );

		$urlset->appendChild( $url );

	} else {

		$url_skipped = $document->createComment( ' URL ' . esc_url( $url[0] ) . ' skipped: Not within allowed domains. ' );
		$urlset->appendChild( $url_skipped );
	}
}

$document->appendChild( $urlset );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();