<?php
/**
 * Renders a single card (framed or overlay) by delegating to the template
 * loader. Pure: takes data in, returns markup, never echoes.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class CardRenderer {

	private TemplateLoader $loader;

	public function __construct( TemplateLoader $loader ) {
		$this->loader = $loader;
	}

	public function render( FormSettings $settings, array $entry, string $base_url = '', array $saved_index = [] ): string {
		$style    = $settings->card_style();
		$template = $style === 'overlay' ? 'card-overlay' : 'card-framed';

		return $this->loader->render(
			$template,
			[
				'settings' => $settings,
				'entry'    => $entry,
				'data'     => CardData::from( $settings, $entry, $base_url, $saved_index ),
			]
		);
	}
}
