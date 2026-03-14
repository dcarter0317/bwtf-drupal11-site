<?php

namespace Drupal\media_gallery;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media_gallery\Plugin\MediaGalleryLayoutInterface;
use Drupal\media_gallery\Attribute\MediaGalleryLayout;
use Drupal\Core\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\Core\Plugin\Discovery\InfoHookDecorator;

/**
 * Manages media gallery layout plugins.
 */
class MediaGalleryLayoutManager extends DefaultPluginManager {

  /**
   * Constructs a new MediaGalleryLayoutManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/MediaGalleryLayout',
      $namespaces,
      $module_handler,
      MediaGalleryLayoutInterface::class,
      MediaGalleryLayout::class
    );
    $this->setCacheBackend($cache_backend, 'media_gallery_layout_plugins');
    $this->alterInfo('media_gallery_layout_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AttributeClassDiscovery(
        $this->subdir,
        $this->namespaces,
        MediaGalleryLayout::class
      );
      $this->discovery = new InfoHookDecorator($discovery, $this->alterHook);
    }
    return $this->discovery;
  }

}
