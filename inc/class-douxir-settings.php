<?php
/**
 * Douxir settings, appearance tokens and permalink helpers.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Default settings for a new install.
 *
 * @return array
 */
function douxir_default_settings() {
	return array(
		'hub_title'               => __( 'Documentation', 'douxir' ),
		'docs_slug'               => 'documentation',
		'include_category_in_url' => true,
		'docs_page_id'            => 0,
		'docs_per_page'           => 12,
		'archive_layout'          => 'grid',
		'archive_columns'         => 3,
		'archive_card_style'      => 'elevated',
		'archive_show_count'      => true,
		'archive_show_description'=> true,
		'show_sidebar'            => true,
		'sidebar_position'        => 'left',
		'show_toc'                => true,
		'show_breadcrumbs'        => true,
		'show_prev_next'          => true,
		'show_reading_time'       => true,
		'show_last_updated'       => true,
		'show_print'              => true,
		'show_feedback'           => true,
		'show_related'            => true,
		'related_count'           => 3,
		'show_search'             => true,
		'search_placeholder'      => __( 'Search documentation...', 'douxir' ),
		'search_results'          => 10,
		'color_primary'           => '#5B21B6',
		'color_text'              => '#0f172a',
		'color_background'        => '#ffffff',
		'color_link'              => '#7C3AED',
		'font_family'             => 'inherit',
		'font_size'               => 16,
		'container_width'         => 1200,
		'custom_css'              => '',
	);
}

/**
 * Allowed font-family stacks. No remote fonts are loaded.
 *
 * @return array
 */
function douxir_allowed_fonts() {
	return array(
		'inherit'   => 'inherit',
		'system'    => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
		'serif'     => 'Georgia, "Times New Roman", serif',
		'monospace' => 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
	);
}

/**
 * Allowed category icon keys.
 *
 * @return array
 */
function douxir_allowed_icons() {
	return array( 'book', 'folder', 'file', 'rocket', 'settings', 'star', 'life-buoy', 'code', 'lightbulb', 'shield', 'message', 'wrench' );
}

/**
 * Return saved settings merged with defaults.
 *
 * @return array
 */
function douxir_get_settings() {
	$saved = get_option( 'douxir_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( douxir_default_settings(), $saved );
}

/**
 * Return one setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback if missing.
 * @return mixed
 */
function douxir_get_setting( $key, $default = null ) {
	$settings = douxir_get_settings();
	if ( array_key_exists( $key, $settings ) ) {
		return $settings[ $key ];
	}
	return $default;
}

/**
 * Documentation base slug (path segment only).
 *
 * @return string
 */
function douxir_get_docs_slug() {
	$slug = sanitize_title( (string) douxir_get_setting( 'docs_slug', 'documentation' ) );
	return $slug ? $slug : 'documentation';
}

/**
 * Public URL of the documentation homepage.
 *
 * @return string
 */
function douxir_get_docs_home_url() {
	$page_id = absint( douxir_get_setting( 'docs_page_id', 0 ) );
	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}
	return home_url( '/' . douxir_get_docs_slug() . '/' );
}

/**
 * Sanitize a CSS color to a 6-digit hex value.
 *
 * @param string $color  Raw color.
 * @param string $fallback Fallback hex.
 * @return string
 */
function douxir_sanitize_hex_color( $color, $fallback = '#000000' ) {
	$color = sanitize_hex_color( $color );
	return $color ? $color : $fallback;
}

/**
 * Sanitize custom CSS. Strips tags and dangerous constructs.
 *
 * @param string $css Raw CSS.
 * @return string
 */
function douxir_sanitize_css( $css ) {
	$css = is_string( $css ) ? $css : '';
	$css = wp_strip_all_tags( $css );
	$css = preg_replace( '/expression\s*\(/i', '', $css );
	$css = preg_replace( '/javascript\s*:/i', '', $css );
	$css = preg_replace( '/@import/i', '', $css );
	$css = preg_replace( '/behavior\s*:/i', '', $css );
	$css = preg_replace( '/-moz-binding/i', '', $css );
	return is_string( $css ) ? $css : '';
}

/**
 * Sanitize a full settings array.
 *
 * @param array $input Raw settings.
 * @return array
 */
