<?php
/**
 * Main Douxir plugin class.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Douxir_Plugin' ) ) {

	/**
	 * Main plugin class.
	 */
	class Douxir_Plugin {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->includes();
			$this->init_shortcodes();
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( DOUXIR_FILE ), array( $this, 'action_links' ) );
		}

		/**
		 * Load plugin files.
		 *
		 * @return void
		 */
		public function includes() {
			$files = array(
				'/inc/class-douxir-settings.php',
				'/inc/doc-functions.php',
				'/inc/class-douxir-frontend.php',
				'/inc/class-douxir-shortcodes.php',
				'/inc/class-douxir-blocks.php',
				'/inc/class-douxir-admin.php',
			);
			foreach ( $files as $relative ) {
				$path = DOUXIR_PATH . $relative;
				if ( file_exists( $path ) ) {
					require_once $path;
				}
			}
		}

		/**
		 * Initialize shortcodes.
		 *
		 * @return void
		 */
		public function init_shortcodes() {
			if ( class_exists( 'Douxir_Shortcodes' ) ) {
				new Douxir_Shortcodes();
			}
		}


		/**
		 * Enqueue front-end assets.
		 *
		 * @return void
		 */
		public function enqueue_assets() {
			wp_enqueue_style( 'douxir-main', DOUXIR_URL . '/assets/css/style.css', array(), DOUXIR_VERSION );
			wp_enqueue_script( 'douxir-main', DOUXIR_URL . '/assets/js/min.js', array( 'jquery' ), DOUXIR_VERSION, true );
			$settings = function_exists( 'douxir_get_settings' ) ? douxir_get_settings() : array();
			wp_localize_script(
				'douxir-main',
				'douxir_data',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'plugin_url'  => DOUXIR_URL,
					'no_results'  => __( 'No results found', 'douxir' ),
					'feedback_nonce' => wp_create_nonce( 'douxir_feedback' ),
					'already_voted'  => __( 'You have already voted on this document.', 'douxir' ),
					'thanks'         => __( 'Thank you for your feedback.', 'douxir' ),
					'search_results' => isset( $settings['search_results'] ) ? (int) $settings['search_results'] : 10,
				)
			);
		}

		/**
		 * Add an Open Douxir link on the Plugins screen.
		 *
		 * @param array $links Plugin action links.
		 * @return array
		 */
		public function action_links( $links ) {
			array_unshift(
				$links,
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit.php?post_type=custom_docs&page=douxir' ) ),
					esc_html__( 'Open Douxir', 'douxir' )
				)
			);
			if ( current_user_can( 'manage_options' ) ) {
				array_unshift(
					$links,
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( admin_url( 'edit.php?post_type=custom_docs&page=douxir#settings' ) ),
						esc_html__( 'Settings', 'douxir' )
					)
				);
			}
			return $links;
		}

		/**
		 * Flush rewrite rules on activation so document URLs work straight away.
		 *
		 * @return void
		 */
		public static function activate() {
			douxir_register_content_types();
			flush_rewrite_rules();
		}

		/**
		 * Flush rewrite rules on deactivation.
		 *
		 * @return void
		 */
		public static function deactivate() {
			flush_rewrite_rules();
		}
	}
}
