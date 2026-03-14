<?php

namespace Drupal\media_gallery\Plugin\MediaGalleryLayout;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_gallery\Attribute\MediaGalleryLayout;
use Drupal\media_gallery\Plugin\MediaGalleryLayoutBase;

/**
 * Provides a vertical layout for the media gallery.
 *
 * This layout renders media items in a simple vertical list. It relies entirely
 * on the functionality provided by the MediaGalleryLayoutBase class and does
 * not require any special configuration or custom rendering logic.
 *
 * The base class automatically adds a `media-gallery-layout--[layout-id]` CSS
 * class to the gallery container, where `[layout-id]` is the plugin's ID
 * (`vertical` in this case). This allows for layout-specific styling in a
 * theme's CSS file.
 *
 * @see /drupal/web/modules/contrib/media_gallery/css/gallery_block.css
 */
#[MediaGalleryLayout(
  id: 'vertical',
  label: new TranslatableMarkup('Vertical'),
  description: new TranslatableMarkup('A simple vertical list of items.'),
  preview_icon: 'vertical.svg'
)]
class Vertical extends MediaGalleryLayoutBase {

  // The entire build process is handled by the parent class, making this layout
  // a straightforward example of a basic layout implementation.
}
