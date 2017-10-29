<?php

class Lana_Sitemap_Admin extends Lana_Sitemap{

	/**
	 * Lana_Sitemap_Admin constructor.
	 */
	function __construct() {

		$sitemaps = parent::get_sitemaps();

		add_filter( 'plugin_action_links_' . LANA_SITEMAP_PLUGIN_BASENAME, array( $this, 'add_action_link' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'lana_sitemap_admin_scripts' ) );

		/**
		 * sitemaps
		 */
		register_setting( 'reading', 'lana_sitemap_sitemaps', array( $this, 'sanitize_sitemaps_settings' ) );
		add_settings_field( 'lana_sitemap_sitemaps', __( 'Enable XML sitemaps', 'lana-sitemap' ), array(
			$this,
			'sitemaps_settings_field'
		), 'reading' );

		/**
		 * robots rules only when permalinks are set
		 */
		$rules = get_option( 'rewrite_rules' );
		if ( get_option( 'permalink_structure' ) && isset( $rules['robots\.txt$'] ) ) {
			register_setting( 'reading', 'lana_sitemap_robots', array( $this, 'sanitize_robots_settings' ) );
			add_settings_field( 'lana_sitemap_robots', __( 'Additional robots.txt rules', 'lana-sitemap' ), array(
				$this,
				'robots_settings_field'
			), 'reading' );
		}

		/**
		 * stop here if blog is not public
		 */
		if ( ! get_option( 'blog_public' ) ) {
			return;
		}

		/**
		 * SITEMAP NEWS
		 */
		if ( isset( $sitemaps['sitemap-news'] ) ) {
			add_settings_section( 'news_sitemap_section', '<a name="xmlnf"></a>' . __( 'Google News Sitemap', 'lana-sitemap' ), array(
				$this,
				'news_sitemap_settings'
			), 'reading' );

			register_setting( 'reading', 'lana_sitemap_news_tags', array( $this, 'sanitize_news_tags_settings' ) );

			add_settings_field( 'lana_sitemap_news_name', '<label for="lana_sitemap_news_name">' . __( 'Publication name', 'lana-sitemap' ) . '</label>', array(
				$this,
				'news_name_field'
			), 'reading', 'news_sitemap_section' );
			add_settings_field( 'lana_sitemap_news_post_type', __( 'Include post types', 'lana-sitemap' ), array(
				$this,
				'news_post_type_field'
			), 'reading', 'news_sitemap_section' );
			add_settings_field( 'lana_sitemap_news_categories', translate( 'Categories' ), array(
				$this,
				'news_categories_field'
			), 'reading', 'news_sitemap_section' );
			add_settings_field( 'lana_sitemap_news_image', translate( 'Images' ), array(
				$this,
				'news_image_field'
			), 'reading', 'news_sitemap_section' );
			add_settings_field( 'lana_sitemap_news_labels', __( 'Source labels', 'lana-sitemap' ), array(
				$this,
				'news_labels_field'
			), 'reading', 'news_sitemap_section' );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box_news' ) );
		}

		/**
		 * SITEMAP
		 */
		if ( isset( $sitemaps['sitemap'] ) ) {
			add_settings_section( 'xml_sitemap_section', '<a name="xmlsf"></a>' . __( 'XML Sitemap', 'lana-sitemap' ), array(
				$this,
				'xml_sitemap_settings'
			), 'reading' );

			register_setting( 'reading', 'lana_sitemap_post_types', array(
				$this,
				'sanitize_post_types_settings'
			) );

			add_settings_field( 'lana_sitemap_post_types', __( 'Include post types', 'lana-sitemap' ), array(
				$this,
				'post_types_settings_field'
			), 'reading', 'xml_sitemap_section' );

			register_setting( 'reading', 'lana_sitemap_taxonomies', array(
				$this,
				'sanitize_taxonomies_settings'
			) );
			add_settings_field( 'lana_sitemap_taxonomies', __( 'Include taxonomies', 'lana-sitemap' ), array(
				$this,
				'taxonomies_settings_field'
			), 'reading', 'xml_sitemap_section' );

			register_setting( 'reading', 'lana_sitemap_domains', array( $this, 'sanitize_domains_settings' ) );
			add_settings_field( 'lana_sitemap_domains', __( 'Allowed domains', 'lana-sitemap' ), array(
				$this,
				'domains_settings_field'
			), 'reading', 'xml_sitemap_section' );

			register_setting( 'reading', 'lana_sitemap_urls', array( $this, 'sanitize_urls_settings' ) );
			add_settings_field( 'lana_sitemap_urls', __( 'Include custom URLs', 'lana-sitemap' ), array(
				$this,
				'urls_settings_field'
			), 'reading', 'xml_sitemap_section' );

			register_setting( 'reading', 'lana_sitemap_custom_sitemaps', array(
				$this,
				'sanitize_custom_sitemaps_settings'
			) );
			add_settings_field( 'lana_sitemap_custom_sitemaps', __( 'Include custom XML Sitemaps', 'lana-sitemap' ), array(
				$this,
				'custom_sitemaps_settings_field'
			), 'reading', 'xml_sitemap_section' );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		}

		if ( isset( $sitemaps['sitemap'] ) || isset( $sitemaps['sitemap-news'] ) ) {
			register_setting( 'writing', 'lana_sitemap_ping', array( $this, 'sanitize_ping_settings' ) );

			add_settings_field( 'lana_sitemap_ping', translate( 'Update Services' ), array(
				$this,
				'ping_settings_field'
			), 'writing' );

			add_action( 'save_post', array( $this, 'save_metadata' ) );
		}
	}

	/**
	 * Load sitemap admin scripts
	 *
	 * @param $hook
	 */
	public function lana_sitemap_admin_scripts( $hook ) {

		/** only in options page */
		if ( ! in_array( $hook, array( 'options-writing.php', 'options-reading.php' ) ) ) {
			return;
		}

		wp_enqueue_script( 'lana-sitemap-admin', LANA_SITEMAP_DIR_URL . '/assets/js/lana-sitemap-admin.js', array( 'jquery' ), LANA_SITEMAP_VERSION );
	}

