<?php

namespace Drupal\media_gallery\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for media gallery layout plugins.
 *
 * Media gallery layouts are plugins that define how a collection of media items
 * are rendered. These layouts are typically used within blocks or other display
 * elements to present a visually appealing gallery of media.
 *
 * To create a new layout, you need to:
 * 1. Define a new plugin class that implements this interface. It's recommended
 *    to extend \Drupal\media_gallery\Plugin\MediaGalleryLayoutBase to inherit
 *    common functionality.
 * 2. Add the #[MediaGalleryLayout] attribute to your class, providing an `id`,
 *    `label`, and `description`.
 * 3. Implement the build() method to define the render array for your layout.
 * 4. Optionally, implement buildConfigurationForm() to provide settings for
 *    your layout.
 *
 * Layouts are managed by the
 * \Drupal\media_gallery\MediaGalleryLayoutPluginManager service and are
 * typically instantiated within a block or other UI component.
 *
 * @see \Drupal\media_gallery\Attribute\MediaGalleryLayout
 * @see \Drupal\media_gallery\Plugin\MediaGalleryLayoutBase
 * @see \Drupal\media_gallery\Plugin\Block\LatestGalleryItemsBlock
 * @see plugin_api
 */
interface MediaGalleryLayoutInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Builds the render array for the layout.
   *
   * @param \Drupal\media\MediaInterface[] $media_items
   *   The media items to be rendered.
   * @param array $gallery_attributes
   *   Additional attributes for the gallery container. These are merged with
   *   the default attributes.
   *
   * @return array
   *   A render array.
   */
  public function build(array $media_items, array $gallery_attributes = []): array;

  /**
   * Returns the configuration of the plugin.
   *
   * @return array
   *   The plugin configuration.
   */
  public function getConfiguration();

  /**
   * Sets the configuration of the plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration);

}
