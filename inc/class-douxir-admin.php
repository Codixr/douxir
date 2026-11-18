<?php
/**
 * Douxir admin screens and REST endpoints for the Douxir admin screen.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin screens and REST endpoints.
 */
class Douxir_Admin {

	/**
	 * Register admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'adjust_admin_menu' ), 999 );
		add_filter( 'parent_file', array( $this, 'set_active_parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'set_active_submenu_item' ) );
		add_action( 'load-edit.php', array( $this, 'redirect_list_to_app' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_icon_style' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Highlight the parent menu when on the Douxir admin screen.
	 *
	 * @param string $parent_file Parent file.
	 * @return string
	 */
	public function set_active_parent_menu( $parent_file ) {
		global $current_screen;
		if ( $current_screen && in_array( $current_screen->id, array( 'custom_docs_page_douxir', 'custom_docs_page_douxir-settings' ), true ) ) {
			return 'edit.php?post_type=custom_docs';
		}
		return $parent_file;
	}

	/**
	 * Highlight the the Douxir admin screen submenu item when on the Douxir admin screen.
	 *
	 * @param string $submenu_file Submenu file.
	 * @return string
	 */
	public function set_active_submenu_item( $submenu_file ) {
		global $current_screen;
		if ( $current_screen && 'custom_docs_page_douxir-settings' === $current_screen->id ) {
			return 'douxir-settings';
		}
		if ( $current_screen && 'custom_docs_page_douxir' === $current_screen->id ) {
			return 'douxir';
		}
		return $submenu_file;
	}