function douxir_sanitize_settings( $input ) {
	$defaults = douxir_default_settings();
	$input    = is_array( $input ) ? $input : array();
	$fonts    = douxir_allowed_fonts();
	$out      = $defaults;

	if ( isset( $input['hub_title'] ) ) {
		$out['hub_title'] = sanitize_text_field( $input['hub_title'] );
		if ( '' === $out['hub_title'] ) {
			$out['hub_title'] = $defaults['hub_title'];
		}
	}

	if ( isset( $input['docs_slug'] ) ) {
		$slug = sanitize_title( $input['docs_slug'] );
		$out['docs_slug'] = $slug ? $slug : 'documentation';
	}

	$out['include_category_in_url'] = ! empty( $input['include_category_in_url'] );
	$out['docs_page_id']            = isset( $input['docs_page_id'] ) ? absint( $input['docs_page_id'] ) : 0;
	if ( $out['docs_page_id'] && 'page' !== get_post_type( $out['docs_page_id'] ) ) {
		$out['docs_page_id'] = 0;
	}

	$per_page             = isset( $input['docs_per_page'] ) ? absint( $input['docs_per_page'] ) : 12;
	$out['docs_per_page'] = min( 50, max( 1, $per_page ) );

	$layout = isset( $input['archive_layout'] ) ? sanitize_key( $input['archive_layout'] ) : 'grid';
	$out['archive_layout'] = in_array( $layout, array( 'grid', 'list' ), true ) ? $layout : 'grid';

	$columns                 = isset( $input['archive_columns'] ) ? absint( $input['archive_columns'] ) : 3;
	$out['archive_columns']  = min( 4, max( 1, $columns ) );

	$card = isset( $input['archive_card_style'] ) ? sanitize_key( $input['archive_card_style'] ) : 'elevated';
	$out['archive_card_style'] = in_array( $card, array( 'simple', 'bordered', 'elevated' ), true ) ? $card : 'elevated';

	$out['archive_show_count']       = ! empty( $input['archive_show_count'] );
	$out['archive_show_description'] = ! empty( $input['archive_show_description'] );
	$out['show_sidebar']             = ! empty( $input['show_sidebar'] );

	$pos = isset( $input['sidebar_position'] ) ? sanitize_key( $input['sidebar_position'] ) : 'left';
	$out['sidebar_position'] = in_array( $pos, array( 'left', 'right' ), true ) ? $pos : 'left';

	$out['show_toc']           = ! empty( $input['show_toc'] );
	$out['show_breadcrumbs']   = ! empty( $input['show_breadcrumbs'] );
	$out['show_prev_next']     = ! empty( $input['show_prev_next'] );
	$out['show_reading_time']  = ! empty( $input['show_reading_time'] );
	$out['show_last_updated']  = ! empty( $input['show_last_updated'] );
	$out['show_print']         = ! empty( $input['show_print'] );
	$out['show_feedback']      = ! empty( $input['show_feedback'] );
	$out['show_related']       = ! empty( $input['show_related'] );

	$related               = isset( $input['related_count'] ) ? absint( $input['related_count'] ) : 3;
	$out['related_count']  = min( 8, max( 1, $related ) );
	$out['show_search']    = ! empty( $input['show_search'] );

	if ( isset( $input['search_placeholder'] ) ) {
		$out['search_placeholder'] = sanitize_text_field( $input['search_placeholder'] );
	}

	$results               = isset( $input['search_results'] ) ? absint( $input['search_results'] ) : 10;
	$out['search_results'] = min( 25, max( 3, $results ) );

	$out['color_primary']    = douxir_sanitize_hex_color( isset( $input['color_primary'] ) ? $input['color_primary'] : '', $defaults['color_primary'] );
	$out['color_text']       = douxir_sanitize_hex_color( isset( $input['color_text'] ) ? $input['color_text'] : '', $defaults['color_text'] );
	$out['color_background'] = douxir_sanitize_hex_color( isset( $input['color_background'] ) ? $input['color_background'] : '', $defaults['color_background'] );
	$out['color_link']       = douxir_sanitize_hex_color( isset( $input['color_link'] ) ? $input['color_link'] : '', $defaults['color_link'] );

	$font = isset( $input['font_family'] ) ? sanitize_key( $input['font_family'] ) : 'inherit';
	$out['font_family'] = isset( $fonts[ $font ] ) ? $font : 'inherit';

	$size              = isset( $input['font_size'] ) ? absint( $input['font_size'] ) : 16;
	$out['font_size']  = min( 22, max( 12, $size ) );

	$width                  = isset( $input['container_width'] ) ? absint( $input['container_width'] ) : 1200;
	$out['container_width'] = min( 1600, max( 720, $width ) );

	$out['custom_css'] = isset( $input['custom_css'] ) ? douxir_sanitize_css( $input['custom_css'] ) : '';

	return $out;
}

