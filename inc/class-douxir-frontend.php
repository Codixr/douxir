<?php
/**
 * Douxir front-end helpers: archives, document meta, feedback and print.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register the documentation homepage query var and rewrite.
 *
 * @return void
 */
function douxir_register_archive_rewrite() {
	$slug = douxir_get_docs_slug();
	add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?douxir_archive=1', 'top' );
}
add_action( 'init', 'douxir_register_archive_rewrite', 11 );

/**
 * Expose the documentation homepage query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function douxir_register_query_vars( $vars ) {
	$vars[] = 'douxir_archive';
	return $vars;
}
add_filter( 'query_vars', 'douxir_register_query_vars' );

/**
 * Flush rewrite rules once after a slug or permalink setting change.
 *
 * @return void
 */
function douxir_maybe_flush_rewrites() {
	if ( get_option( 'douxir_flush_rewrites' ) ) {
		flush_rewrite_rules( false );
		delete_option( 'douxir_flush_rewrites' );
	}
}
add_action( 'init', 'douxir_maybe_flush_rewrites', 99 );

/**
 * Choose a plugin template for the docs home, category archives and single docs.
 *
 * @param string $template Current template path.
 * @return string
 */
function douxir_template_include( $template ) {
	if ( get_query_var( 'douxir_archive' ) ) {
		$custom = DOUXIR_PATH . '/templates/archive-douxir.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}

	if ( is_tax( 'doc_category' ) ) {
		$custom = DOUXIR_PATH . '/templates/taxonomy-doc_category.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}

	if ( is_singular( 'custom_docs' ) ) {
		$custom = DOUXIR_PATH . '/templates/single-douxir.php';
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}

	return $template;
}
add_filter( 'template_include', 'douxir_template_include', 20 );

/**
 * Estimated reading time in whole minutes (minimum 1).
 *
 * @param int $post_id Post ID.
 * @return int
 */
function douxir_get_reading_time( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 1;
	}
	$words = str_word_count( wp_strip_all_tags( (string) $post->post_content ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Published documents in a category, flattened in menu order (parents then children).
 *
 * @param int $term_id Category term ID.
 * @return WP_Post[]
 */
function douxir_get_category_docs_flat( $term_id ) {
	$term_id = absint( $term_id );
	$args    = array(
		'post_type'      => 'custom_docs',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_parent'    => 0,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);
	if ( $term_id > 0 ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Documents are listed per category.
			array(
				'taxonomy' => 'doc_category',
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);
	}

	$parents = get_posts( $args );
	$flat    = array();
	foreach ( $parents as $parent ) {
		$flat[]   = $parent;
		$children = get_posts(
			array(
				'post_type'      => 'custom_docs',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $parent->ID,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		foreach ( $children as $child ) {
			$flat[] = $child;
		}
	}
	return $flat;
}

/**
 * Previous and next published documents in the same category, following menu order.
 *
 * @param int $post_id Current document ID.
 * @return array{prev: WP_Post|null, next: WP_Post|null}
 */
function douxir_get_adjacent_docs( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'doc_category' );
	$term  = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : null;
	$flat  = douxir_get_category_docs_flat( $term ? (int) $term->term_id : 0 );
	$prev  = null;
	$next  = null;
	foreach ( $flat as $index => $doc ) {
		if ( (int) $doc->ID === (int) $post_id ) {
			if ( $index > 0 ) {
				$prev = $flat[ $index - 1 ];
			}
			if ( isset( $flat[ $index + 1 ] ) ) {
				$next = $flat[ $index + 1 ];
			}
			break;
		}
	}
	return array(
		'prev' => $prev,
		'next' => $next,
	);
}

/**
 * Related published documents in the same category.
 *
 * @param int $post_id Current document ID.
 * @param int $count   How many to return.
 * @return WP_Post[]
 */
function douxir_get_related_docs( $post_id, $count = 3 ) {
	$terms = wp_get_post_terms( $post_id, 'doc_category', array( 'fields' => 'ids' ) );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$candidates = get_posts(
		array(
			'post_type'      => 'custom_docs',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $count ) + 5,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Related docs share a category.
				array(
					'taxonomy' => 'doc_category',
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', $terms ),
				),
			),
		)
	);

	$related = array();
	foreach ( $candidates as $candidate ) {
		if ( (int) $candidate->ID === (int) $post_id ) {
			continue;
		}
		$related[] = $candidate;
		if ( count( $related ) >= max( 1, (int) $count ) ) {
			break;
		}
	}
	return $related;
}

/**
 * Render document meta (reading time, last updated, print).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function douxir_render_doc_meta( $post_id ) {
	$s      = douxir_get_settings();
	$parts  = array();

	if ( ! empty( $s['show_reading_time'] ) ) {
		$mins    = douxir_get_reading_time( $post_id );
		$parts[] = '<span class="douxir-meta-item douxir-reading-time">' . esc_html( sprintf( /* translators: %d: minutes */ _n( '%d min read', '%d min read', $mins, 'douxir' ), $mins ) ) . '</span>';
	}

	if ( ! empty( $s['show_last_updated'] ) ) {
		$parts[] = '<span class="douxir-meta-item douxir-last-updated">' . esc_html( sprintf( /* translators: %s: date */ __( 'Updated %s', 'douxir' ), get_the_modified_date( '', $post_id ) ) ) . '</span>';
	}

	if ( empty( $parts ) && empty( $s['show_print'] ) ) {
		return '';
	}

	$html  = '<div class="douxir-doc-meta">';
	$html .= '<div class="douxir-doc-meta-items">' . implode( '', $parts ) . '</div>';
	if ( ! empty( $s['show_print'] ) ) {
		$html .= '<button type="button" class="douxir-print-btn" data-douxir-print="1">' . esc_html__( 'Print', 'douxir' ) . '</button>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * Render previous / next document links.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function douxir_render_prev_next( $post_id ) {
	if ( empty( douxir_get_setting( 'show_prev_next' ) ) ) {
		return '';
	}
	$adj = douxir_get_adjacent_docs( $post_id );
	if ( ! $adj['prev'] && ! $adj['next'] ) {
		return '';
	}

	$html  = '<nav class="douxir-prev-next" aria-label="' . esc_attr__( 'Document navigation', 'douxir' ) . '">';
	if ( $adj['prev'] ) {
		$html .= '<a class="douxir-prev" href="' . esc_url( get_permalink( $adj['prev'] ) ) . '"><span class="douxir-pn-label">' . esc_html__( 'Previous', 'douxir' ) . '</span><span class="douxir-pn-title">' . esc_html( get_the_title( $adj['prev'] ) ) . '</span></a>';
	} else {
		$html .= '<span class="douxir-prev douxir-pn-empty"></span>';
	}
	if ( $adj['next'] ) {
		$html .= '<a class="douxir-next" href="' . esc_url( get_permalink( $adj['next'] ) ) . '"><span class="douxir-pn-label">' . esc_html__( 'Next', 'douxir' ) . '</span><span class="douxir-pn-title">' . esc_html( get_the_title( $adj['next'] ) ) . '</span></a>';
	} else {
		$html .= '<span class="douxir-next douxir-pn-empty"></span>';
	}
	$html .= '</nav>';
	return $html;
}

/**
 * Render related documents.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function douxir_render_related( $post_id ) {
	$s = douxir_get_settings();
	if ( empty( $s['show_related'] ) ) {
		return '';
	}
	$related = douxir_get_related_docs( $post_id, (int) $s['related_count'] );
	if ( empty( $related ) ) {
		return '';
	}

	$html  = '<section class="douxir-related">';
	$html .= '<h3 class="douxir-related-title">' . esc_html__( 'Related documents', 'douxir' ) . '</h2>';
	$html .= '<ul class="douxir-related-list">';
	foreach ( $related as $doc ) {
		$html .= '<li><a href="' . esc_url( get_permalink( $doc ) ) . '">' . esc_html( get_the_title( $doc ) ) . '</a></li>';
	}
	$html .= '</ul></section>';
	return $html;
}

/**
 * Render the helpful-feedback form.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function douxir_render_feedback( $post_id ) {
	if ( empty( douxir_get_setting( 'show_feedback' ) ) ) {
		return '';
	}

	$yes = absint( get_post_meta( $post_id, '_douxir_helpful_yes', true ) );
	$no  = absint( get_post_meta( $post_id, '_douxir_helpful_no', true ) );

	$html  = '<div class="douxir-feedback" data-douxir-feedback="' . esc_attr( (string) $post_id ) . '">';
	$html .= '<p class="douxir-feedback-q">' . esc_html__( 'Was this document helpful?', 'douxir' ) . '</p>';
	$html .= '<div class="douxir-feedback-actions">';
	$html .= '<button type="button" class="douxir-feedback-btn" data-vote="yes">' . esc_html__( 'Yes', 'douxir' ) . '</button>';
	$html .= '<button type="button" class="douxir-feedback-btn" data-vote="no">' . esc_html__( 'No', 'douxir' ) . '</button>';
	$html .= '</div>';
	$html .= '<p class="douxir-feedback-counts" aria-live="polite">' . esc_html( sprintf( /* translators: 1: yes count, 2: no count */ __( '%1$d yes, %2$d no', 'douxir' ), $yes, $no ) ) . '</p>';
	$html .= '<p class="douxir-feedback-thanks" hidden>' . esc_html__( 'Thank you for your feedback.', 'douxir' ) . '</p>';
	$html .= '</div>';
	return $html;
}

