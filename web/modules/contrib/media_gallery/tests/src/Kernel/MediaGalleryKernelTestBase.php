<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Base class for Media Gallery kernel tests.
 */
abstract class MediaGalleryKernelTestBase extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'views',
    'text',
    'filter',
    'media',
    'media_library',
    'photoswipe',
    'media_gallery',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('media_gallery');

    // The 'image' media type is a dependency for the media_gallery module's
    // configuration. We need to create it before we can install the config.
    $this->createMediaType('image', ['id' => 'image']);
    $this->createMediaType('oembed:video', ['id' => 'remote_video']);
    $this->createMediaType('video_file', ['id' => 'video']);
    $this->createMediaType('file', ['id' => 'document']);
    $this->createMediaType('audio_file', ['id' => 'audio']);

    // The parent::setUp() from MediaKernelTestBase already installs config
    // for 'field', 'system', 'image', 'file', and 'media'. We only need to
    // install the rest from this test's :: property.
    $this->installConfig(
          [
            'user',
            'views',
            'text',
            'media_library',
            'photoswipe',
            'media_gallery',
          ]
      );
  }

}
