<?php

class Lana_Sitemap{

	/**
	 * Pretty permalinks base name
	 * @var string
	 */
	public $base_name = 'sitemap';

	/**
	 * Pretty permalinks extension
	 * @var string
	 */
	public $extension = 'xml';

	/**
	 * Timezone
	 * @var null $timezone
	 */
	private $timezone = null;

	/**
	 * Default language
	 * @var null $blog_language
	 */
	private $blog_language = null;

	/**
	 * Flushed flag
	 * @var bool
	 */
	private $flushed = false;

	/**
	 * Defaults
	 * @var array
	 */
	private $defaults = array();

	/**
	 * attachment post type is disabled
	 * images are included via tags in the post and page sitemaps
	 * @var array
	 */
	private $disabled_post_types = array( 'attachment' );

	/**
	 * post format taxonomy is disabled
	 * @var array
	 */
	private $disabled_taxonomies = array( 'post_format' );

	/**
	 * Google News genres
	 * @var array
	 */
	private $gn_genres = array(
		'PressRelease',
		'Satire',
		'Blog',
		'OpEd',
		'Opinion',
		'UserGenerated'
	);

	/**
	 * Global values used for priority and changefreq calculation
	 */
	private $domain;
	private $first_date;
	private $last_modified;
	private $post_modified = array();
	private $term_modified = array();
	private $front_pages = null;
	private $blog_pages = null;
	private $images = array();

	/**
	 * Lana_Sitemap constructor.
	 */
	function __construct() {

		/** sitemap element filters */
		add_filter( 'the_title_xml_sitemap', 'strip_tags' );
		add_filter( 'the_title_xml_sitemap', 'ent2ncr', 8 );
		add_filter( 'the_title_xml_sitemap', 'esc_html' );
		add_filter( 'bloginfo_xml_sitemap', 'ent2ncr', 8 );

		/** REQUEST main filtering function */
		add_filter( 'request', array( $this, 'filter_request' ), 1 );

		/** TEXT DOMAIN */
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

		/** REWRITES */
		add_action( 'generate_rewrite_rules', array( $this, 'rewrite_rules' ) );
		add_filter( 'user_trailingslashit', array( $this, 'trailingslash' ) );

		/** TAXONOMIES, ACTIONS */
		add_action( 'init', array( $this, 'init' ), 0 );

		/** REGISTER SETTINGS, SETTINGS FIELDS */
		add_action( 'admin_init', array( $this, 'admin_init' ), 0 );

		/** ROBOTSTXT */
		add_action( 'do_robotstxt', array( $this, 'robots' ), 0 );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 0 );

		/** PINGING */
		add_action( 'transition_post_status', array( $this, 'do_pings' ), 10, 3 );

		/** CLEAR OBJECT CACHE */
		add_action( 'transition_post_status', array( $this, 'cache_flush' ), 99, 3 );

