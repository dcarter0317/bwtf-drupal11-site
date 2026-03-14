<?php

namespace Drupal\Tests\media_gallery\Functional;

use Drupal\media_gallery\Entity\MediaGallery;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests ListBuilder.
 */
class MediaGalleryListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_gallery',
  ];

  /**
   * Content manager user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * Content manager user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access media gallery overview',
      'add media gallery entities',
      'edit own media gallery entities',
      'delete own media gallery entities',
      'view published media gallery entities',
      'view unpublished media gallery entities',
    ];
    $this->user1 = $this->drupalCreateUser($permissions);
    $this->user2 = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests ListBuilder.
   */
  public function testListBuilder() {
    MediaGallery::create([
      'title' => $this->randomString(),
      'description' => $this->randomString(),
      'status' => TRUE,
      'uid' => $this->user2->id(),
    ])->save();

    $this->drupalLogin($this->user1);
    $this->drupalGet('admin/content/media-gallery');
    $this->assertSession()->pageTextContains('Total media galleries: 1');
  }

}
