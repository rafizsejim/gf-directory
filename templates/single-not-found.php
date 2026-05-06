<?php
/**
 * Shown when an entry id is invalid, not approved, or belongs to another form.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var string $base_url
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="gfd gfd-single-missing">
	<h2 class="gfd-single-missing__title"><?php esc_html_e( 'Listing unavailable', 'gf-directory' ); ?></h2>
	<p class="gfd-single-missing__text"><?php esc_html_e( 'This listing may have been removed or is not currently published.', 'gf-directory' ); ?></p>
	<a class="gfd-single-missing__back" href="<?php echo esc_url( $base_url ); ?>">
		<?php esc_html_e( '← Back to directory', 'gf-directory' ); ?>
	</a>
</div>
