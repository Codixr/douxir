<?php
/**
 * Douxir content types, permalinks and front-end search.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Return the saved category order.
 *
 * Reads the Douxir option and falls back to the legacy option key used by
 * earlier versions, so existing sites keep their category order.
 *
 * @return array|false
 */
function douxir_get_categories_order() {
	$order = get_option( 'douxir_categories_order', false );
	if ( false === $order ) {
		$order = get_option( 'docixr_categories_order', false );
	}
	if ( false === $order ) {
		$order = get_option( 'cd_categories_order', false );
	}
	return $order;
}

/**
 * One-time migration of the legacy category order option.
 *
 * The legacy option is copied, not deleted, so a rollback to the old plugin
 * still works.
 *
 * @return void
 */
function douxir_maybe_migrate_options() {
	if ( get_option( 'douxir_db_version' ) === DOUXIR_VERSION ) {
		return;
	}
	if ( false === get_option( 'douxir_categories_order', false ) ) {
		foreach ( array( 'docixr_categories_order', 'cd_categories_order' ) as $legacy_key ) {
			$legacy = get_option( $legacy_key, false );
			if ( false !== $legacy ) {
				update_option( 'douxir_categories_order', $legacy );
				break;
			}
		}
	}
	update_option( 'douxir_db_version', DOUXIR_VERSION );
}
add_action( 'admin_init', 'douxir_maybe_migrate_options' );

/**
 * Register the documentation post type and category taxonomy.
 *
 * The post type key (custom_docs) and taxonomy key (doc_category) are kept
 * from earlier versions so existing documents, categories and URLs keep working.
 *
 * @return void
 */
function douxir_register_content_types() {
	add_rewrite_tag( '%doc_category%', '([^/]+)', 'doc_category=' );

	$docs_slug   = function_exists( 'douxir_get_docs_slug' ) ? douxir_get_docs_slug() : 'documentation';
	$include_cat = function_exists( 'douxir_get_setting' ) ? (bool) douxir_get_setting( 'include_category_in_url', true ) : true;
	$cpt_slug    = $include_cat ? $docs_slug . '/%doc_category%' : $docs_slug;
	$tax_slug    = $include_cat ? $docs_slug : $docs_slug . '/category';

	$args = array(
		'public'       => true,
		'has_archive'  => false,
		'label'        => __( 'Documentation', 'douxir' ),
		'labels'       => array(
			'name'               => __( 'Documentation', 'douxir' ),
			'singular_name'      => __( 'Documentation', 'douxir' ),
			'menu_name'          => __( 'Douxir', 'douxir' ),
			'add_new_item'       => __( 'Add New Document', 'douxir' ),
			'edit_item'          => __( 'Edit Document', 'douxir' ),
			'new_item'           => __( 'New Document', 'douxir' ),
			'view_item'          => __( 'View Document', 'douxir' ),
			'search_items'       => __( 'Search Documents', 'douxir' ),
			'not_found'          => __( 'No documents found.', 'douxir' ),
			'not_found_in_trash' => __( 'No documents found in Trash.', 'douxir' ),
			'all_items'          => __( 'All Documents', 'douxir' ),
		),
		'menu_icon'    => DOUXIR_URL . '/assets/images/douxir-icon.png',
		'supports'     => array( 'title', 'editor', 'page-attributes' ),
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array(
			'slug'       => $cpt_slug,
			'with_front' => false,
		),
	);

	register_post_type( 'custom_docs', $args );

	// Register taxonomy for categories.
	$taxonomy_args = array(
		'hierarchical'      => true,
		'labels'            => array(
			'name'          => __( 'Doc Categories', 'douxir' ),
			'singular_name' => __( 'Doc Category', 'douxir' ),
			'search_items'  => __( 'Search Doc Categories', 'douxir' ),
			'all_items'     => __( 'All Doc Categories', 'douxir' ),
			'edit_item'     => __( 'Edit Doc Category', 'douxir' ),
			'add_new_item'  => __( 'Add New Doc Category', 'douxir' ),
		),
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array(
			'slug'         => $tax_slug,
			'with_front'   => false,
			'hierarchical' => true,
		),
	);

	register_taxonomy( 'doc_category', array( 'custom_docs' ), $taxonomy_args );
}
add_action( 'init', 'douxir_register_content_types' );

/**
 * Replace the %doc_category% placeholder in document permalinks.
 *
 * @param string  $post_link The post permalink.
 * @param WP_Post $post      The post object.
 * @return string
 */
function douxir_docs_permalink( $post_link, $post ) {
	if ( is_object( $post ) && 'custom_docs' === $post->post_type ) {

		$terms = wp_get_object_terms(
			$post->ID,
			'doc_category',
			array(
				'orderby' => 'parent',
				'order'   => 'DESC',
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

			$term = $terms[0];
			$slug = $term->slug;

			// Build the full hierarchy.
			if ( $term->parent ) {
				$parents = get_ancestors( $term->term_id, 'doc_category' );
				$parents = array_reverse( $parents );

				foreach ( $parents as $parent_id ) {
					$parent = get_term( $parent_id, 'doc_category' );
					if ( ! is_wp_error( $parent ) ) {
						$slug = $parent->slug . '/' . $slug;
					}
				}
			}

			return str_replace( '%doc_category%', $slug, $post_link );
		}

		// Fallback.
		return str_replace( '%doc_category%', 'uncategorized', $post_link );
	}

	return $post_link;
}
add_filter( 'post_type_link', 'douxir_docs_permalink', 10, 2 );

/**
 * AJAX handler for the public documentation search box.
 *
 * Read-only search of published documents, available to visitors. No nonce
 * is used on purpose so cached pages keep a working search box.
 *
 * @return void
 */
function douxir_search_docs_handler() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public, read-only search of published content.
	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

	$limit = function_exists( 'douxir_get_setting' ) ? absint( douxir_get_setting( 'search_results', 10 ) ) : 10;
	$limit = min( 25, max( 3, $limit ) );

	$args = array(
		'post_type'      => 'custom_docs',
		'posts_per_page' => $limit,
		's'              => $query,
		'post_status'    => 'publish',
	);

	$search_query = new WP_Query( $args );
	$results      = array();

	if ( $search_query->have_posts() ) {
		while ( $search_query->have_posts() ) {
			$search_query->the_post();
			$results[] = array(
				'title' => html_entity_decode( wp_strip_all_tags( get_the_title() ), ENT_QUOTES, 'UTF-8' ),
				'link'  => esc_url_raw( get_permalink() ),
			);
		}
		wp_reset_postdata();
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_douxir_search_docs', 'douxir_search_docs_handler' );
add_action( 'wp_ajax_nopriv_douxir_search_docs', 'douxir_search_docs_handler' );