	/**
	 * Sitemaps
	 * settings field
	 */
	public function sitemaps_settings_field() {

		$options = parent::get_sitemaps();
		?>

		<fieldset id="lana_sitemap_sitemaps">
			<legend class="screen-reader-text">
				<?php _e( 'XML Sitemaps', 'lana-sitemap' ); ?>
			</legend>

			<label>
				<input type="checkbox" name="lana_sitemap_sitemaps[sitemap]" id="lana_sitemap_sitemaps_index"
				       value="<?php echo htmlspecialchars( LANA_SITEMAP_NAME ); ?>" <?php checked( isset( $options['sitemap'] ) ); ?> <?php disabled( ! get_option( 'blog_public' ) ); ?> />
				<?php _e( 'XML Sitemap Index', 'lana-sitemap' ); ?>
			</label>

			<?php if ( isset( $options['sitemap'] ) ): ?>

				<span class="description">
				&nbsp;&ndash;&nbsp;
				<a href="#xmlsf" id="xmlsf_link"><?php echo translate( 'Settings' ); ?></a>
				&nbsp;&ndash;&nbsp;
				<a href="<?php echo trailingslashit( get_bloginfo( 'url' ) ) . ( ( '' == get_option( 'permalink_structure' ) ) ? '?feed=sitemap' : $options['sitemap'] ); ?>"
				   target="_blank"><?php echo translate( 'View' ); ?></a>
			</span>

			<?php endif; ?>

			<br/>

			<label>
				<input type="checkbox" name="lana_sitemap_sitemaps[sitemap-news]"
				       id="lana_sitemap_sitemaps_news"
				       value="<?php echo htmlspecialchars( LANA_SITEMAP_NEWS_NAME ); ?>" <?php checked( isset( $options['sitemap-news'] ) ); ?> <?php disabled( ! get_option( 'blog_public' ) ); ?> />
				<?php _e( 'Google News Sitemap', 'lana-sitemap' ); ?>
			</label>

			<?php if ( isset( $options['sitemap-news'] ) ): ?>

				<span class="description">
				&nbsp;&ndash;&nbsp;
				<a href="#xmlnf" id="xmlnf_link"><?php echo translate( 'Settings' ); ?></a>
				&nbsp;&ndash;&nbsp;
				<a href="<?php echo trailingslashit( get_bloginfo( 'url' ) ) . ( ( '' == get_option( 'permalink_structure' ) ) ? '?feed=sitemap-news' : $options['sitemap-news'] ); ?>"
				   target="_blank"><?php echo translate( 'View' ); ?></a>
			</span>

			<?php endif; ?>

		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * ping settings field
	 */
	public function ping_settings_field() {
		$options         = parent::get_ping();
		$defaults        = parent::defaults( 'ping' );
		$update_services = get_option( 'ping_sites' );

		$names = array(
			'google' => array(
				'name' => __( 'Google', 'lana-sitemap' ),
			),
			'bing'   => array(
				'name' => __( 'Bing & Yahoo', 'lana-sitemap' ),
			),
			'yandex' => array(
				'name' => __( 'Yandex', 'lana-sitemap' ),
			),
			'baidu'  => array(
				'name' => __( 'Baidu', 'lana-sitemap' ),
			),
			'others' => array(
				'name' => __( 'Ping-O-Matic', 'lana-sitemap' ),
			)
		);

		foreach ( $names as $key => $values ) {
			if ( array_key_exists( $key, $defaults ) && is_array( $values ) ) {
				$defaults[ $key ] += $values;
			}
		}
		?>


		<fieldset id="lana_sitemap_ping">
			<legend class="screen-reader-text">
				<?php echo translate( 'Update Services' ); ?>
			</legend>

			<?php foreach ( $defaults as $key => $values ): ?>

				<?php
				$active = ( ! empty( $options[ $key ]['active'] ) );

				if ( isset( $values['type'] ) && $values['type'] == 'RPC' ) {
					$active = ( strpos( $update_services, untrailingslashit( $values['uri'] ) ) === false );
				}
				?>

				<label>
					<input type="checkbox" name="lana_sitemap_ping[<?php echo esc_attr( $key ); ?>][active]"
					       id="lana_sitemap_ping_<?php echo esc_attr( $key ); ?>"
					       value="1" <?php checked( $active ); ?> />
					<?php echo isset( $names[ $key ] ) && ! empty( $names[ $key ]['name'] ) ? $names[ $key ]['name'] : $key; ?>
				</label>

				<input type="hidden" name="lana_sitemap_ping[<?php echo esc_attr( $key ); ?>][uri]"
				       value="<?php echo esc_attr( $values['uri'] ); ?>"/>
				<input type="hidden" name="lana_sitemap_ping[<?php echo esc_attr( $key ); ?>][type]"
				       value="<?php echo esc_attr( $values['type'] ); ?>"/>

				<?php if ( isset( $values['news'] ) ): ?>
					<input type="hidden" name="lana_sitemap_ping[<?php echo esc_attr( $key ); ?>][news]"
					       value="<?php echo esc_attr( $values['news'] ); ?>"/>
				<?php endif; ?>

				<span class="description">
					<?php if ( ! empty( $options[ $key ]['pong'] ) ): ?>

						<?php
						$timezone_format = 'Y-m-d G:i:s T';

						if ( $tzstring = get_option( 'timezone_string' ) ) {
							$timezone_format = translate_with_gettext_context( 'Y-m-d G:i:s', 'timezone date format' );
							date_default_timezone_set( $tzstring );
						}
						?>

						<?php foreach ( (array) $options[ $key ]['pong'] as $pretty => $time ): ?>

							<input type="hidden"
							       name="lana_sitemap_ping[<?php echo esc_attr( $key ); ?>][pong][<?php echo esc_attr( $pretty ); ?>]"
							       value="<?php echo esc_attr( $time ); ?>"/>

							<?php
							if ( ! empty( $time ) ) {
								echo sprintf( __( 'Successfully sent %1$s on %2$s.', 'lana-sitemap' ), $pretty, date( $timezone_format, $time ) ) . ' ';
							}
							?>

						<?php endforeach; ?>

						<?php date_default_timezone_set( 'UTC' ); ?>

					<?php endif; ?>
				</span>
				<br>

			<?php endforeach; ?>

		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * robots settings field
	 */
	public function robots_settings_field() {
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Additional robots.txt rules', 'lana-sitemap' ); ?>
			</legend>
			<label>
				<?php printf( __( 'Rules that will be appended to the %s generated by WordPress:', 'lana-sitemap' ), '<a href="' . trailingslashit( get_bloginfo( 'url' ) ) . 'robots.txt" target="_blank">robots.txt</a>' ); ?>
				<br>
				<textarea name="lana_sitemap_robots" id="lana_sitemap_robots" class="large-text" cols="50"
				          rows="6"><?php echo esc_attr( parent::get_robots() ); ?></textarea>
			</label>

			<p class="description">
				<?php _e( 'These rules will not have effect when you are using a static robots.txt file.', 'lana-sitemap' ); ?>
				<br>
				<span style="color: red" class="warning">
					<?php _e( 'Only add rules here when you know what you are doing, otherwise you might break search engine access to your site.', 'lana-sitemap' ); ?>
				</span>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * XML SITEMAP SECTION
	 */
	public function xml_sitemap_settings() {
		?>
		<p>
			<?php printf( __( 'These settings control the XML Sitemaps generated by the %s plugin.', 'lana-sitemap' ), __( 'Lana Sitemap', 'lana-sitemap' ) ); ?>
			<?php printf( __( 'For ping options, go to %s.', 'lana-sitemap' ), '<a href="options-writing.php">' . translate( 'Writing Settings' ) . '</a>' ); ?>
		</p>
		<?php
	}

	/**
	 * Lana Sitemap
	 * post types settings field
	 */
	public function post_types_settings_field() {
		$options  = parent::get_post_types();
		$defaults = parent::defaults( 'post_types' );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		/**
		 * Validate
		 * post types
		 */
		if ( ! is_array( $post_types ) || is_wp_error( $post_types ) ) {
			return;
		}
		?>

		<fieldset id="lana_sitemap_post_types">
			<legend class="screen-reader-text">
				<?php _e( 'XML Sitemaps for post types', 'lana-sitemap' ); ?>
			</legend>

			<?php foreach ( $post_types as $post_type ): ?>
				<?php
				/** skip unallowed post types */
				if ( in_array( $post_type->name, parent::disabled_post_types() ) ) {
					continue;
				}

				$post_count = wp_count_posts( $post_type->name );
				?>

				<input type="hidden"
				       name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][name]"
				       value="<?php echo esc_attr( $post_type->name ); ?>"/>
				<label>
					<input type="checkbox"
					       name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][active]"
					       id="lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>"
					       value="1" <?php checked( ! empty( $options[ $post_type->name ]["active"] ) ); ?> />
					<?php echo $post_type->label; ?>
				</label>
				(<?php echo $post_count->publish; ?>)


				<?php if ( ! empty( $options[ $post_type->name ]['active'] ) ): ?>

					&nbsp;&ndash;&nbsp;
					<span class="description">
						<a id="lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>_link"
						   href="#lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>_settings"
						   class="lana_sitemap_post_types_link"
						   data-post-type="<?php echo esc_attr( $post_type->name ); ?>"><?php echo translate( 'Settings' ); ?></a>
					</span>
					<br/>

					<ul class="lana_sitemap_post_types_settings" style="margin-left:18px"
					    id="lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>_settings"
					    data-post-type="<?php echo esc_attr( $post_type->name ); ?>">

						<?php if ( isset( $defaults[ $post_type->name ]['archive'] ) ): ?>

							<?php
							/**
							 * Archive
							 * variable
							 */
							$archives = array(
								'yearly'  => __( 'Year', 'lana-sitemap' ),
								'monthly' => __( 'Month', 'lana-sitemap' )
							);
							$archive  = ! empty( $options[ $post_type->name ]['archive'] ) ? $options[ $post_type->name ]['archive'] : $defaults[ $post_type->name ]['archive'];
							?>
							<li>
								<label>
									<?php _e( 'Split by', 'lana-sitemap' ); ?>

									<select
										name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][archive]"
										id="lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>_archive">
										<option value="">
											<?php echo translate( 'None' ); ?>
										</option>

										<?php foreach ( $archives as $value => $translation ): ?>
											<option
												value="<?php echo esc_attr( $value ); ?>" <?php selected( $archive == $value ); ?>>
												<?php echo $translation; ?>
											</option>
										<?php endforeach; ?>

									</select>
								</label>
								<br/>
								<span class="description">
									<?php _e( 'Split by year if you experience errors or slow sitemaps. In very rare cases, split by month is needed.', 'lana-sitemap' ); ?>
								</span>
							</li>

						<?php endif; ?>

						<?php
						/**
						 * Priority
						 * variable
						 */
						$priority = ! empty( $options[ $post_type->name ]['priority'] ) ? $options[ $post_type->name ]['priority'] : $defaults[ $post_type->name ]['priority'];

						/**
						 * Image
						 * variable
						 */
						$image = isset( $options[ $post_type->name ]['tags']['image'] ) ? $options[ $post_type->name ]['tags']['image'] : $defaults[ $post_type->name ]['tags']['image'];
						?>
						<li>
							<label>
								<?php _e( 'Priority', 'lana-sitemap' ); ?>
								<input type="number" step="0.1" min="0.1" max="0.9"
								       name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][priority]"
								       id="lana_sitemap_post_types_<?php echo esc_attr( $post_type->name ); ?>_priority"
								       value="<?php echo esc_attr( $priority ); ?>" class="small-text">
							</label>
							<br/>
							<span class="description">
								<?php _e( 'Priority can be overridden on individual posts.', 'lana-sitemap' ); ?> *
							</span>
						</li>

