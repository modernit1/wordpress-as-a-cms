<?php
/**
 * Google News Sitemap Feed Template
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
 * Get terms
 */
$terms = get_terms( get_query_var( 'taxonomy' ), array(
	'orderby'     => 'count',
	'order'       => 'DESC',
	'lang'        => '',
	'hierachical' => 0,
	'pad_counts'  => true,
	'number'      => 50000
) );

if ( $terms ) {
	foreach ( $terms as $term ) {

		$url = $document->createElement( 'url' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = get_term_link( $term );
		$url->appendChild( $loc );

		$priority            = $document->createElement( 'priority' );
		$priority->nodeValue = $lana_sitemap->get_priority( 'taxonomy', $term );
		$url->appendChild( $priority );

		$lastmod_value = $lana_sitemap->get_lastmod( 'taxonomy', $term );

		if ( $lastmod_value ) {
			$lastmod            = $document->createElement( 'lastmod' );
			$lastmod->nodeValue = $lastmod_value;;
			$url->appendChild( $lastmod );
		}

		$changefreq            = $document->createElement( 'changefreq' );
		$changefreq->nodeValue = $lana_sitemap->get_changefreq( 'taxonomy', $term );
		$url->appendChild( $changefreq );

		$urlset->appendChild( $url );
	}
}

$document->appendChild( $urlset );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();