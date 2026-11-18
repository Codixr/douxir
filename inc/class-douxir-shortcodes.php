<?php
/**
 * Douxir front-end shortcodes.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Douxir_Shortcodes' ) ) {

	/**
	 * Registers and renders the documentation shortcodes.
	 */
	class Douxir_Shortcodes {

		/**
		 * Register shortcodes.
		 *
		 * The original shortcode names are kept so existing pages keep working.
		 * Prefixed aliases are added for new content.
		 */
		public function __construct() {
			add_shortcode( 'documentation_list', array( $this, 'doc_list_function' ) );
			add_shortcode( 'documentation_content', array( $this, 'documentation_content_fun' ) );
			add_shortcode( 'doc_categories_list', array( $this, 'doc_categories_list_func' ) );
			add_shortcode( 'douxir_list', array( $this, 'doc_list_function' ) );
			add_shortcode( 'douxir_content', array( $this, 'documentation_content_fun' ) );
			add_shortcode( 'douxir_categories', array( $this, 'doc_categories_list_func' ) );
			add_shortcode( 'douxir_search', array( $this, 'search_func' ) );
			// Silent aliases from the earlier working title, so existing test pages keep working.
			add_shortcode( 'docixr_list', array( $this, 'doc_list_function' ) );
			add_shortcode( 'docixr_content', array( $this, 'documentation_content_fun' ) );
			add_shortcode( 'docixr_categories', array( $this, 'doc_categories_list_func' ) );
		}

		/**
		 * Public wrapper used by Gutenberg blocks.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public static function render_doc_list( $atts ) {
			$instance = new self();
			return $instance->doc_list_function( $atts );
		}

		/**
		 * Public wrapper used by Gutenberg blocks.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public static function render_search( $atts ) {
			$instance = new self();
			return $instance->search_func( $atts );
		}

		/**
		 * Public wrapper used by Gutenberg blocks.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public static function render_categories( $atts ) {
			$instance = new self();
			return $instance->doc_categories_list_func( $atts );
		}

		/**
		 * Read the "pro" flag of a document.
		 *
		 * Uses ACF when it is active, and the post meta value otherwise.
		 *
		 * @param int $post_id Post ID.
		 * @return mixed
		 */
		private function get_pro_flag( $post_id ) {
			if ( function_exists( 'get_field' ) ) {
				return get_field( 'documentation_pro', $post_id );
			}
			return get_post_meta( $post_id, 'documentation_pro', true );
		}

		/**
		 * Sort terms by the saved drag-and-drop order.
		 *
		 * @param array $terms     Terms to sort.
		 * @param array $cat_order Saved order of term IDs.
		 * @return array
		 */
		private function sort_terms( $terms, $cat_order ) {
			usort(
				$terms,
				function ( $a, $b ) use ( $cat_order ) {
					$pos_a = array_search( $a->term_id, $cat_order ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Loose match kept for stored IDs of any type.
					$pos_b = array_search( $b->term_id, $cat_order ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Loose match kept for stored IDs of any type.
					if ( false === $pos_a && false === $pos_b ) {
						return 0;
					}
					if ( false === $pos_a ) {
						return 1;
					}
					if ( false === $pos_b ) {
						return -1;
					}
					return $pos_a - $pos_b;
				}
			);
			return $terms;
		}

		/**
		 * Render the [documentation_list] shortcode.
		 *
		 * @return string
		 */
		public function doc_list_function( $atts = array() ) {
			$atts = shortcode_atts(
				array(
					'category'    => '',
					'layout'      => 'nav',
					'columns'     => 0,
					'limit'       => -1,
					'show_search' => '',
					'parent'      => 0,
				),
				is_array( $atts ) ? $atts : array(),
				'douxir_list'
			);

			$layout = sanitize_key( $atts['layout'] );
			if ( in_array( $layout, array( 'grid', 'list' ), true ) ) {
				return $this->render_docs_catalog( $atts, $layout );
			}

			ob_start();

			$settings = function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : array();

			// Get all categories.
			$term_args = array(
				'taxonomy'   => 'doc_category',
				'hide_empty' => true,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			);
			if ( '' !== $atts['category'] ) {
				if ( is_numeric( $atts['category'] ) ) {
					$term_args['include'] = array( absint( $atts['category'] ) );
				} else {
					$term_args['slug'] = sanitize_title( $atts['category'] );
				}
			}
			$categories = get_terms( $term_args );

			// Respect drag-and-drop category order if saved.
			$cat_order = douxir_get_categories_order();
			if ( ! empty( $cat_order ) && is_array( $cat_order ) && ! is_wp_error( $categories ) ) {
				$categories = $this->sort_terms( $categories, $cat_order );
			}

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) { ?>

				<div class="doc-left">

					<?php
					$show_search = $atts['show_search'];
					if ( '' === $show_search ) {
						$show_search = empty( $settings['show_search'] ) ? 'no' : 'yes';
					}
					if ( in_array( strtolower( (string) $show_search ), array( 'yes', '1', 'true' ), true ) ) {
						$placeholder = ! empty( $settings['search_placeholder'] ) ? $settings['search_placeholder'] : __( 'Search documentation...', 'douxir' );
						echo self::search_markup( $placeholder ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
					}
					?>

					<!-- Mobile dropdown placeholder -->
					<div class="doc-mobile-dropdown"></div>
					<div class="doc-title-list">
						<?php foreach ( $categories as $category ) : ?>

							<?php
							$limit = intval( $atts['limit'] );
							$args  = array(
								'post_type'      => 'custom_docs',
								'posts_per_page' => ( $limit > 0 ) ? $limit : -1,
								'post_parent'    => isset( $atts['parent'] ) ? absint( $atts['parent'] ) : 0,
								'orderby'        => 'menu_order',
								'order'          => 'ASC',
								'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed to list documents per category.
									array(
										'taxonomy' => 'doc_category',
										'field'    => 'term_id',
										'terms'    => $category->term_id,
									),
								),
							);

							$doc_lists = get_posts( $args );

							if ( ! empty( $doc_lists ) ) :
								?>
							<h5 class="doc-category-title">
								<?php
								$icon = get_term_meta( $category->term_id, 'douxir_icon', true );
								if ( $icon && function_exists( 'douxir_get_icon_svg' ) ) {
									echo douxir_get_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is built from an allow-list.
								}
								echo esc_html( $category->name );
								?>
							</h5>
							<ul class="doc-title">

								<?php
								foreach ( $doc_lists as $doc_list ) {

									setup_postdata( $doc_list );

									$pro = $this->get_pro_flag( $doc_list->ID );
									?>

									<li>
										<a href="<?php echo esc_url( get_permalink( $doc_list->ID ) ); ?>">
											<?php echo esc_html( $doc_list->post_title ); ?>
											<?php echo ( ! empty( $pro ) ? '<span class="pro-tag">' . esc_html__( 'pro', 'douxir' ) . '</span>' : '' ); ?>
										</a>

										<?php
										$child_args = array(
											'post_type'   => 'custom_docs',
											'posts_per_page' => -1,
											'post_parent' => $doc_list->ID,
											'orderby'     => 'menu_order',
											'order'       => 'ASC',
										);

										$doc_child_lists = get_posts( $child_args );

										if ( ! empty( $doc_child_lists ) ) {
											?>

											<i class="douxir-toggle" aria-hidden="true"></i>

											<ul class="doc-child-title">

												<?php
												foreach ( $doc_child_lists as $doc_child_list ) {

													setup_postdata( $doc_child_list );
													?>

													<li>
														<a href="<?php echo esc_url( get_permalink( $doc_child_list->ID ) ); ?>">
															<?php echo esc_html( $doc_child_list->post_title ); ?>
														</a>
													</li>

												<?php } ?>

											</ul>

										<?php } ?>

									</li>

								<?php } ?>

							</ul>

								<?php wp_reset_postdata(); ?>

							<?php endif; ?>

					<?php endforeach; ?>
					</div>
				</div>

				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Render the [documentation_content] shortcode.
		 *
		 * @return string
		 */
		public function documentation_content_fun() {
			ob_start();
			$get_queried_object = get_queried_object();
			$settings           = function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : array();

			if ( isset( $get_queried_object->post_name ) ) {

				$args = array(
					'post_type'      => 'custom_docs',
					'name'           => $get_queried_object->post_name,
					'posts_per_page' => 1,
				);

				$post = get_posts( $args );

				if ( ! empty( $post ) ) {

					$post = $post[0];
					setup_postdata( $post );

					$pro     = $this->get_pro_flag( $post->ID );
					$pro_tag = ( ! empty( $pro ) ? '<span class="pro-tag">' . esc_html__( 'pro', 'douxir' ) . '</span>' : '' );

					if ( empty( $settings ) || ! empty( $settings['show_breadcrumbs'] ) ) {
						echo '<nav class="breadcrumbs">';
						echo wp_kses_post( $this->generate_breadcrumbs( $post ) );
						echo '</nav>';
					}

					echo '<h1>' . esc_html( $post->post_title ) . wp_kses_post( $pro_tag ) . '</h1>';

					if ( function_exists( 'douxir_render_doc_meta' ) ) {
						echo douxir_render_doc_meta( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
					}

					// Core content filter output, escaped by the filters themselves.
					echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

					$child_args = array(
						'post_type'      => 'custom_docs',
						'post_parent'    => $post->ID,
						'posts_per_page' => -1,
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
					);

					$child_posts = get_posts( $child_args );

					if ( ! empty( $child_posts ) ) {
						echo '<div class="doc-child-list">';
						echo '<ul>';

						foreach ( $child_posts as $child ) {
							echo '<li>';
							echo '<a href="' . esc_url( get_permalink( $child->ID ) ) . '">';
							echo esc_html( $child->post_title );
							echo '</a>';
							echo '</li>';
						}

						echo '</ul>';
						echo '</div>';
					}

					if ( function_exists( 'douxir_render_prev_next' ) ) {
						echo douxir_render_prev_next( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
					}
					if ( function_exists( 'douxir_render_related' ) ) {
						echo douxir_render_related( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
					}
					if ( function_exists( 'douxir_render_feedback' ) ) {
						echo douxir_render_feedback( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
					}
				}

				wp_reset_postdata();
			}

			return ob_get_clean();
		}


		/**
		 * Generate breadcrumbs for a post with post type slug.
		 *
		 * @param WP_Post $post Current document.
		 * @return string
		 */
		private function generate_breadcrumbs( $post ) {
			$breadcrumbs = array();

			// Add post type slug as the root.
			$post_type = get_post_type_object( $post->post_type );
			if ( $post_type ) {
				$home_url      = function_exists( 'douxir_get_docs_home_url' ) ? douxir_get_docs_home_url() : home_url( '/documentation/' );
				$hub_title     = function_exists( 'douxir_get_setting' ) ? douxir_get_setting( 'hub_title', $post_type->labels->singular_name ) : $post_type->labels->singular_name;
				$breadcrumbs[] = '<a href="' . esc_url( $home_url ) . '">' . esc_html( $hub_title ) . '</a>';
			}

			// Traverse up the hierarchy.
			$current_post = $post;
			$hierarchy    = array();
			while ( $current_post->post_parent ) {
				$parent = get_post( $current_post->post_parent );
				array_unshift( $hierarchy, '<a href="' . esc_url( get_permalink( $parent->ID ) ) . '">' . esc_html( $parent->post_title ) . '</a>' );
				$current_post = $parent;
			}

			// Add parent hierarchy, if any.
			if ( ! empty( $hierarchy ) ) {
				$breadcrumbs = array_merge( $breadcrumbs, $hierarchy );
			}

			// Add the current post without a link.
			$breadcrumbs[] = '<span>' . esc_html( $post->post_title ) . '</span>';

			// Combine breadcrumbs.
			return implode( ' / ', $breadcrumbs );
		}

		/**
		 * Render the [doc_categories_list] shortcode.
		 *
		 * @return string
		 */
		public function doc_categories_list_func( $atts = array() ) {
			$atts = shortcode_atts(
				array(
					'layout'  => '',
					'columns' => 0,
				),
				is_array( $atts ) ? $atts : array(),
				'douxir_categories'
			);
			$settings = function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : array();
			$layout   = sanitize_key( $atts['layout'] );
			if ( ! in_array( $layout, array( 'grid', 'list' ), true ) ) {
				$layout = ! empty( $settings['archive_layout'] ) ? $settings['archive_layout'] : 'grid';
			}
			$columns = absint( $atts['columns'] );
			if ( $columns < 1 ) {
				$columns = ! empty( $settings['archive_columns'] ) ? (int) $settings['archive_columns'] : 3;
			}
			$columns = min( 4, max( 1, $columns ) );
			$style   = ! empty( $settings['archive_card_style'] ) ? $settings['archive_card_style'] : 'elevated';

			ob_start();

			$terms = get_terms(
				array(
					'taxonomy'   => 'doc_category',
					'hide_empty' => true,
					'parent'     => 0,
					'orderby'    => 'term_id',
					'order'      => 'ASC',
				)
			);

			// Respect drag-and-drop category order if saved.
			$cat_order = douxir_get_categories_order();
			if ( ! empty( $cat_order ) && is_array( $cat_order ) && ! is_wp_error( $terms ) ) {
				$terms = $this->sort_terms( $terms, $cat_order );
			}

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

				echo '<div class="doc-categories-list douxir-cat-grid douxir-archive-' . esc_attr( $layout ) . ' douxir-card-' . esc_attr( $style ) . '" style="--douxir-columns:' . esc_attr( (string) $columns ) . '">';

				foreach ( $terms as $term ) {

					echo '<div class="doc-category-item douxir-cat-card">';
					$icon = get_term_meta( $term->term_id, 'douxir_icon', true );
					if ( $icon && function_exists( 'douxir_get_icon_svg' ) ) {
						echo '<span class="douxir-cat-card-icon">' . douxir_get_icon_svg( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is built from an allow-list.
					}
					echo '<h2>' . esc_html( $term->name ) . '</h2>';

					$args = array(
						'post_type'      => 'custom_docs',
						'posts_per_page' => 5,
						'post_parent'    => 0,
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Needed to list documents per category.
							array(
								'taxonomy' => 'doc_category',
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
					);

					$query = new WP_Query( $args );

					if ( $query->have_posts() ) {

						$first_post_link = '';
						echo '<ul>';

						while ( $query->have_posts() ) {
							$query->the_post();

							if ( empty( $first_post_link ) ) {
								$first_post_link = get_permalink();
							}
							echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
						}
						echo '</ul>';
						if ( ! empty( $first_post_link ) ) {
							echo '<a href="' . esc_url( $first_post_link ) . '" class="view-details-link">' . esc_html__( 'View Details', 'douxir' ) . '</a>';
						}

						wp_reset_postdata();
					}

					echo '</div>';
				}

				echo '</div>';
			}

			return ob_get_clean();
		}

		/**
		 * Render the [douxir_search] shortcode.
		 *
		 * @param array $atts Attributes.
		 * @return string
		 */
		public function search_func( $atts = array() ) {
			$atts = shortcode_atts(
				array(
					'placeholder' => '',
				),
				is_array( $atts ) ? $atts : array(),
				'douxir_search'
			);
			$placeholder = $atts['placeholder'];
			if ( '' === $placeholder && function_exists( 'douxir_get_setting' ) ) {
				$placeholder = douxir_get_setting( 'search_placeholder', __( 'Search documentation...', 'douxir' ) );
			}
			if ( '' === $placeholder ) {
				$placeholder = __( 'Search documentation...', 'douxir' );
			}
			return self::search_markup( $placeholder );
		}

		/**
		 * Markup for the live search box. IDs are unique when more than one box is on the page.
		 *
		 * @param string $placeholder Input placeholder.
		 * @return string
		 */
		public static function search_markup( $placeholder ) {
			static $count = 0;
			++$count;
			$id      = 1 === $count ? 'cd-doc-search' : 'cd-doc-search-' . $count;
			$results = 1 === $count ? 'cd-search-results' : 'cd-search-results-' . $count;
			$html    = '<div class="doc-search-wrapper douxir-search-box">';
			$html   .= '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html__( 'Search documentation', 'douxir' ) . '</label>';
			$html   .= '<input type="text" class="douxir-search-input" id="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-label="' . esc_attr__( 'Search documentation', 'douxir' ) . '" autocomplete="off">';
			$html   .= '<div id="' . esc_attr( $results ) . '" class="cd-search-results"></div>';
			$html   .= '</div>';
			return $html;
		}

		/**
		 * Render a standalone grid or list of documents (used when the list shortcode is not the sidebar).
		 *
		 * @param array  $atts   Attributes.
		 * @param string $layout grid or list.
		 * @return string
		 */
		private function render_docs_catalog( $atts, $layout ) {
			$settings = function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : array();
			$columns  = absint( $atts['columns'] );
			if ( $columns < 1 ) {
				$columns = ! empty( $settings['archive_columns'] ) ? (int) $settings['archive_columns'] : 3;
			}
			$columns = min( 4, max( 1, $columns ) );
			$style   = ! empty( $settings['archive_card_style'] ) ? $settings['archive_card_style'] : 'elevated';
			$limit   = intval( $atts['limit'] );

			$query_args = array(
				'post_type'      => 'custom_docs',
				'post_status'    => 'publish',
				'posts_per_page' => ( $limit > 0 ) ? $limit : 12,
				'post_parent'    => absint( $atts['parent'] ),
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			);
			if ( '' !== $atts['category'] ) {
				$field = is_numeric( $atts['category'] ) ? 'term_id' : 'slug';
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtered docs list.
					array(
						'taxonomy' => 'doc_category',
						'field'    => $field,
						'terms'    => is_numeric( $atts['category'] ) ? absint( $atts['category'] ) : sanitize_title( $atts['category'] ),
					),
				);
			}

			$docs = get_posts( $query_args );
			$html = '<div class="douxir-wrap douxir-docs-catalog douxir-archive-' . esc_attr( $layout ) . ' douxir-card-' . esc_attr( $style ) . '" style="--douxir-columns:' . esc_attr( (string) $columns ) . '">';
			$show_search = strtolower( (string) $atts['show_search'] );
			if ( in_array( $show_search, array( 'yes', '1', 'true' ), true ) ) {
				$html .= $this->search_func( array() );
			}
			if ( empty( $docs ) ) {
				$html .= '<p class="douxir-empty">' . esc_html__( 'No documents found.', 'douxir' ) . '</p>';
			} else {
				$html .= '<div class="douxir-doc-grid">';
				foreach ( $docs as $doc ) {
					$html .= '<article class="douxir-doc-card"><a class="douxir-doc-card-link" href="' . esc_url( get_permalink( $doc ) ) . '">';
					$html .= '<h2 class="douxir-doc-card-title">' . esc_html( get_the_title( $doc ) ) . '</h2>';
					$excerpt = has_excerpt( $doc ) ? get_the_excerpt( $doc ) : wp_trim_words( wp_strip_all_tags( $doc->post_content ), 24 );
					if ( $excerpt ) {
						$html .= '<p class="douxir-doc-card-excerpt">' . esc_html( $excerpt ) . '</p>';
					}
					$html .= '</a></article>';
				}
				$html .= '</div>';
			}
			$html .= '</div>';
			return $html;
		}

	}

}
