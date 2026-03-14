<?php

namespace Drupal\media_gallery\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Blank entity.
 *
 * @see \Drupal\media_gallery\Entity\MediaGallery.
 */
class MediaGalleryEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\media_gallery\MediaGalleryInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished media gallery entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published media gallery entities');

      case 'update':
        if ($account->hasPermission('edit any media gallery entities')) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIf($entity->getOwnerId() == $account->id() && $account->hasPermission('edit own media gallery entities'));

      case 'delete':
        if ($account->hasPermission('delete any media gallery entities')) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIf($entity->getOwnerId() == $account->id() && $account->hasPermission('delete own media gallery entities'));
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add media gallery entities');
  }

}
