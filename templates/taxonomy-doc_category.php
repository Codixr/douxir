<?php
/**
 * Template for a documentation category archive.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

$term = get_queried_object();

echo '<div class="doc-section"><div class="doc-box douxir-archive-box">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static wrapper.
if ( $term instanceof WP_Term ) {
	echo douxir_render_archive( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
}
echo '</div></div>';

get_footer();