/**
 * AJAX: record a helpful / not helpful vote.
 *
 * @return void
 */
function douxir_feedback_handler() {
	check_ajax_referer( 'douxir_feedback', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
	$vote    = isset( $_POST['vote'] ) ? sanitize_key( wp_unslash( $_POST['vote'] ) ) : '';

	if ( ! $post_id || 'custom_docs' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid document.', 'douxir' ) ), 400 );
	}

	if ( ! in_array( $vote, array( 'yes', 'no' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid vote.', 'douxir' ) ), 400 );
	}

	$cookie_name = 'douxir_fb_' . $post_id;
	if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
		wp_send_json_error( array( 'message' => __( 'You have already voted on this document.', 'douxir' ) ), 409 );
	}

	$key   = 'yes' === $vote ? '_douxir_helpful_yes' : '_douxir_helpful_no';
	$count = absint( get_post_meta( $post_id, $key, true ) ) + 1;
	update_post_meta( $post_id, $key, $count );

	// phpcs:ignore WordPress.VIP.RestrictedFunctions -- Frontend vote flag, not an auth cookie.
	setcookie( $cookie_name, '1', time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );

	$yes = absint( get_post_meta( $post_id, '_douxir_helpful_yes', true ) );
	$no  = absint( get_post_meta( $post_id, '_douxir_helpful_no', true ) );

	wp_send_json_success(
		array(
			'yes' => $yes,
			'no'  => $no,
			/* translators: 1: yes count, 2: no count */
			'label' => sprintf( __( '%1$d yes, %2$d no', 'douxir' ), $yes, $no ),
		)
	);
}
add_action( 'wp_ajax_douxir_feedback', 'douxir_feedback_handler' );
add_action( 'wp_ajax_nopriv_douxir_feedback', 'douxir_feedback_handler' );

/**
 * Render the documentation archive (home or a single category).
 *
 * @param WP_Term|null $term Category term, or null for the full archive.
 * @return string
 */
function douxir_render_archive( $term = null ) {
	$s      = douxir_get_settings();
	$layout = in_array( $s['archive_layout'], array( 'grid', 'list' ), true ) ? $s['archive_layout'] : 'grid';
	$style  = in_array( $s['archive_card_style'], array( 'simple', 'bordered', 'elevated' ), true ) ? $s['archive_card_style'] : 'elevated';
	$cols   = max( 1, min( 4, (int) $s['archive_columns'] ) );

	$html  = '<div class="douxir-wrap douxir-archive douxir-archive-' . esc_attr( $layout ) . ' douxir-card-' . esc_attr( $style ) . '" style="--douxir-columns:' . esc_attr( (string) $cols ) . '">';
	$html .= '<header class="douxir-archive-header">';
	if ( $term ) {
		$html .= '<p class="douxir-archive-kicker"><a href="' . esc_url( douxir_get_docs_home_url() ) . '">' . esc_html( $s['hub_title'] ) . '</a></p>';
		$html .= '<h1 class="douxir-archive-title">' . esc_html( $term->name ) . '</h1>';
		if ( ! empty( $s['archive_show_description'] ) && $term->description ) {
			$html .= '<div class="douxir-archive-desc">' . wp_kses_post( wpautop( $term->description ) ) . '</div>';
		}
	} else {
		$html .= '<h1 class="douxir-archive-title">' . esc_html( $s['hub_title'] ) . '</h1>';
	}
	if ( ! empty( $s['show_search'] ) ) {
		$html .= do_shortcode( '[douxir_search]' );
	}
	$html .= '</header>';

	if ( $term ) {
		$html .= douxir_render_category_docs( $term, $s );
	} else {
		$html .= douxir_render_category_cards( $s );
	}

	$html .= '</div>';
	return $html;
}

/**
 * Render category cards for the documentation homepage.
 *
 * @param array $s Settings.
 * @return string
 */
function douxir_render_category_cards( $s ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'doc_category',
			'hide_empty' => true,
			'parent'     => 0,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p class="douxir-empty">' . esc_html__( 'No documentation has been published yet.', 'douxir' ) . '</p>';
	}

	$cat_order = douxir_get_categories_order();
	if ( ! empty( $cat_order ) && is_array( $cat_order ) ) {
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
	}

	$html = '<div class="douxir-cat-grid">';
	foreach ( $terms as $term ) {
		$icon  = get_term_meta( $term->term_id, 'douxir_icon', true );
		$count = (int) $term->count;
		$html .= '<article class="douxir-cat-card">';
		$html .= '<a class="douxir-cat-card-link" href="' . esc_url( get_term_link( $term ) ) . '">';
		$html .= '<span class="douxir-cat-card-icon">' . douxir_get_icon_svg( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is built from an allow-list.
		$html .= '<h2 class="douxir-cat-card-title">' . esc_html( $term->name ) . '</h2>';
		if ( ! empty( $s['archive_show_count'] ) ) {
			$html .= '<p class="douxir-cat-card-count">' . esc_html( sprintf( /* translators: %d: number of documents */ _n( '%d document', '%d documents', $count, 'douxir' ), $count ) ) . '</p>';
		}
		if ( ! empty( $s['archive_show_description'] ) && $term->description ) {
			$html .= '<p class="douxir-cat-card-desc">' . esc_html( wp_trim_words( $term->description, 24 ) ) . '</p>';
		}
		$html .= '</a></article>';
	}
	$html .= '</div>';
	return $html;
}

/**
 * Render the documents that belong to one category, in saved menu order.
 *
 * @param WP_Term $term Category.
 * @param array   $s    Settings.
 * @return string
 */
function douxir_render_category_docs( $term, $s ) {
	$docs = douxir_get_category_docs_flat( (int) $term->term_id );
	if ( empty( $docs ) ) {
		return '<p class="douxir-empty">' . esc_html__( 'No documents in this category yet.', 'douxir' ) . '</p>';
	}

	$html = '<div class="douxir-doc-grid">';
	foreach ( $docs as $doc ) {
		$html .= '<article class="douxir-doc-card">';
		$html .= '<a class="douxir-doc-card-link" href="' . esc_url( get_permalink( $doc ) ) . '">';
		$html .= '<h2 class="douxir-doc-card-title">' . esc_html( get_the_title( $doc ) ) . '</h2>';
		$excerpt = has_excerpt( $doc ) ? get_the_excerpt( $doc ) : wp_trim_words( wp_strip_all_tags( $doc->post_content ), 24 );
		if ( $excerpt ) {
			$html .= '<p class="douxir-doc-card-excerpt">' . esc_html( $excerpt ) . '</p>';
		}
		$html .= '</a></article>';
	}
	$html .= '</div>';
	return $html;
}
