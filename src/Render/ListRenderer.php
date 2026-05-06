<?php
/**
 * Renders a single list item.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class ListRenderer {

	private TemplateLoader $loader;

	public function __construct( TemplateLoader $loader ) {
		$this->loader = $loader;
	}

	public function render( FormSettings $settings, array $entry, string $base_url = '', array $saved_index = [] ): string {
		return $this->loader->render(
			'list-item',
			[
				'settings' => $settings,
				'entry'    => $entry,
				'data'     => CardData::from( $settings, $entry, $base_url, $saved_index ),
			]
		);
	}
}
