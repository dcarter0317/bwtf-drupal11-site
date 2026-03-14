<?php

namespace Drupal\media_gallery;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Render\Element;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Drupal\media\MediaInterface;
use Drupal\media_gallery\Entity\MediaGallery;

/**
 * Utility functions for the media_gallery module.
 */
class MediaGalleryUtilities {

  /**
   * Get image dimensions for the provided MediaGallery.
   *
   * @param \Drupal\media_gallery\Entity\MediaGallery $gallery
   *   The gallery to get dimensions for.
   *
   * @return array
   *   An array containing [width, height] as items.
   */
  public static function getImageDimensionsForGallery(MediaGallery $gallery) : array {
    $default_max_width = 300;
    $default_max_height = 180;

    $formatter_settings = $gallery->getFieldDefinitions()["images"]
      ->getDisplayOptions('view')['settings'];

    if (empty($formatter_settings['photoswipe_thumbnail_style'])) {
      return [$default_max_width, $default_max_height];
    }

    $style_name = $formatter_settings['photoswipe_thumbnail_style'];

    try {
      return self::getDimensionsForImageStyle($style_name);
    }
    catch (\Exception $ex) {
      \Drupal::logger('media_gallery')
        ->notice("Could not get dimensions for style: $style_name. {$ex->getMessage()}");
    }

    return [$default_max_width, $default_max_height];
  }

  /**
   * Get image dimensions for image style by its name.
   *
   * @param string $style_name
   *   The name of the image style to get dimensions for.
   *
   * @return array
   *   An array containing [width, height] as items.
   *
   * @throws \Exception
   *    If the image style is not found or doesn't contain dimensions.
   */
  public static function getDimensionsForImageStyle(string $style_name) : array {
    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style_name);

    if (!$image_style) {
      throw new \Exception("Unknown image style");
    }

    $found_width = NULL;
    $found_height = NULL;

    // An image style can have multiple effects. We check for one that
    // defines dimensions (like scale, resize, or crop).
    foreach ($image_style->getEffects() as $effect) {
      $config = $effect->getConfiguration();
      if (isset($config['data']['width'])) {
        $found_width = (int) $config['data']['width'];
      }
      if (isset($config['data']['height'])) {
        $found_height = (int) $config['data']['height'];
      }
      // If we found dimensions, we can stop.
      if (isset($found_width) || isset($found_height)) {
        break;
      }
    }

