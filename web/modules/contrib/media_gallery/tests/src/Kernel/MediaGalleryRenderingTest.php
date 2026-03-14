<?php

namespace Drupal\Tests\media_gallery\Kernel;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Element;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

use Drupal\media_gallery\MediaGalleryUtilities;
use Drupal\media_gallery\Entity\MediaGallery;

/**
 * Tests the rendering of media galleries, including field preprocessing.
 */
class MediaGalleryRenderingTest extends MediaGalleryKernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'media_test_oembed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an image style for thumbnails.
    // This is not created by the parent setup and is needed for the test.
    ImageStyle::create([
      'name' => 'media_gallery_thumbnail',
      'label' => 'Media Gallery Thumbnail',
    ])->save();
  }

  /**
   * Tests that non-image media is altered correctly in preprocess_field.
   */
  public function testNonImageMediaIsAlteredInFieldPreprocess() {
    // Given a media gallery with mixed media.
    $gallery = $this->givenMediaGalleryWithMediaBundles(['image', 'remote_video']);

    // And given a PreprocessField $variables for this media gallery.
    $variables = $this->givenPreprocessFieldVariablesForGallery($gallery);

    // When the preprocess_field hook is called.
    media_gallery_preprocess_field($variables);

    // Then the video item should be the second item.
    $video_item_render_array = $variables['items'][1]['content'];
    $this->assertNotNull($video_item_render_array, 'Video item render array was found.');
    // And then the video item's render array should be as expected.
    $this->assertEquals('html_tag', $video_item_render_array['#type']);
    $this->assertEquals('iframe', $video_item_render_array['#tag']);
    $this->assertArrayHasKey('src', $video_item_render_array['#attributes']);
    $this->assertStringContainsString('media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DdQw4w9WgXcQ', $video_item_render_array['#attributes']['src']);
    // And then the video has the expected CSS classes.
    $this->assertContains('media-oembed-content', $video_item_render_array['#attributes']['class']);
    $this->assertContains('media-gallery-item--remote_video', $video_item_render_array['#attributes']['class']);
    // And then the height and width attributes are valid integers.
    $this->assertArrayHasKey('width', $video_item_render_array['#attributes']);
    $this->assertIsInt($video_item_render_array['#attributes']['width']);
    $this->assertArrayHasKey('height', $video_item_render_array['#attributes']);
    $this->assertIsInt($video_item_render_array['#attributes']['height']);
    // And then the title attribute is a string.
    $this->assertArrayHasKey('title', $video_item_render_array['#attributes']);
    $this->assertIsString($video_item_render_array['#attributes']['title']);
    // And then the media/oembed.formatter library is attached.
    $this->assertContains('media/oembed.formatter', $video_item_render_array['#attached']['library']);

    // And then the image item should be the first item.
    $image_item_render_array = $variables['items'][0]['content'];
    $this->assertNotNull($image_item_render_array, 'Image item render array was found.');
    $this->assertEquals('photoswipe_image_formatter', $image_item_render_array['#theme']);
    $this->assertNotContains('media-gallery-item--image', $image_item_render_array['#attributes']['class'] ?? []);
  }

  /**
   * Tests that non-oEmbed media is altered correctly in preprocess_field.
   */
  public function testNonOembedMediaIsAlteredInFieldPreprocess() {
    // Given a media gallery with a non-image, non-oembed media type.
    $gallery = $this->givenMediaGalleryWithMediaBundles(['document']);

    // And given a PreprocessField $variables for this media gallery.
    $variables = $this->givenPreprocessFieldVariablesForGallery($gallery);

    // When the preprocess_field hook is called.
    media_gallery_preprocess_field($variables);

    // Then the #theme should be media.
    $document_item_render_array = $variables['items'][0]['content'];
    $this->assertArrayHasKey('#theme', $document_item_render_array);
    $this->assertEquals('media', $document_item_render_array['#theme']);
    // And then the #media item should match the expected item in the gallery.
    $this->assertArrayHasKey('#media', $document_item_render_array);
    $this->assertEquals($gallery->images[0]->target_id, $document_item_render_array['#media']->id());
    // And then the expected CSS class should be applied.
    $this->assertContains('media-gallery-item--document', $document_item_render_array['#attributes']['class']);
  }

  /**
   * Tests getImageDimensionsForGallery w/ empty photoswipe_thumbnail_style .
   */
  public function testImageDimensionsForGalleryWithEmptyThumbnailStyle() {
    // Given a media gallery.
    $gallery = $this->givenMockMediaGallery([
      'name' => 'Test Gallery Empty Thumbnail Style',
    ]);

    // And given the images field display settings have an empty
    // photoswipe_thumbnail_style. We need to mock the field definition and
    // its display options.
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $display_options = [
      'settings' => [
        'photoswipe_thumbnail_style' => '',
      ],
    ];

    $field_definition->method('getDisplayOptions')
      ->with('view')
      ->willReturn($display_options);

    // Mock the getFieldDefinitions method to return mocked field definition.
    $gallery->method('getFieldDefinitions')
      ->willReturn(['images' => $field_definition]);

    // When getImageDimensionsForGallery is called.
    $dimensions = MediaGalleryUtilities::getImageDimensionsForGallery($gallery);

    // Then it should return the default dimensions.
    $this->assertEquals([300, 180], $dimensions);
  }

  /**
   * Prepares field variables for a media gallery.
   *
   * @param \Drupal\media_gallery\Entity\MediaGallery $gallery
   *   The media gallery entity.
   *
   * @return array
   *   The prepared variables array.
   */
  protected function givenPreprocessFieldVariablesForGallery($gallery) {
    // 1. Build Field Render Array.
    $field_render_array = $gallery->get('images')->view('default');

    // 2. Simulate Preprocessing.
    $variables = [
      'element' => $field_render_array,
      'items' => [],
      'field_name' => 'images',
      'attributes' => [],
    ];
    foreach (Element::children($field_render_array) as $key) {
      $variables['items'][$key] = ['content' => $field_render_array[$key]];
    }
    return $variables;
  }

  /**
   * Returns a mock of the media gallery entity.
   *
   * @param array $values
   *   The values to use for the entity.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The media gallery mock.
   */
  protected function givenMockMediaGallery(array $values) {
    $gallery = $this->getMockBuilder(MediaGallery::class)
      ->disableOriginalConstructor()
      ->getMock();

    if (isset($values['name'])) {
      $gallery->method('label')->willReturn($values['name']);
    }

    return $gallery;
  }

  /**
   * Given a media gallery with a single media item of a given bundle.
   *
   * @param array $bundles
   *   The bundles to create media items for.
   *
   * @return \Drupal\media_gallery\Entity\MediaGallery
   *   The media gallery.
   */
  protected function givenMediaGalleryWithMediaBundles(array $bundles): MediaGallery {
    $media_items = [];
    foreach ($bundles as $bundle) {
      switch ($bundle) {
        case 'image':
          $media = Media::create([
            'bundle' => $bundle,
            'name' => 'Image',
          ]);
          break;

        case 'remote_video':
          $media = Media::create([
            'bundle' => $bundle,
            'name' => 'Remote Video',
            'field_media_oembed_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
          ]);
          break;

        case 'document':
          $media = Media::create([
            'bundle' => $bundle,
            'name' => 'Document',
          ]);
          break;

        default:
          throw new \InvalidArgumentException("Unknown bundle: $bundle");
      }
      $media->save();
      $media_items[] = $media;
    }

    return $this->givenMediaGalleryWithMedia($media_items);
  }

  /**
   * Given a media gallery with a given array of media items.
   *
   * @param array $media_items
   *   The media items to add to the gallery.
   *
   * @return \Drupal\media_gallery\Entity\MediaGallery
   *   The media gallery.
   */
  protected function givenMediaGalleryWithMedia(array $media_items): MediaGallery {
    $gallery = MediaGallery::create([
      'title' => 'My Test Gallery',
    ]);
    $gallery_media_items = [];
    foreach ($media_items as $media) {
      $gallery_media_items[] = ['target_id' => $media->id()];
    }
    $gallery->set('images', $gallery_media_items);
    $gallery->save();
    return $gallery;
  }

}
