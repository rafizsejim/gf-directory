<?php
/**
 * Hero search bar.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array  $criteria
 * @var string $base_url
 * @var array  $filter_meta
 * @var array  $sort_meta
 * @var array  $param
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

$strings        = $settings->strings();
$date_field_id  = (string) $settings->field_id( 'date_filter_field' );
$active_filters = (array) ( $criteria['filters'] ?? [] );
?>
<form class="gfd-hero" method="get" action="<?php echo esc_url( $base_url ); ?>" role="search">
	<div class="gfd-hero__inner">
		<div class="gfd-hero__copy">
			<h2 class="gfd-hero__title"><?php echo esc_html( $strings['hero_title'] ); ?></h2>
			<?php if ( $strings['hero_subtitle'] !== '' ) : ?>
				<p class="gfd-hero__subtitle"><?php echo esc_html( $strings['hero_subtitle'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="gfd-hero__bar">
			<label class="gfd-hero__field gfd-hero__field--search">
				<span class="gfd-hero__label"><?php esc_html_e( 'Search', 'gf-directory' ); ?></span>
				<input
					type="search"
					name="<?php echo esc_attr( $param['q'] ); ?>"
					value="<?php echo esc_attr( (string) ( $criteria['q'] ?? '' ) ); ?>"
					placeholder="<?php echo esc_attr( $strings['search_placeholder'] ); ?>"
					autocomplete="off" />
			</label>

			<?php foreach ( $filter_meta as $filter ) : ?>
				<label class="gfd-hero__field">
					<span class="gfd-hero__label"><?php echo esc_html( $filter['label'] ); ?></span>
					<select name="<?php echo esc_attr( $param['filters'] . '[' . $filter['field_id'] . ']' ); ?>" data-gfd-autosubmit>
						<option value=""><?php esc_html_e( 'Any', 'gf-directory' ); ?></option>
						<?php
						$current = (string) ( $active_filters[ $filter['field_id'] ] ?? '' );
						foreach ( $filter['choices'] as $choice ) :
							$selected = ( $current === $choice['value'] ) ? ' selected' : '';
							?>
							<option value="<?php echo esc_attr( $choice['value'] ); ?>"<?php echo $selected; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $choice['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>

			<?php if ( $date_field_id !== '' ) : ?>
				<label class="gfd-hero__field">
					<span class="gfd-hero__label"><?php esc_html_e( 'From', 'gf-directory' ); ?></span>
					<input type="date"
						name="<?php echo esc_attr( $param['date_from'] ); ?>"
						value="<?php echo esc_attr( (string) $criteria['date_from'] ); ?>" />
				</label>
				<label class="gfd-hero__field">
					<span class="gfd-hero__label"><?php esc_html_e( 'To', 'gf-directory' ); ?></span>
					<input type="date"
						name="<?php echo esc_attr( $param['date_to'] ); ?>"
						value="<?php echo esc_attr( (string) $criteria['date_to'] ); ?>" />
				</label>
			<?php endif; ?>

			<button type="submit" class="gfd-hero__submit"><?php esc_html_e( 'Search', 'gf-directory' ); ?></button>
		</div>

		<div class="gfd-hero__sort-row">
			<label class="gfd-hero__sort">
				<span class="gfd-hero__label"><?php esc_html_e( 'Sort', 'gf-directory' ); ?></span>
				<select name="<?php echo esc_attr( $param['sort'] ); ?>" data-gfd-autosubmit>
					<?php foreach ( $sort_meta as $sort_choice ) :
						$selected = ( (string) $criteria['sort'] === $sort_choice['value'] ) ? ' selected' : '';
						?>
						<option value="<?php echo esc_attr( $sort_choice['value'] ); ?>"<?php echo $selected; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $sort_choice['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<?php if ( ! empty( $active_filters ) || ( $criteria['q'] ?? '' ) !== '' ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="gfd-hero__clear">
					<?php esc_html_e( 'Clear filters', 'gf-directory' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</form>
