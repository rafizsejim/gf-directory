<?php
/**
 * Directory add-on. Owns the per-form Directory tab under Form Settings.
 *
 * Extends GFAddOn so we get settings tab registration, field rendering and
 * persistence for free, and never touch GF internals.
 *
 * @package GFDirectory\Addon
 */

declare( strict_types=1 );

namespace GFDirectory\Addon;

defined( 'ABSPATH' ) || exit;

\GFForms::include_addon_framework();

final class DirectoryAddOn extends \GFAddOn {

	protected $_version                  = GFDIRECTORY_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug                     = 'gf-directory';
	protected $_path                     = 'gf-directory/gf-directory.php';
	protected $_full_path                = GFDIRECTORY_FILE;
	protected $_url                      = 'https://github.com/rafizsejim/gf-directory';
	protected $_title                    = 'Gravity Forms Directory';
	protected $_short_title              = 'Directory';

	private static ?DirectoryAddOn $_instance = null;

	public static function get_instance(): DirectoryAddOn {
		if ( self::$_instance === null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Per-form Directory settings tab.
	 *
	 * Each section returns the relevant field group. We split the UI into
	 * focused sections so admins can scan top to bottom without scrolling
	 * past unrelated controls.
	 */
	public function form_settings_fields( $form ): array {
		return [
			$this->section_activation(),
			$this->section_layout(),
			$this->section_field_map(),
			$this->section_meta_icons(),
			$this->section_stats(),
			$this->section_search_and_filter(),
			$this->section_appearance(),
			$this->section_strings(),
		];
	}

	private function section_activation(): array {
		return [
			'title'  => esc_html__( 'Directory activation', 'gf-directory' ),
			'fields' => [
				[
					'name'          => 'enabled',
					'label'         => esc_html__( 'Enable directory for this form', 'gf-directory' ),
					'type'          => 'toggle',
					'default_value' => false,
					'tooltip'       => esc_html__(
						'When enabled, approved entries from this form become available to the [gf_directory] shortcode and block.',
						'gf-directory'
					),
				],
			],
		];
	}

	private function section_layout(): array {
		return [
			'title'       => esc_html__( 'Layout', 'gf-directory' ),
			'description' => esc_html__( 'Choose which views visitors can see and how cards are laid out.', 'gf-directory' ),
			'fields'      => [
				[
					'name'          => 'enabled_views',
					'label'         => esc_html__( 'Enabled views', 'gf-directory' ),
					'type'          => 'checkbox',
					'choices'       => [
						[ 'name' => 'enabled_views_card', 'label' => esc_html__( 'Card view', 'gf-directory' ) ],
						[ 'name' => 'enabled_views_list', 'label' => esc_html__( 'List view', 'gf-directory' ) ],
					],
					'default_value' => [ 'enabled_views_card' => '1', 'enabled_views_list' => '1' ],
					'tooltip'       => esc_html__( 'Select one or both. If both are enabled, visitors can toggle between them on the front end.', 'gf-directory' ),
				],
				[
					'name'          => 'default_view',
					'label'         => esc_html__( 'Default view', 'gf-directory' ),
					'type'          => 'radio',
					'horizontal'    => true,
					'choices'       => [
						[ 'value' => 'card', 'label' => esc_html__( 'Card', 'gf-directory' ) ],
						[ 'value' => 'list', 'label' => esc_html__( 'List', 'gf-directory' ) ],
					],
					'default_value' => 'card',
				],
				[
					'name'          => 'card_style',
					'label'         => esc_html__( 'Card style', 'gf-directory' ),
					'type'          => 'radio',
					'horizontal'    => true,
					'choices'       => [
						[ 'value' => 'framed',  'label' => esc_html__( 'Framed', 'gf-directory' ) ],
						[ 'value' => 'overlay', 'label' => esc_html__( 'Overlay', 'gf-directory' ) ],
					],
					'default_value' => 'framed',
				],
				[
					'name'          => 'cards_per_row',
					'label'         => esc_html__( 'Cards per row (desktop)', 'gf-directory' ),
					'type'          => 'select',
					'choices'       => [
						[ 'value' => '2', 'label' => '2' ],
						[ 'value' => '3', 'label' => '3' ],
						[ 'value' => '4', 'label' => '4' ],
					],
					'default_value' => '3',
				],
			],
		];
	}

	private function section_field_map(): array {
		return [
			'title'       => esc_html__( 'Field mapping', 'gf-directory' ),
			'description' => esc_html__( 'Pick which form field provides each piece of card and list content. Leave blank to hide.', 'gf-directory' ),
			'fields'      => [
				$this->field_select( 'field_title',         esc_html__( 'Title', 'gf-directory' ),       [ 'input_types' => [ 'text' ] ] ),
				$this->field_select( 'field_subtitle',      esc_html__( 'Subtitle / location', 'gf-directory' ), [ 'input_types' => [ 'text', 'address' ] ] ),
				$this->field_select( 'field_image',         esc_html__( 'Image (file upload)', 'gf-directory' ), [ 'input_types' => [ 'fileupload', 'post_image' ] ] ),
				$this->field_select( 'field_badge',         esc_html__( 'Badge', 'gf-directory' ),       [ 'input_types' => [ 'select', 'radio', 'text' ] ] ),
				$this->field_select( 'field_price',         esc_html__( 'Price', 'gf-directory' ),       [ 'input_types' => [ 'number', 'singleproduct', 'product' ] ] ),
				$this->field_select( 'field_rating',        esc_html__( 'Rating (0-5)', 'gf-directory' ), [ 'input_types' => [ 'number' ] ] ),
				$this->field_select( 'field_rating_count',  esc_html__( 'Rating count', 'gf-directory' ), [ 'input_types' => [ 'number' ] ] ),
				$this->field_select( 'field_description',   esc_html__( 'Description', 'gf-directory' ), [ 'input_types' => [ 'textarea', 'text' ] ] ),
				$this->field_select( 'field_features',      esc_html__( 'Features (multi-choice → chips)', 'gf-directory' ), [ 'input_types' => [ 'checkbox', 'multiselect' ] ] ),
				$this->field_select( 'field_cta_url',       esc_html__( 'CTA URL', 'gf-directory' ),     [ 'input_types' => [ 'website', 'text' ] ] ),
				[
					'name'          => 'cta_label',
					'label'         => esc_html__( 'CTA button label', 'gf-directory' ),
					'type'          => 'text',
					'default_value' => esc_html__( 'View details', 'gf-directory' ),
				],
			],
		];
	}

	private function section_meta_icons(): array {
		$fields = [];
		for ( $i = 1; $i <= 4; $i++ ) {
			$fields[] = $this->field_select(
				"meta_icon_{$i}_field",
				/* translators: %d: meta icon slot number. */
				sprintf( esc_html__( 'Meta icon %d field', 'gf-directory' ), $i ),
				[ 'input_types' => [ 'number', 'text', 'select', 'radio' ] ]
			);
			$fields[] = [
				'name'    => "meta_icon_{$i}_icon",
				/* translators: %d: meta icon slot number. */
				'label'   => sprintf( esc_html__( 'Meta icon %d icon', 'gf-directory' ), $i ),
				'type'    => 'select',
				'choices' => $this->icon_choices(),
			];
		}

		return [
			'title'       => esc_html__( 'Meta icons (up to 4)', 'gf-directory' ),
			'description' => esc_html__( 'Small icon stats shown beneath the title (beds, baths, etc.). Each slot picks a field and an icon.', 'gf-directory' ),
			'fields'      => $fields,
		];
	}

	private function section_stats(): array {
		$fields = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$fields[] = $this->field_select(
				"stat_{$i}_field",
				/* translators: %d: stat column number. */
				sprintf( esc_html__( 'Stat %d field', 'gf-directory' ), $i ),
				[ 'input_types' => [ 'number', 'text' ] ]
			);
			$fields[] = [
				'name'  => "stat_{$i}_label",
				/* translators: %d: stat column number. */
				'label' => sprintf( esc_html__( 'Stat %d label', 'gf-directory' ), $i ),
				'type'  => 'text',
			];
		}

		return [
			'title'       => esc_html__( 'Stats footer (up to 3)', 'gf-directory' ),
			'description' => esc_html__( 'Three columns at the bottom of cards and the single page (e.g. Token price, IRR, APR).', 'gf-directory' ),
			'fields'      => $fields,
		];
	}

	private function section_search_and_filter(): array {
		return [
			'title'       => esc_html__( 'Search & filter', 'gf-directory' ),
			'description' => esc_html__( 'Configure the hero search bar and filter chips.', 'gf-directory' ),
			'fields'      => [
				$this->field_select(
					'searchable_fields',
					esc_html__( 'Searchable fields', 'gf-directory' ),
					[ 'input_types' => [ 'text', 'textarea', 'address' ] ],
					true
				),
				$this->field_select( 'filter_field_1', esc_html__( 'Filter slot 1', 'gf-directory' ), [ 'input_types' => [ 'select', 'radio', 'multiselect', 'checkbox' ] ] ),
				$this->field_select( 'filter_field_2', esc_html__( 'Filter slot 2', 'gf-directory' ), [ 'input_types' => [ 'select', 'radio', 'multiselect', 'checkbox' ] ] ),
				$this->field_select( 'filter_field_3', esc_html__( 'Filter slot 3', 'gf-directory' ), [ 'input_types' => [ 'select', 'radio', 'multiselect', 'checkbox' ] ] ),
				$this->field_select( 'filter_field_4', esc_html__( 'Filter slot 4', 'gf-directory' ), [ 'input_types' => [ 'select', 'radio', 'multiselect', 'checkbox' ] ] ),
				$this->field_select( 'date_filter_field', esc_html__( 'Date range filter field', 'gf-directory' ), [ 'input_types' => [ 'date' ] ] ),
				$this->field_select(
					'sort_options',
					esc_html__( 'Sortable fields', 'gf-directory' ),
					[ 'input_types' => [ 'number', 'date', 'text' ] ],
					true
				),
				[
					'name'          => 'per_page',
					'label'         => esc_html__( 'Results per page', 'gf-directory' ),
					'type'          => 'text',
					'input_type'    => 'number',
					'default_value' => '12',
				],
			],
		];
	}

	private function section_appearance(): array {
		return [
			'title'       => esc_html__( 'Appearance', 'gf-directory' ),
			'description' => esc_html__( 'Color tokens applied to this directory only. Leave blank to inherit theme defaults.', 'gf-directory' ),
			'fields'      => [
				[ 'name' => 'color_accent',    'label' => esc_html__( 'Accent color', 'gf-directory' ),    'type' => 'text', 'class' => 'small-text', 'default_value' => '#6D5CE7' ],
				[ 'name' => 'color_badge',     'label' => esc_html__( 'Badge color', 'gf-directory' ),     'type' => 'text', 'class' => 'small-text', 'default_value' => '#1A8C4A' ],
				[ 'name' => 'color_hero_bg',   'label' => esc_html__( 'Hero background', 'gf-directory' ), 'type' => 'text', 'class' => 'small-text', 'default_value' => '#FFFFFF' ],
				[ 'name' => 'color_hero_text', 'label' => esc_html__( 'Hero text', 'gf-directory' ),       'type' => 'text', 'class' => 'small-text', 'default_value' => '#0F1115' ],
				[
					'name'          => 'show_hero',
					'label'         => esc_html__( 'Show hero search bar', 'gf-directory' ),
					'type'          => 'toggle',
					'default_value' => true,
				],
			],
		];
	}

	private function section_strings(): array {
		return [
			'title'       => esc_html__( 'Strings', 'gf-directory' ),
			'description' => esc_html__( 'Customize the visible text on the directory page.', 'gf-directory' ),
			'fields'      => [
				[ 'name' => 'hero_title',         'label' => esc_html__( 'Hero title', 'gf-directory' ),         'type' => 'text', 'default_value' => esc_html__( 'Find your next listing', 'gf-directory' ) ],
				[ 'name' => 'hero_subtitle',      'label' => esc_html__( 'Hero subtitle', 'gf-directory' ),      'type' => 'text', 'default_value' => esc_html__( 'Curated by us', 'gf-directory' ) ],
				[ 'name' => 'search_placeholder', 'label' => esc_html__( 'Search placeholder', 'gf-directory' ), 'type' => 'text', 'default_value' => esc_html__( 'Search…', 'gf-directory' ) ],
				[ 'name' => 'no_results_text',    'label' => esc_html__( 'No results message', 'gf-directory' ), 'type' => 'text', 'default_value' => esc_html__( 'No listings match your filters yet.', 'gf-directory' ) ],
			],
		];
	}

	/**
	 * Build a `field_select` setting that filters by field input types.
	 *
	 * Wraps GF's native field_select so the same shape is reusable.
	 */
	private function field_select( string $name, string $label, array $args = [], bool $multiple = false ): array {
		$field = [
			'name'  => $name,
			'label' => $label,
			'type'  => 'field_select',
			'args'  => $args,
		];

		if ( $multiple ) {
			$field['multiple'] = 'multiple';
		}

		return $field;
	}

	private function icon_choices(): array {
		$icons = [ 'none', 'bed', 'bath', 'area', 'car', 'guests', 'wifi', 'parking', 'food', 'pet', 'pool', 'star', 'tag', 'clock', 'calendar' ];
		$out   = [];
		foreach ( $icons as $icon ) {
			$out[] = [ 'value' => $icon, 'label' => ucfirst( $icon ) ];
		}
		return $out;
	}

	/**
	 * Convenience: read directory settings for a form, defaulting safely.
	 */
	public function settings_for( int $form_id ): array {
		$form = \GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return [];
		}
		return (array) $this->get_form_settings( $form );
	}
}
