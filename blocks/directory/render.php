<?php
/**
 * Server render for the gf-directory/directory block.
 *
 * Reuses the [gf_directory] shortcode so the block and shortcode share a
 * single render path. Any improvement to the shortcode flows automatically
 * through to the block.
 *
 * @var array $attributes
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

$form_id = isset( $attributes['formId'] ) ? (int) $attributes['formId'] : 0;
if ( $form_id <= 0 ) {
	return;
}

echo do_shortcode( '[gf_directory form="' . esc_attr( (string) $form_id ) . '"]' );
