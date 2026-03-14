<?php

declare(strict_types=1);

namespace Drupal\Tests\media_entity_browser_media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * A test for the media entity browser with media library.
 *
 * @group media_entity_browser
 */
class MediaEntityBrowserMediaLibraryTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'inline_entity_form',
    'entity_browser',
    'entity_browser_entity_form',
    'media_entity_browser',
    'media_entity_browser_media_library',
    'media_library',
    'ctools',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(\array_keys($this->container->get('user.permissions')->getPermissions())));
    $this->createMediaType('oembed:video', [
      'label' => 'Video',
      'id' => 'video',
    ]);

    Media::create([
      'bundle' => 'video',
      'field_media_oembed_video' => [['value' => 'https://www.youtube.com/watch?v=JQFKVbfqz7w']],
    ])->save();
  }

  /**
   * Test the media entity browser.
   */
  public function testMediaBrowser(): void {
    $this->drupalGet('entity-browser/iframe/media_entity_browser_media_library');
    $this->clickLink('Choose existing media');
    $this->assertSession()->waitForElement('css', '.media-library-view');

    $this->assertSession()->elementExists('css', '.media-library-view');
    $this->assertSession()->elementExists('css', '.media-library-item');

    $this->assertSession()->elementNotExists('css', '.js-click-to-select.checked');
    $this->getSession()->getPage()->find('css', '.js-click-to-select input[type=checkbox]')->press();
    $this->assertNotNull($this->assertSession()->waitForElement('css', '.js-click-to-select.checked'));
  }

}
