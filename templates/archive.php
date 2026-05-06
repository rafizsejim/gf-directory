<?php
/**
 * Archive wrapper.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array  $criteria
 * @var array  $result
 * @var string $cards_html
 * @var string $pagination_html
 * @var string $search_html
 * @var string $base_url
 * @var string $instance_id
 * @var string $inline_style
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

use GFDirectory\Query\SearchParser;

$enabled_views = $settings->enabled_views();
$show_toggle   = count( $enabled_views ) > 1;
$current_view  = (string) $criteria['view'];

$total = (int) $result['total'];
?>
<?php echo $inline_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- prebuilt scoped CSS ?>
<div id="<?php echo esc_attr( $instance_id ); ?>" class="gfd gfd--view-<?php echo esc_attr( $current_view ); ?> gfd--cards-<?php echo esc_attr( (string) $settings->card_style() ); ?>" data-form-id="<?php echo esc_attr( (string) $settings->form_id() ); ?>">

	<?php echo $search_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed by SearchBarRenderer ?>

	<div class="gfd__bar">
		<div class="gfd__count">
			<?php
			printf(
				/* translators: %s: number of results. */
				esc_html( _n( '%s listing', '%s listings', $total, 'gf-directory' ) ),
				'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			?>
		</div>

		<?php if ( $show_toggle ) : ?>
			<div class="gfd__toggle" role="tablist" aria-label="<?php esc_attr_e( 'View', 'gf-directory' ); ?>">
				<?php foreach ( $enabled_views as $view ) :
					$url    = SearchParser::build_url( $base_url, $criteria, [ 'view' => $view, 'page' => 1 ] );
					$active = $view === $current_view;
					$label  = $view === 'list' ? __( 'List', 'gf-directory' ) : __( 'Cards', 'gf-directory' );
					$icon   = $view === 'list' ? '☰' : '▦';
					?>
					<a href="<?php echo esc_url( $url ); ?>"
					   class="gfd__toggle-btn<?php echo $active ? ' is-active' : ''; ?>"
					   role="tab"
					   aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
						<span class="gfd__toggle-icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $current_view === 'list' ) : ?>
		<div class="gfd__list"><?php echo $cards_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php else : ?>
		<div class="gfd__grid"><?php echo $cards_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>

	<?php echo $pagination_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

</div>