    if (!isset($found_width) || !isset($found_height)) {
      throw new \Exception("No dimensions found in image style");
    }
    return [$found_width, $found_height];
  }

  /**
   * Returns render array paginated based on $itemsPerPage and current page.
   *
   * @param array $galleryItems
   *   A render array for the images field of the media gallery.
   * @param int $itemsPerPage
   *   The number of gallery items to display per page.
   * @param bool $reverse
   *   Pass in true when items should be in reverse order.
   *
   * @return array
   *   The render array only containing items for the current page
   */
  public static function paginateMediaGallery(array $galleryItems, int $itemsPerPage, bool $reverse = FALSE): ?array {
    // The #items property must exist and be a field item list.
    if (!isset($galleryItems['#items']) || !$galleryItems['#items'] instanceof EntityReferenceFieldItemList) {
      return $galleryItems;
    }

    // A field render array contains numeric keys for each item's render array,
    // and string keys (starting with '#') for properties. We separate them.
    $item_render_arrays = array_filter($galleryItems, 'is_numeric', ARRAY_FILTER_USE_KEY);
    $properties = array_filter($galleryItems, 'is_string', ARRAY_FILTER_USE_KEY);

    // The #items property contains the raw field items. We need to paginate
    // both the raw items and their corresponding render arrays.
    $items = iterator_to_array($galleryItems['#items']);

    if ($reverse) {
      $items = array_reverse($items);
      $item_render_arrays = array_reverse($item_render_arrays, TRUE);
    }

    $total = \count($items);
    if ($total === 0) {
      return $galleryItems;
    }

    $pager_manager = \Drupal::service('pager.manager');
    $pager = $pager_manager->createPager($total, $itemsPerPage);
    $currentPage = $pager->getCurrentPage();

    // Calculate the offset for the current page.
    $offset = $currentPage * $itemsPerPage;

    // Slice both the raw items and their render arrays for the current page.
    $items_for_current_page = array_slice($items, $offset, $itemsPerPage);
    $render_arrays_for_current_page = array_slice($item_render_arrays, $offset, $itemsPerPage);

    // Rebuild the final render array. Start with the original properties.
    $output = $properties;
    // Replace #items with the paginated version.
    $output['#items'] = $items_for_current_page;
    // Add the paginated item render arrays back, preserving numeric keys.
    $output += $render_arrays_for_current_page;

    return $output;
  }

  /**
   * Set non image items to their own formatter.
   *
   * Alters the render array of a media_gallery 'images' field in-place,
   * assigning a fallback formatter to non-image media.
   *
   * This method is designed to be called from media_gallery_preprocess_field,
   * passing the $variables parameter in as the $rendered_field parameter to
   * this method.  Other structures may not contain the expected structure to
   * be compatible.
   *
   * @param array &$rendered_field
   *   The render array for a media_gallery's 'images' field (passed by ref).
   */
  public static function alterNonImageMediaRendering(array &$rendered_field): void {
    $dimensions = MediaGalleryUtilities::getImageDimensionsForGallery($rendered_field['element']['#object']);

    foreach ($rendered_field['items'] as $delta => $item) {
      /** @var \Drupal\media\MediaInterface|null $media */
      $media = $item['content']['#item']->entity ?? NULL;

      // If it's not an image media item, override its rendering behavior.
      if ($media instanceof MediaInterface && $media->bundle() !== 'image') {
        $rendered_field['items'][$delta]['content'] = self::getRenderArrayForNonImageMedia($media, $dimensions);
      }
    }
  }

  /**
   * Get a render array for a non-image media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   * @param array $dimensions
   *   An array containing [width, height] for the output.
   *
   * @return array
   *   A render array for the media item.
   */
  public static function getRenderArrayForNonImageMedia(MediaInterface $media, array $dimensions): array {
    $itemContent = [];
    // The photoswipe formatter may not produce a render array for non-image
    // media. We will replace it entirely with a standard entity view.
    $formatter_manager = \Drupal::service('plugin.manager.field.formatter');
    // Get the media source.
    $source = $media->getSource();
    // Check if it's an oEmbed source that we can handle this way.
    if ($source instanceof OEmbedInterface) {
      $source_field_name = $source->getSourceFieldDefinition($media->bundle->entity)->getName();
      $field_definition = $media->getFieldDefinition($source_field_name);
      $formatter_settings = [
        'max_width' => $dimensions[0],
        'max_height' => $dimensions[1],
        'loading' => ['attribute' => 'lazy'],
      ];

      // Create an instance of the oEmbed formatter with our settings.
      $formatter = $formatter_manager->createInstance('oembed', [
        'field_definition' => $field_definition,
        'settings' => $formatter_settings,
        'label' => 'hidden',
        'view_mode' => 'default',
        'third_party_settings' => [],
      ]);

      // Generate the render array using the configured formatter.
      $build = $formatter->view($media->get($source_field_name));
      $itemContent = $build[0] ?? [];
    }
    elseif ($source->getPluginId() === 'video_file') {
      $source_field_name = $source->getSourceFieldDefinition($media->bundle->entity)->getName();
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $media->get($source_field_name)->entity;

      if ($file) {
        $src = \Drupal::service('file_url_generator')->generate($file->getFileUri())->toString();
        $attributes = [
          'src' => $src,
          'width' => $dimensions[0],
          'height' => $dimensions[1],
          'controls' => 'controls',
        ];

        $itemContent = [
          '#theme' => 'file_video',
          '#file' => $file,
          '#attributes' => $attributes,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
      }
    }
    else {
      // Fallback for other non-image, non-oEmbed media types.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
      $itemContent = $view_builder->view($media, "default", $media->language()->getId());
    }
    if (is_array($itemContent)) {
      $itemContent['#attributes']['class'][] = 'media-gallery-item--' . $media->bundle();
    }
    return $itemContent;
  }

  /**
   * Finds the media gallery images field in the Layout Builder layout.
   *
   * @param array &$build
   *   A render array from Layout Builder, passed by reference.
   *
   * @return ?array&
   *   Returns a reference to the images field array in the layout builder
   *   layout or null if it is not present.
   */
  public static function &getLayoutBuilderImagesField(array &$build): ?array {
    $imagesField = NULL;
    foreach (Element::children($build) as $section_key) {
      $section =& $build[$section_key];
      foreach (Element::children($section) as $region_key) {
        $region =& $section[$region_key];
        foreach (Element::children($region) as $component_key) {
          $component =& $region[$component_key];
          if (isset($component['#derivative_plugin_id']) &&
            $component['#derivative_plugin_id'] === 'media_gallery:media_gallery:images') {
            $imagesField =& $component['content'][0];
            break 3;
          }
        }
      }
    }
    return $imagesField;
  }

}
