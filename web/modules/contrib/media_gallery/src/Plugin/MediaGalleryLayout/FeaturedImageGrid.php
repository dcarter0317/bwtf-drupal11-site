<?php

namespace Drupal\media_gallery\Plugin\MediaGalleryLayout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_gallery\Attribute\MediaGalleryLayout;

/**
 * Provides a featured image grid layout for the media gallery.
 *
 * This layout displays media items in a CSS grid, with the first item being
 * larger, or "featured." The featured item can span multiple rows and columns,
 * creating a visually distinct focal point. The size of the featured item and
 * the number of columns in the grid are configurable.
 *
 * This layout extends the standard `Grid` layout and adds options to control
 * the dimensions of the featured item.
 *
 * @see /drupal/web/modules/contrib/media_gallery/css/gallery_block.css
 */
#[MediaGalleryLayout(
  id: 'featured_image_grid',
  label: new TranslatableMarkup('Featured Image Grid'),
  description: new TranslatableMarkup('A responsive grid with a larger first image.'),
  preview_icon: 'featured-image-grid.svg'
)]
class FeaturedImageGrid extends Grid {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'featured_image_column_span' => 2,
      'featured_image_row_span' => 2,
      'thumbnail_style_first' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $image_style_options = ['' => $this->t('Do not override')] + image_style_options(FALSE);
    asort($image_style_options);

    $form['featured_image_column_span'] = [
      '#type' => 'number',
      '#title' => $this->t('Featured image column span'),
      '#description' => $this->t('How many columns the first item should span.'),
      '#default_value' => $this->configuration['featured_image_column_span'] ?? $this->defaultConfiguration()['featured_image_column_span'],
      '#min' => 1,
    ];

    $form['featured_image_row_span'] = [
      '#type' => 'number',
      '#title' => $this->t('Featured image row span'),
      '#description' => $this->t('How many rows the first item should span.'),
      '#default_value' => $this->configuration['featured_image_row_span'] ?? $this->defaultConfiguration()['featured_image_row_span'],
      '#min' => 1,
    ];

    $form['thumbnail_style_first'] = [
      '#type' => 'select',
      '#title' => $this->t('Override first image thumbnail style'),
      '#description' => $this->t('Sometimes the first image should be displayed differently, e.g. larger than other images. This option overrides the first image style.'),
      '#default_value' => $this->configuration['thumbnail_style_first'] ?? $this->defaultConfiguration()['thumbnail_style_first'],
      '#options' => $image_style_options,
      '#empty_option' => $this->t('Do not override'),
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderItems(array $media_items): array {
    $items = [];
    $defaultThumbnailStyle = $this->configuration['thumbnail_image_style'];
    $featuredThumbnailStyle = $this->configuration['thumbnail_style_first'] ?: $defaultThumbnailStyle;
    $photoswipe_image_style = $this->configuration['photoswipe_image_style'];
    $isFirst = TRUE;

    foreach ($media_items as $media) {
      $thumbnailStyle = $isFirst ? $featuredThumbnailStyle : $defaultThumbnailStyle;
      $item = $this->itemRenderer->getRenderable($media, $thumbnailStyle, $photoswipe_image_style);

      if ($isFirst) {
        $row_span = $this->configuration['featured_image_row_span'];
        $col_span = $this->configuration['featured_image_column_span'];
        $item['#attributes']['style'] = "grid-row: span {$row_span}; grid-column: span {$col_span};";
        $isFirst = FALSE;
      }
      $items[$media->id()] = $item;
    }
    return $items;
  }

}
