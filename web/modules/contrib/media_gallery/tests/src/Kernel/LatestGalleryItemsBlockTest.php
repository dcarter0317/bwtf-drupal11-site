<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\Session\AnonymousUserSession;

use Drupal\Tests\media_gallery\Kernel\Traits\MediaGalleryCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\media_gallery\Plugin\Block\LatestGalleryItemsBlock;

/**
 * Tests the LatestGalleryItemsBlock.
 *
 * @group media_gallery
 */
class LatestGalleryItemsBlockTest extends MediaGalleryKernelTestBase {

  use MediaGalleryCreationTrait;
  use UserCreationTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface|null
   */
  protected ?BlockManagerInterface $blockManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected ?RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->blockManager = $this->container->get('plugin.manager.block');
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Test that an empty array is returned when no galleries or media exist.
   */
  public function testEmptyState(): void {
    // Given: No galleries or media exist.
    // When: The block is rendered.
    $block_instance = $this->createBlock();
    $build = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($block_instance) {
      return $block_instance->build();
    });

    // Then: The block is empty.
    $this->assertEmpty($build, 'The block is empty when no galleries or media exist.');
  }

  /**
   * Test that an empty array is returned when no published media exist.
   */
  public function testBlockWithNoPublishedMedia(): void {
    // Given: A gallery with only unpublished media.
    $media = $this->createImageMedia(['status' => 0]);
    $this->givenMediaGalleryWithImages([$media]);

    // When: The block is rendered.
    $block_instance = $this->createBlock();
    $build = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($block_instance) {
      return $block_instance->build();
    });

    // Then: The block is empty.
    $this->assertEmpty($build, 'The block is empty when only unpublished media exist.');
  }

  /**
   * Tests the item count configuration.
   */
  public function testItemCountConfiguration(): void {
    // Given: A gallery with 10 published media items.
    $media_items = [];
    for ($i = 0; $i < 10; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // When: The block is rendered with an item count of 5.
    $block_instance = $this->createBlock(['item_count' => 5]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The block shows 5 items.
    $this->assertCount(5, Element::children($build['gallery']), 'The block respects the item count configuration.');

    // When: The block is rendered with an item count of 3.
    $block_instance = $this->createBlock(['item_count' => 3]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The block shows 3 items.
    $this->assertCount(3, Element::children($build['gallery']), 'The block respects a different item count configuration.');
  }

  /**
   * Tests the media order.
   */
  public function testMediaOrder(): void {
    // Given: A gallery with 3 media items with different creation times.
    $media_items = [];
    $time = time();
    for ($i = 0; $i < 3; $i++) {
      $media_items[] = $this->createImageMedia([
        'status' => 1,
        'created' => $time + $i,
      ]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // When: The block is rendered.
    $block_instance = $this->createBlock(['item_count' => 3]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The media items are ordered by most recent first.
    $expected_order = array_map(fn($media) => (int) $media->id(), array_reverse($media_items));
    unset($build['gallery']['#type']);
    unset($build['gallery']['#attributes']);
    unset($build['gallery']['#cache']);
    $actual_order = array_keys($build['gallery']);
    $this->assertSame($expected_order, $actual_order, 'The media items are ordered by most recent first.');
  }

  /**
   * Tests that duplicate media items are only shown once.
   */
  public function testDuplicateMediaItems(): void {
    // Given: A single media item is added to two different galleries.
    $mediaItem = $this->createImageMedia([
      'status' => 1,
    ]);
    $this->givenMediaGalleryWithImages([$mediaItem]);
    $this->givenMediaGalleryWithImages([$mediaItem]);

    // When: The block is rendered.
    $block_instance = $this->createBlock(['item_count' => 5]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The media item should appear only once in the result.
    $this->assertCount(1, Element::children($build['gallery']));
    $this->assertEquals($mediaItem->id(), $build['gallery'][$mediaItem->id()]['#object']->id());
  }

  /**
   * Tests access control for the block.
   */
  public function testAccessControl(): void {
    // Given: A non-admin user to own the media items.
    $mediaOwner = $this->createUser([]);

    // And given: 1 published & 1 unpublished media item in the same gallery.
    $publishedMedia = $this->createImageMedia([
      'status' => 1,
      'uid' => $mediaOwner->id(),
      'file_uid' => $mediaOwner->id(),
    ]);
    $unpublishedMedia = $this->createImageMedia([
      'status' => 0,
      'uid' => $mediaOwner->id(),
      'file_uid' => $mediaOwner->id(),
    ]);
    $this->givenMediaGalleryWithImages([$publishedMedia, $unpublishedMedia]);

    // Test Case 1: Unprivileged user.
    // When: An unprivileged (anonymous) user views the block.
    $anonSession = new AnonymousUserSession();
    \Drupal::service('account_switcher')->switchTo($anonSession);
    $access_result = \Drupal::entityTypeManager()->getAccessControlHandler('media')->access($publishedMedia, 'view', $anonSession);

    // Then: unprivileged user should not have access to the published media.
    $this->assertFalse($access_result, 'Unprivileged user should not have access to published media.');

    // And when: The block is rendered.
    $block = $this->createBlock(['item_count' => 5]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The block should be empty.
    $this->assertEmpty($build, 'Unprivileged users should not see any items.');

    \Drupal::service('account_switcher')->switchBack();

    // Test Case 2: Privileged user.
    // When: A user with 'view media' permission views the block.
    $privilegedUser = $this->createUser(['view media']);
    \Drupal::service('account_switcher')->switchTo($privilegedUser);

    $block = $this->createBlock(['item_count' => 5]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The block should only contain the published media item.
    $this->assertCount(1, Element::children($build['gallery']), 'Privileged users should only see published items.');
    $this->assertArrayHasKey($publishedMedia->id(), $build['gallery']);
    $this->assertArrayNotHasKey($unpublishedMedia->id(), $build['gallery']);

    \Drupal::service('account_switcher')->switchBack();
  }

  /**
   * Tests the cache tags for the block.
   */
  public function testCacheTags(): void {
    // Given: Media galleries and media items exist.
    $media1 = $this->createImageMedia(['status' => 1]);
    $media2 = $this->createImageMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$media1, $media2]);

    // When: The block is rendered.
    $block = $this->createBlock();
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The render array's cache tags must contain 'media_gallery_list'
    // and 'media_list'.
    $this->assertArrayHasKey('#cache', $build['gallery']);
    $this->assertArrayHasKey('tags', $build['gallery']['#cache']);
    $this->assertContains('media_gallery_list', $build['gallery']['#cache']['tags']);
    $this->assertContains('media_list', $build['gallery']['#cache']['tags']);
  }

  /**
   * Tests the handling of non-image media.
   */
  public function testNonImageMediaHandling(): void {
    // Given: A non-image media item (e.g., a video) is among the latest items.
    $videoMedia = $this->createVideoMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$videoMedia]);

    // When: The block is rendered.
    $block = $this->createBlock();
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The render array for the video item should be processed correctly.
    $this->assertArrayHasKey($videoMedia->id(), $build['gallery']);
    $videoRenderArray = $build['gallery'][$videoMedia->id()];
    $this->assertArrayHasKey('#theme', $videoRenderArray);
    $this->assertEquals('file_video', $videoRenderArray['#theme']);
    $this->assertArrayHasKey('#attributes', $videoRenderArray);
    $this->assertArrayHasKey('class', $videoRenderArray['#attributes']);
    $this->assertContains('media-gallery-item--video', $videoRenderArray['#attributes']['class']);
  }

  /**
   * Tests the grid layout.
   */
  public function testGridLayout(): void {
    // Given: Some media items exist in a gallery.
    $media_items = [];
    for ($i = 0; $i < 5; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // And given: block is configured with the `grid` layout and `4` columns.
    $block = $this->createBlock([
      'layout' => 'grid',
      'grid_columns' => 4,
    ]);

    // When: The block's render array is generated.
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The layout classes and styles should be correct.
    $this->assertArrayHasKey('#attributes', $build);
    $this->assertArrayHasKey('class', $build['#attributes']);
    $this->assertContains('media-gallery-layout--grid', $build['#attributes']['class']);
    $this->assertArrayHasKey('gallery', $build);
    $this->assertArrayHasKey('#attributes', $build['gallery']);
    $this->assertArrayHasKey('style', $build['gallery']['#attributes']);
    $this->assertEquals('grid-template-columns: repeat(4, 1fr);', $build['gallery']['#attributes']['style']);
  }

  /**
   * Tests the featured image grid layout.
   */
  public function testFeaturedImageGridLayout(): void {
    // Given: Some media items exist in a gallery.
    $media_items = [];
    for ($i = 0; $i < 5; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // And given: The block is configured with `featured_image_grid`.
    $block = $this->createBlock([
      'layout' => 'featured_image_grid',
      'grid_columns' => 3,
      'featured_image_grid_col_span' => 2,
      'featured_image_grid_row_span' => 2,
    ]);

    // When: The block's render array is generated.
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The layout classes/styles should be correct for the featured grid.
    $this->assertArrayHasKey('#attributes', $build);
    $this->assertArrayHasKey('class', $build['#attributes']);
    $this->assertContains('media-gallery-layout--featured_image_grid', $build['#attributes']['class']);
    $this->assertArrayHasKey('gallery', $build);
    $this->assertArrayHasKey('#attributes', $build['gallery']);
    $this->assertArrayHasKey('style', $build['gallery']['#attributes']);
    $this->assertEquals('grid-template-columns: repeat(3, 1fr);', $build['gallery']['#attributes']['style']);

    // And then: The first item should have the correct span styles.
    $child_keys = Element::children($build['gallery']);
    $first_item_key = $child_keys[0];
    $this->assertArrayHasKey($first_item_key, $build['gallery']);
    $first_item_render_array = $build['gallery'][$first_item_key];
    $this->assertArrayHasKey('#attributes', $first_item_render_array);
    $this->assertArrayHasKey('style', $first_item_render_array['#attributes']);
    $this->assertEquals('grid-row: span 2; grid-column: span 2;', $first_item_render_array['#attributes']['style']);
  }

  /**
   * Tests the horizontal layout.
   */
  public function testHorizontalLayout(): void {
    // Given: Some media items exist in a gallery.
    $media_items = [];
    for ($i = 0; $i < 5; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // And given: The block is configured with the `horizontal` layout.
    $block = $this->createBlock([
      'layout' => 'horizontal',
    ]);

    // When: The block's render array is generated.
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The layout class should be correct.
    $this->assertArrayHasKey('#attributes', $build);
    $this->assertArrayHasKey('class', $build['#attributes']);
    $this->assertContains('media-gallery-layout--horizontal', $build['#attributes']['class']);
  }

  /**
   * Test swiper layout behavior when module not installed.
   */
  public function testSwiperLayoutWithoutSwiperModule(): void {
    // Given: Some media items exist in a gallery.
    $media_items = [];
    for ($i = 0; $i < 5; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // And given: The block is configured with the `swiper` layout.
    $block = $this->createBlock([
      'layout' => 'swiper',
    ]);

    // When: The block's render array is generated.
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The layout should display an error.
    $this->assertEquals(['error'], array_keys($build['gallery']));
    $this->assertEquals([
      '#type' => 'container',
      '#attributes' => ['class' => ['messages', 'messages--warning']],
      '#markup' => 'The Swiper module is not installed. This layout will not be available without it.',
    ], $build['gallery']['error']);
  }

  /**
   * Tests the vertical layout.
   */
  public function testVerticalLayout(): void {
    // Given: Some media items exist in a gallery.
    $media_items = [];
    for ($i = 0; $i < 5; $i++) {
      $media_items[] = $this->createImageMedia(['status' => 1]);
    }
    $this->givenMediaGalleryWithImages($media_items);

    // And given: The block is configured with the `vertical` layout.
    $block = $this->createBlock([
      'layout' => 'vertical',
    ]);

    // When: The block's render array is generated.
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block->build());

    // Then: The layout class should be correct.
    $this->assertArrayHasKey('#attributes', $build);
    $this->assertArrayHasKey('class', $build['#attributes']);
    $this->assertContains('media-gallery-layout--vertical', $build['#attributes']['class']);
  }

  /**
   * Tests the blockForm method.
   */
  public function testBlockForm(): void {
    // Given: A block instance.
    $block_instance = $this->createBlock();
    $form = [];
    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');

    // When: The blockForm method is called.
    $form_output = $block_instance->blockForm($form, $form_state);

    // Then: The form should have the correct default values.
    $this->assertEquals(5, $form_output['item_count']['#default_value']);
    $this->assertEquals('grid', $form_output['layout_settings']['layout_wrapper']['layout']['#default_value']);
    $this->assertEquals(3, $form_output['layout_settings']['settings_wrapper']['grid_columns']['#default_value']);
    $this->assertEquals('thumbnail', $form_output['image_styles']['thumbnail_image_style']['#default_value']);
    $this->assertEquals('', $form_output['image_styles']['photoswipe_image_style']['#default_value']);
  }

  /**
   * Tests the blockSubmit method.
   */
  public function testBlockSubmit(): void {
    // Given: A block instance and a form state with submitted values.
    $block_instance = $this->createBlock();
    $form = [
      'settings' => [
        '#parents' => [],
        'layout_settings' => [
          '#parents' => ['layout_settings'],
          'layout_wrapper' => [],
          'settings_wrapper' => [
            // Parents are needed by SubformState to correctly extract values.
            '#parents' => ['layout_settings', 'settings_wrapper'],
          ],
        ],
      ],
    ];

    // Create a form state and populate it like the actual submission would be.
    $form_state = new FormState();

    $form_values = [
      'item_count' => 10,
      'image_styles' => [
        'thumbnail_image_style' => 'medium',
        'photoswipe_image_style' => 'large',
      ],
      'layout_settings' => [
        'layout_wrapper' => [
          'layout' => 'featured_image_grid',
        ],
        'settings_wrapper' => [
          'featured_image_column_span' => 3,
          'featured_image_row_span' => 3,
          'thumbnail_style_first' => 'large',
        ],
      ],
      'view_all' => [
        'view_all_show_link' => TRUE,
        'view_all_text' => "View galleries",
        'view_all_position' => 'top',
        'view_all_link_classes' => '.button',
      ],
    ];

    $form_state->setValues($form_values);

    // When: The blockSubmit method is called.
    $block_instance->blockSubmit($form, $form_state);

    // Then: The block configuration should be updated with submitted values.
    $config = $block_instance->getConfiguration();
    $this->assertEquals($form_values['item_count'], $config['item_count']);
    $this->assertEquals('featured_image_grid', $config['layout']);
    $this->assertEquals($form_values['image_styles']['thumbnail_image_style'], $config['thumbnail_image_style']);
    $this->assertEquals($form_values['image_styles']['photoswipe_image_style'], $config['photoswipe_image_style']);

    $layout_config = $form_values['layout_settings']['settings_wrapper'];
    $this->assertEquals($layout_config['featured_image_column_span'], $config['layout_configuration']['featured_image_column_span']);
    $this->assertEquals($layout_config['featured_image_row_span'], $config['layout_configuration']['featured_image_row_span']);
  }

  /**
   * Creates an instance of the LatestGalleryItemsBlock.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\media_gallery\Plugin\Block\LatestGalleryItemsBlock
   *   The block instance.
   */
  protected function createBlock(array $configuration = []): LatestGalleryItemsBlock {
    return $this->blockManager->createInstance(
      'media_gallery_latest_items_all_galleries',
      $configuration
    );
  }

  /**
   * Creates an image media entity.
   *
   * @param array $values
   *   Values to pass to the media creation.
   *
   * @return \Drupal\media\Entity\Media
   *   The created image media entity.
   */
  protected function createImageMedia(array $values = []): Media {
    // Create a file to use for the media item.
    $file_values = [
      'uri' => 'public://test-image.jpg',
    // Use file_uid if provided, else admin.
      'uid' => $values['file_uid'] ?? $this->user->id(),
    ];
    // Remove it from media values.
    unset($values['file_uid']);

    $file = File::create($file_values);
    $file->setPermanent();
    $file->save();

    $media = Media::create($values + [
      'bundle' => 'image',
      'name' => $this->randomString(),
      'field_media_image' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->save();
    return $media;
  }

  /**
   * Creates a video media entity.
   *
   * @param array $values
   *   Values to pass to the media creation.
   *
   * @return \Drupal\media\Entity\Media
   *   The created video media entity.
   */
  protected function createVideoMedia(array $values = []): Media {
    // Create a file to use for the media item.
    $file_values = [
      'uri' => 'public://test-video.mp4',
    // Use file_uid if provided, else admin.
      'uid' => $values['file_uid'] ?? $this->user->id(),
    ];
    // Remove it from media values.
    unset($values['file_uid']);

    $file = File::create($file_values);
    $file->setPermanent();
    $file->save();

    $media = Media::create($values + [
      'bundle' => 'video',
      'name' => $this->randomString(),
      'field_media_video_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->save();
    return $media;
  }

  /**
   * Tests that "View all" link is not displayed when configured to be hidden.
   */
  public function testViewAllLinkNotShown(): void {
    // Given: A gallery with a published media item.
    $media = $this->createImageMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$media]);

    // When: The block is rendered with the "show link" setting disabled.
    $block_instance = $this->createBlock(['view_all_show_link' => FALSE]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The "View all" link should not be in the render array.
    $this->assertArrayNotHasKey('view_all_link', $build);
    $this->assertArrayNotHasKey('items_wrapper', $build);

    // And: The gallery itself should be present.
    $this->assertArrayHasKey('gallery', $build);
  }

  /**
   * Tests that the "View all" link is displayed when configured to be visible.
   */
  public function testViewAllLinkShown(): void {
    // Given: A gallery with a published media item.
    $media = $this->createImageMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$media]);

    // When: The block is rendered with the "show link" setting enabled.
    $block_instance = $this->createBlock(['view_all_show_link' => TRUE]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The "View all" link should be in the render array.
    $this->assertArrayHasKey('view_all_link', $build);
    $this->assertEquals('View galleries', $build['view_all_link']['#title']);

    // And: The block should have the correct position class.
    $this->assertContains('view-all-position-bottom', $build['#attributes']['class']);
  }

  /**
   * Data provider for testViewAllLinkPositions().
   */
  public static function viewAllLinkPositionProvider() {
    return [
      'top' => ['top', ['view_all_link', 'gallery']],
      'bottom' => ['bottom', ['gallery', 'view_all_link']],
      'left' => ['left', ['items_wrapper']],
      'right' => ['right', ['items_wrapper']],
      'under_title' => ['under_title', ['gallery', 'view_all_link']],
    ];
  }

  /**
   * Tests the various positions of the "View all" link.
   *
   * @dataProvider viewAllLinkPositionProvider
   */
  public function testViewAllLinkPositions($position, $expected_structure): void {
    // Given: A gallery with a published media item.
    $media = $this->createImageMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$media]);

    // When: The block is rendered with the link position set.
    $block_instance = $this->createBlock([
      'view_all_show_link' => TRUE,
      'view_all_position' => $position,
    ]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The render array should have correct structure for the position.
    $this->assertEquals($expected_structure, Element::children($build));

    // And: The block should have the correct position class.
    $this->assertContains('view-all-position-' . $position, $build['#attributes']['class']);
  }

  /**
   * Tests that custom classes are correctly applied to the "View all" link.
   */
  public function testViewAllLinkCustomClasses(): void {
    // Given: A gallery with a published media item.
    $media = $this->createImageMedia(['status' => 1]);
    $this->givenMediaGalleryWithImages([$media]);

    // When: The block is rendered with custom classes for the link.
    $block_instance = $this->createBlock([
      'view_all_show_link' => TRUE,
      'view_all_link_classes' => 'my-class1 my-class2',
    ]);
    $build = $this->renderer->executeInRenderContext(new RenderContext(), fn() => $block_instance->build());

    // Then: The custom classes should be present on the link's render array.
    $this->assertContains('my-class1', $build['view_all_link']['#attributes']['class']);
    $this->assertContains('my-class2', $build['view_all_link']['#attributes']['class']);
    $this->assertContains('media-gallery-latest-items-view-all', $build['view_all_link']['#attributes']['class']);
  }

}
