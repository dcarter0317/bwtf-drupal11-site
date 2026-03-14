<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

use Drupal\media_gallery\MediaGalleryConstants;

/**
 * Tests image field uses the correct Photoswipe formatter and settings.
 */
class MediaGalleryDisplayConfigTest extends MediaGalleryKernelTestBase {

  /**
   * Tests the view display settings for the media_gallery image field.
   */
  public function testImageFieldFormatterViewDisplaySettings() {
    // Load the entity view display configuration for the 'default' view
    // mode of the 'media_gallery' bundle.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display  EntityViewDisplay for media gallery */
    $display = EntityViewDisplay::load('media_gallery.media_gallery.default');

    $this->assertNotNull($display, 'Entity view display media_gallery.media_gallery.default was found.');

    $component = $display->getComponent('images');
    $this->assertNotEmpty($component, 'Images field is configured in the display.');

    $this->assertEquals('photoswipe_field_formatter', $component['type'], 'Formatter is set to Photoswipe Image Field Formatter.');
    $expected_settings = [
      'photoswipe_image_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_IMAGE_STYLE,
      'photoswipe_thumbnail_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_THUMBNAIL_STYLE,
    ];

    foreach ($expected_settings as $key => $expected_value) {
      $this->assertArrayHasKey($key, $component['settings'], "Formatter setting '$key' exists.");
      $this->assertEquals($expected_value, $component['settings'][$key], "Formatter setting '$key' is '$expected_value'.");
    }
  }

  /**
   * Tests the form display settings for the media_gallery image field.
   */
  public function testImageFieldFormatterFormDisplaySettings() {
    $field_definitions = $this->container->get('entity_field.manager')->getFieldDefinitions('media_gallery', 'media_gallery');
    $images_field_definition = $field_definitions['images'];

    $media_bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');

    $expected_bundles_assoc = array_combine(
      array_keys($media_bundle_info), array_keys($media_bundle_info));

    // Test the handler settings.
    $handler_settings = $images_field_definition->getSetting('handler_settings');
    $this->assertEquals($expected_bundles_assoc, $handler_settings['target_bundles'], 'Target bundles set to assoc array of bundle names.');

    // Test media_library_widget settings.
    $expected_bundles = array_keys($media_bundle_info);
    $expected_settings = [
      'media_library_widget' => [
        'media_types' => $expected_bundles,
      ],
    ];

    $display = $images_field_definition->getDisplayOptions('form');
    $this->assertEquals('media_library_widget', $display['type'], 'Type is set to media library widget.');
    $this->assertEquals($expected_settings, $display['settings'], "Media library widget display options are set");
  }

}
