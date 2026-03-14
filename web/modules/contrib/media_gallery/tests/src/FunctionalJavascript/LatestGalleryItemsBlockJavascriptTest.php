<?php

namespace Drupal\Tests\media_gallery\FunctionalJavascript;

use Drupal\block\Entity\Block;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\media_gallery\Entity\MediaGallery;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the JavaScript functionalities of the Latest Gallery Items block.
 *
 * @group media_gallery
 */
class LatestGalleryItemsBlockJavascriptTest extends WebDriverTestBase {

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
    'photoswipe',
    'swiper_formatter',
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable CDN for photoswipe.
    $config = $this->config('photoswipe.settings');
    $config->set('enable_cdn', TRUE);
    $config->save();

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

    // Create 10 image media items.
    $images = $this->createImages(10);

    // Create a media gallery and add the images to it.
    $media_gallery = MediaGallery::create([
      'name' => 'My Gallery',
      'status' => 1,
    ]);
    $media_ids = array_map(function ($media) {
      return $media->id();
    }, $images);
    $media_gallery->set('images', $media_ids);
    $media_gallery->save();

    // Place the block on the front page.
    $this->drupalPlaceBlock('media_gallery_latest_items_all_galleries', [
      'id' => 'latest_gallery_items',
      'region' => 'content',
      'label' => 'Latest Gallery Items',
    ]);
  }

  /**
   * Tests PhotoSwipe functionality.
   */
  public function testPhotoswipeFunctionality() {
    // When the user goes to the front page.
    $page = $this->whenUserGoesToFrontPage();

    // Then block's title should appear on the page.
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Latest Gallery Items');

    // And then the image link for the first image should be on the page.
    $first_image_link = $this->firstImageLinkIsPresent($page);

    // And then the href for the link should not be empty.
    $photoswipe_src = $this->imageHrefShouldBePresent($first_image_link);

    // When the user clicks the image.
    $first_image_link->click();

    // Then the PhotoSwipe overlay should become visible.
    $this->photoswipeOverlayShouldDisplay($assert_session);

    // And then image in the overlay should match data-photoswipe-src.
    $this->imageInPhotoswipeOverlayIsClickedImage($photoswipe_src, $page);
  }

  /**
   * Tests the Swiper layout.
   */
  public function testSwiperLayout() {
    // Given the block uses swiper layout.
    $block = $this->givenTheBlockUsesSwiperLayout();

    // When the user goes to the front page.
    $page = $this->whenUserGoesToFrontPage();

    // Then the Swiper container should be present.
    $assert_session = $this->assertSession();
    $swiper_container = $assert_session->waitForElementVisible('css', '.swiper-container');
    $this->assertNotNull($swiper_container);

    // And then the correct number of slides should be present.
    $slides = $page->findAll('css', '.swiper-slide');
    $this->assertCount($block->get('settings')['item_count'], $slides);

    // And then the JavaScript Swiper instance is initialized on the container.
    $this->assertJsCondition('document.querySelector(".swiper-container").swiper !== undefined');
  }

  /**
   * Tests PhotoSwipe integration with the Swiper layout.
   */
  public function testSwiperAndPhotoswipeIntegration() {
    // Given the block uses swiper layout.
    $this->givenTheBlockUsesSwiperLayout();

    // When the user goes to the front page.
    $page = $this->whenUserGoesToFrontPage();

    // Then the swiper container should be present.
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', '.swiper-container');

    // And then the first image should be present.
    $first_slide_link = $this->firstSwiperImageLinkIsPresent($page);

    // And then the Get the PhotoSwipe source from the href attribute.
    $photoswipe_src = $this->imageHrefShouldBePresent($first_slide_link);

    // When the user clicks the image.
    $first_slide_link->click();

    // The PhotoSwipe overlay should become visible.
    $this->photoswipeOverlayShouldDisplay($assert_session);

    // The image in the overlay should correspond to the href.
    $this->imageInPhotoswipeOverlayIsClickedImage($photoswipe_src, $page);
  }

  /**
   * Sets the block layout to 'swiper'.
   *
   * @return \Drupal\block\Entity\Block
   *   The modified block entity.
   */
  protected function givenTheBlockUsesSwiperLayout() {
    // Change the block layout to 'swiper'.
    $block = Block::load('latest_gallery_items');
    $block->set('settings', [
      'layout' => 'swiper',
      'layout_configuration' => ['swiper_template' => 'default'],
    ]);

    $block->save();
    return $block;
  }

  /**
   * Navigates to the front page.
   *
   * @return \Behat\Mink\Element\DocumentElement
   *   The page element.
   */
  protected function whenUserGoesToFrontPage() {
    $this->drupalGet('<front>');
    return $this->getSession()->getPage();
  }

  /**
   * Finds the first image link for PhotoSwipe.
   *
   * @param \Behat\Mink\Element\DocumentElement $page
   *   The page element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The link element.
   */
  protected function firstImageLinkIsPresent($page) {
    $first_image_link = $page->find('css', '.block-media-gallery-latest-items-all-galleries a.photoswipe');
    $this->assertNotNull($first_image_link);
    return $first_image_link;
  }

  /**
   * Finds the first image link within a Swiper slide.
   *
   * @param \Behat\Mink\Element\DocumentElement $page
   *   The page element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The link element.
   */
  protected function firstSwiperImageLinkIsPresent($page) {
    $first_image_link = $page->find('css', '.block-media-gallery-latest-items-all-galleries .swiper-slide a.photoswipe');
    $this->assertNotNull($first_image_link);
    return $first_image_link;
  }

  /**
   * Asserts that an image link has a non-empty href attribute.
   *
   * @param \Behat\Mink\Element\NodeElement $imageLink
   *   The image link element.
   *
   * @return string
   *   The value of the href attribute.
   */
  protected function imageHrefShouldBePresent($imageLink) {
    $href = $imageLink->getAttribute('href');
    $this->assertNotEmpty($href);
    return $href;
  }

  /**
   * Asserts that the PhotoSwipe overlay is displayed.
   *
   * @param \Drupal\Tests\WebAssert $assert_session
   *   The assertion session.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The PhotoSwipe element.
   */
  protected function photoswipeOverlayShouldDisplay($assert_session) {
    $photoswipeElement = $assert_session->waitForElementVisible('css', '.pswp');
    $this->assertNotNull($photoswipeElement);
    return $photoswipeElement;
  }

  /**
   * Asserts that image in the Photoswipe overlay is the one that was clicked.
   *
   * @param string $imageSrc
   *   The expected image source.
   * @param \Behat\Mink\Element\DocumentElement $page
   *   The page element.
   */
  protected function imageInPhotoswipeOverlayIsClickedImage($imageSrc, $page) {
    $this->assertJsCondition('document.querySelector("div.pswp__item[aria-hidden=\'false\'] .pswp__img").src === "' . $imageSrc . '"');
    $pswp_image = $page->find('css', 'div.pswp__item[aria-hidden="false"] .pswp__img');
    $this->assertNotNull($pswp_image);
    $this->assertEquals($imageSrc, $pswp_image->getAttribute('src'));
  }

  /**
   * Creates a number of image media entities.
   *
   * @param int $count
   *   The number of images to create.
   *
   * @return \Drupal\media\MediaInterface[]
   *   The created media entities.
   */
  protected function createImages(int $count): array {
    $images = [];
    $test_images = $this->getTestFiles('image');
    for ($i = 0; $i < $count; $i++) {
      $file = File::create([
        'uri' => $test_images[$i % count($test_images)]->uri,
      ]);
      $file->save();
      $media = Media::create([
        'bundle' => 'image',
        'name' => 'Image ' . $i,
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
