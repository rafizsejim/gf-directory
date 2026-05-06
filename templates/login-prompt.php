<?php
/**
 * Inline "please log in" panel for the dashboard.
 *
 * @var string $login_url
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="gfd gfd-login">
	<div class="gfd-login__art" aria-hidden="true">↪</div>
	<h2 class="gfd-login__title"><?php esc_html_e( 'Sign in to view your dashboard', 'gf-directory' ); ?></h2>
	<p class="gfd-login__text"><?php esc_html_e( 'Save listings, track your submissions, and pick up where you left off.', 'gf-directory' ); ?></p>
	<a class="gfd-login__cta" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'gf-directory' ); ?></a>
</div>