	/**
	 * Send the default documents list to the Douxir admin screen.
	 *
	 * The trash view stays reachable so trashed documents can be restored.
	 *
	 * @return void
	 */
	public function redirect_list_to_app() {
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing, no data is changed.
		$post_status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing, no data is changed.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( $screen && 'custom_docs' === $screen->post_type && in_array( $page, array( 'douxir-studio', 'docixr-studio', 'docixr' ), true ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=custom_docs&page=douxir' ) );
			exit;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing, no data is changed.
		if ( $screen && 'custom_docs' === $screen->post_type && empty( $_GET['page'] ) && 'trash' !== $post_status ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=custom_docs&page=douxir' ) );
			exit;
		}
	}

	/**
	 * Register the the Douxir admin screen admin page.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=custom_docs',
			__( 'Douxir', 'douxir' ),
			__( 'All Docs', 'douxir' ),
			'edit_posts',
			'douxir',
			array( $this, 'render_admin_manager_page' ),
			0
		);

		add_submenu_page(
			'edit.php?post_type=custom_docs',
			__( 'Douxir Settings', 'douxir' ),
			__( 'Settings', 'douxir' ),
			'manage_options',
			'douxir-settings',
			array( $this, 'render_admin_manager_page' )
		);
	}

	/**
	 * Keep only the Douxir admin screen in the Douxir submenu.
	 *
	 * @return void
	 */
	public function adjust_admin_menu() {
		global $submenu;

		remove_submenu_page( 'edit.php?post_type=custom_docs', 'edit.php?post_type=custom_docs' );
		remove_submenu_page( 'edit.php?post_type=custom_docs', 'post-new.php?post_type=custom_docs' );
		remove_submenu_page( 'edit.php?post_type=custom_docs', 'edit-tags.php?taxonomy=doc_category&post_type=custom_docs' );

		// Remove other items so only the Douxir admin screen remains in the submenu.
		if ( ! empty( $submenu['edit.php?post_type=custom_docs'] ) ) {
			foreach ( $submenu['edit.php?post_type=custom_docs'] as $key => $item ) {
				if ( ! in_array( $item[2], array( 'douxir', 'douxir-settings' ), true ) ) {
					unset( $submenu['edit.php?post_type=custom_docs'][ $key ] );
				}
			}
		}
	}

	/**
	 * Render the container where the the Douxir admin screen app mounts.
	 *
	 * @return void
	 */
	public function render_admin_manager_page() {
		?>
		<h1 class="screen-reader-text"><?php esc_html_e( 'Douxir', 'douxir' ); ?></h1>
		<div id="douxir-admin-root">
			<div class="douxir-loading" role="status" aria-live="polite">
				<span class="douxir-loading__spinner" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Loading Douxir...', 'douxir' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets on the Douxir screens only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to decide which assets to load.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $hook, array( 'custom_docs_page_douxir', 'custom_docs_page_douxir-settings' ), true ) && ! in_array( $page, array( 'douxir', 'douxir-settings' ), true ) ) {
			return;
		}

		$css_file  = DOUXIR_PATH . '/assets/css/admin-app.css';
		$admin_css_file = DOUXIR_PATH . '/assets/css/admin.css';
		$js_file   = DOUXIR_PATH . '/assets/dist/admin-app.js';
		$asset_file = DOUXIR_PATH . '/assets/dist/admin-app.asset.php';
		$asset     = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ),
			'version'      => DOUXIR_VERSION,
		);
		$deps      = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array( 'wp-element' );
		$js_ver    = isset( $asset['version'] ) ? $asset['version'] : DOUXIR_VERSION;

		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'douxir-admin-app',
				DOUXIR_URL . '/assets/css/admin-app.css',
				array(),
				(string) filemtime( $css_file )
			);
		}

		// Loaded after the app styles so the WordPress admin palette wins.
		$admin_css_ver = file_exists( $admin_css_file ) ? (string) filemtime( $admin_css_file ) : DOUXIR_VERSION;
		wp_enqueue_style( 'douxir-admin', DOUXIR_URL . '/assets/css/admin.css', file_exists( $css_file ) ? array( 'douxir-admin-app' ) : array(), $admin_css_ver );

		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'douxir-admin-app',
				DOUXIR_URL . '/assets/dist/admin-app.js',
				$deps,
				$js_ver,
				true
			);

			// Preload initial data so the app renders without an extra request.
			$initial_data = $this->get_admin_data();

			wp_localize_script(
				'douxir-admin-app',
				'douxirAdminData',
				array(
					'restUrl'       => esc_url_raw( rest_url( 'douxir/v1/admin/' ) ),
					'nonce'         => wp_create_nonce( 'wp_rest' ),
					'adminUrl'      => esc_url_raw( admin_url() ),
					'newPostUrl'    => esc_url_raw( admin_url( 'post-new.php?post_type=custom_docs' ) ),
					'categoriesUrl' => esc_url_raw( admin_url( 'edit-tags.php?taxonomy=doc_category&post_type=custom_docs' ) ),
					'initialData'   => $initial_data,
					'currentUser'   => wp_get_current_user()->display_name,
					'logoUrl'       => esc_url_raw( DOUXIR_URL . '/assets/images/douxir-icon.png' ),
					'canManageCats'      => current_user_can( 'manage_categories' ),
					'canManageSettings'  => current_user_can( 'manage_options' ),
					'settings'           => current_user_can( 'manage_options' ) && function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : (object) array(),
					'fontOptions'        => function_exists( 'douxir_allowed_fonts' ) ? array_keys( douxir_allowed_fonts() ) : array(),
					'iconOptions'        => function_exists( 'douxir_allowed_icons' ) ? douxir_allowed_icons() : array(),
					'pages'              => $this->get_pages_for_settings(),
					'view'               => 'douxir-settings' === $page ? 'settings' : 'dashboard',
				)
			);
		}
	}

	/**
	 * Register REST API routes
	 */

	/**
	 * Load the compact admin-menu icon on every admin screen.
	 *
	 * @return void
	 */
	public function enqueue_menu_icon_style() {
		wp_enqueue_style(
			'douxir-admin-menu',
			DOUXIR_URL . '/assets/css/admin-menu.css',
			array(),
			DOUXIR_VERSION
		);
	}

	public function register_rest_routes() {
		register_rest_route(
			'douxir/v1',
			'/admin/data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/doc',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_doc' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/quick-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_doc' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/doc/(?P<id>\d+)',
			array(
				'methods'             => array( 'DELETE', 'POST' ),
				'callback'            => array( $this, 'rest_trash_doc' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/category',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_category' ),
				'permission_callback' => array( $this, 'check_category_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/category/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_category' ),
				'permission_callback' => array( $this, 'check_category_permission' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/reorder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_reorder_docs' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/reorder-categories',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_reorder_categories' ),
				'permission_callback' => array( $this, 'check_category_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/category/(?P<id>\d+)/update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_update_category' ),
				'permission_callback' => array( $this, 'check_category_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_settings' ),
					'permission_callback' => array( $this, 'check_settings_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_save_settings' ),
					'permission_callback' => array( $this, 'check_settings_permission' ),
				),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_export' ),
				'permission_callback' => array( $this, 'check_settings_permission' ),
			)
		);

		register_rest_route(
			'douxir/v1',
			'/admin/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_import' ),
				'permission_callback' => array( $this, 'check_settings_permission' ),
			)
		);
	}

	/**
	 * Base permission check for all the Douxir admin screen routes.
	 *
	 * Per-document checks are done again inside each write callback.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission check for category routes.
	 *
	 * @return bool
	 */
	public function check_category_permission() {
		return current_user_can( 'edit_posts' ) && current_user_can( 'manage_categories' );
	}

	/**
	 * Permission check for settings, import and export.
	 *
	 * @return bool
	 */
	public function check_settings_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Published pages for the documentation homepage dropdown.
	 *
	 * @return array
	 */
	private function get_pages_for_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}
		$pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
				'post_status' => 'publish',
				'number'      => 200,
			)
		);
		$out = array(
			array(
				'id'    => 0,
				'title' => __( '— Use the default documentation URL —', 'douxir' ),
			),
		);
		if ( empty( $pages ) ) {
			return $out;
		}
		foreach ( $pages as $page ) {
			$out[] = array(
				'id'    => (int) $page->ID,
				'title' => $page->post_title ? $page->post_title : __( '(No title)', 'douxir' ),
			);
		}
		return $out;
	}

	/**
	 * Return the the Douxir admin screen data.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_data() {
		return rest_ensure_response( $this->get_admin_data() );
	}

	/**
	 * Build the categories and nested documents tree.
	 *
	 * @return array
	 */
	public function get_admin_data() {
		$categories_terms = get_terms(
			array(
				'taxonomy'   => 'doc_category',
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);

		// Sort categories by the saved drag-and-drop order.
		$cat_order = douxir_get_categories_order();
		if ( ! empty( $cat_order ) && is_array( $cat_order ) && ! is_wp_error( $categories_terms ) ) {
			usort(
				$categories_terms,
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

		$all_posts = get_posts(
			array(
				'post_type'      => 'custom_docs',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$total_docs     = 0;
		$published_docs = 0;
		$draft_docs     = 0;
		$pro_docs       = 0;

		$posts_by_id = array();
		foreach ( $all_posts as $post ) {
			// Do not expose drafts or private documents the current user cannot read.
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}

			++$total_docs;

			$is_pro = (bool) get_post_meta( $post->ID, 'documentation_pro', true );
			if ( function_exists( 'get_field' ) ) {
				$acf_pro = get_field( 'documentation_pro', $post->ID );
				if ( ! empty( $acf_pro ) ) {
					$is_pro = true;
				}
			}

			if ( 'publish' === $post->post_status ) {
				++$published_docs;
			} else {
				++$draft_docs;
			}

			if ( $is_pro ) {
				++$pro_docs;
			}

			$terms  = wp_get_post_terms( $post->ID, 'doc_category', array( 'fields' => 'ids' ) );
			$author = get_the_author_meta( 'display_name', $post->post_author );

			$posts_by_id[ $post->ID ] = array(
				'id'               => $post->ID,
				'title'            => $post->post_title ? $post->post_title : __( '(No title)', 'douxir' ),
				'slug'             => $post->post_name,
				'status'           => $post->post_status,
				'parent_id'        => (int) $post->post_parent,
				'menu_order'       => (int) $post->menu_order,
				'pro'              => $is_pro,
				'permalink'        => get_permalink( $post->ID ),
				'edit_url'         => get_edit_post_link( $post->ID, 'raw' ),
				'date'             => get_the_date( 'M j, Y', $post ),
				'modified'         => get_the_modified_date( 'M j, Y, g:i a', $post ),
				'author'           => $author ? $author : __( 'Admin', 'douxir' ),
				'categories'       => ! is_wp_error( $terms ) ? array_map( 'intval', $terms ) : array(),
				'content_raw'      => $post->post_content,
				'content_rendered' => apply_filters( 'the_content', $post->post_content ),  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
				'children'         => array(),
			);
		}

		// Build the parent-child tree mapping.
		$top_level_by_cat = array();
		$uncategorized    = array();

		foreach ( $posts_by_id as $id => $doc ) {
			if ( $doc['parent_id'] > 0 && isset( $posts_by_id[ $doc['parent_id'] ] ) ) {
				$posts_by_id[ $doc['parent_id'] ]['children'][] = &$posts_by_id[ $id ];
			}
		}

		// Categories structure.
		$categories_data = array();
		if ( ! is_wp_error( $categories_terms ) ) {
			foreach ( $categories_terms as $cat ) {
				$cat_docs = array();
				foreach ( $posts_by_id as $id => $doc ) {
					if ( 0 === $doc['parent_id'] && in_array( $cat->term_id, $doc['categories'], true ) ) {
						$cat_docs[] = $posts_by_id[ $id ];
					}
				}

				$categories_data[] = array(
					'id'          => $cat->term_id,
					'name'        => $cat->name,
					'slug'        => $cat->slug,
					'parent'      => $cat->parent,
					'description' => $cat->description,
					'icon'        => (string) get_term_meta( $cat->term_id, 'douxir_icon', true ),
					'count'       => count( $cat_docs ),
					'docs'        => $cat_docs,
				);
			}
		}

		// Check for docs without a category.
		foreach ( $posts_by_id as $id => $doc ) {
			if ( 0 === $doc['parent_id'] && empty( $doc['categories'] ) ) {
				$uncategorized[] = $posts_by_id[ $id ];
			}
		}

		if ( ! empty( $uncategorized ) ) {
			$categories_data[] = array(
				'id'          => 0,
				'name'        => __( 'Uncategorized', 'douxir' ),
				'slug'        => 'uncategorized',
				'parent'      => 0,
				'description' => __( 'Documents without any category assigned', 'douxir' ),
				'icon'        => '',
				'count'       => count( $uncategorized ),
				'docs'        => $uncategorized,
			);
		}

		return array(
			'stats'      => array(
				'total_docs'       => $total_docs,
				'total_categories' => ( ! is_wp_error( $categories_terms ) ) ? count( $categories_terms ) : 0,
				'pro_docs'         => $pro_docs,
				'published_docs'   => $published_docs,
				'draft_docs'       => $draft_docs,
			),
			'categories' => $categories_data,
			'all_docs'   => array_values( $posts_by_id ),
		);
	}

	/**
	 * Create a document via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_create_doc( $request ) {
		$params    = $request->get_json_params();
		$params    = is_array( $params ) ? $params : array();
		$title     = ! empty( $params['title'] ) ? sanitize_text_field( $params['title'] ) : __( 'New Document', 'douxir' );
		$cat_id    = ! empty( $params['category_id'] ) ? $this->sanitize_category_id( $params['category_id'] ) : 0;
		$parent_id = ! empty( $params['parent_id'] ) ? $this->sanitize_parent_id( $params['parent_id'] ) : 0;
		$status    = ! empty( $params['status'] ) ? sanitize_key( $params['status'] ) : 'publish';
		$is_pro     = ! empty( $params['pro'] ) ? 1 : 0;
		$content    = ! empty( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';
		$menu_order = isset( $params['menu_order'] ) ? intval( $params['menu_order'] ) : 0;
		$status = $this->sanitize_doc_status( $status );
		if ( false === $status ) {
			$status = 'draft';
		}

		// Users who cannot publish get a pending document instead.
		if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_posts' ) ) {
			$status = 'pending';
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'    => 'custom_docs',
				'post_status'  => $status,
				'post_parent'  => $parent_id,
				'post_content' => $content,
				'menu_order'   => $menu_order,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'cannot_create', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		if ( $cat_id > 0 ) {
			wp_set_post_terms( $post_id, array( $cat_id ), 'doc_category' );
		}

		update_post_meta( $post_id, 'documentation_pro', $is_pro );
		if ( function_exists( 'update_field' ) ) {
			update_field( 'documentation_pro', $is_pro, $post_id );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'post_id' => $post_id,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Quick update document details.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_update_doc( $request ) {
		$params  = $request->get_json_params();
		$params  = is_array( $params ) ? $params : array();
		$post_id = ! empty( $params['id'] ) ? intval( $params['id'] ) : 0;

		if ( ! $post_id || 'custom_docs' !== get_post_type( $post_id ) ) {
			return new WP_Error( 'invalid_id', __( 'Invalid document ID.', 'douxir' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not allowed to edit this document.', 'douxir' ), array( 'status' => 403 ) );
		}

		$update_data = array( 'ID' => $post_id );

		if ( isset( $params['title'] ) ) {
			$update_data['post_title'] = sanitize_text_field( $params['title'] );
		}
		if ( isset( $params['slug'] ) ) {
			$update_data['post_name'] = sanitize_title( $params['slug'] );
		}
		if ( isset( $params['status'] ) ) {
			$status = $this->sanitize_doc_status( $params['status'] );
			if ( false === $status ) {
				return new WP_Error( 'invalid_status', __( 'Invalid document status.', 'douxir' ), array( 'status' => 400 ) );
			}
			$update_data['post_status'] = $status;
			if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_post', $post_id ) ) {
				return new WP_Error( 'forbidden', __( 'You are not allowed to publish this document.', 'douxir' ), array( 'status' => 403 ) );
			}
		}
		if ( isset( $params['parent_id'] ) ) {
			$update_data['post_parent'] = $this->sanitize_parent_id( $params['parent_id'], $post_id );
		}
		if ( isset( $params['menu_order'] ) ) {
			$update_data['menu_order'] = intval( $params['menu_order'] );
		}
		if ( isset( $params['content'] ) ) {
			$update_data['post_content'] = wp_kses_post( $params['content'] );
		}

		$updated = wp_update_post( $update_data );
		if ( is_wp_error( $updated ) ) {
			return new WP_Error( 'update_failed', $updated->get_error_message(), array( 'status' => 500 ) );
		}

		if ( isset( $params['category_id'] ) ) {
			$cat_id = $this->sanitize_category_id( $params['category_id'] );
			if ( $cat_id > 0 ) {
				wp_set_post_terms( $post_id, array( $cat_id ), 'doc_category' );
			} else {
				wp_set_post_terms( $post_id, array(), 'doc_category' );
			}
		}

		if ( isset( $params['pro'] ) ) {
			$is_pro = ! empty( $params['pro'] ) ? 1 : 0;
			update_post_meta( $post_id, 'documentation_pro', $is_pro );
			if ( function_exists( 'update_field' ) ) {
				update_field( 'documentation_pro', $is_pro, $post_id );
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'post_id' => $post_id,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Trash a document via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_trash_doc( $request ) {
		$post_id = intval( $request['id'] );
		if ( ! $post_id || 'custom_docs' !== get_post_type( $post_id ) ) {
			return new WP_Error( 'invalid_id', __( 'Invalid document ID.', 'douxir' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'You are not allowed to delete this document.', 'douxir' ), array( 'status' => 403 ) );
		}

		$result = wp_trash_post( $post_id );
		if ( ! $result ) {
			return new WP_Error( 'trash_failed', __( 'Failed to trash post.', 'douxir' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Create a category via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_create_category( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$name        = ! empty( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$slug        = ! empty( $params['slug'] ) ? sanitize_title( $params['slug'] ) : '';
		$parent      = ! empty( $params['parent'] ) ? $this->sanitize_category_id( $params['parent'] ) : 0;
		$description = isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';
		$icon        = isset( $params['icon'] ) && function_exists( 'douxir_sanitize_icon' ) ? douxir_sanitize_icon( $params['icon'] ) : '';

		if ( empty( $name ) ) {
			return new WP_Error( 'empty_name', __( 'Category name is required.', 'douxir' ), array( 'status' => 400 ) );
		}

		$term = wp_insert_term(
			$name,
			'doc_category',
			array(
				'slug'        => $slug,
				'parent'      => $parent,
				'description' => $description,
			)
		);

		if ( is_wp_error( $term ) ) {
			return new WP_Error( 'category_failed', $term->get_error_message(), array( 'status' => 500 ) );
		}

		if ( $icon && ! empty( $term['term_id'] ) ) {
			update_term_meta( (int) $term['term_id'], 'douxir_icon', $icon );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'term'    => $term,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Delete a category via REST.
	 *
	 * Documents in the category are not deleted. WordPress only removes the
	 * link between the documents and the term, so documents with no other
	 * category show under "Uncategorized". Child categories move up to the
	 * deleted category's parent.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_delete_category( $request ) {
		$term_id = absint( $request['id'] );
		$term    = get_term( $term_id, 'doc_category' );

		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'category_not_found', __( 'Category not found.', 'douxir' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'delete_term', $term_id ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You are not allowed to delete this category.', 'douxir' ), array( 'status' => 403 ) );
		}

		$deleted = wp_delete_term( $term_id, 'doc_category' );

		if ( is_wp_error( $deleted ) ) {
			return new WP_Error( 'category_delete_failed', $deleted->get_error_message(), array( 'status' => 500 ) );
		}

		if ( ! $deleted ) {
			return new WP_Error( 'category_delete_failed', __( 'The category could not be deleted.', 'douxir' ), array( 'status' => 500 ) );
		}

		// Drop the deleted category from the saved order.
		$cat_order = douxir_get_categories_order();
		if ( ! empty( $cat_order ) && is_array( $cat_order ) ) {
			$cat_order = array_values(
				array_filter(
					array_map( 'intval', $cat_order ),
					function ( $id ) use ( $term_id ) {
						return $id !== $term_id;
					}
				)
			);
			update_option( 'douxir_categories_order', $cat_order );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Bulk reorder documents.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_reorder_docs( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$orders = ! empty( $params['orders'] ) ? (array) $params['orders'] : array();

		foreach ( $orders as $item ) {
			if ( ! empty( $item['id'] ) ) {
				// Only reorder documents the current user can edit.
				if ( 'custom_docs' !== get_post_type( intval( $item['id'] ) ) || ! current_user_can( 'edit_post', intval( $item['id'] ) ) ) {
					continue;
				}
				wp_update_post(
					array(
						'ID'         => intval( $item['id'] ),
						'menu_order' => isset( $item['menu_order'] ) ? intval( $item['menu_order'] ) : 0,
					)
				);
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Bulk reorder categories.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_reorder_categories( $request ) {
		$params  = $request->get_json_params();
		$params  = is_array( $params ) ? $params : array();
		$orders  = ! empty( $params['orders'] ) ? (array) $params['orders'] : array();
		$cat_ids = array();
		foreach ( $orders as $term_id ) {
			$term_id = $this->sanitize_category_id( $term_id );
			if ( $term_id > 0 && ! in_array( $term_id, $cat_ids, true ) ) {
				$cat_ids[] = $term_id;
			}
		}

		if ( ! empty( $cat_ids ) ) {
			update_option( 'douxir_categories_order', $cat_ids );

			// Also update the 'order' term meta for compatibility with other tools.
			foreach ( $cat_ids as $index => $term_id ) {
				if ( $term_id > 0 ) {
					update_term_meta( $term_id, 'order', $index );
				}
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Allow only recognised document statuses.
	 *
	 * @param string $status Requested status.
	 * @return string|false Sanitized status, or false when the value is not allowed.
	 */
	private function sanitize_doc_status( $status ) {
		$status = sanitize_key( $status );
		$allowed = array( 'publish', 'draft', 'pending', 'private' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		return $status;
	}

	/**
	 * Allow a parent only when it is a documentation document (and not the same post).
	 *
	 * @param int $parent_id Requested parent ID.
	 * @param int $post_id   Current document ID, if any.
	 * @return int
	 */
	private function sanitize_parent_id( $parent_id, $post_id = 0 ) {
		$parent_id = absint( $parent_id );
		if ( $parent_id <= 0 ) {
			return 0;
		}
		if ( $post_id && (int) $parent_id === (int) $post_id ) {
			return 0;
		}
		if ( 'custom_docs' !== get_post_type( $parent_id ) ) {
			return 0;
		}
		return $parent_id;
	}

	/**
	 * Allow a term ID only when it belongs to the documentation category taxonomy.
	 *
	 * @param int $term_id Requested term ID.
	 * @return int
	 */
	private function sanitize_category_id( $term_id ) {
		$term_id = absint( $term_id );
		if ( $term_id <= 0 ) {
			return 0;
		}
		$term = get_term( $term_id, 'doc_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return 0;
		}
		return $term_id;
	}


	/**
	 * Return current settings and helper lists.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_settings() {
		return rest_ensure_response(
			array(
				'settings'    => douxir_get_settings(),
				'defaults'    => douxir_default_settings(),
				'fontOptions' => array_keys( douxir_allowed_fonts() ),
				'iconOptions' => douxir_allowed_icons(),
				'pages'       => $this->get_pages_for_settings(),
				'homeUrl'     => douxir_get_docs_home_url(),
			)
		);
	}

	/**
	 * Save plugin settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_save_settings( $request ) {
		$params   = $request->get_json_params();
		$params   = is_array( $params ) ? $params : array();
		$settings = douxir_save_settings( $params );
		return rest_ensure_response(
			array(
				'success'  => true,
				'settings' => $settings,
				'homeUrl'  => douxir_get_docs_home_url(),
			)
		);
	}

	/**
	 * Update a category's name, description, parent or icon.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_update_category( $request ) {
		$term_id = absint( $request['id'] );
		$term    = get_term( $term_id, 'doc_category' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'category_not_found', __( 'Category not found.', 'douxir' ), array( 'status' => 404 ) );
		}

		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();
		$update = array();

		if ( isset( $params['name'] ) ) {
			$name = sanitize_text_field( $params['name'] );
			if ( '' === $name ) {
				return new WP_Error( 'empty_name', __( 'Category name is required.', 'douxir' ), array( 'status' => 400 ) );
			}
			$update['name'] = $name;
		}
		if ( isset( $params['slug'] ) ) {
			$update['slug'] = sanitize_title( $params['slug'] );
		}
		if ( isset( $params['description'] ) ) {
			$update['description'] = sanitize_textarea_field( $params['description'] );
		}
		if ( isset( $params['parent'] ) ) {
			$parent = $this->sanitize_category_id( $params['parent'] );
			if ( $parent === $term_id ) {
				$parent = 0;
			}
			$update['parent'] = $parent;
		}

		if ( ! empty( $update ) ) {
			$updated = wp_update_term( $term_id, 'doc_category', $update );
			if ( is_wp_error( $updated ) ) {
				return new WP_Error( 'category_update_failed', $updated->get_error_message(), array( 'status' => 500 ) );
			}
		}

		if ( isset( $params['icon'] ) ) {
			$icon = douxir_sanitize_icon( $params['icon'] );
			if ( $icon ) {
				update_term_meta( $term_id, 'douxir_icon', $icon );
			} else {
				delete_term_meta( $term_id, 'douxir_icon' );
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->get_admin_data(),
			)
		);
	}

	/**
	 * Export documents and categories as JSON.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_export() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'doc_category',
				'hide_empty' => false,
				'number'     => 500,
			)
		);
		$categories = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$parent_slug = '';
				if ( $term->parent ) {
					$parent = get_term( $term->parent, 'doc_category' );
					if ( $parent && ! is_wp_error( $parent ) ) {
						$parent_slug = $parent->slug;
					}
				}
				$categories[] = array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'parent_slug' => $parent_slug,
					'description' => $term->description,
					'icon'        => (string) get_term_meta( $term->term_id, 'douxir_icon', true ),
				);
			}
		}

		$posts = get_posts(
			array(
				'post_type'      => 'custom_docs',
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => 2000,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$documents = array();
		foreach ( $posts as $post ) {
			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}
			$term_slugs  = wp_get_post_terms( $post->ID, 'doc_category', array( 'fields' => 'slugs' ) );
			$parent_slug = '';
			if ( $post->post_parent ) {
				$parent = get_post( $post->post_parent );
				if ( $parent && 'custom_docs' === $parent->post_type ) {
					$parent_slug = $parent->post_name;
				}
			}
			$documents[] = array(
				'title'          => $post->post_title,
				'slug'           => $post->post_name,
				'content'        => $post->post_content,
				'status'         => $post->post_status,
				'parent_slug'    => $parent_slug,
				'category_slugs' => ( ! is_wp_error( $term_slugs ) ) ? array_values( $term_slugs ) : array(),
				'menu_order'     => (int) $post->menu_order,
				'pro'            => (bool) get_post_meta( $post->ID, 'documentation_pro', true ),
			);
		}

		return rest_ensure_response(
			array(
				'plugin'     => 'douxir',
				'version'    => DOUXIR_VERSION,
				'categories' => $categories,
				'documents'  => $documents,
			)
		);
	}

	/**
	 * Import documents and categories from a Douxir export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_import( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		if ( isset( $params['payload'] ) && is_array( $params['payload'] ) ) {
			$params = $params['payload'];
		}

		if ( empty( $params['plugin'] ) || 'douxir' !== $params['plugin'] ) {
			return new WP_Error( 'invalid_export', __( 'This file is not a Douxir export.', 'douxir' ), array( 'status' => 400 ) );
		}

		$created_cats = 0;
		$created_docs = 0;
		$term_by_slug = array();

		$categories = isset( $params['categories'] ) && is_array( $params['categories'] ) ? $params['categories'] : array();
		for ( $pass = 0; $pass < 2; $pass++ ) {
			foreach ( $categories as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$name = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
				$slug = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : sanitize_title( $name );
				if ( '' === $name || '' === $slug ) {
					continue;
				}
				$existing = get_term_by( 'slug', $slug, 'doc_category' );
				if ( $existing ) {
					$term_by_slug[ $slug ] = (int) $existing->term_id;
					continue;
				}
				$parent_slug = isset( $item['parent_slug'] ) ? sanitize_title( $item['parent_slug'] ) : '';
				$parent_id   = ( $parent_slug && isset( $term_by_slug[ $parent_slug ] ) ) ? $term_by_slug[ $parent_slug ] : 0;
				if ( $parent_slug && ! $parent_id && 0 === $pass ) {
					continue;
				}
				$term = wp_insert_term(
					$name,
					'doc_category',
					array(
						'slug'        => $slug,
						'parent'      => $parent_id,
						'description' => isset( $item['description'] ) ? sanitize_textarea_field( $item['description'] ) : '',
					)
				);
				if ( is_wp_error( $term ) ) {
					continue;
				}
				$term_by_slug[ $slug ] = (int) $term['term_id'];
				if ( ! empty( $item['icon'] ) ) {
					update_term_meta( (int) $term['term_id'], 'douxir_icon', douxir_sanitize_icon( $item['icon'] ) );
				}
				++$created_cats;
			}
		}

		$documents    = isset( $params['documents'] ) && is_array( $params['documents'] ) ? $params['documents'] : array();
		$post_by_slug = array();
		for ( $pass = 0; $pass < 2; $pass++ ) {
			foreach ( $documents as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$title = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
				$slug  = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : sanitize_title( $title );
				if ( '' === $title || '' === $slug ) {
					continue;
				}
				if ( isset( $post_by_slug[ $slug ] ) ) {
					continue;
				}
				$existing = get_page_by_path( $slug, OBJECT, 'custom_docs' );
				if ( $existing ) {
					$post_by_slug[ $slug ] = (int) $existing->ID;
					continue;
				}
				$parent_slug = isset( $item['parent_slug'] ) ? sanitize_title( $item['parent_slug'] ) : '';
				$parent_id   = ( $parent_slug && isset( $post_by_slug[ $parent_slug ] ) ) ? $post_by_slug[ $parent_slug ] : 0;
				if ( $parent_slug && ! $parent_id && 0 === $pass ) {
					continue;
				}
				$status = isset( $item['status'] ) ? $this->sanitize_doc_status( $item['status'] ) : 'draft';
				if ( false === $status ) {
					$status = 'draft';
				}
				if ( in_array( $status, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_posts' ) ) {
					$status = 'pending';
				}
				$post_id = wp_insert_post(
					array(
						'post_title'   => $title,
						'post_name'    => $slug,
						'post_type'    => 'custom_docs',
						'post_status'  => $status,
						'post_parent'  => $parent_id,
						'post_content' => isset( $item['content'] ) ? wp_kses_post( $item['content'] ) : '',
						'menu_order'   => isset( $item['menu_order'] ) ? intval( $item['menu_order'] ) : 0,
					),
					true
				);
				if ( is_wp_error( $post_id ) || ! $post_id ) {
					continue;
				}
				$post_by_slug[ $slug ] = (int) $post_id;
				$cat_slugs = isset( $item['category_slugs'] ) && is_array( $item['category_slugs'] ) ? $item['category_slugs'] : array();
				$term_ids  = array();
				foreach ( $cat_slugs as $cat_slug ) {
					$cat_slug = sanitize_title( $cat_slug );
					if ( isset( $term_by_slug[ $cat_slug ] ) ) {
						$term_ids[] = $term_by_slug[ $cat_slug ];
					}
				}
				if ( $term_ids ) {
					wp_set_post_terms( $post_id, $term_ids, 'doc_category' );
				}
				$is_pro = ! empty( $item['pro'] ) ? 1 : 0;
				update_post_meta( $post_id, 'documentation_pro', $is_pro );
				++$created_docs;
			}
		}

		return rest_ensure_response(
			array(
				'success'            => true,
				'categories_created' => $created_cats,
				'documents_created'  => $created_docs,
				'data'               => $this->get_admin_data(),
			)
		);
	}

}

new Douxir_Admin();
