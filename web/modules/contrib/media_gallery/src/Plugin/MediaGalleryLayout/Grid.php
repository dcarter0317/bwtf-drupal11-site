<?php

namespace Drupal\media_gallery\Plugin\MediaGalleryLayout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_gallery\Attribute\MediaGalleryLayout;
use Drupal\media_gallery\Plugin\MediaGalleryLayoutBase;

/**
 * Provides a grid layout for the media gallery.
 *
 * This layout arranges media items in a responsive CSS grid. The number of
 * columns in the grid is configurable, allowing for flexible and adaptive
 * layouts. This class serves as a base for other grid-based layouts, such as
 * the `FeaturedImageGrid`.
 *
 * @see /drupal/web/modules/contrib/media_gallery/css/gallery_block.css
 */
#[MediaGalleryLayout(
  id: 'grid',
  label: new TranslatableMarkup('Grid'),
  description: new TranslatableMarkup('A responsive grid layout.'),
  preview_icon: 'grid.svg'
)]
class Grid extends MediaGalleryLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'grid_columns' => 3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['grid_columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Grid columns'),
      '#description' => $this->t('The number of columns to use for the grid layout.'),
      '#default_value' => $this->configuration['grid_columns'] ?? $this->defaultConfiguration()['grid_columns'],
      '#min' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $media_items, array $gallery_attributes = []): array {
    $cols = $this->configuration['grid_columns'];
    $gallery_attributes += [
      'style' => 'grid-template-columns: repeat(' . $cols . ', 1fr);',
    ];
    return parent::build($media_items, $gallery_attributes);
  }

}