						<li>
							<label>
								<input type="checkbox"
								       name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][dynamic_priority]"
								       value="1" <?php checked( ! empty( $options[ $post_type->name ]['dynamic_priority'] ) ); ?> />
								<?php _e( 'Automatic Priority calculation.', 'lana-sitemap' ); ?>
							</label>
							<br/>
							<span class="description">
								<?php _e( 'Adjusts the Priority based on factors like age, comments, sticky post or blog page. Individual posts with fixed Priority will always keep that value.', 'lana-sitemap' ); ?>
							</span>
						</li>

						<li>
							<label>
								<input type="checkbox"
								       name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][update_lastmod_on_comments]"
								       value="1" <?php checked( ! empty( $options[ $post_type->name ]["update_lastmod_on_comments"] ) ); ?> />
								<?php _e( 'Update Lastmod and Changefreq on comments.', 'lana-sitemap' ); ?>
							</label>
							<br/>
							<span class="description">
								<?php _e( 'Set this if discussion on your site warrants reindexation upon each new comment.', 'lana-sitemap' ); ?>
							</span>
						</li>

						<li>
							<label>
								<?php _e( 'Add image tags for', 'lana-sitemap' ); ?>
								<select
									name="lana_sitemap_post_types[<?php echo esc_attr( $post_type->name ); ?>][tags][image]">
									<option value="">
										<?php echo translate( 'None' ); ?>
									</option>
									<option value="featured" <?php selected( $image == 'featured' ); ?>>
										<?php echo translate( 'Featured Image' ); ?>
									</option>
									<option value="attached" <?php selected( $image == 'attached' ); ?>>
										<?php _e( 'Attached images', 'lana-sitemap' ); ?>
									</option>
								</select>
							</label>
						</li>

					</ul>
				<?php endif; ?>

				<?php if ( empty( $options[ $post_type->name ]['active'] ) ): ?>
					<br/>
				<?php endif; ?>

			<?php endforeach; ?>

			<p class="description">
				* <?php _e( 'Priority settings do not affect ranking in search results in any way. They are only meant to suggest search engines which URLs to index first. Once a URL has been indexed, its Priority becomes meaningless until its Lastmod is updated.', 'lana-sitemap' ); ?>
				<a href="#lana_sitemap_post_types_note_1_more" id="lana_sitemap_post_types_note_1_link">
					<?php echo translate( '[more]' ); ?>
				</a>
				<span id="lana_sitemap_post_types_note_1_more">
					<?php _e( 'Maximum Priority (1.0) is reserved for the front page, individual posts and, when allowed, posts with high comment count.', 'lana-sitemap' ); ?>
					<?php _e( 'Priority values are taken as relative values. Setting all to the same (high) value is pointless.', 'lana-sitemap' ); ?>
				</span>
			</p>

		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * taxonomies settings field
	 */
	public function taxonomies_settings_field() {

		$options = parent::get_taxonomies();
		$active  = parent::get_option( 'post_types' );
		$output  = '';

		/**
		 * Get taxonomies
		 * and generate checkbox
		 */
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) :

			/**
			 * skip unallowed post types
			 */
			if ( in_array( $taxonomy->name, parent::disabled_taxonomies() ) ) {
				continue;
			}

			/**
			 * skip if none of the associated post types are active
			 * with continue 2 (parent foreach)
			 */
			foreach ( $taxonomy->object_type as $post_type ) {
				if ( empty( $active[ $post_type ]['active'] ) || $active[ $post_type ]['active'] != '1' ) {
					continue 2;
				}
			}

			ob_start();
			?>
			<label>
				<input type="checkbox" name="lana_sitemap_taxonomies[<?php echo esc_attr( $taxonomy->name ); ?>]"
				       id="lana_sitemap_taxonomies_<?php echo esc_attr( $taxonomy->name ); ?>"
				       value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $options ) ); ?> />
				<?php echo $taxonomy->label; ?>
			</label>
			(<?php echo wp_count_terms( $taxonomy->name ); ?>)
			<br>
			<?php
			$output .= ob_get_clean();

		endforeach;

		/**
		 * Without taxonomies
		 * error message
		 */
		if ( empty( $output ) ):
			?>

			<p style="color: red" class="warning">
				<?php _e( 'No taxonomies available for the currently included post types.', 'lana-sitemap' ); ?>
			</p
			<?php
			return;
		endif;
		?>
		<fieldset id="lana_sitemap_taxonomies">
			<legend class="screen-reader-text">
				<?php _e( 'XML Sitemaps for taxonomies', 'lana-sitemap' ); ?>
			</legend>

			<?php echo $output; ?>

			<p class="description">
				<?php _e( 'It is generally not recommended to include taxonomy pages, unless their content brings added value.', 'lana-sitemap' ); ?>
				<a href="#lana_sitemap_taxonomies_note_1_more" id="lana_sitemap_taxonomies_note_1_link">
					<?php echo translate( '[more]' ); ?>
				</a>
			<span id="lana_sitemap_taxonomies_note_1_more">
				<?php _e( 'For example, when you use category descriptions with information that is not present elsewhere on your site or if taxonomy pages list posts with an excerpt that is different from, but complementary to the post content. In these cases you might consider including certain taxonomies. Otherwise, if you fear <a href="http://moz.com/learn/seo/duplicate-content">negative affects of duplicate content</a> or PageRank spread, you might even consider disallowing indexation of taxonomies.', 'lana-sitemap' ); ?>
				<?php printf( __( 'You can do this by adding specific robots.txt rules in the %s field above.', 'lana-sitemap' ), '<strong>' . __( 'Additional robots.txt rules', 'lana-sitemap' ) . '</strong>' ); ?>
			</span>
			</p>
		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * custom sitemaps settings field
	 */
	public function custom_sitemaps_settings_field() {
		$lines = parent::get_custom_sitemaps();
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Include custom XML Sitemaps', 'lana-sitemap' ); ?>
			</legend>
			<label>
				<?php _e( 'Additional XML Sitemaps to append to the main XML Sitemap Index:', 'lana-sitemap' ); ?>
				<br>
				<textarea name="lana_sitemap_custom_sitemaps" id="lana_sitemap_custom_sitemaps"
				          class="large-text" cols="50" rows="4"><?php echo implode( "\n", $lines ); ?></textarea>
			</label>

			<p class="description">
				<?php _e( 'Add the full URL, including protocol (http/https) and domain, of any XML Sitemap that you want to append to the Sitemap Index. Start each URL on a new line.', 'lana-sitemap' ); ?>
				<br>
				<span style="color: red" class="warning">
					<?php _e( 'Only valid sitemaps are allowed in the Sitemap Index. Use your Google/Bing Webmaster Tools to verify!', 'lana-sitemap' ); ?>
				</span>
			</p>
		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * urls settings field
	 */
	public function urls_settings_field() {
		$urls  = parent::get_urls();
		$lines = array();

		if ( ! empty( $urls ) ) {
			foreach ( $urls as $arr ) {
				if ( is_array( $arr ) ) {
					$lines[] = implode( " ", $arr );
				}
			}
		}
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Include custom URLs', 'lana-sitemap' ); ?>
			</legend>
			<label>
				<?php _e( 'Additional URLs to append in an extra XML Sitemap:', 'lana-sitemap' ); ?>
				<br>
				<textarea name="lana_sitemap_urls" id="lana_sitemap_urls" class="large-text" cols="50"
				          rows="4"><?php echo implode( "\n", $lines ); ?></textarea>
			</label>

			<p class="description">
				<?php _e( 'Add the full URL, including protocol (http/https) and domain, of any (static) page that you want to append to the ones already included by WordPress. Optionally add a priority value between 0 and 1, separated with a space after the URL. Start each URL on a new line.', 'lana-sitemap' ); ?>
			</p>
		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * Domains settings field
	 */
	public function domains_settings_field() {

		$default = parent::domain();
		$domains = (array) parent::get_option( 'domains' );
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Allowed domains', 'lana-sitemap' ); ?>
			</legend>
			<label>
				<?php _e( 'Additional domains to allow in the XML Sitemaps:', 'lana-sitemap' ); ?>
				<br>
				<textarea name="lana_sitemap_domains" id="lana_sitemap_domains" class="large-text" cols="50"
				          rows="4"><?php echo implode( "\n", $domains ); ?></textarea>
			</label>

			<p class="description">
				<?php printf( __( 'By default, only the domain %s as used in your WordPress site address is allowed. This means that all URLs that use another domain (custom URLs or using a plugin like Page Links To) are filtered from the XML Sitemap. However, if you are the verified owner of other domains in your Google/Bing Webmaster Tools account, you can include these in the same sitemap. Add these domains, without protocol (http/https) each on a new line. Note that if you enter a domain with www, all URLs without it or with other subdomains will be filtered.', 'lana-sitemap' ), '<strong>' . $default . '</strong>' ); ?>
			</p>
		</fieldset>

		<?php
	}

	/**
	 * GOOGLE NEWS SITEMAP SECTION
	 */
	public function news_sitemap_settings() {
		?>
		<p>
			<?php printf( __( 'These settings control the Google News Sitemap generated by the %s plugin.', 'lana-sitemap' ), __( 'Lana Sitemap', 'lana-sitemap' ) ); ?>
			<?php _e( 'When you are done configuring and preparing your news content and you are convinced your site adheres to the <a href="https://support.google.com/news/publisher/answer/40787" target="_blank">Google News guidelines</a>, go ahead and <a href="https://partnerdash.google.com/partnerdash/d/news" target="_blank">submit your site for inclusion</a>!', 'lana-sitemap' ); ?>
			<?php _e( 'It is strongly recommended to submit your news sitemap to your Google Webmasters Tools account to monitor for warnings or errors. Read more on how to <a href="https://support.google.com/webmasters/answer/183669" target="_blank">Manage sitemaps with the Sitemaps page</a>.', 'lana-sitemap' ); ?>
			<?php printf( __( 'For ping options, go to %s.', 'lana-sitemap' ), '<a href="options-writing.php">' . translate( 'Writing Settings' ) . '</a>' ); ?>
		</p>
		<?php
	}

	/**
	 * Lana Sitemap
	 * News name fields
	 */
	public function news_name_field() {
		$options = parent::get_option( 'news_tags' );

		$name = ! empty( $options['name'] ) ? $options['name'] : '';
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Publication name', 'lana-sitemap' ); ?>
			</legend>
			<label for="lana_sitemap_news_name"></label>
			<input type="text" name="lana_sitemap_news_tags[name]" id="lana_sitemap_news_name"
			       value="<?php echo esc_attr( $name ); ?>" class="regular-text">
			<span class="description">
				<?php printf( __( 'By default, the general %s setting will be used.', 'lana-sitemap' ), '<a href="options-general.php">' . translate( 'Site Title' ) . '</a>' ); ?>
			</span>

			<p class="description">
				<?php _e( 'The publication name should match the name submitted on the Google News Publisher Center. If you wish to change it, please read <a href="https://support.google.com/news/publisher/answer/40402" target="_blank">Updated publication name</a>.', 'lana-sitemap' ); ?>
			</p>
		</fieldset>

		<?php
	}

	public function news_post_type_field() {

		$defaults = parent::defaults( 'news_tags' );
		$options  = parent::get_option( 'news_tags' );

		$news_post_type = ! empty( $options['post_type'] ) ? $options['post_type'] : $defaults['post_type'];
		$post_types     = get_post_types( array( 'publicly_queryable' => true ), 'objects' );

		/**
		 * check for valid post types
		 */
		if ( ! is_array( $post_types ) || empty( $post_types ) || is_wp_error( $post_types ) ) :
			?>

			<p style="color: red" class="error">
				<?php _e( 'Error: There where no valid post types found. Without at least one public post type, a Google News Sitemap cannot be created by this plugin. Please deselect the option Google News Sitemap at <a href="#lana_sitemap_sitemaps">Enable XML sitemaps</a> and choose another method.', 'lana-sitemap' ); ?>
			</p>
			<?php
		endif;
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<?php _e( 'Include post types', 'lana-sitemap' ); ?>
			</legend>

			<?php foreach ( $post_types as $post_type ): ?>

				<?php
				if ( ! is_object( $post_type ) || in_array( $post_type->name, parent::disabled_post_types() ) ) {
					continue;
				}

				$checked  = in_array( $post_type->name, $news_post_type ) ? true : false;
				$disabled = false;

				if ( isset( $options['categories'] ) && is_array( $options['categories'] ) ) {
					$taxonomies = get_object_taxonomies( $post_type->name, 'names' );

					if ( ! in_array( 'category', (array) $taxonomies ) ) {
						$disabled = true;
						$checked  = false;
					}
				}
				?>

				<label>
					<input type="checkbox" name="lana_sitemap_news_tags[post_type][]"
					       id="lana_sitemap_post_type_<?php echo esc_attr( $post_type->name ); ?>"
					       value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( $checked ); ?> <?php disabled( $disabled ); ?> />
					<?php echo $post_type->label; ?>
				</label>
				<br>
			<?php endforeach; ?>

			<p class="description">
				<?php printf( __( 'At least one post type must be selected. By default, the post type %s will be used.', 'lana-sitemap' ), translate( 'Posts' ) ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Lana Sitemap
	 * news categories field
	 */
	public function news_categories_field() {

		$options = parent::get_option( 'news_tags' );

		/**
		 * Validate
		 * post type
		 */
		if ( ! empty( $options['post_type'] ) && array( 'post' ) !== (array) $options['post_type'] ):
			?>

			<p class="description">
				<?php printf( __( 'Selection based on categories will be available when <strong>only</strong> the post type %s is included above.', 'lana-sitemap' ), translate( 'Posts' ) ); ?>
			</p>
			<?php
			return;
		endif;

		$all_categories      = get_terms( 'category', array( 'hide_empty' => 0, 'hierachical' => true ) );
		$selected_categories = isset( $options['categories'] ) && is_array( $options['categories'] ) ? $options['categories'] : array();

		$count = count( $all_categories );
		$size  = $count < 15 ? $count : 15;

		/**
		 * Validate
		 * categories count
		 */
		if ( $count == 0 ):
			?>
			<p class="description">
				<?php echo translate( 'No categories' ); ?>
			</p>
			<?php
			return;
		endif;
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php echo translate( 'Categories' ); ?>
			</legend>
			<label>
				<?php _e( 'Limit to posts in these post categories:', 'lana-sitemap' ); ?>
				<br>
				<select name="lana_sitemap_news_tags[categories][]" size="<?php echo esc_attr( $size ); ?>"
				        multiple>
					<?php foreach ( $all_categories as $category ): ?>
						<?php
						$depth         = count( explode( '%#%', get_category_parents( $category, false, '%#%' ) ) ) - 2;
						$depth_padding = str_repeat( '&nbsp;', $depth * 3 );

						$category_name = apply_filters( 'list_cats', $category->name, $category );
						?>

						<option class="depth-<?php echo esc_attr( $depth ); ?>"
						        value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( in_array( $category->term_id, $selected_categories ) ); ?>>
							<?php echo $depth_padding . $category_name; ?>
						</option>

					<?php endforeach; ?>
				</select>
			</label>

			<p class="description">
				<?php _e( 'If you wish to limit posts that will feature in your News Sitemap to certain categories, select them here. If no categories are selected, posts of all categories will be included in your News Sitemap.', 'lana-sitemap' ); ?>
				<?php _e( 'Use the Ctrl/Cmd key plus click to select more than one or to deselect.', 'lana-sitemap' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Lana Sitemap
	 * news image fields
	 */
	public function news_image_field() {

		$options = parent::get_option( 'news_tags' );

		$image = ! empty( $options['image'] ) ? $options['image'] : '';
		?>

		<fieldset>
			<legend class="screen-reader-text">
				<?php echo translate( 'Images' ); ?>
			</legend>
			<label>
				<?php _e( 'Add image tags for', 'lana-sitemap' ); ?>
				<select name="lana_sitemap_news_tags[image]">
					<option value="">
						<?php echo translate( 'None' ); ?>
					</option>
					<option value="featured" <?php selected( $image == "featured" ); ?>>
						<?php echo translate( 'Featured Image' ); ?>
					</option>
					<option value="attached" <?php selected( $image == "attached" ); ?>>
						<?php _e( 'Attached images', 'lana-sitemap' ); ?>
					</option>
				</select>
			</label>

			<p class="description">
				<?php _e( 'Note: Google News prefers at most one image per article in the News Sitemap. If multiple valid images are specified, the crawler will have to pick one arbitrarily. Images in News Sitemaps should be in jpeg or png format.', 'lana-sitemap' ); ?>
				<a href="https://support.google.com/news/publisher/answer/13369" target="_blank">
					<?php _e( 'More information&hellip;', 'lana-sitemap' ); ?>
				</a>
			</p>
		</fieldset>

		<?php
	}

	/**
	 * Lana Sitemap
	 * news labels field
	 */
	public function news_labels_field() {

		$options = parent::get_option( 'news_tags' );

		/**
		 * access tag
		 */
		$access          = ! empty( $options['access'] ) ? $options['access'] : '';
		$access_default  = ! empty( $access['default'] ) ? $access['default'] : '';
		$access_password = ! empty( $access['password'] ) ? $access['password'] : '';

		/**
		 * genres tag
		 */
		$gn_genres      = parent::gn_genres();
		$genres         = ! empty( $options['genres'] ) ? $options['genres'] : array();
		$genres_default = ! empty( $genres['default'] ) ? (array) $genres['default'] : array();


		/**
		 * keywords
		 */
		$keywords      = ! empty( $options['keywords'] ) ? $options['keywords'] : array();
		$keywords_from = ! empty( $keywords['from'] ) ? $keywords['from'] : '';
		?>

		<fieldset id="lana_sitemap_news_labels">
			<legend class="screen-reader-text">
				<?php _e( 'Source labels', 'lana-sitemap' ); ?>
			</legend>

			<?php printf( __( 'You can use the %1$s and %2$s tags to provide Google more information about the content of your articles.', 'lana-sitemap' ), '&lt;access&gt;', '&lt;genres&gt;' ); ?>

			<a href="https://support.google.com/news/publisher/answer/93992" target="_blank">
				<?php _e( 'More information&hellip;', 'lana-sitemap' ); ?>
			</a>
			<br/>
			<br/>

			<fieldset id="lana_sitemap_news_labels_access">
				<legend class="screen-reader-text">&lt;access&gt;</legend>
				<?php printf( __( 'The %4$s tag specifies whether an article is available to all readers (%1$s), or only to those with a free (%2$s) or paid membership (%3$s) to your site.', 'lana-sitemap' ), translate( 'Public' ), __( 'Registration', 'lana-sitemap' ), __( 'Subscription', 'lana-sitemap' ), '<strong>&lt;access&gt;</strong>' ); ?>
				<?php _e( 'You can assign a different access level when writing a post.', 'lana-sitemap' ); ?>
				<ul>
					<li>
						<label>
							<?php _e( 'Tag normal posts as', 'lana-sitemap' ); ?>

							<select name="lana_sitemap_news_tags[access][default]"
							        id="lana_sitemap_news_tags_access_default">
								<option value="">
									<?php echo translate( 'Public' ); ?>
								</option>
								<option value="Registration" <?php selected( 'Registration' == $access_default ); ?>>
									<?php _e( 'Free registration', 'lana-sitemap' ); ?>
								</option>
								<option value="Subscription" <?php selected( 'Subscription' == $access_default ); ?>>
									<?php _e( 'Paid subscription', 'lana-sitemap' ); ?>
								</option>
							</select>
						</label>
					</li>
					<li>
						<label>
							<?php _e( 'Tag Password Protected posts as', 'lana-sitemap' ); ?>

							<select name="lana_sitemap_news_tags[access][password]"
							        id="lana_sitemap_news_tags_access_password">
								<option value="Registration" <?php selected( "Registration" == $access_password ); ?>>
									<?php _e( 'Free registration', 'lana-sitemap' ); ?>
								</option>
								<option value="Subscription" <?php selected( "Subscription" == $access_password ); ?>>
									<?php _e( 'Paid subscription', 'lana-sitemap' ); ?>
								</option>
							</select>
						</label>
					</li>
				</ul>
			</fieldset>

			<fieldset id="lana_sitemap_news_labels_genres">
				<legend class="screen-reader-text">
					&lt;genres&gt;
				</legend>
				<?php printf( __( 'The %s tag specifies one or more properties for an article, namely, whether it is a press release, a blog post, an opinion, an op-ed piece, user-generated content, or satire.', 'lana-sitemap' ), '<strong>&lt;genres&gt;</strong>' ); ?>
				<?php _e( 'You can assign different genres when writing a post.', 'lana-sitemap' ); ?>
				<ul>
					<li>
						<label>
							<?php _e( 'Default genre:', 'lana-sitemap' ); ?>
							<br>
							<select name="lana_sitemap_news_tags[genres][default][]"
							        id="lana_sitemap_news_tags_genres_default"
							        size="<?php echo esc_attr( count( $gn_genres ) ); ?>" multiple>
								<?php foreach ( $gn_genres as $name ): ?>
									<option
										value="<?php echo esc_attr( $name ); ?>" <?php selected( in_array( $name, $genres_default ) ); ?>>
										<?php echo $name; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
					</li>
				</ul>

				<p class="description">
					<?php _e( 'Use the Ctrl/Cmd key plus click to select more than one or to deselect.', 'lana-sitemap' ); ?>
					<?php printf( __( 'Read more about source labels on %s', 'lana-sitemap' ), '<a href="https://support.google.com/news/publisher/answer/4582731" target="_blank">' . __( 'What does each source label mean?', 'lana-sitemap' ) . '</a>' ); ?>
				</p>
			</fieldset>

			<fieldset id="lana_sitemap_news_keywords">
				<legend class="screen-reader-text">
					&lt;keywords&gt;
				</legend>
				<?php printf( __( 'The %s tag is used to help classify the articles you submit to Google News by <strong>topic</strong>.', 'lana-sitemap' ), '<strong>&lt;keywords&gt;</strong>' ); ?>

				<ul>
					<li>
						<label>
							<select name="lana_sitemap_news_tags[keywords][from]"
							        id="lana_sitemap_news_tags_keywords_from">
								<option value="">
									<?php echo translate( 'None' ); ?>
								</option>
								<option value="category" <?php selected( 'category' == $keywords_from ); ?>>
									<?php echo translate( 'Categories' ); ?>
								</option>
								<option value="post_tag" <?php selected( 'post_tag' == $keywords_from ); ?>>
									<?php echo translate( 'Tags' ); ?>
								</option>
							</select>
							<?php _e( ' - use for topics.', 'lana-sitemap' ); ?>
						</label>
					</li>

					<?php if ( 'category' != $keywords_from ): ?>

						<?php
						$keywords_default_value = '';

						if ( ! empty( $keywords['default'] ) ) {
							$keywords_default_value = $keywords['default'];
						}
						?>

						<li>
							<label>
								<?php _e( 'Default topic(s):', 'lana-sitemap' ); ?>
								<input type="text" name="lana_sitemap_news_tags[keywords][default]"
								       id="lana_sitemap_news_tags_keywords_default" class="regular-text"
								       value="<?php echo esc_attr( $keywords_default_value ); ?>">
							</label>
							<span class="description">
								<?php _e( 'Separate with a comma.', 'lana-sitemap' ); ?>
							</span>
						</li>
					<?php endif; ?>
				</ul>

				<p class="description">
					<?php _e( 'Keywords may be drawn from, but are not limited to, the list of <a href="https://support.google.com/news/publisher/answer/116037" target="_blank">existing Google News keywords</a>.', 'lana-sitemap' ); ?>
				</p>
			</fieldset>

		</fieldset>

		<?php
	}

	/**
	 * Sanitize
	 * ping settings
	 *
	 * @param $new
	 *
	 * @return array
	 */
	public function sanitize_ping_settings( $new ) {

		$defaults  = parent::defaults( 'ping' );
		$old       = parent::get_option( 'ping' );
		$sanitized = array();

		$update_services     = get_option( 'ping_sites' );
		$update_services_new = $update_services;

		foreach ( $defaults as $key => $values ) {
			if ( ! isset( $new[ $key ] ) ) {
				continue;
			}

			if ( isset( $values['type'] ) && $values['type'] == 'RPC' && isset( $values['uri'] ) ) {
				/** did we toggle the option? */
				$changed = true;

				if ( isset( $old[ $key ] ) ) {
					$old_active = isset( $old[ $key ]['active'] ) ? $old[ $key ]['active'] : '';
					$new_active = isset( $new[ $key ]['active'] ) ? $new[ $key ]['active'] : '';
					if ( $old_active == $new_active ) {
						$changed = false;
					}
				}

				if ( $changed ) {
					/** then change the ping_sites list according to option */
					if ( ! empty( $new[ $key ]['active'] ) && strpos( $update_services, untrailingslashit( $values['uri'] ) ) === false ) {
						$update_services_new .= "\n" . $values['uri'];
					} elseif ( empty( $new[ $key ]['active'] ) && strpos( $update_services, untrailingslashit( $values['uri'] ) ) !== false ) {
						$update_services_new = str_replace( array(
							trailingslashit( $values['uri'] ),
							untrailingslashit( $values['uri'] )
						), '', $update_services_new );
					}
				} else {
					/** or change the option according to ping_sites */
					if ( strpos( $update_services, untrailingslashit( $values['uri'] ) ) !== false ) {
						$new[ $key ]['active'] = '1';
					} else {
						unset( $new[ $key ]['active'] );
					}
				}
			}
			if ( is_array( $new[ $key ] ) ) {
				$sanitized += array( $key => $new[ $key ] );
			}
		}

		if ( $update_services_new != $update_services ) {
			update_option( 'ping_sites', $update_services_new );
		}

		return $sanitized;
	}

	/**
	 * Sanitize
	 * robots settings
	 *
	 * @param $new
	 *
	 * @return string
	 */
	public function sanitize_robots_settings( $new ) {

		if ( is_array( $new ) ) {
			$new = array_filter( $new );
			$new = reset( $new );
		}

		return trim( strip_tags( $new ) );
	}

	/**
	 * Sanitize
	 * sitemaps settings
	 *
	 * @param $new
	 *
	 * @return array
	 */
	public function sanitize_sitemaps_settings( $new ) {
		$old = parent::get_sitemaps();

		if ( '1' == get_option( 'blog_public' ) ) {

			/** when sitemaps are added or removed, set transient to flush rewrite rules */
			if ( $old != $new ) {
				set_transient( 'lana_sitemap_flush_rewrite_rules', '' );
			}

			if ( empty( $old['sitemap-news'] ) && ! empty( $new['sitemap-news'] ) ) {
				set_transient( 'lana_sitemap_create_genres', '' );
			}

			$sanitized = $new;
		} else {
			$sanitized = $old;
		}

		return $sanitized;
	}

	/**
	 * Sanitize
	 * post types settings
	 *
	 * @param array $new
	 *
	 * @return array
	 */
	public function sanitize_post_types_settings( $new = array() ) {
		$old       = parent::get_post_types();
		$defaults  = parent::defaults( 'post_types' );
		$sanitized = $new;

		foreach ( $new as $post_type => $settings ) {
			if ( ( ! empty( $old[ $post_type ]['active'] ) && empty( $settings['active'] ) ) || ( empty( $old[ $post_type ]['active'] ) && ! empty( $settings['active'] ) ) ) {
				set_transient( 'lana_sitemap_flush_rewrite_rules', '' );
			}

			if ( isset( $settings['priority'] ) && is_numeric( $settings['priority'] ) ) {
				$sanitized[ $post_type ]['priority'] = $this->sanitize_priority( $settings['priority'], 0.1, 0.9 );
			} else {
				$sanitized[ $post_type ]['priority'] = $defaults[ $post_type ]['priority'];
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize
	 * priority
	 *
	 * @param $priority
	 * @param float $min
	 * @param float $max
	 *
	 * @return string
	 */
	private function sanitize_priority( $priority, $min = 0.0, $max = 1.0 ) {
		$priority = floatval( str_replace( ",", ".", $priority ) );

		if ( $priority <= (float) $min ) {
			return number_format( $min, 1 );
		} elseif ( $priority >= (float) $max ) {
			return number_format( $max, 1 );
		} else {
			return number_format( $priority, 1 );
		}
	}

	/**
	 * Sanitize
	 * taxonomies settings
	 *
	 * @param $new
	 *
	 * @return mixed
	 */
	public function sanitize_taxonomies_settings( $new ) {

		$old = parent::get_taxonomies();

		if ( $old != $new ) {
			set_transient( 'lana_sitemap_flush_rewrite_rules', '' );
		}

		return $new;
	}

	/**
	 * Sanitize
	 * custom sitemaps settings
	 *
	 * @param $new
	 *
	 * @return array
	 */
	public function sanitize_custom_sitemaps_settings( $new ) {

		if ( is_array( $new ) ) {
			$new = array_filter( $new );
			$new = reset( $new );
		}
		$input = $new ? explode( "\n", trim( strip_tags( $new ) ) ) : array();

		$sanitized = array();
		foreach ( $input as $line ) {
			$line = filter_var( esc_url( trim( $line ) ), FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED );
			if ( ! empty( $line ) ) {
				$sanitized[] = $line;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize
	 * urls settings
	 *
	 * @param $new
	 *
	 * @return array|string
	 */
	public function sanitize_urls_settings( $new ) {

		$old = parent::get_urls();

		if ( is_array( $new ) ) {
			$new = array_filter( $new );
			$new = reset( $new );
		}
		$input = $new ? explode( "\n", trim( strip_tags( $new ) ) ) : array();

		$sanitized = array();
		$callback  = create_function( '$a', 'return filter_var($a,FILTER_VALIDATE_URL) || is_numeric($a);' );

		foreach ( $input as $line ) {
			if ( empty( $line ) ) {
				continue;
			}

			$arr = array_values( array_filter( explode( " ", trim( $line ) ), $callback ) );

			if ( isset( $arr[0] ) ) {
				if ( isset( $arr[1] ) ) {
					$arr[1] = $this->sanitize_priority( $arr[1] );
				} else {
					$arr[1] = '0.5';
				}

				$sanitized[] = array( esc_url( $arr[0] ), $arr[1] );
			}
		}

		if ( empty( $old ) ) {
			if ( ! empty( $sanitized ) ) {
				set_transient( 'lana_sitemap_flush_rewrite_rules', '' );
			}
		} else if ( empty( $sanitized ) ) {
			set_transient( 'lana_sitemap_flush_rewrite_rules', '' );
		}

		return ( ! empty( $sanitized ) ) ? $sanitized : '';
	}

	/**
	 * Sanitize
	 * domain settings
	 *
	 * @param $new
	 *
	 * @return array|string
	 */
	public function sanitize_domains_settings( $new ) {

		$default = parent::domain();

		if ( is_array( $new ) ) {
			$new = array_filter( $new );
			$new = reset( $new );
		}
		$input = $new ? explode( "\n", trim( strip_tags( $new ) ) ) : array();

		$sanitized = array();

		foreach ( $input as $line ) {
			$line       = trim( $line );
			$parsed_url = parse_url( trim( filter_var( $line, FILTER_SANITIZE_URL ) ) );

			if ( ! empty( $parsed_url['host'] ) ) {
				$domain = trim( $parsed_url['host'] );
			} else {
				$domain_arr = explode( '/', $parsed_url['path'] );
				$domain_arr = array_filter( $domain_arr );
				$domain     = array_shift( $domain_arr );
				$domain     = trim( $domain );
			}

			if ( ! empty( $domain ) && $domain !== $default && strpos( $domain, "." . $default ) === false ) {
				$sanitized[] = $domain;
			}
		}

		return ( ! empty( $sanitized ) ) ? $sanitized : '';
	}

	/**
	 * Sanitize
	 * news tags settings
	 *
	 * @param $new
	 *
	 * @return mixed
	 */
	public function sanitize_news_tags_settings( $new ) {
		return $new;
	}


	/**
	 * Lana Sitemap
	 * action links to plugin
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function add_action_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-reading.php' ) . '#blog_public">' . translate( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Adds a XML Sitemap box to the side column
	 * only include metaboxes on post types that are included
	 */
	public function add_meta_box() {

		foreach ( parent::get_post_types() as $post_type ) {
			if ( isset( $post_type["active"] ) ) {
				add_meta_box( 'lana_sitemap_section', __( 'XML Sitemap', 'lana-sitemap' ), array(
					$this,
					'meta_box'
				), $post_type['name'], 'side', 'low' );
			}
		}
	}

	/**
	 * Meta box
	 *
	 * @param $post
	 */
	public function meta_box( $post ) {

		wp_nonce_field( plugin_basename( __FILE__ ), 'lana_sitemap_sitemap_nonce' );

		/**
		 * The actual fields for data entry
		 * Use get_post_meta to retrieve an existing value from the database and use the value for the form
		 */
		$exclude  = get_post_meta( $post->ID, '_lana_sitemap_exclude', true );
		$priority = get_post_meta( $post->ID, '_lana_sitemap_priority', true );
		$disabled = false;

		/** disable options and (visibly) set excluded to true for private posts */
		if ( 'private' == $post->post_status ) {
			$disabled = true;
			$exclude  = true;
		}

		/** disable options and (visibly) set priority to 1 for front page */
		if ( $post->ID == get_option( 'page_on_front' ) ) {
			$disabled = true;
			$priority = '1';
		}

		?>

		<div>
			<label>
				<?php _e( 'Priority', 'lana-sitemap' ); ?>
				<input type="number" step="0.1" min="0" max="1" name="lana_sitemap_priority" class="small-text"
				       id="lana_sitemap_priority"
				       value="<?php echo esc_attr( $priority ); ?>" <?php disabled( $disabled ); ?>>
			</label>

			<p class="description">
				<?php printf( __( 'Leave empty for automatic Priority as configured on %1$s > %2$s.', 'lana-sitemap' ), translate( 'Settings' ), '<a href="' . admin_url( 'options-reading.php' ) . '#xmlsf">' . translate( 'Reading' ) . '</a>' ); ?>
			</p>
		</div>

		<p>
			<label>
				<input type="checkbox" name="lana_sitemap_exclude" id="lana_sitemap_exclude"
				       value="1" <?php checked( ! empty( $exclude ) ); ?> <?php disabled( $disabled ); ?>>
				<?php _e( 'Exclude from XML Sitemap', 'lana-sitemap' ); ?>
			</label>
		</p>

		<?php
	}

	/**
	 * Adds a News Sitemap box to the side column
	 * only include metabox on post types that are included
	 */
	public function add_meta_box_news() {
		$news_tags = parent::get_option( 'news_tags' );

		foreach ( (array) $news_tags['post_type'] as $post_type ) {
			add_meta_box( 'lana_sitemap_news_section', __( 'Google News', 'lana-sitemap' ), array(
				$this,
				'meta_box_news'
			), $post_type, 'side' );
		}
	}

	/**
	 * Meta box news
	 *
	 * @param $post
	 */
	public function meta_box_news( $post ) {

		/** Use nonce for verification */
		wp_nonce_field( plugin_basename( __FILE__ ), 'lana_sitemap_sitemap_nonce' );

		/**
		 * The actual fields for data entry
		 * Use get_post_meta to retrieve an existing value from the database and use the value for the form
		 */
		$exclude  = get_post_meta( $post->ID, '_lana_sitemap_news_exclude', true );
		$access   = get_post_meta( $post->ID, '_lana_sitemap_news_access', true );
		$disabled = false;

		/** disable options and (visibly) set excluded to true for private posts */
		if ( 'private' == $post->post_status ) {
			$exclude  = true;
			$disabled = true;
		}

		?>

		<p>
			<label for="lana_sitemap_news_access">
				<?php _e( 'Access', 'lana-sitemap' ); ?>
				<select name="lana_sitemap_news_access" id="lana_sitemap_news_access">
					<option value="">
						<?php echo translate( 'Default' ); ?>
					</option>
					<option value="Public" <?php selected( 'Public' == $access ); ?>>
						<?php echo translate( 'Public' ); ?>
					</option>
					<option value="Registration" <?php selected( 'Registration' == $access ); ?>>
						<?php _e( 'Registration', 'lana-sitemap' ); ?>
					</option>
					<option value="Subscription" <?php selected( 'Subscription' == $access ); ?>>
						<?php _e( 'Subscription', 'lana-sitemap' ); ?>
					</option>
				</select>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="lana_sitemap_news_exclude" id="lana_sitemap_news_exclude"
				       value="1" <?php checked( ! empty( $exclude ) ); ?> <?php disabled( $disabled ); ?> />
				<?php _e( 'Exclude from Google News Sitemap.', 'lana-sitemap' ); ?>
			</label>
		</p>

		<?php
	}

	/**
	 * When the post is saved, save our meta data
	 *
	 * @param $post_id
	 */
	function save_metadata( $post_id ) {
		if ( ! isset( $post_id ) ) {
			$post_id = (int) $_REQUEST['post_ID'];
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['lana_sitemap_sitemap_nonce'] ) || ! wp_verify_nonce( $_POST['lana_sitemap_sitemap_nonce'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		/** _lana_sitemap_priority */
		if ( isset( $_POST['lana_sitemap_priority'] ) && is_numeric( $_POST['lana_sitemap_priority'] ) ) {
			update_post_meta( $post_id, '_lana_sitemap_priority', $this->sanitize_priority( $_POST['lana_sitemap_priority'] ) );
		} else {
			delete_post_meta( $post_id, '_lana_sitemap_priority' );
		}

		/** _lana_sitemap_exclude */
		if ( isset( $_POST['lana_sitemap_exclude'] ) && $_POST['lana_sitemap_exclude'] != '' ) {
			update_post_meta( $post_id, '_lana_sitemap_exclude', $_POST['lana_sitemap_exclude'] );
		} else {
			delete_post_meta( $post_id, '_lana_sitemap_exclude' );
		}

		/** _lana_sitemap_news_exclude */
		if ( isset( $_POST['lana_sitemap_news_exclude'] ) && $_POST['lana_sitemap_news_exclude'] != '' ) {
			update_post_meta( $post_id, '_lana_sitemap_news_exclude', $_POST['lana_sitemap_news_exclude'] );
		} else {
			delete_post_meta( $post_id, '_lana_sitemap_news_exclude' );
		}

		/** _lana_sitemap_news_access */
		if ( isset( $_POST['lana_sitemap_news_access'] ) && $_POST['lana_sitemap_news_access'] != '' ) {
			update_post_meta( $post_id, '_lana_sitemap_news_access', $_POST['lana_sitemap_news_access'] );
		} else {
			delete_post_meta( $post_id, '_lana_sitemap_news_access' );
		}
	}
}

/**
 * INSTANTIATE
 */
$lana_sitemap_admin = new Lana_Sitemap_Admin();
