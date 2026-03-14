<?php

namespace Drupal\media_gallery\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media_gallery\MediaGalleryItemRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Media gallery layout plugins.
 *
 * This class provides a foundation for creating new media gallery layouts.
 * It handles common tasks such as dependency injection, configuration
 * management, and basic rendering logic. Developers creating a new layout
 * should extend this class to simplify development.
 *
 * Key features provided by this base class:
 * - Integration with the plugin system via ContainerFactoryPluginInterface.
 * - A media gallery item renderer for consistent rendering of individual items.
 * - Default implementation of the build() method, which can be overridden for
 *   custom layout structures.
 * - Helper methods for rendering items (renderItems()) and building the final
 *   layout structure (buildLayout()).
 *
 * To create a new layout, extend this class and add the
 * `#[MediaGalleryLayout]` attribute. At a minimum, you should define the
 * plugin's `id`, `label`, and `description`. For custom behavior, you can
 * override methods like `build()`, `buildConfigurationForm()`, and
 * `defaultConfiguration()`.
 *
 * @see \Drupal\media_gallery\Plugin\MediaGalleryLayoutInterface
 * @see \Drupal\media_gallery\Attribute\MediaGalleryLayout
 * @see /drupal/web/modules/contrib/media_gallery/css/gallery_block.css
 */
abstract class MediaGalleryLayoutBase extends PluginBase implements MediaGalleryLayoutInterface, ContainerFactoryPluginInterface {

  /**
   * The media gallery item renderer.
   *
   * This service is responsible for rendering individual media items into a
   * consistent structure, typically including a thumbnail and a link to the
   * full media item.
   *
   * @var \Drupal\media_gallery\MediaGalleryItemRenderer
   */
  protected $itemRenderer;

  /**
   * Constructs a new MediaGalleryLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\media_gallery\MediaGalleryItemRenderer $item_renderer
   *   The media gallery item renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaGalleryItemRenderer $item_renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->itemRenderer = $item_renderer;
    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_gallery.item_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * This default implementation renders all media items and arranges them in a
   * simple container. Layouts that require more complex structures should
   * override this method.
   */
  public function build(array $media_items, array $gallery_attributes = []): array {
    $rendered_items = $this->renderItems($media_items);
    return $this->buildLayout($rendered_items, $gallery_attributes);
  }

  /**
   * Renders the media items using the item renderer.
   *
   * This method iterates through the media items and uses the item renderer
   * service to generate a render array for each one. The thumbnail and
   * PhotoSwipe image styles are retrieved from the plugin's configuration.
   *
   * This can be overridden by plugins that need custom item rendering logic,
   * such as applying different image styles to specific items.
   *
   * @param \Drupal\media\MediaInterface[] $media_items
   *   The media items to be rendered.
   *
   * @return array
   *   A render array of the media items, keyed by media item ID.
   */
  protected function renderItems(array $media_items): array {
    $items = [];
    $thumbnail_style = $this->configuration['thumbnail_image_style'];
    $photoswipe_image_style = $this->configuration['photoswipe_image_style'];

    foreach ($media_items as $media) {
      $items[$media->id()] = $this->itemRenderer->getRenderable($media, $thumbnail_style, $photoswipe_image_style);
    }

    return $items;
  }

  /**
   * Builds the common render array structure for a layout.
   *
   * This method constructs the outer shell of the gallery, including CSS
   * classes for styling and the necessary libraries. The rendered items are
   * placed inside a 'gallery' container, which is also configured for
   * PhotoSwipe integration.
   *
   * @param array $items
   *   The rendered media items.
   * @param array $gallery_attributes
   *   Additional attributes for the gallery container. These are merged with
   *   the default attributes.
   *
   * @return array
   *   The final render array for the block.
   */
  protected function buildLayout(array $items, array $gallery_attributes = []): array {

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['photoswipe-gallery'],
      ],
    ];

    if (!empty($gallery_attributes)) {
      $build['#attributes'] = array_merge_recursive($build['#attributes'], $gallery_attributes);
    }

    $build += $items;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // By default, no validation is performed. Override this method to add
    // custom validation logic.
  }

  /**
   * {@inheritdoc}
   *
   * This default implementation saves all form values to the plugin's
   * configuration. Override this method if you need to process the form
   * values before saving them.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

}
