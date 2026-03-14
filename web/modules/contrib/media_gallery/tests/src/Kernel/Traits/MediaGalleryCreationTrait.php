<?php

namespace Drupal\Tests\media_gallery\Kernel\Traits;

use Drupal\media\Entity\Media;
use Drupal\media_gallery\Entity\MediaGallery;

/**
 * Provides a trait for creating media galleries in tests.
 */
trait MediaGalleryCreationTrait {

  /**
   * Creates a media gallery with a given number of images.
   *
   * @param int $num_images
   *   The number of images to add to the gallery.
   * @param bool $with_pager
   *   Whether the gallery should use a pager.
   * @param int $items_per_page
   *   The number of items to show per page.
   * @param bool $reverse
   *   Whether the gallery order should be reversed.
   *
   * @return \Drupal\media_gallery\Entity\MediaGallery
   *   The created media gallery.
   */
  protected function givenGalleryWithImages(int $num_images, bool $with_pager = FALSE, int $items_per_page = 12, bool $reverse = FALSE): MediaGallery {
    $gallery = MediaGallery::create([
      'title' => 'My Test Gallery',
      'use_pager' => $with_pager,
      'items_per_page' => $items_per_page,
      'reverse' => $reverse,
    ]);

    $media_items = [];
    if ($num_images > 0) {
      for ($i = 0; $i < $num_images; $i++) {
        $media = Media::create([
          'bundle' => 'image',
          'name' => "Image $i",
        ]);
        $media->save();
        $media_items[] = ['target_id' => $media->id()];
      }
      $gallery->set('images', $media_items);
    }
    $gallery->save();
    return $gallery;
  }

  /**
   * Creates a media gallery the given media items.
   *
   * @param array $mediaItems
   *   Media items to add to the gallery.
   *
   * @return \Drupal\media_gallery\Entity\MediaGallery
   *   The created media gallery.
   */
  protected function givenMediaGalleryWithImages(array $mediaItems = []): MediaGallery {
    $values = [
      'name' => $this->randomString(),
      'uid' => $this->user->id(),
      'images' => $mediaItems,
    ];
    $gallery = MediaGallery::create($values);
    $gallery->save();
    return $gallery;
  }

  /**
   * Creates a render array for a media gallery.
   *
   * @param \Drupal\media_gallery\Entity\MediaGallery $gallery
   *   The media gallery entity.
   * @param bool $layoutBuilder
   *   Whether to simulate a layout builder structure.
   *
   * @return array
   *   A render array for the media gallery.
   */
  public function givenMediaGalleryPreprocessVariables(MediaGallery $gallery, bool $layoutBuilder = FALSE) : array {
    $field_render_array = $gallery->get('images')->view('default');
    $variables = [
      'elements' => [
        '#media_gallery' => $gallery,
      ],
    ];

    if ($layoutBuilder) {
      $variables['elements']['_layout_builder'] = [
        'section1' => [
          'region1' => [
            'component1' => [
              '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
              'content' => [
                0 => $field_render_array,
              ],
            ],
          ],
        ],
      ];
    }
    else {
      $variables['elements']['images'] = $field_render_array;
    }

    return $variables;
  }

}
