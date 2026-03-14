<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Element;
use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryCreationTrait;
use Psr\Log\LoggerInterface;

/**
 * Tests the media gallery when used in the Layout Builder.
 */
class MediaGalleryLayoutBuilderTest extends MediaGalleryKernelTestBase {

  use MediaGalleryCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The media_gallery.module file is not loaded by default in kernel tests,
    // but it's where the function we want to test lives.
    $this->container->get('module_handler')->load('media_gallery');
  }

  /**
   * Tests that a gallery in layout builder with an images field works.
   */
  public function testLayoutBuilderWithImages() {
    // Given a media gallery with 5 images and a pager with 2 items per page.
    $gallery = $this->givenGalleryWithImages(5, TRUE, 2);

    // And given a simulated layout builder render array.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery, TRUE);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // Then the images should be in the content array and paginated.
    $componentOneContent = $variables['content']['_layout_builder']['section1']['region1']['component1']['content'][0];
    $children = Element::children($componentOneContent);
    $this->assertCount(2, $children);

    // And the pager should be present.
    $this->assertArrayHasKey('pager', $variables);
    $this->assertEquals('pager', $variables['pager']['#type']);
  }

  /**
   * Tests galleries in layout builder without an images field logs a notice.
   */
  public function testLayoutBuilderWithoutImages() {
    // Given a media gallery with no images.
    $gallery = $this->givenGalleryWithImages(0);

    // And given a layout builder render array without an images field.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery, TRUE);
    $variables['elements']['_layout_builder']['section1']['region1']['component1']['#derivative_plugin_id'] = 'some_other_field';

    // We expect a notice to be logged, so we set up a mock logger.
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('notice')
      ->with('Media gallery displayed in layout builder without images field in layout');

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())->method('get')
      ->with('media_gallery')
      ->willReturn($logger);

    $this->container->set('logger.factory', $logger_factory);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // The assertion is handled by the mock logger expectation.
  }

}
