<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\UpdateException;
use Drupal\media_gallery\MediaGalleryConstants;
use Drupal\media_gallery\MediaGalleryInterface;

/**
 * Set a default value for the items_per_page field on existing galleries.
 */
function media_gallery_post_update_set_default_items_per_page(&$sandbox) {
  $entity_storage = \Drupal::entityTypeManager()->getStorage('media_gallery');

  if (!isset($sandbox['total'])) {
    // Find all media_gallery entities where items_per_page is not set.
    $query = $entity_storage->getQuery()
      ->accessCheck(FALSE)
      ->notExists('items_per_page');

    $sandbox['ids'] = $query->execute();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['current'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return t('No media galleries needed an update for items_per_page.');
    }
  }

  // Process up to 50 entities in each batch to avoid timeouts.
  $ids_to_process = array_slice($sandbox['ids'], $sandbox['current'], 50);

  /** @var \Drupal\media_gallery\MediaGalleryInterface[] $galleries */
  $galleries = $entity_storage->loadMultiple($ids_to_process);

  foreach ($galleries as $gallery) {
    if ($gallery instanceof MediaGalleryInterface && $gallery->get('items_per_page')->isEmpty()) {
      $gallery->set('items_per_page', 9);
      $gallery->save();
    }
    $sandbox['current']++;
  }

  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];

  return t('Updating items_per_page for existing media galleries: processed @current of @total.',
     ['@current' => $sandbox['current'], '@total' => $sandbox['total']]);
}

/**
 * Update Galleries using Media Photoswipe view mode to use PS field formatter.
 */
function media_gallery_post_update_switch_to_photoswipe_field_formatter(?array &$sandbox = NULL) : ?string {
  // Set up parameters.
  $entity_type = 'media_gallery';
  $bundle = 'media_gallery';
  $field_name = 'images';
  $old_type = 'entity_reference_entity_view';
  $old_view_mode = 'media_photoswipe';
  $new_type = 'photoswipe_field_formatter';

  $logger = \Drupal::logger('media_gallery');
  $entityViewDisplayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository  entity display repo */
  $entityDisplayRepository = \Drupal::service('entity_display.repository');
  $viewModeLabelsByModeId = $entityDisplayRepository->getViewModeOptionsByBundle($entity_type, $bundle);

  $mediaGalleryViewDisplayIds = $entityViewDisplayStorage->getQuery()
    ->condition('targetEntityType', $entity_type)
    ->condition('bundle', $bundle)
    ->execute();

  if (empty($mediaGalleryViewDisplayIds)) {
    $logger->info("No entity_view_display configs found for $entity_type:$bundle\n");
    return t("No updates view display updates were necessary");
  }

  foreach ($entityViewDisplayStorage->loadMultiple($mediaGalleryViewDisplayIds) as $id => $display) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $view_mode_label = $viewModeLabelsByModeId[$display->getMode()];

    $component = $display->getComponent($field_name);
    if (!$component) {
      $logger->info("[View Display $id] No component for field: $field_name\n");
      continue;
    }

    if (($component['type'] ?? NULL) !== $old_type) {
      $logger->info("[View Display $id] Skipping: formatter already changed or customized (found: {$component['type']})\n");
      continue;
    }

    if (is_array($component['settings']) && $component['settings']['view_mode'] !== $old_view_mode) {
      $logger->info("[View Display $id] Skipping: The view mode setting is not $old_view_mode\n");
      continue;
    }

    $logger->notice(
          "Changing formatter for @field in @display to @new_type", [
            '@field' => $field_name,
            '@display' => $id,
            '@new_type' => $new_type,
          ]
      );

    $component['type'] = $new_type;
    $component['settings'] = [
      'photoswipe_image_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_IMAGE_STYLE,
      'photoswipe_thumbnail_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_THUMBNAIL_STYLE,
    ];
    $display->setComponent($field_name, $component);

    try {
      $display->save();
    }
    catch (EntityStorageException $ex) {
      $logger->error("Could not update the image field view mode of [Media Gallery View Display $id]");
      throw new UpdateException(
        message: "Could not update the the image field view mode for Media Gallery View Mode: $view_mode_label",
        previous: $ex
      );
    }
  }

  return t("Media Gallery view displays were successfully updated.");
}

/**
 * Remove deprecated media photoswipe view mode that is no longer needed.
 */
function media_gallery_post_update_remove_media_photoswipe_view_mode(&$sandbox = NULL) {
  $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
  foreach ($bundles as $bundle => $value) {
    \Drupal::configFactory()->getEditable("core.entity_view_display.media.{$bundle}.media_photoswipe")->delete();
  }
  \Drupal::configFactory()->getEditable('core.entity_view_mode.media.media_photoswipe')->delete();
  return t("Deleted Media Photoswipe view mode");
}
