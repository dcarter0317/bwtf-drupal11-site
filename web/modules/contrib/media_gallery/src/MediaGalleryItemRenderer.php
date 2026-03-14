<?php

namespace Drupal\media_gallery;

use Drupal\media\MediaInterface;

/**
 * A service for rendering individual media gallery items.
 */
class MediaGalleryItemRenderer {

  /**
   * Gets the render array for a media item.
   *
   * @param \Drupal\media\MediaInterface $media_item
   *   The media item.
   * @param string $thumbnail_style
   *   The thumbnail style.
   * @param string $photoswipe_image_style
   *   The photoswipe image style.
   *
   * @return array
   *   The render array for the media item.
   */
  public function getRenderable(MediaInterface $media_item, string $thumbnail_style, string $photoswipe_image_style): array {
    $source_field = $media_item->getSource()->getConfiguration()['source_field'];

    if ($media_item->bundle() === 'image') {
      $item = $media_item->get($source_field)->view([
        'label' => 'hidden',
        'type' => 'photoswipe_field_formatter',
        'formatter' => 'photoswipe_field_formatter',
        'settings' => [
          'photoswipe_thumbnail_style' => $thumbnail_style,
          'photoswipe_image_style' => $photoswipe_image_style,
        ],
      ]);
      $item['#attributes']->removeClass('photoswipe-gallery');
    }
    else {
      $dimensions = MediaGalleryUtilities::getDimensionsForImageStyle($thumbnail_style);
      $item = MediaGalleryUtilities::getRenderArrayForNonImageMedia($media_item, $dimensions);
    }

    return $item;
  }

}
