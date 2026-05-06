<?php
/**
 * Dashboard wrapper.
 *
 * @var string $tab
 * @var string $tabs_html
 * @var string $panel_html
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="gfd gfd-dash">
	<header class="gfd-dash__header">
		<h2 class="gfd-dash__title"><?php esc_html_e( 'Your dashboard', 'gf-directory' ); ?></h2>
		<?php echo $tabs_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</header>

	<div class="gfd-dash__panel" role="tabpanel">
		<?php echo $panel_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
