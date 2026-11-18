<?php
/**
 * Template for the documentation homepage.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

echo '<div class="doc-section"><div class="doc-box douxir-archive-box">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static wrapper.
echo douxir_render_archive( null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper.
echo '</div></div>';

get_footer();