/**
 * Persist settings and flag a rewrite flush when the slug or URL pattern changes.
 *
 * @param array $input Raw settings.
 * @return array Sanitized settings that were saved.
 */
function douxir_save_settings( $input ) {
	$previous = douxir_get_settings();
	$clean    = douxir_sanitize_settings( $input );
	update_option( 'douxir_settings', $clean );

	if ( $previous['docs_slug'] !== $clean['docs_slug'] || (bool) $previous['include_category_in_url'] !== (bool) $clean['include_category_in_url'] ) {
		update_option( 'douxir_flush_rewrites', 1 );
	}

	return $clean;
}

/**
 * CSS font-family value for a stored font key.
 *
 * @param string $key Font key.
 * @return string
 */
function douxir_font_stack( $key ) {
	$fonts = douxir_allowed_fonts();
	return isset( $fonts[ $key ] ) ? $fonts[ $key ] : $fonts['inherit'];
}

/**
 * Build the inline CSS variables and custom CSS for the front end.
 *
 * @return string
 */
function douxir_get_appearance_css() {
	$s     = douxir_get_settings();
	$stack = douxir_font_stack( $s['font_family'] );
	$css   = sprintf(
		':root,.doc-section,.douxir-wrap{--douxir-color-primary:%1$s;--douxir-color-text:%2$s;--douxir-color-background:%3$s;--douxir-color-link:%4$s;--douxir-font-family:%5$s;--douxir-font-size:%6$dpx;--douxir-container-width:%7$dpx;--douxir-columns:%8$d;}',
		esc_attr( $s['color_primary'] ),
		esc_attr( $s['color_text'] ),
		esc_attr( $s['color_background'] ),
		esc_attr( $s['color_link'] ),
		$stack,
		(int) $s['font_size'],
		(int) $s['container_width'],
		(int) $s['archive_columns']
	);
	if ( ! empty( $s['custom_css'] ) ) {
		$css .= "\n" . $s['custom_css'];
	}
	return $css;
}

/**
 * Attach appearance CSS after the front-end stylesheet.
 *
 * @return void
 */
function douxir_print_appearance_css() {
	if ( ! wp_style_is( 'douxir-main', 'enqueued' ) ) {
		return;
	}
	wp_add_inline_style( 'douxir-main', douxir_get_appearance_css() );
}
add_action( 'wp_enqueue_scripts', 'douxir_print_appearance_css', 20 );

/**
 * Sanitize a category icon key.
 *
 * @param string $icon Raw icon.
 * @return string
 */
function douxir_sanitize_icon( $icon ) {
	$icon = sanitize_key( $icon );
	return in_array( $icon, douxir_allowed_icons(), true ) ? $icon : '';
}

/**
 * Return a small decorative SVG for a category icon key.
 *
 * @param string $icon Icon key.
 * @return string
 */
function douxir_get_icon_svg( $icon ) {
	$icon = douxir_sanitize_icon( $icon );
	if ( '' === $icon ) {
		$icon = 'folder';
	}

	$paths = array(
		'book'      => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
		'folder'    => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/>',
		'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
		'rocket'    => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
		'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
		'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
		'life-buoy' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/>',
		'code'      => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
		'lightbulb' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12c.6.6 1 1.5 1 2.4V18h6v-1.6c0-.9.4-1.8 1-2.4A7 7 0 0 0 12 2z"/>',
		'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'message'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'wrench'    => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
	);

	$path = isset( $paths[ $icon ] ) ? $paths[ $icon ] : $paths['folder'];

	return '<svg class="douxir-icon douxir-icon-' . esc_attr( $icon ) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
