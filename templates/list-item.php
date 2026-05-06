<?php
/**
 * List view row.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array $entry
 * @var \GFDirectory\Render\CardData $data
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

$has_rating = $data->rating !== '' && is_numeric( $data->rating );
?>
<article class="gfd-row">
	<div class="gfd-row__media">
		<?php if ( $data->image_url !== '' ) : ?>
			<img src="<?php echo esc_url( $data->image_url ); ?>" alt="<?php echo esc_attr( $data->title ); ?>" loading="lazy" decoding="async" />
		<?php endif; ?>
		<?php if ( $data->badge !== '' ) : ?>
			<span class="gfd-row__badge"><?php echo esc_html( $data->badge ); ?></span>
		<?php endif; ?>
	</div>

	<div class="gfd-row__body">
		<h3 class="gfd-row__title">
			<a href="<?php echo esc_url( $data->permalink ); ?>"><?php echo esc_html( $data->title !== '' ? $data->title : '#' . $data->entry_id ); ?></a>
		</h3>

		<?php if ( $has_rating ) : ?>
			<div class="gfd-row__rating">
				<?php
				$value     = (float) $data->rating;
				$full      = (int) floor( $value );
				$has_half  = ( $value - $full ) >= 0.5;
				for ( $i = 1; $i <= 5; $i++ ) {
					$class = $i <= $full ? 'is-full' : ( ( $i === $full + 1 && $has_half ) ? 'is-half' : 'is-empty' );
					echo '<span class="gfd-row__star ' . esc_attr( $class ) . '">★</span>';
				}
				?>
				<span class="gfd-row__rating-value"><?php echo esc_html( $data->rating ); ?></span>
				<?php if ( $data->rating_count !== '' ) : ?>
					<span class="gfd-row__rating-count">(<?php echo esc_html( $data->rating_count ); ?>)</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $data->subtitle !== '' ) : ?>
			<p class="gfd-row__subtitle"><?php echo esc_html( $data->subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $data->features ) ) : ?>
			<ul class="gfd-row__features">
				<?php foreach ( array_slice( $data->features, 0, 5 ) as $feature ) : ?>
					<li class="gfd-row__feature"><?php echo esc_html( $feature ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="gfd-row__aside">
		<?php if ( $data->price !== '' ) : ?>
			<div class="gfd-row__price"><?php echo esc_html( $data->price ); ?></div>
		<?php endif; ?>

		<div class="gfd-row__actions">
			<button type="button" class="gfd-row__icon-btn" aria-label="<?php esc_attr_e( 'Save', 'gf-directory' ); ?>" data-gfd-save="<?php echo esc_attr( (string) $data->entry_id ); ?>" data-gfd-form="<?php echo esc_attr( (string) $settings->form_id() ); ?>" data-gfd-saved="<?php echo $data->is_saved ? '1' : '0'; ?>"><?php echo $data->is_saved ? '♥' : '♡'; ?></button>
			<a href="<?php echo esc_url( $data->permalink ); ?>" class="gfd-row__cta">
				<?php echo esc_html( $data->cta_label ); ?>
			</a>
		</div>
	</div>
</article>
