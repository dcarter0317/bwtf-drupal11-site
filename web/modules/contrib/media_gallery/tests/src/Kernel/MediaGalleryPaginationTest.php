<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\media\Entity\Media;
use Drupal\media_gallery\MediaGalleryUtilities;
use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryPagerTrait;
use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryCreationTrait;
use Drupal\Core\Render\Element;

/**
 * Tests the pagination logic in media_gallery.module .
 */
class MediaGalleryPaginationTest extends MediaGalleryKernelTestBase {

  use MediaGalleryPagerTrait;
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
   * Tests pagination of a field render array.
   */
  public function testPaginatedItemsAndRenderArrays() {
    // Given we have 10 fake media entities and corresponding render arrays.
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(10);
    // And given the current page is 2. (indexing starts at 0)
    $current_page = 1;
    $this->givenTheCurrentPageIs($current_page);

    // When the paginate function is called with the render array and 4 items
    // per page.
    $result = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 4);

    // Then expect items 4 through 7 (zero-based index)to be returned.
    $this->assertCount(4, $result['#items'], 'Paginated items count is correct.');
    $this->assertEquals('Item 4', $result[0]['#markup']);
    $this->assertEquals('Item 5', $result[1]['#markup']);
    $this->assertEquals('Item 6', $result[2]['#markup']);
    $this->assertEquals('Item 7', $result[3]['#markup']);
  }

  /**
   * Tests fallback when #items is missing.
   */
  public function testNoItemsKeyReturnsUnchanged() {
    // Given that the input does not have an #items key.
    $input = ['0' => ['#markup' => 'test']];

    // When the pagination function is called.
    $output = MediaGalleryUtilities::paginateMediaGallery($input, 4);

    // Then the output should remain unchanged.
    $this->assertEquals($input, $output, 'Unchanged when #items is missing.');
  }

  /**
   * Tests fallback when #items is the wrong type.
   */
  public function testInvalidItemsTypeReturnsUnchanged() {
    // Given the #items key is not of the expected type.
    $input = ['#items' => 'not a field item list'];

    // When the pagination function is called.
    $output = MediaGalleryUtilities::paginateMediaGallery($input, 4);

    // Then the output should remain unchanged.
    $this->assertEquals($input, $output, 'Unchanged when #items is not a FieldItemList.');
  }

  /**
   * Tests behavior with an empty items list.
   */
  public function testEmptyItemsReturnsUnchanged() {
    // Given an empty list.
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(0);

    // When the pagination function is called.
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 5);

    // Then the output should remain unchanged.
    $this->assertEquals($field_render_array, $output, 'Unchanged when #items is empty.');
  }

  /**
   * Tests behavior with fewer items than the page size.
   */
  public function testFewerItemsThanPageSize() {
    $this->givenTheCurrentPageIs(0);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(3);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 5);
    $this->assertCount(3, $output['#items'], 'Paginated items count is correct.');
    $this->assertEquals('Item 0', $output[0]['#markup']);
    $this->assertEquals('Item 1', $output[1]['#markup']);
    $this->assertEquals('Item 2', $output[2]['#markup']);
  }

  /**
   * Tests behavior when total items are an exact multiple of the page size.
   */
  public function testItemsAreExactMultipleOfPageSize() {
    $this->givenTheCurrentPageIs(0);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(6);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 3);
    $this->assertCount(3, $output['#items'], 'Only items for first page are returned.');
  }

  /**
   * Tests pagination when on the second page.
   */
  public function testReturnsSecondPage() {
    $this->givenTheCurrentPageIs(1);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(6);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 3);
    $this->assertCount(3, $output['#items'], 'Three items on second page.');
    $this->assertEquals('Item 3', $output[0]['#markup']);
  }

  /**
   * Tests behavior when current page is out of bounds.
   */
  public function testPageOutOfBoundsReturnsEmpty() {
    $this->givenTheCurrentPageIs(5);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(3);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 2);
    $this->assertEmpty($output['#items'], 'Returns empty when page exceeds total.');
  }

  /**
   * Tests behavior when items count is equal to page size.
   */
  public function testExactPageSizeMatch() {
    $this->givenTheCurrentPageIs(0);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(4);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 4);
    $this->assertCount(4, $output['#items'], 'Returns all items when count == page size.');
  }

  /**
   * Tests that keys in #items are reset after pagination.
   */
  public function testItemsArrayIsReindexed() {
    $this->givenTheCurrentPageIs(0);
    $field_render_array = $this->givenFieldRenderArrayWithNumItems(4);
    $output = MediaGalleryUtilities::paginateMediaGallery($field_render_array, 2);
    $this->assertSame(array_keys($output['#items']), [0, 1], 'Keys are reindexed after pagination.');
  }

  /**
   * Tests pagination of a gallery in layout builder.
   */
  public function testLayoutBuilderPagination() {
    // Given a media gallery with 5 media items.
    // And given it is set to be paginated
    // And given items_per_page is set to 3.
    $gallery = $this->givenGalleryWithImages(5, TRUE, 3);

    // And given a simulated layout builder render array.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery, TRUE);

    // And given the current page is 2.
    $this->givenTheCurrentPageIs(1);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // Then there should be 2 items on the second page.
    $componentOneContent = $variables['content']['_layout_builder']['section1']['region1']['component1']['content'][0];
    $children = Element::children($componentOneContent);
    $this->assertCount(2, $children);

  }

  /**
   * Tests a media gallery with no pager.
   */
  public function testMediaGalleryNoPager() {
    // Given a media gallery with 20 media items and no pager.
    $gallery = $this->givenGalleryWithImages(20);

    // And given a simulated render array.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // Then the output content array should not have pager configuration.
    $this->assertArrayNotHasKey('pager', $variables);
    // And then the render array should have 20 children.
    $this->assertCount(20, Element::children($variables['content']['images']));
  }

  /**
   * Tests a layout builder media gallery with no pager.
   */
  public function testLayoutBuilderMediaGalleryNoPager() {
    // Given a media gallery with 20 media items and no pager.
    $gallery = $this->givenGalleryWithImages(20);

    // And given a simulated render array in Layout Builder form.
    $variables = $this->givenMediaGalleryPreprocessVariables($gallery, TRUE);

    // When the preprocess function is called.
    media_gallery_preprocess_media_gallery($variables);

    // Then the output content array should not have pager configuration.
    $componentOneContent = $variables['content']['_layout_builder']['section1']['region1']['component1']['content'][0];
    $children = Element::children($componentOneContent);
    $this->assertArrayNotHasKey('pager', $variables);
    // And then the render array should have 20 children.
    $this->assertCount(20, $children);

  }

  /**
   * Produces a field render array with the provided number of mock items.
   *
   * @param int $numItems
   *   The number of items to put in the field render array.
   *
   * @return array
   *   a field render array with the provided number of mock items
   */
  public function givenFieldRenderArrayWithNumItems(int $numItems): array {
    $items = [];
    $render_arrays = [];

    for ($i = 0; $i < $numItems; $i++) {
      $media = $this->createMock(Media::class);
      $items[] = $media;
      $render_arrays[$i] = ['#markup' => "Item $i"];
    }

    // Create a mock EntityReferenceFieldItemList with an iterator.
    $mock_field_item_list = $this->getMockBuilder(EntityReferenceFieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getIterator'])
      ->getMock();

    $mock_field_item_list->method('getIterator')->willReturn(new \ArrayIterator($items));

    // Create a fake render array for the field with #items and numerically
    // keyed render arrays.
    $field_render_array = [
      '#items' => $mock_field_item_list,
    ] + $render_arrays;

    return $field_render_array;
  }

}
