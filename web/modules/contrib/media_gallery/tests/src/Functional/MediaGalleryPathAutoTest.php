<?php

namespace Drupal\Tests\media_gallery\Functional;

use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Tests pathauto media_gallery UI integration.
 */
class MediaGalleryPathAutoTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'media_gallery',
    'pathauto',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'administer media gallery',
      'access media gallery overview',
      'add media gallery entities',
      'edit any media gallery entities',
      'view published media gallery entities',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests pathauto integration.
   */
  public function testEnablingEntityTypes() {
    $this->drupalLogin($this->adminUser);

    $pattern = PathautoPattern::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => 'Media Gallery',
      'type' => 'media_gallery',
      'pattern' => '/sample/gallery/[media_gallery:id]',
      'weight' => 10,
    ]);
    $pattern->save();

    $this->drupalGet('/admin/config/search/path/settings');
    $edit = [
      'enabled_entity_types[media_gallery]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('admin/content/media-gallery/add');
    $edit = [
      'title[0][value]' => 'Sample Gallery',
      'description[0][value]' => 'Sample Gallery description',
    ];
    $this->submitForm($edit, 'Save');

    // Verify that an alias has been generated.
    $this->assertAliasExists(['alias' => '/sample/gallery/1']);
    $this->assertSession()->pageTextContains('Sample Gallery description');
  }

}
