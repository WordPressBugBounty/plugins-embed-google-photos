<?php
/**
 * Frontend markup for the Embed Google Photos block.
 *
 * Renders an empty wrapper carrying the block attributes as JSON. The view
 * script (view.js) rehydrates the shared Gallery React component from it, so
 * the frontend and the editor render every setting identically.
 *
 * @var array $attributes Block attributes provided by WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bpgpb_cid = ! empty( $attributes['cId'] ) ? $attributes['cId'] : wp_unique_id();
$bpgpb_id  = 'bpgpbGooglePhotos-' . $bpgpb_cid;

// Enrich each photo from its Media Library attachment so the gallery can render
// a low-res blur-up placeholder and a responsive srcset. Done here (rather than
// only at import time) so galleries picked before these features existed get
// them too, without re-importing. Attachment lookups are cached by WP.
//
// The same pass collects Schema.org ImageObject entries for JSON-LD, output
// below. Building it server-side (not in the React view) means search engines
// read the image metadata straight from the HTML, without running JavaScript.
$bpgpb_schema_items = array();
if ( ! empty( $attributes['selectedPhotos'] ) && is_array( $attributes['selectedPhotos'] ) ) {
	foreach ( $attributes['selectedPhotos'] as &$bpgpb_photo ) {
		if ( ! is_array( $bpgpb_photo ) ) {
			continue;
		}

		// SECURITY: block attributes are author-controlled and are re-emitted to
		// the frontend (as JSON in data-attributes, then rehydrated by view.js).
		// A crafted title/url would otherwise reach the Fancybox lightbox caption
		// and video markup — both rendered via innerHTML — as stored XSS. Treat
		// every field as untrusted and normalise it here, before it is output.
		if ( isset( $bpgpb_photo['id'] ) ) {
			$bpgpb_photo['id'] = absint( $bpgpb_photo['id'] );
		}
		foreach ( array( 'url', 'thumb', 'placeholder' ) as $bpgpb_url_key ) {
			if ( isset( $bpgpb_photo[ $bpgpb_url_key ] ) ) {
				$bpgpb_photo[ $bpgpb_url_key ] = esc_url_raw( (string) $bpgpb_photo[ $bpgpb_url_key ] );
			}
		}
		foreach ( array( 'title', 'date', 'alt' ) as $bpgpb_text_key ) {
			if ( isset( $bpgpb_photo[ $bpgpb_text_key ] ) ) {
				$bpgpb_photo[ $bpgpb_text_key ] = sanitize_text_field( (string) $bpgpb_photo[ $bpgpb_text_key ] );
			}
		}
		if ( isset( $bpgpb_photo['type'] ) ) {
			$bpgpb_photo['type'] = in_array( $bpgpb_photo['type'], array( 'image', 'video' ), true ) ? $bpgpb_photo['type'] : 'image';
		}
		foreach ( array( 'width', 'height' ) as $bpgpb_int_key ) {
			if ( isset( $bpgpb_photo[ $bpgpb_int_key ] ) ) {
				$bpgpb_photo[ $bpgpb_int_key ] = (int) $bpgpb_photo[ $bpgpb_int_key ];
			}
		}
		if ( isset( $bpgpb_photo['srcset'] ) ) {
			// srcset is only ever used as a DOM attribute (React srcSet), but strip
			// any angle brackets/quotes as defence in depth.
			$bpgpb_photo['srcset'] = preg_replace( '/[<>"\']/', '', (string) $bpgpb_photo['srcset'] );
		}

		$bpgpb_att_id = isset( $bpgpb_photo['id'] ) ? absint( $bpgpb_photo['id'] ) : 0;
		if ( ! $bpgpb_att_id ) {
			continue;
		}

		// Blur-up placeholder: the WordPress "thumbnail" size (~150px) loads almost
		// instantly and looks good blurred. Videos aren't image attachments, so
		// fall back to their stored poster.
		if ( empty( $bpgpb_photo['placeholder'] ) ) {
			$bpgpb_ph = wp_get_attachment_image_url( $bpgpb_att_id, 'thumbnail' );
			if ( $bpgpb_ph ) {
				$bpgpb_photo['placeholder'] = $bpgpb_ph;
			} elseif ( ! empty( $bpgpb_photo['thumb'] ) ) {
				$bpgpb_photo['placeholder'] = $bpgpb_photo['thumb'];
			}
		}

		// Responsive srcset: all image sizes WordPress generated for this
		// attachment, so the browser downloads the one that fits the display
		// width. Returns false for videos (not image attachments).
		if ( empty( $bpgpb_photo['srcset'] ) ) {
			$bpgpb_srcset = wp_get_attachment_image_srcset( $bpgpb_att_id, 'full' );
			if ( $bpgpb_srcset ) {
				$bpgpb_photo['srcset'] = $bpgpb_srcset;
			}
		}

		// Alt text: fall back to the Media Library attachment's alt when the
		// block has no custom alt, so existing galleries still get one.
		if ( empty( $bpgpb_photo['alt'] ) ) {
			$bpgpb_alt = get_post_meta( $bpgpb_att_id, '_wp_attachment_image_alt', true );
			if ( $bpgpb_alt ) {
				$bpgpb_photo['alt'] = $bpgpb_alt;
			}
		}

		// Schema.org ImageObject for each photo (skip videos — those would need a
		// VideoObject with different required fields).
		if ( ! empty( $bpgpb_photo['url'] ) && ( ! isset( $bpgpb_photo['type'] ) || 'video' !== $bpgpb_photo['type'] ) ) {
			$bpgpb_image = array(
				'@type'      => 'ImageObject',
				'contentUrl' => $bpgpb_photo['url'],
			);
			if ( ! empty( $bpgpb_photo['thumb'] ) ) {
				$bpgpb_image['thumbnailUrl'] = $bpgpb_photo['thumb'];
			}
			if ( ! empty( $bpgpb_photo['width'] ) ) {
				$bpgpb_image['width'] = (int) $bpgpb_photo['width'];
			}
			if ( ! empty( $bpgpb_photo['height'] ) ) {
				$bpgpb_image['height'] = (int) $bpgpb_photo['height'];
			}
			// Prefer a saved alt text (Feature: per-image alt), fall back to title.
			$bpgpb_name = '';
			if ( ! empty( $bpgpb_photo['alt'] ) ) {
				$bpgpb_name = $bpgpb_photo['alt'];
			} elseif ( ! empty( $bpgpb_photo['title'] ) ) {
				$bpgpb_name = $bpgpb_photo['title'];
			}
			if ( $bpgpb_name ) {
				$bpgpb_image['name']    = $bpgpb_name;
				$bpgpb_image['caption'] = $bpgpb_name;
			}

			$bpgpb_schema_items[] = array(
				'@type'    => 'ListItem',
				'position' => count( $bpgpb_schema_items ) + 1,
				'item'     => $bpgpb_image,
			);
		}
	}
	unset( $bpgpb_photo );
}

$bpgpb_wrapper = get_block_wrapper_attributes(
	array(
		'id'    => $bpgpb_id,
		'class' => 'bpgpb-gallery',
	)
);
?>
<div
	<?php echo wp_kses_post( $bpgpb_wrapper ); ?>
	data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'>
</div>
<?php
// Schema.org JSON-LD (an ItemList of ImageObject) so Google can index the
// gallery images for Google Images / rich results. wp_json_encode escapes
// forward slashes by default, which keeps a stray "</script>" from breaking out.
if ( ! empty( $bpgpb_schema_items ) ) {
	$bpgpb_schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $bpgpb_schema_items,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $bpgpb_schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
}
?>
