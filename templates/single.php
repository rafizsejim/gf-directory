<?php
/**
 * Single entry detail page.
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array  $entry
 * @var \GFDirectory\Render\CardData $data
 * @var array  $details   Catch-all key/value pairs of unmapped fields.
 * @var string $related_html
 * @var string $base_url
 * @var string $instance_id
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;

$has_rating = $data->rating !== '' && is_numeric( $data->rating );
?>
<article id="<?php echo esc_attr( $instance_id ); ?>" class="gfd gfd-single">
	<a href="<?php echo esc_url( $base_url ); ?>" class="gfd-single__back">
		<span aria-hidden="true">←</span> <?php esc_html_e( 'Back to directory', 'gf-directory' ); ?>
	</a>

	<div class="gfd-single__layout">
		<section class="gfd-single__main">
			<div class="gfd-single__gallery">
				<?php if ( $data->image_url !== '' ) : ?>
					<img class="gfd-single__hero-img" src="<?php echo esc_url( $data->image_url ); ?>" alt="<?php echo esc_attr( $data->title ); ?>" />
				<?php else : ?>
					<div class="gfd-single__hero-placeholder" aria-hidden="true"></div>
				<?php endif; ?>

				<?php if ( count( $data->images ) > 1 ) : ?>
					<div class="gfd-single__thumbs">
						<?php foreach ( array_slice( $data->images, 0, 5 ) as $i => $url ) : ?>
							<button type="button" class="gfd-single__thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-gfd-image="<?php echo esc_url( $url ); ?>">
								<img src="<?php echo esc_url( $url ); ?>" alt="" loading="lazy" />
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<header class="gfd-single__header">
				<?php if ( $data->badge !== '' ) : ?>
					<span class="gfd-single__badge">● <?php echo esc_html( $data->badge ); ?></span>
				<?php endif; ?>
				<h1 class="gfd-single__title"><?php echo esc_html( $data->title !== '' ? $data->title : '#' . $data->entry_id ); ?></h1>
				<?php if ( $data->subtitle !== '' ) : ?>
					<p class="gfd-single__subtitle"><?php echo esc_html( $data->subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $has_rating ) : ?>
					<div class="gfd-single__rating">
						<?php
						$value    = (float) $data->rating;
						$full     = (int) floor( $value );
						$has_half = ( $value - $full ) >= 0.5;
						for ( $i = 1; $i <= 5; $i++ ) {
							$class = $i <= $full ? 'is-full' : ( ( $i === $full + 1 && $has_half ) ? 'is-half' : 'is-empty' );
							echo '<span class="gfd-single__star ' . esc_attr( $class ) . '">★</span>';
						}
						?>
						<span class="gfd-single__rating-value"><?php echo esc_html( $data->rating ); ?></span>
						<?php if ( $data->rating_count !== '' ) : ?>
							<span class="gfd-single__rating-count">
								<?php
								printf(
									/* translators: %s: review count. */
									esc_html__( '(%s reviews)', 'gf-directory' ),
									esc_html( $data->rating_count )
								);
								?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</header>

			<?php if ( $data->description !== '' ) : ?>
				<section class="gfd-single__section">
					<h2 class="gfd-single__section-title"><?php esc_html_e( 'About', 'gf-directory' ); ?></h2>
					<div class="gfd-single__description">
						<?php echo wp_kses_post( wpautop( $data->description ) ); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $data->features ) ) : ?>
				<section class="gfd-single__section">
					<h2 class="gfd-single__section-title"><?php esc_html_e( 'Highlights', 'gf-directory' ); ?></h2>
					<ul class="gfd-single__features">
						<?php foreach ( $data->features as $feature ) : ?>
							<li class="gfd-single__feature"><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $data->stats ) ) : ?>
				<section class="gfd-single__section">
					<dl class="gfd-single__stats">
						<?php foreach ( $data->stats as $stat ) : ?>
							<div class="gfd-single__stat">
								<dt><?php echo esc_html( $stat['label'] ); ?></dt>
								<dd><?php echo esc_html( $stat['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $details ) ) : ?>
				<section class="gfd-single__section">
					<h2 class="gfd-single__section-title"><?php esc_html_e( 'Details', 'gf-directory' ); ?></h2>
					<dl class="gfd-single__details">
						<?php foreach ( $details as $row ) : ?>
							<div class="gfd-single__details-row">
								<dt><?php echo esc_html( $row['label'] ); ?></dt>
								<dd><?php echo esc_html( $row['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</section>
			<?php endif; ?>
		</section>

		<aside class="gfd-single__aside">
			<div class="gfd-single__card">
				<?php if ( $data->price !== '' ) : ?>
					<div class="gfd-single__price"><?php echo esc_html( $data->price ); ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $data->meta_icons ) ) : ?>
					<ul class="gfd-single__meta">
						<?php foreach ( $data->meta_icons as $meta ) : ?>
							<li class="gfd-single__meta-item">
								<span class="gfd-single__meta-icon" data-icon="<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></span>
								<span><?php echo esc_html( $meta['value'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $data->cta_url !== '' ) : ?>
					<a class="gfd-single__cta" href="<?php echo esc_url( $data->cta_url ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( $data->cta_label ); ?>
					</a>
				<?php endif; ?>

				<div class="gfd-single__actions">
					<button type="button" class="gfd-single__action" data-gfd-save="<?php echo esc_attr( (string) $data->entry_id ); ?>" data-gfd-form="<?php echo esc_attr( (string) $settings->form_id() ); ?>" data-gfd-saved="<?php echo $data->is_saved ? '1' : '0'; ?>" aria-label="<?php esc_attr_e( 'Save', 'gf-directory' ); ?>">
						<span aria-hidden="true" data-gfd-save-icon><?php echo $data->is_saved ? '♥' : '♡'; ?></span>
						<span data-gfd-save-label><?php echo $data->is_saved ? esc_html__( 'Saved', 'gf-directory' ) : esc_html__( 'Save', 'gf-directory' ); ?></span>
					</button>
					<button type="button" class="gfd-single__action" data-gfd-share aria-label="<?php esc_attr_e( 'Share', 'gf-directory' ); ?>">
						<span aria-hidden="true">↗</span> <?php esc_html_e( 'Share', 'gf-directory' ); ?>
					</button>
				</div>
			</div>
		</aside>
	</div>

	<?php if ( $related_html !== '' ) : ?>
		<section class="gfd-single__related">
			<h2 class="gfd-single__section-title"><?php esc_html_e( 'You might also like', 'gf-directory' ); ?></h2>
			<div class="gfd__grid"><?php echo $related_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</section>
	<?php endif; ?>
</article>
