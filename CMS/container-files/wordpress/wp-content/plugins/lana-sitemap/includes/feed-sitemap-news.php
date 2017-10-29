<?php
/**
 * Google News Sitemap Feed Template
 */

defined( 'ABSPATH' ) or die();

global $lana_sitemap;

$options = $lana_sitemap->get_option( 'news_tags' );

/** start output */
echo $lana_sitemap->headers();

$document = $lana_sitemap->xml_document( 'news' );

$urlset = $document->createElement( 'urlset' );
$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
$urlset->setAttribute( 'xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9' );

if ( ! empty( $options['image'] ) ) {
	$urlset->setAttribute( 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1' );
}

$urlset->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instanc' );

$xsi_schemaLocation   = array();
$xsi_schemaLocation[] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
$xsi_schemaLocation[] = 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-news/0.9';
$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd';

if ( ! empty( $options['image'] ) ) {
	$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-image/1.1';
	$xsi_schemaLocation[] = 'http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd';
}

$urlset->setAttribute( 'xsi:schemaLocation', implode( "\n", $xsi_schemaLocation ) );

if ( have_posts() ) {
	while( have_posts() ){
		the_post();

		$exclude = get_post_meta( $post->ID, '_lana_sitemap_news_exclude', true );

		if ( ! empty( $exclude ) || ! $lana_sitemap->is_allowed_domain( get_permalink() ) ) {
			continue;
		}

		$url = $document->createElement( 'url' );

		$loc            = $document->createElement( 'loc' );
		$loc->nodeValue = esc_url( get_permalink() );
		$url->appendChild( $loc );

		$news_news = $document->createElement( 'news:news' );

		$news_publication = $document->createElement( 'news:publication' );

		$news_name = $document->createElement( 'news:name' );

		if ( ! empty( $options['name'] ) ) {
			$news_name->nodeValue = apply_filters( 'the_title_xml_sitemap', $options['name'] );
		} elseif ( defined( 'LANA_SITEMAP_GOOGLE_NEWS_NAME' ) ) {
			$news_name->nodeValue = apply_filters( 'the_title_xml_sitemap', LANA_SITEMAP_GOOGLE_NEWS_NAME );
		} else {
			$news_name->nodeValue = apply_filters( 'the_title_xml_sitemap', get_bloginfo( 'name' ) );
		}

		$news_publication->appendChild( $news_name );

		$news_language            = $document->createElement( 'news:language' );
		$news_language->nodeValue = $lana_sitemap->get_language( $post->ID );
		$news_publication->appendChild( $news_language );

		$news_news->appendChild( $news_publication );

		$news_publication_date            = $document->createElement( 'news:publication_date' );
		$news_publication_date->nodeValue = mysql2date( 'Y-m-d\TH:i:s+00:00', $post->post_date_gmt, false );
		$news_news->appendChild( $news_publication_date );

		$news_title            = $document->createElement( 'news:title' );
		$news_title->nodeValue = apply_filters( 'the_title_xmlsitemap', get_the_title() );
		$news_news->appendChild( $news_title );

		/**
		 * access tag
		 * @var string $access
		 */
		$access = get_post_meta( $post->ID, '_lana_sitemap_news_access', true );

		/** if not set per meta, let's get global settings */
		if ( empty( $access ) ) {
			if ( ! empty( $options['access'] ) ) {
				if ( post_password_required() ) {
					if ( ! empty( $options['access']['password'] ) ) {
						$access = $options['access']['password'];
					} else if ( ! empty( $options['access']['default'] ) ) {
						$access = $options['access']['default'];
					}
				}
			}
		}

		if ( ! empty( $access ) && $access != 'Public' ) {
			$news_access            = $document->createElement( 'news:access' );
			$news_access->nodeValue = $access;
			$news_news->appendChild( $news_access );
		}

		/**
		 * genres tag
		 */
		$genres = '';
		$terms  = get_the_terms( $post->ID, 'gn-genre' );

		if ( is_array( $terms ) ) {
			$sep = '';
			foreach ( $terms as $obj ) {
				if ( ! empty( $obj->name ) ) {
					$genres .= $sep . $obj->name;
					$sep    = ', ';
				}
			}
		}

		$genres = trim( apply_filters( 'the_title_xml_sitemap', $genres ) );

		if ( empty( $genres ) && ! empty( $options['genres'] ) && ! empty( $options['genres']['default'] ) ) {
			$genres = implode( ', ', (array) $options['genres']['default'] );
		}

		if ( ! empty( $genres ) ) {
			$news_genres            = $document->createElement( 'news:genres' );
			$news_genres->nodeValue = $genres;
			$news_news->appendChild( $news_genres );
		}

		/**
		 * keywords tag
		 */
		$keywords = '';

		if ( ! empty( $options['keywords'] ) ) {
			if ( ! empty( $options['keywords']['from'] ) ) {
				$terms = get_the_terms( $post->ID, $options['keywords']['from'] );
				if ( is_array( $terms ) ) {
					$sep = '';
					foreach ( $terms as $obj ) {
						if ( ! empty( $obj->name ) ) {
							$keywords .= $sep . $obj->name;
							$sep      = ', ';
						}
					}
				}
			}

			$keywords = trim( apply_filters( 'the_title_xml_sitemap', $keywords ) );

			if ( empty( $keywords ) && ! empty( $options['keywords']['default'] ) ) {
				$keywords = trim( apply_filters( 'the_title_xml_sitemap', $options['keywords']['default'] ) );
			}
		}

		if ( ! empty( $keywords ) ) {
			$news_keywords            = $document->createElement( 'news:keywords' );
			$news_keywords->nodeValue = $keywords;
			$news_news->appendChild( $news_keywords );
		}

		do_action( 'lana_sitemap_news_tags_after' );

		$url->appendChild( $news_news );

		if ( ! empty( $options['image'] ) && $lana_sitemap->get_images( 'news' ) ) {
			foreach ( $lana_sitemap->get_images() as $image ) {
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

	$urlset->appendChild( $url );
}

$document->appendChild( $urlset );
$xml = $document->saveXML();

echo $xml;

$lana_sitemap->_e_usage();