		/** ACTIVATION */
		register_activation_hook( LANA_SITEMAP_PLUGIN_BASENAME, array( $this, 'activate' ) );
	}

	/**
	 * Get gn_genres
	 * @return array
	 */
	public function gn_genres() {
		return $this->gn_genres;
	}

	/**
	 * Get domain
	 * @return mixed
	 */
	public function domain() {

		if ( empty( $this->domain ) ) {
			$url_parsed   = parse_url( home_url() );
			$this->domain = str_replace( "www.", "", $url_parsed['host'] );
		}

		return $this->domain;
	}

	/**
	 * default options
	 */
	private function set_defaults() {
		/** sitemaps */
		if ( '1' == get_option( 'blog_public' ) ) {
			$this->defaults['sitemaps'] = array(
				'sitemap' => LANA_SITEMAP_NAME
			);
		} else {
			$this->defaults['sitemaps'] = array();
		}

		/** post_types */
		$this->defaults['post_types'] = array();

		/** want 'publicly_queryable' but that excludes pages for some weird reason */
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $name ) {

			/** skip unallowed post types */
			if ( in_array( $name, $this->disabled_post_types ) ) {
				continue;
			}

			$this->defaults['post_types'][ $name ] = array(
				'name'             => $name,
				'active'           => '',
				'archive'          => '',
				'priority'         => '0.5',
				'dynamic_priority' => '',
				'tags'             => array( 'image' => 'attached' )
			);
		}

		$active_arr = array( 'post', 'page' );

		foreach ( $active_arr as $name ) {
			if ( isset( $this->defaults['post_types'][ $name ] ) ) {
				$this->defaults['post_types'][ $name ]['active'] = '1';
			}
		}

		if ( isset( $this->defaults['post_types']['post'] ) ) {
			if ( wp_count_posts( 'post' )->publish > 500 ) {
				$this->defaults['post_types']['post']['archive'] = 'yearly';
			}
			$this->defaults['post_types']['post']['priority']         = '0.7';
			$this->defaults['post_types']['post']['dynamic_priority'] = '1';
		}

		if ( isset( $this->defaults['post_types']['page'] ) ) {
			unset( $this->defaults['post_types']['page']['archive'] );
			$this->defaults['post_types']['page']['priority']         = '0.3';
			$this->defaults['post_types']['page']['dynamic_priority'] = '1';
		}

		/**
		 * taxonomies
		 * by default do not include any taxonomies
		 */
		$this->defaults['taxonomies'] = array();

		/**
		 * news sitemap settings
		 */
		$this->defaults['news_sitemap'] = array();

		/**
		 * search engines to ping
		 */
		$this->defaults['ping'] = array(
			'google' => array(
				'active' => '1',
				'uri'    => 'http://www.google.com/webmasters/tools/ping?sitemap=',
				'type'   => 'GET',
				'news'   => '1'
			),
			'bing'   => array(
				'active' => '1',
				'uri'    => 'http://www.bing.com/ping?sitemap=',
				'type'   => 'GET',
				'news'   => '1'
			),
			'yandex' => array(
				'active' => '',
				'uri'    => 'http://ping.blogs.yandex.ru/RPC2',
				'type'   => 'RPC',
			),
			'baidu'  => array(
				'active' => '',
				'uri'    => 'http://ping.baidu.com/ping/RPC2',
				'type'   => 'RPC',
			),
			'others' => array(
				'active' => '1',
				'uri'    => 'http://rpc.pingomatic.com/',
				'type'   => 'RPC',
			),
		);

		/** robots */
		$this->defaults['robots'] = "";

		/** additional urls */
		$this->defaults['urls'] = array();

		/** additional custom_sitemaps */
		$this->defaults['custom_sitemaps'] = array();

		/** additional allowed domains */
		$this->defaults['domains'] = array();

		/** news sitemap tags settings */
		$this->defaults['news_tags'] = array(
			'name'       => '',
			'post_type'  => array( 'post' ),
			'categories' => '',
			'image'      => 'featured',
			'access'     => array(
				'default'  => '',
				'password' => 'Subscription'
			),
			'genres'     => array(
				'default' => array( 'Blog' )
			),
			'keywords'   => array(
				'from'    => 'category',
				'default' => ''
			)
		);
	}

	/**
	 * Get timezone
	 * @return null|string
	 */
	protected function timezone() {
		$gmt = date_default_timezone_set( 'UTC' );
		if ( $this->timezone === null ) {
			$this->timezone = $gmt ? 'gmt' : 'blog';
		}

		return $this->timezone;
	}

	/**
	 * Get defaults
	 *
	 * @param bool|false $key
	 *
	 * @return mixed
	 */
	protected function defaults( $key = false ) {
		if ( empty( $this->defaults ) ) {
			$this->set_defaults();
		}

		if ( $key ) {
			$return = ( isset( $this->defaults[ $key ] ) ) ? $this->defaults[ $key ] : '';
		} else {
			$return = $this->defaults;
		}

		return apply_filters( 'lana_sitemap_defaults', $return, $key );
	}

	/**
	 * Get option
	 *
	 * @param $option
	 *
	 * @return mixed
	 */
	public function get_option( $option ) {
		return get_option( 'lana_sitemap_' . $option, $this->defaults( $option ) );
	}

	/**
	 * Get sitemaps
	 * @return array
	 */
	public function get_sitemaps() {
		$return = $this->get_option( 'sitemaps' );

		if ( empty( $return ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return;
	}

	/**
	 * Get ping
	 * @return array
	 */
	public function get_ping() {
		$return = $this->get_option( 'ping' );

		if ( empty( $return ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return;
	}

	/**
	 * Get disabled post types
	 * @return array
	 */
	protected function disabled_post_types() {
		return $this->disabled_post_types;
	}

	/**
	 * Get disabled taxonomies
	 * @return array
	 */
	protected function disabled_taxonomies() {
		return $this->disabled_taxonomies;
	}

	/**
	 * Get post types
	 * @return array
	 */
	public function get_post_types() {
		$return = $this->get_option( 'post_types' );

		if ( empty( $return ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return;
	}

	/**
	 * Have post types
	 * @return array
	 */
	public function have_post_types() {
		$return = array();

		foreach ( $this->get_post_types() as $type => $values ) {
			if ( ! empty( $values['active'] ) ) {
				$count = wp_count_posts( $values['name'] );
				if ( $count->publish > 0 ) {
					$values['count'] = $count->publish;
					$return[ $type ] = $values;
				}
			}
		}

		if ( empty( $return ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return;
	}

	/**
	 * Get taxonomies
	 * @return array
	 */
	public function get_taxonomies() {
		$return = $this->get_option( 'taxonomies' );

		if ( empty( $return ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return;
	}

	/**
	 * Get custom sitemaps
	 * @return mixed
	 */
	public function get_custom_sitemaps() {
		$urls = $this->get_option( 'custom_sitemaps' );

		/** make sure it's an array we are returning */
		if ( ! empty( $urls ) ) {
			$return = ( ! is_array( $urls ) ) ? explode( "\n", $urls ) : $urls;
		} else {
			$return = array();
		}

		return apply_filters( 'lana_sitemap_custom_sitemaps', $return );
	}

	/**
	 * Get urls
	 * @return mixed
	 */
	public function get_urls() {
		$urls = $this->get_option( 'urls' );

		/** make sure it's an array we are returning */
		if ( ! empty( $urls ) ) {
			$return = ( ! is_array( $urls ) ) ? explode( "\n", $urls ) : $urls;
		} else {
			$return = array();
		}

		return apply_filters( 'lana_sitemap_custom_urls', $return );
	}

	/**
	 * Get domains
	 * @return array
	 */
	public function get_domains() {
		$domains = $this->get_option( 'domains' );
		if ( ! empty( $domains ) && is_array( $domains ) ) {
			return array_merge( array( $this->domain() ), $domains );
		} else {
			return array( $this->domain() );
		}
	}

	/**
	 * Get archives
	 *
	 * @param string $post_type
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_archives( $post_type = 'post', $type = '' ) {
		global $wpdb;

		$return = array();

		if ( 'monthly' == $type ) {
			$query = "SELECT YEAR(post_date) AS `year`, LPAD(MONTH(post_date),2,'0') AS `month`, count(ID) as posts FROM " . $wpdb->posts . " WHERE post_type = '" . $post_type . "' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC";
			$key   = md5( $query );
			$cache = wp_cache_get( 'lana_sitemap_get_archives', 'general' );
			if ( ! isset( $cache[ $key ] ) ) {
				$arcresults    = $wpdb->get_results( $query );
				$cache[ $key ] = $arcresults;
				wp_cache_set( 'lana_sitemap_get_archives', $cache, 'general' );
			} else {
				$arcresults = $cache[ $key ];
			}
			if ( $arcresults ) {
				foreach ( (array) $arcresults as $arcresult ) {
					$return[ $arcresult->year . $arcresult->month ] = $this->get_index_url( 'post_type', $post_type, $arcresult->year . $arcresult->month );
				}
			}
		} elseif ( 'yearly' == $type ) {
			$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM " . $wpdb->posts . " WHERE post_type = '" . $post_type . "' AND post_status = 'publish' GROUP BY YEAR(post_date) ORDER BY post_date DESC";
			$key   = md5( $query );
			$cache = wp_cache_get( 'lana_sitemap_get_archives', 'general' );
			if ( ! isset( $cache[ $key ] ) ) {
				$arcresults    = $wpdb->get_results( $query );
				$cache[ $key ] = $arcresults;
				wp_cache_set( 'lana_sitemap_get_archives', $cache, 'general' );
			} else {
				$arcresults = $cache[ $key ];
			}
			if ( $arcresults ) {
				foreach ( (array) $arcresults as $arcresult ) {
					$return[ $arcresult->year ] = $this->get_index_url( 'post_type', $post_type, $arcresult->year );
				}
			}
		} else {
			/** $sitemap = 'home', $type = false, $param = false */
			$return[0] = $this->get_index_url( 'post_type', $post_type );
		}

		return $return;
	}

	/**
	 * Get robots
	 * @return mixed|string
	 */
	public function get_robots() {
		return ( $robots = $this->get_option( 'robots' ) ) ? $robots : '';
	}

	/**
	 * Do tags
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function do_tags( $type = 'post' ) {
		$return = $this->get_post_types();

		if ( ! is_string( $type ) ) {
			return array();
		}

		if ( ! isset( $return[ $type ] ) ) {
			return array();
		}

		if ( empty( $return[ $type ]['tags'] ) ) {
			return array();
		}

		/** make sure it's an array we are returning */
		return (array) $return[ $type ]['tags'];
	}

	/**
	 * Get translations
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	private function get_translations( $post_id ) {
		$translation_ids = array();

		/** WPML compat */
		global $sitepress;

		if ( isset( $sitepress ) && is_object( $sitepress ) && method_exists( $sitepress, 'get_languages' ) && method_exists( $sitepress, 'get_object_id' ) ) {
			foreach ( array_keys( $sitepress->get_languages( false, true ) ) as $term ) {
				$id = $sitepress->get_object_id( $post_id, 'page', false, $term );
				if ( $post_id != $id ) {
					$translation_ids[] = $id;
				}
			}
		}

		return $translation_ids;
	}

	/**
	 * Get blog_pages
	 * @return array|null
	 */
	private function get_blog_pages() {

		if ( null === $this->blog_pages ) {
			$blog_pages = array();
			if ( 'page' == get_option( 'show_on_front' ) ) {
				$blogpage = (int) get_option( 'page_for_posts' );
				if ( ! empty( $blogpage ) ) {
					$blog_pages = array_merge( (array) $blogpage, $this->get_translations( $blogpage ) );
				}
			}
			$this->blog_pages = $blog_pages;
		}

		return $this->blog_pages;
	}

	/**
	 * Get front_pages
	 * @return array|null
	 */
	private function get_front_pages() {

		if ( null === $this->front_pages ) {
			$front_pages = array();
			if ( 'page' == get_option( 'show_on_front' ) ) {
				$frontpage   = (int) get_option( 'page_on_front' );
				$front_pages = array_merge( (array) $frontpage, $this->get_translations( $frontpage ) );
			}
			$this->front_pages = $front_pages;
		}

		return $this->front_pages;
	}

	/**
	 * Is home?
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	private function is_home( $id ) {
		return in_array( $id, $this->get_blog_pages() );
	}

	/**
	 * Lana Sitemap
	 * header
	 * @return string
	 */
	public function headers() {
		/** maybe output buffering is on, then just make sure we start with a clean buffer */
		if ( ob_get_level() ) {
			ob_clean();
		}

		/** check if headers are already sent (bad) and set up a warning in admin (how?) */
		if ( ! headers_sent( $filename, $linenum ) ) {
			status_header( '200' );
			header( 'Content-Type: text/xml; charset=' . get_bloginfo( 'charset' ), true );
			header( 'X-Robots-Tag: noindex, follow', true );
			$output = '';
		} else {
			$output = "<!-- WARNING: Headers already sent by " . $filename . " on line " . $linenum . ". Please fix! -->\n";
		}

		return $output;
	}

	/**
	 * Lana Sitemap
	 * xml document
	 *
	 * @param string $style
	 *
	 * @return DOMDocument
	 */
	public function xml_document( $style = '' ) {

		$document = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );

		$document->preserveWhiteSpace = false;
		$document->formatOutput       = true;

		$stylesheet_url = plugins_url( 'xsl/sitemap.xsl', __FILE__ );

		if ( $style == 'index' ) {
			$stylesheet_url = plugins_url( 'xsl/sitemap-index.xsl', __FILE__ );
		}

		if ( $style == 'news' ) {
			$stylesheet_url = plugins_url( 'xsl/sitemap-news.xsl', __FILE__ );
		}

		$xslt = $document->createProcessingInstruction( 'xml-stylesheet', 'type="text/xsl" href="' . $stylesheet_url . '?ver=' . LANA_SITEMAP_VERSION . '"' );
		$document->appendChild( $xslt );

		$generated_on = $document->createComment( ' generated-on="' . date( 'Y-m-d\TH:i:s+00:00' ) . '" ' );
		$document->appendChild( $generated_on );

		$generator = $document->createComment( ' generator="Lana Sitemap plugin for WordPress" ' );
		$document->appendChild( $generator );

		$generator_url = $document->createComment( ' generator-url="http://wp.lanaprojekt.hu/blog/wordpress-plugins/lana-sitemap/" ' );
		$document->appendChild( $generator_url );

		$generator_version = $document->createComment( ' generator-version="' . LANA_SITEMAP_VERSION . '" ' );
		$document->appendChild( $generator_version );

		return $document;
	}

	/**
	 * Modified
	 *
	 * @param string $sitemap
	 * @param string $term
	 *
	 * @return string
	 */
	public function modified( $sitemap = 'post_type', $term = '' ) {

		if ( 'post_type' == $sitemap ) {
			global $post;

			/** if blog page then look for last post date */
			if ( $post->post_type == 'page' && $this->is_home( $post->ID ) ) {
				return lana_sitemap_get_last_modified( 'GMT', 'post' );
			}

			if ( empty( $this->post_modified[ $post->ID ] ) ) {
				$post_modified = get_post_modified_time( 'Y-m-d H:i:s', true, $post->ID );
				$options       = $this->get_post_types();

				$lastcomment = array();

				if ( ! empty( $options[ $post->post_type ]['update_lastmod_on_comments'] ) ) {
					$lastcomment = get_comments( array(
						'status'  => 'approve',
						'number'  => 1,
						'post_id' => $post->ID,
					) );
				}

				if ( isset( $lastcomment[0]->comment_date_gmt ) ) {
					if ( mysql2date( 'U', $lastcomment[0]->comment_date_gmt, false ) > mysql2date( 'U', $post_modified, false ) ) {
						$post_modified = $lastcomment[0]->comment_date_gmt;
					}
				}

				/** make sure lastmod is not older than publication date (happens on scheduled posts) */
				if ( isset( $post->post_date_gmt ) && strtotime( $post->post_date_gmt ) > strtotime( $post_modified ) ) {
					$post_modified = $post->post_date_gmt;
				}

				$this->post_modified[ $post->ID ] = $post_modified;
			}

			return $this->post_modified[ $post->ID ];

		} elseif ( ! empty( $term ) ) {

			/** @var WP_Term $term */
			if ( is_object( $term ) ) {
				if ( ! isset( $this->term_modified[ $term->term_id ] ) ) {

					/**
					 * get the latest post in this taxonomy item, to use its post_date as lastmod
					 * @var WP_Post[] $posts
					 */
					$posts = get_posts( array(
						'post_type'              => 'any',
						'numberposts'            => 1,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'update_cache'           => false,
						'tax_query'              => array(
							array(
								'taxonomy' => $term->taxonomy,
								'field'    => 'slug',
								'terms'    => $term->slug
							)
						)
					) );

					$this->term_modified[ $term->term_id ] = isset( $posts[0]->post_date_gmt ) ? $posts[0]->post_date_gmt : '';
				}

				return $this->term_modified[ $term->term_id ];
			} else {
				$taxonomy = get_taxonomy( $term );

				return lana_sitemap_get_last_date( 'gmt', $taxonomy->object_type );
			}

		} else {
			return '';
		}
	}

	/**
	 * Get images
	 *
	 * @param string $sitemap
	 *
	 * @return array|bool
	 */
	public function get_images( $sitemap = '' ) {
		global $post;

		if ( empty( $this->images[ $post->ID ] ) ) {

			if ( 'news' == $sitemap ) {
				$options = $this->get_option( 'news_tags' );
				$which   = isset( $options['image'] ) ? $options['image'] : '';
			} else {
				$options = $this->get_post_types();
				$which   = isset( $options[ $post->post_type ]['tags']['image'] ) ? $options[ $post->post_type ]['tags']['image'] : '';
			}

			if ( 'attached' == $which ) {
				$args        = array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'numberposts'    => - 1,
					'post_status'    => 'inherit',
					'post_parent'    => $post->ID
				);
				$attachments = get_posts( $args );
				if ( $attachments ) {
					foreach ( $attachments as $attachment ) {
						$url = wp_get_attachment_image_src( $attachment->ID, 'full' );

						$this->images[ $post->ID ][] = array(
							'loc'     => esc_attr( esc_url_raw( $url[0] ) ),
							'title'   => apply_filters( 'the_title_xml_sitemap', $attachment->post_title ),
							'caption' => apply_filters( 'the_title_xml_sitemap', $attachment->post_excerpt )
						);
					}
				}
			} elseif ( 'featured' == $which ) {
				if ( has_post_thumbnail( $post->ID ) ) {
					$attachment = get_post( get_post_thumbnail_id( $post->ID ) );
					$url        = wp_get_attachment_image_src( $attachment->ID, 'full' );

					$this->images[ $post->ID ][] = array(
						'loc'     => esc_attr( esc_url_raw( $url[0] ) ),
						'title'   => apply_filters( 'the_title_xml_sitemap', $attachment->post_title ),
						'caption' => apply_filters( 'the_title_xml_sitemap', $attachment->post_excerpt )
					);
				}
			}
		}

		return ( isset( $this->images[ $post->ID ] ) ) ? $this->images[ $post->ID ] : false;
	}

	/**
	 * Get last modified
	 *
	 * @param string $sitemap
	 * @param string $term
	 *
	 * @return string
	 */
	public function get_lastmod( $sitemap = 'post_type', $term = '' ) {
		$return = trim( mysql2date( 'Y-m-d\TH:i:s+00:00', $this->modified( $sitemap, $term ), false ) );

		return ! empty( $return ) ? $return : false;
	}

	/**
	 * Get change frequency
	 *
	 * @param string $sitemap
	 * @param string $term
	 *
	 * @return string
	 */
	public function get_changefreq( $sitemap = 'post_type', $term = '' ) {
		$modified = trim( $this->modified( $sitemap, $term ) );

		if ( empty( $modified ) ) {
			return 'weekly';
		}

		$last_activity_age = ( gmdate( 'U' ) - mysql2date( 'U', $modified, false ) );

		if ( ( $last_activity_age / 86400 ) < 1 ) {
			$changefreq = 'hourly';
		} elseif ( ( $last_activity_age / 86400 ) < 7 ) {
			$changefreq = 'daily';
		} elseif ( ( $last_activity_age / 86400 ) < 30 ) {
			$changefreq = 'weekly';
		} elseif ( ( $last_activity_age / 86400 ) < 365 ) {
			$changefreq = 'monthly';
		} else {
			$changefreq = 'yearly';
		}

		return $changefreq;
	}

	/**
	 * Get priority
	 *
	 * @param string $sitemap
	 * @param WP_Term|string $term
	 *
	 * @return string
	 */
	public function get_priority( $sitemap = 'post_type', $term = '' ) {

		if ( 'post_type' == $sitemap ) {
			global $post;

			$options       = $this->get_post_types();
			$defaults      = $this->defaults( 'post_types' );
			$priority_meta = get_metadata( 'post', $post->ID, '_lana_sitemap_priority', true );

			if ( ! empty( $priority_meta ) || $priority_meta == '0' ) {

				$priority = floatval( str_replace( ",", ".", $priority_meta ) );

			} elseif ( ! empty( $options[ $post->post_type ]['dynamic_priority'] ) ) {

				$post_modified = mysql2date( 'U', $post->post_modified_gmt, false );

				/**
				 * last posts or page modified date in Unix seconds
				 * uses lana_sitemap_get_last_modified() function defined in lana-sitemap/lana-sitemap-functions.php !
				 */
				if ( empty( $this->last_modified ) ) {
					$this->last_modified = mysql2date( 'U', lana_sitemap_get_last_modified( 'GMT', $post->post_type ), false );
				}

				/**
				 * uses get_first_date() function defined in lana-sitemap/lana-sitemap-functions.php !
				 */
				if ( empty( $this->first_date ) ) {
					$this->first_date = mysql2date( 'U', lana_sitemap_get_first_date( 'GMT', $post->post_type ), false );
				}

				if ( isset( $options[ $post->post_type ]['priority'] ) ) {
					$priority_value = floatval( str_replace( ",", ".", $options[ $post->post_type ]['priority'] ) );
				} else {
					$priority_value = floatval( $defaults[ $post->post_type ]['priority'] );
				}

				/**
				 * reduce by age
				 * NOTE : home/blog page gets same treatment as sticky post, i.e. no reduction by age
				 */
				if ( is_sticky( $post->ID ) || $this->is_home( $post->ID ) ) {
					$priority = $priority_value;
				} else {
					$priority = ( $this->last_modified > $this->first_date ) ? $priority_value - $priority_value * ( $this->last_modified - $post_modified ) / ( $this->last_modified - $this->first_date ) : $priority_value;
				}

				if ( $post->comment_count > 0 ) {
					$priority = $priority + 0.1 + ( 0.9 - $priority ) * $post->comment_count / wp_count_comments( $post->post_type )->approved;
				}

			} else {

				$priority = ( isset( $options[ $post->post_type ]['priority'] ) && is_numeric( $options[ $post->post_type ]['priority'] ) ) ? $options[ $post->post_type ]['priority'] : $defaults[ $post->post_type ]['priority'];
			}

		} elseif ( ! empty( $term ) ) {

			$max_priority = 0.4;
			$min_priority = 0.0;

			$tax_obj   = get_taxonomy( $term->taxonomy );
			$postcount = 0;
			foreach ( $tax_obj->object_type as $post_type ) {
				$_post_count = wp_count_posts( $post_type );
				$postcount += $_post_count->publish;
			}

			$priority = ( $postcount > 0 ) ? $min_priority + ( $max_priority * $term->count / $postcount ) : $min_priority;

		} else {

			$priority = 0.5;
		}

		/** make sure we're not below zero */
		if ( $priority < 0 ) {
			$priority = 0;
		}

		/** and a final trim for cases where we ended up above 1 (sticky posts with many comments) */
		if ( $priority > 1 ) {
			$priority = 1;
		}

		return number_format( $priority, 1 );
	}

	/**
	 * Get home urls
	 * @return array
	 */
	public function get_home_urls() {
		$urls = array();

		/** WPML compat */
		global $sitepress;

		if ( isset( $sitepress ) && is_object( $sitepress ) && method_exists( $sitepress, 'get_languages' ) && method_exists( $sitepress, 'language_url' ) ) {
			foreach ( array_keys( $sitepress->get_languages( false, true ) ) as $term ) {
				$urls[] = $sitepress->language_url( $term );
			}
		} else {
			$urls[] = home_url();
		}

		return $urls;
	}

	/**
	 * Is excluded
	 *
	 * @param null $post_id
	 *
	 * @return bool|mixed|void
	 */
	public function is_excluded( $post_id = null ) {

		/** no ID, try and get it from global post object */
		if ( null == $post_id ) {
			global $post;
			if ( is_object( $post ) && isset( $post->ID ) ) {
				$post_id = $post->ID;
			} else {
				return false;
			}
		}

		$excluded = get_post_meta( $post_id, '_lana_sitemap_exclude', true ) || in_array( $post_id, $this->get_front_pages() ) ? true : false;

		return apply_filters( 'lana_sitemap_excluded', $excluded, $post_id );
	}

	/**
	 * Is allowed domain
	 *
	 * @param $url
	 *
	 * @return mixed|void
	 */
	public function is_allowed_domain( $url ) {

		$domains    = $this->get_domains();
		$return     = false;
		$parsed_url = parse_url( $url );

		if ( isset( $parsed_url['host'] ) ) {
			foreach ( $domains as $domain ) {
				if ( $parsed_url['host'] == $domain || strpos( $parsed_url['host'], "." . $domain ) !== false ) {
					$return = true;
					break;
				}
			}
		}

		return apply_filters( 'lana_sitemap_allowed_domain', $return, $url );
	}

	/**
	 * Get index url
	 *
	 * @param string $sitemap
	 * @param bool|false $type
	 * @param bool|false $param
	 *
	 * @return string
	 */
	public function get_index_url( $sitemap = 'home', $type = false, $param = false ) {
		$split_url = explode( '?', home_url() );

		$name = $this->base_name . '-' . $sitemap;

		if ( $type ) {
			$name .= '-' . $type;
		}

		if ( '' == get_option( 'permalink_structure' ) || '1' != get_option( 'blog_public' ) ) {
			$name = '?feed=' . $name;
			$name .= $param ? '&m=' . $param : '';
			$name .= isset( $split_url[1] ) && ! empty( $split_url[1] ) ? '&' . $split_url[1] : '';
		} else {
			$name .= $param ? '.' . $param : '';
			$name .= '.' . $this->extension;
			$name .= isset( $split_url[1] ) && ! empty( $split_url[1] ) ? '?' . $split_url[1] : '';
		}

		return esc_url( trailingslashit( $split_url[0] ) . $name );
	}

	/**
	 * Get language
	 *
	 * @param $id
	 *
	 * @return null|string
	 */
	public function get_language( $id ) {
		$language = null;

		if ( empty( $this->blog_language ) ) {
			$blog_language = convert_chars( strip_tags( get_bloginfo( 'language' ) ) );
			$expl          = explode( '-', $blog_language );
			$blog_language = $expl[0];

			$this->blog_language = ! empty( $blog_language ) ? $blog_language : 'en';
		}

		/** WPML compat */
		global $sitepress;

		if ( isset( $sitepress ) && is_object( $sitepress ) && method_exists( $sitepress, 'get_language_for_element' ) ) {
			$post_type = get_query_var( 'post_type', 'post' );
			$language  = $sitepress->get_language_for_element( $id, 'post_' . $post_type[0] );
		}

		return ! empty( $language ) ? $language : $this->blog_language;
	}

	/**
	 * add sitemap location in robots.txt generated by WP
	 */
	public function robots() {
		echo "\n# Lana Sitemap version " . LANA_SITEMAP_VERSION . " - http://wp.lanaprojekt.hu/blog/wordpress-plugins/lana-sitemap/";

		if ( '1' != get_option( 'blog_public' ) ) {
			echo "\n# XML Sitemaps are disabled. Please see Site Visibility on Settings > Reading.";
		} else {
			foreach ( $this->get_sitemaps() as $pretty ) {
				echo "\nSitemap: " . trailingslashit( get_bloginfo( 'url' ) ) . $pretty;
			}

			if ( empty( $pretty ) ) {
				echo "\n# No XML Sitemaps are enabled. Please see XML Sitemaps on Settings > Reading.";
			}
		}
		echo "\n\n";
	}

	/**
	 * add robots.txt rules
	 *
	 * @param $output
	 *
	 * @return string
	 */
	public function robots_txt( $output ) {
		return $output . $this->get_option( 'robots' ) . "\n\n";
	}

	/**
	 * Remove the trailing slash from permalinks that have an extension, such as /sitemap.xml
	 *
	 * @param $request
	 *
	 * @return mixed
	 */
	public function trailingslash( $request ) {
		if ( pathinfo( $request, PATHINFO_EXTENSION ) ) {
			return untrailingslashit( $request );
		}

		return $request;
	}

	/**
	 * Add sitemap rewrite rules
	 *
	 * @param WP_Rewrite $wp_rewrite
	 */
	public function rewrite_rules( $wp_rewrite ) {
		$lana_sitemap_rules = array();
		$sitemaps           = $this->get_sitemaps();

		foreach ( $sitemaps as $name => $pretty ) {
			$lana_sitemap_rules[ preg_quote( $pretty ) . '$' ] = $wp_rewrite->index . '?feed=' . $name;
		}

		if ( ! empty( $sitemaps['sitemap'] ) ) {
			/** home urls */
			$lana_sitemap_rules[ $this->base_name . '-home\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-home';

			/**
			 * add rules for post types (can be split by month or year)
			 */
			foreach ( $this->get_post_types() as $post_type ) {
				if ( isset( $post_type['active'] ) && '1' == $post_type['active'] ) {
					$lana_sitemap_rules[ $this->base_name . '-post_type-' . $post_type['name'] . '\.([0-9]+)?\.?' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-post_type-' . $post_type['name'] . '&m=$matches[1]';
				}
			}

			/**
			 * add rules for taxonomies
			 */
			foreach ( $this->get_taxonomies() as $taxonomy ) {
				$lana_sitemap_rules[ $this->base_name . '-taxonomy-' . $taxonomy . '\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-taxonomy-' . $taxonomy;
			}

			$urls = $this->get_urls();
			if ( ! empty( $urls ) ) {
				$lana_sitemap_rules[ $this->base_name . '-custom\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-custom';
			}

		}

		$wp_rewrite->rules = $lana_sitemap_rules + $wp_rewrite->rules;
	}

	/**
	 * WPML: switch language
	 * @see https://wpml.org/wpml-hook/wpml_post_language_details/
	 */
	public function wpml_language_switcher() {
		global $sitepress, $post;

		if ( isset( $sitepress ) ) {
			$post_language = apply_filters( 'wpml_post_language_details', null, $post->ID );
			$sitepress->switch_lang( $post_language['language_code'] );
		}

	}

	/**
	 * Filter request
	 *
	 * @param $request
	 *
	 * @return mixed
	 */
	public function filter_request( $request ) {
		if ( isset( $request['feed'] ) && strpos( $request['feed'], 'sitemap' ) === 0 ) {

			/** modify request parameters */
			$request['post_status']            = 'publish';
			$request['no_found_rows']          = true;
			$request['cache_results']          = false;
			$request['update_post_term_cache'] = false;
			$request['update_post_meta_cache'] = false;

			/** WPML compat */
			global $wpml_query_filter;

			if ( isset( $wpml_query_filter ) && is_object( $wpml_query_filter ) ) {
				remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) );
				remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ) );
				add_action( 'the_post', array( $this, 'wpml_language_switcher' ) );
			}

			if ( $request['feed'] == 'sitemap-news' ) {
				$defaults       = $this->defaults( 'news_tags' );
				$options        = $this->get_option( 'news_tags' );
				$news_post_type = isset( $options['post_type'] ) && ! empty( $options['post_type'] ) ? $options['post_type'] : $defaults['post_type'];

				if ( empty( $news_post_type ) ) {
					$news_post_type = 'post';
				}

				/** disable caching */
				define( 'DONOTCACHEPAGE', true );
				define( 'DONOTCACHEDB', true );

				/**
				 * set up query filters
				 */
				$zone = $this->timezone();

				if ( lana_sitemap_get_last_date( $zone, $news_post_type ) > date( 'Y-m-d H:i:s', strtotime( '-48 hours' ) ) ) {
					add_filter( 'post_limits', array( $this, 'filter_news_limits' ) );
					add_filter( 'posts_where', array( $this, 'filter_news_where' ), 10, 1 );
				} else {
					add_filter( 'post_limits', array( $this, 'filter_no_news_limits' ) );
				}

				/** post type */
				$request['post_type'] = $news_post_type;

				/** categories */
				if ( isset( $options['categories'] ) && is_array( $options['categories'] ) ) {
					$request['cat'] = implode( ',', $options['categories'] );
				}

				return $request;
			}

			if ( strpos( $request['feed'], 'sitemap-post_type' ) === 0 ) {
				foreach ( $this->get_post_types() as $post_type ) {
					if ( $request['feed'] == 'sitemap-post_type-' . $post_type['name'] ) {

						/** setup filter */
						add_filter( 'post_limits', array( $this, 'filter_limits' ) );

						$request['post_type'] = $post_type['name'];
						$request['orderby']   = 'modified';

						return $request;
					}
				}
			}

			if ( strpos( $request['feed'], 'sitemap-taxonomy' ) === 0 ) {
				foreach ( $this->get_taxonomies() as $taxonomy ) {
					if ( $request['feed'] == 'sitemap-taxonomy-' . $taxonomy ) {

						$request['taxonomy'] = $taxonomy;

						/** WPML compat */
						global $sitepress;

						if ( isset( $sitepress ) && is_object( $sitepress ) ) {
							remove_filter( 'get_terms_args', array( $sitepress, 'get_terms_args_filter' ) );
							remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
							remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
							$sitepress->switch_lang( 'all' );
						}

						return $request;
					}
				}
			}
		}

		return $request;
	}

	/**
	 * Set up the sitemap index template
	 */
	public function load_template_index() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap.php' );
	}

	/**
	 * set up the sitemap home page(s) template
	 */
	public function load_template_base() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap-home.php' );
	}

	/**
	 * set up the post types sitemap template
	 */
	public function load_template() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap-post_type.php' );
	}

	/**
	 * set up the taxonomy sitemap template
	 */
	public function load_template_taxonomy() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap-taxonomy.php' );
	}

	/**
	 * set up the news sitemap template
	 */
	public function load_template_news() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap-news.php' );
	}

	/**
	 * set up the custom sitemap template
	 */
	public function load_template_custom() {
		load_template( dirname( __FILE__ ) . '/feed-sitemap-custom.php' );
	}

	/**
	 * Filter limits
	 * override default feed limit
	 * @return string
	 */
	public function filter_limits() {
		return 'LIMIT 0, 50000';
	}

	/**
	 * Filter news WHERE
	 * only posts from the last 48 hours
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function filter_news_where( $where = '' ) {
		$_gmt = ( 'gmt' === $this->timezone() ) ? '_gmt' : '';

		return $where . " AND post_date" . $_gmt . " > '" . date( 'Y-m-d H:i:s', strtotime( '-48 hours' ) ) . "'";
	}

	/**
	 * Filter news LIMITS
	 * override default feed limit for GN
	 * @return string
	 */
	public function filter_news_limits() {
		return 'LIMIT 0, 1000';
	}

	/**
	 * Filter no news LIMITS
	 * in case there is no news, just take the latest post
	 * @return string
	 */
	public function filter_no_news_limits() {
		return 'LIMIT 0, 1';
	}

	/**
	 * Lana Sitemap
	 * Ping
	 *
	 * @param $uri
	 * @param int $timeout
	 *
	 * @return bool
	 */
	public function ping( $uri, $timeout = 3 ) {
		$options            = array();
		$options['timeout'] = $timeout;

		$response = wp_remote_request( $uri, $options );

		if ( '200' == wp_remote_retrieve_response_code( $response ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lana Sitemap
	 * Do pings
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public function do_pings( $new_status, $old_status, $post ) {
		$sitemaps = $this->get_sitemaps();
		$to_ping  = $this->get_ping();
		$update   = false;

		/** first check if news sitemap is set */
		if ( ! empty( $sitemaps['sitemap-news'] ) ) {

			$news_tags = $this->get_option( 'news_tags' );

			if ( ! empty( $news_tags['post_type'] ) && is_array( $news_tags['post_type'] ) && in_array( $post->post_type, $news_tags['post_type'] ) ) {

				if ( $old_status != 'publish' && $new_status == 'publish' ) {
					foreach ( $to_ping as $se => $data ) {

						if ( empty( $data['active'] ) || empty( $data['news'] ) ) {
							continue;
						}

						if ( ! empty( $data['pong'] ) && is_array( $data['pong'] ) && ! empty( $data['pong'][ $sitemaps['sitemap-news'] ] ) && (int) $data['pong'][ $sitemaps['sitemap-news'] ] + 300 > time() ) {
							continue;
						}

						if ( $this->ping( $data['uri'] . urlencode( trailingslashit( get_bloginfo( 'url' ) ) . $sitemaps['sitemap-news'] ) ) ) {
							$to_ping[ $se ]['pong'][ $sitemaps['sitemap-news'] ] = time();

							$update = true;
						}

					}
				}
			}
		}

		/** first check if regular sitemap is set */
		if ( ! empty( $sitemaps['sitemap'] ) ) {
			foreach ( $this->get_post_types() as $post_type ) {
				if ( ! empty( $post_type ) && is_array( $post_type ) && in_array( $post->post_type, $post_type ) ) {
					if ( $old_status != 'publish' && $new_status == 'publish' ) {
						foreach ( $to_ping as $se => $data ) {

							if ( empty( $data['active'] ) || empty( $data['type'] ) || $data['type'] != 'GET' ) {
								continue;
							}

							if ( ! empty( $data['pong'] ) && is_array( $data['pong'] ) && ! empty( $data['pong'][ $sitemaps['sitemap'] ] ) && (int) $data['pong'][ $sitemaps['sitemap'] ] + 3600 > time() ) {
								continue;
							}

							if ( $this->ping( $data['uri'] . urlencode( trailingslashit( get_bloginfo( 'url' ) ) . $sitemaps['sitemap'] ) ) ) {
								$to_ping[ $se ]['pong'][ $sitemaps['sitemap'] ] = time();

								$update = true;
							}
						}
					}
				}
			}
		}

		if ( $update ) {
			update_option( 'lana_sitemap_ping', $to_ping );
		}
	}

	/**
	 * Lana Sitemap
	 * Clear settings
	 */
	public function clear_settings() {

		foreach ( $this->defaults() as $option => $settings ) {
			delete_option( 'lana_sitemap_' . $option );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Lana Sitemap settings cleared' );
		}
	}

	/**
	 * Lana Sitemap
	 * Cache flush
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	function cache_flush( $new_status, $old_status, $post ) {
		if ( $new_status == 'publish' || $old_status == 'publish' ) {
			wp_cache_delete( 'lana_sitemap_get_archives', 'general' );
			wp_cache_delete( $this->get_time_key( $post ), 'timeinfo' );
		}
	}

	/**
	 * This method mimics triggers the cache-key calculation used within _get_time()
	 * The passed parameters mimic the behavior of get_last_modified
	 *
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	private function get_time_key( $post ) {
		$timezone = 'gmt';
		$which    = 'last';
		$field    = 'modified';
		$m        = 0;

		return lana_sitemap_get_time_key( $timezone, $field, $post->post_type, $which, $m );
	}

	/**
	 * Lana Sitemap
	 * Plugins loaded
	 */
	public function plugins_loaded() {
		if ( ! is_admin() ) {
			return;
		}
		load_plugin_textdomain( 'lana-sitemap', false, dirname( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Lana Sitemap
	 * activate
	 */
	public function activate() {
		$this->flush_rules();

		$home_path = trailingslashit( get_home_path() );
		$sitemaps  = $this->get_sitemaps();

		foreach ( $sitemaps as $name => $pretty ) {
			if ( file_exists( $home_path . $pretty ) ) {
				unlink( $home_path . $pretty );
			}
		}
	}

	/**
	 * Lana Sitemap
	 * init
	 */
	public function init() {

		$sitemaps = $this->get_sitemaps();

		if ( isset( $sitemaps['sitemap'] ) ) {
			add_action( 'do_feed_sitemap', array( $this, 'load_template_index' ), 10, 1 );
			add_action( 'do_feed_sitemap-home', array( $this, 'load_template_base' ), 10, 1 );
			add_action( 'do_feed_sitemap-custom', array( $this, 'load_template_custom' ), 10, 1 );

			foreach ( $this->get_post_types() as $post_type ) {
				add_action( 'do_feed_sitemap-post_type-' . $post_type['name'], array( $this, 'load_template' ), 10, 1 );
			}

			foreach ( $this->get_taxonomies() as $taxonomy ) {
				add_action( 'do_feed_sitemap-taxonomy-' . $taxonomy, array( $this, 'load_template_taxonomy' ), 10, 1 );
			}
		}

		if ( isset( $sitemaps['sitemap-news'] ) ) {
			add_action( 'do_feed_sitemap-news', array( $this, 'load_template_news' ), 10, 1 );

			$this->register_gn_taxonomies();

			if ( delete_transient( 'lana_sitemap_create_genres' ) ) {
				foreach ( $this->gn_genres as $name ) {
					wp_insert_term( $name, 'gn-genre' );
				}
			}
		}
	}

	/**
	 * Lana Sitemap
	 * admin init
	 */
	public function admin_init() {
		if ( delete_transient( 'lana_sitemap_clear_settings' ) ) {
			$this->clear_settings();
		}

		if ( delete_transient( 'lana_sitemap_flush_rewrite_rules' ) ) {
			$this->flush_rules();
		}

		include_once dirname( __FILE__ ) . '/class-lana-sitemap-admin.php';
	}

	/**
	 * Lana Sitemap
	 * Flush rules
	 *
	 * @param bool|false $hard
	 */
	public function flush_rules( $hard = false ) {

		if ( $this->flushed ) {
			return;
		}

		global $wp_rewrite;

		$wp_rewrite->flush_rules( $hard );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Lana Sitemap rewrite rules flushed' );
		}

		$this->flushed = true;
	}

	/**
	 * Lana Sitemap
	 * register google news taxonomies
	 */
	public function register_gn_taxonomies() {

		$defaults = $this->defaults( 'news_tags' );
		$options  = $this->get_option( 'news_tags' );

		$post_types = ! empty( $options['post_type'] ) ? $options['post_type'] : $defaults['post_type'];

		register_taxonomy( 'gn-genre', $post_types, array(
			'hierarchical'  => true,
			'labels'        => array(
				'name'          => __( 'Google News Genres', 'lana-sitemap' ),
				'singular_name' => __( 'Google News Genre', 'lana-sitemap' ),
				'all_items'     => translate( 'All' )
			),
			'public'        => false,
			'show_ui'       => true,
			'show_tagcloud' => false,
			'query_var'     => false,
			'capabilities'  => array(
				'manage_terms' => 'nobody',
				'edit_terms'   => 'nobody',
				'delete_terms' => 'nobody',
				'assign_terms' => 'edit_posts'
			)
		) );
	}

	/**
	 * Lana Sitemap
	 * for debugging
	 */
	public function _e_usage() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) {
			echo '<!-- Queries executed ' . get_num_queries();
			if ( function_exists( 'memory_get_peak_usage' ) ) {
				echo ' | Peak memory usage ' . round( memory_get_peak_usage() / 1024 / 1024, 2 ) . 'M';
			}
			echo ' -->';
		}
	}
}
