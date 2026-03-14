<?php

namespace Drupal\Tests\media_gallery\Functional;

use Drupal\block\Entity\Block;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media_gallery\Entity\MediaGallery;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the Gallery block.
 *
 * @group media_gallery
 */
class GalleryBlockTest extends BrowserTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_gallery',
    'block',
    'views',
    'node',
    'text',
    'field',
    'file',
    'image',
    'media',
    'media_library',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * A user with permission to access the administrative theme.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The first media gallery.
   *
   * @var \Drupal\media_gallery\Entity\MediaGallery
   */
  protected $gallery1;

  /**
   * The second media gallery.
   *
   * @var \Drupal\media_gallery\Entity\MediaGallery
   */
  protected $gallery2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('image', ['id' => 'image']);

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer blocks',
      'administer media',
      'create media',
      'view the administration theme',
      'access media overview',
    ]);
    $this->drupalLogin($this->adminUser);

    // Create 5 image media items for the first gallery.
    $images1 = $this->createImages(5, 'one');

    // Create a media gallery and add the images to it.
    $this->gallery1 = MediaGallery::create([
      'name' => 'My Gallery',
      'status' => 1,
    ]);
    $media_ids1 = array_map(function ($media) {
      return $media->id();
    }, $images1);
    $this->gallery1->set('images', $media_ids1);
    $this->gallery1->save();

    // Create 3 image media items for the second gallery.
    $images2 = $this->createImages(3, 'two');

    // Create a second media gallery and add the images to it.
    $this->gallery2 = MediaGallery::create([
      'name' => 'Another Gallery',
      'status' => 1,
    ]);
    $media_ids2 = array_map(function ($media) {
      return $media->id();
    }, $images2);
    $this->gallery2->set('images', $media_ids2);
    $this->gallery2->save();
  }

  /**
   * Tests that the block displays the correct gallery.
   */
  public function testGalleryBlock() {
    // Place the block on the front page and configure it to show the first
    // gallery.
    $this->drupalPlaceBlock('media_gallery_gallery', [
      'id' => 'media_gallery_block',
      'region' => 'content',
      'label' => 'Media Gallery Block',
      'gallery_id' => $this->gallery1->id(),
    ]);

    // Go to the front page.
    $this->drupalGet('<front>');

    // Assert that the block title is visible.
    $this->assertSession()->pageTextContains('Media Gallery Block');

    // Assert that the images from the first gallery are visible.
    $this->assertSession()->elementsCount('css', '.photoswipe-gallery img', 5);

    // Change the block to show the second gallery.
    $block = Block::load('media_gallery_block');
    $settings = $block->get('settings');
    $settings['gallery_id'] = $this->gallery2->id();
    $block->set('settings', $settings);
    $block->save();
    \Drupal::cache('render')->deleteAll();

    // Go to the front page again.
    $this->drupalGet('<front>');

    // Assert that the images from the second gallery are visible.
    $this->assertSession()->elementsCount('css', '.photoswipe-gallery img', 3);
  }

  /**
   * Creates a number of image media entities.
   *
   * @param int $count
   *   The number of images to create.
   * @param string $prefix
   *   A prefix for the image names.
   *
   * @return \Drupal\media\MediaInterface[]
   *   The created media entities.
   */
  protected function createImages(int $count, string $prefix): array {
    $images = [];
    $test_images = $this->getTestFiles('image');
    for ($i = 0; $i < $count; $i++) {
      $file = File::create([
        'uri' => $test_images[$i % count($test_images)]->uri,
      ]);
      $file->save();
      $media = Media::create([
        'bundle' => 'image',
        'name' => 'Image ' . $prefix . ' ' . $i,
        'field_media_image' => [
          'target_id' => $file->id(),
        ],
        'status' => 1,
      ]);
      $media->save();
      $images[] = $media;
    }
    return $images;
  }

}
