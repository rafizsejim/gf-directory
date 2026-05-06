<?php
/**
 * No-results state.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array $criteria
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

$strings = $settings->strings();
?>
<div class="gfd-empty">
	<div class="gfd-empty__art" aria-hidden="true">∅</div>
	<p class="gfd-empty__text"><?php echo esc_html( $strings['no_results_text'] ); ?></p>
</div>
