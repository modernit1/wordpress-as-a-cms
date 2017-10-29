<?php
/**
 * Lana Sitemap Template for displaying an XML Sitemap feed
 */
defined( 'ABSPATH' ) or die();

global $lana_sitemap;

/**
 * get tags
 * @var array $tags
 */
$tags = $lana_sitemap->do_tags( get_query_var( 'post_type' ) );

/** start output */
echo $lana_sitemap->headers();

$document = $lana_sitemap->xml_document();

$urlset = $document->createElement( 'urlset' );
$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

if ( ! empty( $tags['image'] ) ) {
	$urlset->setAttribute( 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1' );
}

$urlset->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instanc' );

$xsi_schemaLocation   = array();
$xsi_schemaLocation[] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
$xsi_schemaLocation[] = 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';

if ( ! empty( $tags['image'] ) ) {
	$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-image/1.1';
	$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd';
}

$urlset->setAttribute( 'xsi:schemaLocation', implode( "\n", $xsi_schemaLocation ) );

if ( have_posts() ) {
	while( have_posts() ){
		the_post();

		if ( $lana_sitemap->is_excluded( $post->ID ) || ! $lana_sitemap->is_allowed_domain( get_permalink() ) ) {
			continue;
		}

		$url = $document->createElement( 'url' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = esc_url( get_permalink() );
		$url->appendChild( $loc );

		$lastmod_value = $lana_sitemap->get_lastmod();

		if ( $lastmod_value ) {
			$lastmod            = $document->createElement( 'lastmod' );
			$lastmod->nodeValue = $lastmod_value;
			$url->appendChild( $lastmod );
		}

		$changefreq            = $document->createElement( 'changefreq' );
		$changefreq->nodeValue = $lana_sitemap->get_changefreq();
		$url->appendChild( $changefreq );

		$priority            = $document->createElement( 'priority' );
		$priority->nodeValue = $lana_sitemap->get_priority();
		$url->appendChild( $priority );

		/**
		 * Get
		 * sitemap images
		 */
		$lana_sitemap_images = $lana_sitemap->get_images();

		if ( ! empty( $tags['image'] ) && $lana_sitemap_images ) {
			foreach ( $lana_sitemap_images as $image ) {
				if ( empty( $image['loc'] ) ) {
					continue;
				}

				$image_image = $document->createElement( 'image:image' );

				$image_loc            = $document->createElement( 'image:loc' );
				$image_loc->nodeValue = utf8_uri_encode( $image['loc'] );
				$image_image->appendChild( $image_loc );

				if ( ! empty( $image['title'] ) ) {
					$image_title = $document->createElement( 'image:title' );

					$title_cdata = $document->createCDATASection( $image['title'] );
					$image_title->appendChild( $title_cdata );

					$image_image->appendChild( $image_title );
				}

				if ( ! empty( $image['caption'] ) ) {
					$image_caption = $document->createElement( 'image:caption' );

					$caption_cdata = $document->createCDATASection( $image['caption'] );
					$image_caption->appendChild( $caption_cdata );

					$image_image->appendChild( $image_caption );
				}

				$url->appendChild( $image_image );
			}
		}

		$urlset->appendChild( $url );
	}

} else {
	/** No posts done? Then do at least the homepage to prevent error message in GWT. */

	$url = $document->createElement( 'url' );

	$loc            = $document->createElement( 'loc' );
	$loc->nodeValue = esc_url( home_url() );
	$url->appendChild( $loc );

	$priority            = $document->createElement( 'priority' );
	$priority->nodeValue = '1.0';
	$url->appendChild( $priority );

	$urlset->appendChild( $url );
}

$document->appendChild( $urlset );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();