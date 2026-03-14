<?php

namespace Drupal\media_gallery;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a media gallery entity type.
 */
interface MediaGalleryInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the media gallery title.
   */
  public function getTitle(): string;

  /**
   * Sets the media gallery title.
   */
  public function setTitle(string $title): MediaGalleryInterface;

  /**
   * Gets the media gallery creation timestamp.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the media gallery creation timestamp.
   */
  public function setCreatedTime(int $timestamp): MediaGalleryInterface;

  /**
   * Returns the media gallery status.
   */
  public function isEnabled(): bool;

  /**
   * Sets the media gallery status.
   */
  public function setStatus(bool $status): MediaGalleryInterface;

}
