<?php

namespace Drupal\Tests\media_gallery\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\image\ImageStyleStorage;
use Drupal\media_gallery\Entity\MediaGallery;
use Drupal\media_gallery\MediaGalleryUtilities;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\media_gallery\MediaGalleryUtilities
 * @group media_gallery
 */
class MediaGalleryUtilitiesTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The image style storage.
   *
   * @var \Drupal\image\ImageStyleStorage
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->imageStyleStorage = $this->createMock(ImageStyleStorage::class);

    $this->entityTypeManager->method('getStorage')
      ->with('image_style')
      ->willReturn($this->imageStyleStorage);

    $logger = $this->createMock(LoggerChannel::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->with('media_gallery')->willReturn($logger);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getImageDimensionsForGallery
   */
  public function testGetImageDimensionsForGalleryWithInvalidStyle() {
    $this->imageStyleStorage->method('load')->with('invalid_style')->willReturn(NULL);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getDisplayOptions')
      ->with('view')
      ->willReturn(['settings' => ['photoswipe_thumbnail_style' => 'invalid_style']]);

    $gallery = $this->createMock(MediaGallery::class);
    $gallery->method('getFieldDefinitions')
      ->willReturn(['images' => $field_definition]);

    $dimensions = MediaGalleryUtilities::getImageDimensionsForGallery($gallery);
    $this->assertEquals([300, 180], $dimensions);
  }

  /**
   * @covers ::getDimensionsForImageStyle
   */
  public function testGetDimensionsForImageStyleWithNoStyle() {
    $this->imageStyleStorage->method('load')->with('non_existent_style')->willReturn(NULL);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unknown image style');
    MediaGalleryUtilities::getDimensionsForImageStyle('non_existent_style');
  }

  /**
   * @covers ::getDimensionsForImageStyle
   */
  public function testGetDimensionsForImageStyleWithNoDimensions() {
    $style = $this->createMock(ImageStyleInterface::class);
    $style->method('getEffects')->willReturn([]);
    $this->imageStyleStorage->method('load')->with('style_with_no_dimensions')->willReturn($style);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('No dimensions found in image style');
    MediaGalleryUtilities::getDimensionsForImageStyle('style_with_no_dimensions');
  }

}
