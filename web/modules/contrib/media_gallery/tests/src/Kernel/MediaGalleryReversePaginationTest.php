<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Render\Element;

use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryCreationTrait;
use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryPagerTrait;

/**
 * Tests the reverse pagination logic in media_gallery.module.
 *
 * @group media_gallery
 */
class MediaGalleryReversePaginationTest extends MediaGalleryKernelTestBase {
  use MediaGalleryCreationTrait;
  use MediaGalleryPagerTrait;

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
   * Tests that reversing a gallery with a pager works correctly.
   *
   * This test is expected to fail with the buggy implementation.
   */
  public function testReverseWithPager() {
    $total_items = 15;
    $items_per_page = 12;

    // Given a media gallery with $total_items media items, with pager enabled
    // and reverse order set.
    $gallery = $this->givenGalleryWithImages($total_items, TRUE, $items_per_page, TRUE);

    // And given a simulated render array.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery);

    // When processing the first page.
    $this->givenTheCurrentPageIs(0);
    $page1_vars = $variables;
    media_gallery_preprocess_media_gallery($page1_vars);

    // Then expect page 1 to have the last $items_per_page items in reverse
    // order.
    $expected_page1_count = $items_per_page;
    $expected_page1_first_index = $total_items - 1;
    $expected_page1_last_index = $total_items - $items_per_page;

    $this->assertCount($expected_page1_count, Element::children($page1_vars['content']['images']));
    $this->assertEquals($expected_page1_first_index, $page1_vars['content']['images'][0]['#item']->getName());
    $this->assertEquals($expected_page1_last_index, $page1_vars['content']['images'][$items_per_page - 1]['#item']->getName());

    // When processing the second page.
    $this->givenTheCurrentPageIs(1);
    $page2_vars = $variables;
    media_gallery_preprocess_media_gallery($page2_vars);

    // Then expect page 2 to have the remaining items in reverse order.
    $expected_page2_count = $total_items - $items_per_page;
    $expected_page2_first_index = $expected_page2_count - 1;
    $expected_page2_last_index = 0;

    $this->assertCount($expected_page2_count, Element::children($page2_vars['content']['images']));
    $this->assertEquals($expected_page2_first_index, $page2_vars['content']['images'][0]['#item']->getName());
    $this->assertEquals($expected_page2_last_index, $page2_vars['content']['images'][$expected_page2_count - 1]['#item']->getName());
  }

  /**
   * Tests that reversing a gallery without a pager works correctly.
   */
  public function testReverseWithoutPager() {
    $total_items = 15;

    // Given a media gallery with $total_items media items, with pager disabled
    // and reverse order set.
    $gallery = $this->givenGalleryWithImages($total_items, FALSE, 12, TRUE);

    // And given a simulated render array.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // Then expect all items to be present in reverse order.
    $this->assertCount($total_items, Element::children($variables['content']['images']));
    $this->assertEquals($total_items - 1, $variables['content']['images'][0]['#item']->getName());
    $this->assertEquals(0, $variables['content']['images'][$total_items - 1]['#item']->getName());

    // And then the output content array should not have pager configuration.
    $this->assertArrayNotHasKey('pager', $variables);
  }

}
