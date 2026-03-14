<?php

namespace Drupal\media_gallery\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a media gallery layout plugin attribute object.
 *
 * @see \Drupal\media_gallery\Plugin\MediaGalleryLayoutInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MediaGalleryLayout extends Plugin {
  /**
   * Human readable name for the layout.
   */
  public TranslatableMarkup $label;


  /**
   * A short description of the layout.
   */
  public TranslatableMarkup $description;

  /**
   * The filename of the preview icon for the layout.
   *
   * The file should be located in the module's 'icons' directory.
   */
  public string $preview_icon;

  /**
   * Constructs a new MediaGalleryLayout object.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the layout.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   A short description of the layout.
   * @param string $preview_icon
   *   The filename of the preview icon for the layout.
   */
  public function __construct(
    string $id,
    TranslatableMarkup $label,
    TranslatableMarkup $description,
    string $preview_icon,
  ) {
    parent::__construct($id);
    $this->label = $label;
    $this->description = $description;
    $this->preview_icon = $preview_icon;
  }

}
