<?php
/**
 * Template for displaying single documents.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$douxir_settings  = douxir_get_settings();
$douxir_classes   = array( 'doc-section' );
if ( empty( $douxir_settings['show_sidebar'] ) ) {
	$douxir_classes[] = 'douxir-no-sidebar';
}
if ( empty( $douxir_settings['show_toc'] ) ) {
	$douxir_classes[] = 'douxir-no-toc';
}
if ( 'right' === $douxir_settings['sidebar_position'] ) {
	$douxir_classes[] = 'douxir-sidebar-right';
}

get_header();
?>

<div class="<?php echo esc_attr( implode( ' ', $douxir_classes ) ); ?>">
	<div class="doc-box">
		<?php if ( ! empty( $douxir_settings['show_sidebar'] ) ) : ?>
		<div class="doc-titles">
			<div class="doc-titles-group">
				<h3><?php echo esc_html( $douxir_settings['hub_title'] ); ?></h3>
				<?php
				echo do_shortcode( '[documentation_list]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the shortcode.
				?>
			</div>
		</div>
		<?php endif; ?>
		<div class="doc-content">
			<?php
			echo do_shortcode( '[documentation_content]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the shortcode.
			?>
		</div>
		<?php if ( ! empty( $douxir_settings['show_toc'] ) ) : ?>
		<div class="doc-toc">
			<div class="doc-toc-group">
				<h4><?php esc_html_e( 'On this page', 'douxir' ); ?></h4>
				<ul id="toc-box"></ul>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
