<?php

namespace Drupal\media_gallery\Plugin\pathauto\AliasType;

use Drupal\pathauto\Plugin\pathauto\AliasType\EntityAliasTypeBase;

/**
 * A pathauto alias type plugin for media galleries.
 *
 * @AliasType(
 *   id = "media_gallery",
 *   label = @Translation("Media Gallery"),
 *   types = {"media_gallery"},
 *   provider = "media_gallery",
 *   context_definitions = {
 *     "media_gallery" = @ContextDefinition("entity:media_gallery", label = @Translation("Media gallery"))
 *   }
 * )
 */
class MediaGalleryAliasType extends EntityAliasTypeBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return 'media_gallery';
  }

}
