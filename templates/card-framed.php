<?php
/**
 * Framed card template (white card, image on top).
 *
 * @var \GFDirectory\Settings\FormSettings $settings
 * @var array $entry
 * @var \GFDirectory\Render\CardData $data
 *
 * @package GFDirectory
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="gfd-card gfd-card--framed">
	<div class="gfd-card__media">
		<?php if ( $data->image_url !== '' ) : ?>
			<img src="<?php echo esc_url( $data->image_url ); ?>" alt="<?php echo esc_attr( $data->title ); ?>" loading="lazy" decoding="async" />
		<?php else : ?>
			<div class="gfd-card__media-placeholder" aria-hidden="true"></div>
		<?php endif; ?>

		<?php if ( $data->badge !== '' ) : ?>
			<span class="gfd-card__badge">● <?php echo esc_html( $data->badge ); ?></span>
		<?php endif; ?>

		<div class="gfd-card__icons">
			<button type="button" class="gfd-card__icon-btn" aria-label="<?php esc_attr_e( 'Save', 'gf-directory' ); ?>" data-gfd-save="<?php echo esc_attr( (string) $data->entry_id ); ?>" data-gfd-form="<?php echo esc_attr( (string) $settings->form_id() ); ?>" data-gfd-saved="<?php echo $data->is_saved ? '1' : '0'; ?>"><?php echo $data->is_saved ? '♥' : '♡'; ?></button>
			<button type="button" class="gfd-card__icon-btn" aria-label="<?php esc_attr_e( 'Share', 'gf-directory' ); ?>" data-gfd-share>↗</button>
		</div>
	</div>

	<div class="gfd-card__body">
		<div class="gfd-card__heading">
			<h3 class="gfd-card__title">
				<a href="<?php echo esc_url( $data->permalink ); ?>"><?php echo esc_html( $data->title !== '' ? $data->title : '#' . $data->entry_id ); ?></a>
			</h3>
			<?php if ( $data->price !== '' ) : ?>
				<div class="gfd-card__price"><?php echo esc_html( $data->price ); ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $data->subtitle !== '' ) : ?>
			<p class="gfd-card__subtitle"><?php echo esc_html( $data->subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $data->meta_icons ) ) : ?>
			<ul class="gfd-card__meta">
				<?php foreach ( $data->meta_icons as $meta ) : ?>
					<li class="gfd-card__meta-item">
						<span class="gfd-card__meta-icon" data-icon="<?php echo esc_attr( $meta['icon'] ); ?>" aria-hidden="true"></span>
						<span class="gfd-card__meta-value"><?php echo esc_html( $meta['value'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $data->stats ) ) : ?>
			<dl class="gfd-card__stats">
				<?php foreach ( $data->stats as $stat ) : ?>
					<div class="gfd-card__stat">
						<dt><?php echo esc_html( $stat['label'] ); ?></dt>
						<dd><?php echo esc_html( $stat['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</article>
