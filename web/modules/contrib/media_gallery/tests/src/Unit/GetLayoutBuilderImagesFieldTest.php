<?php

namespace Drupal\Tests\media_gallery\Unit;

use PHPUnit\Framework\TestCase;

use Drupal\media_gallery\MediaGalleryUtilities;

/**
 * Tests MediaGalleryUtilities::getLayoutBuilderImagesField.
 */
class GetLayoutBuilderImagesFieldTest extends TestCase {

  /**
   * Tests finding a match at the first component.
   */
  public function testFindsMatchAtFirstComponent() {
    // Given the the media gallery images field is in the first component
    // of the first region of the first section.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['found-me']],
          ],
        ],
      ],
    ];

    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);

    // The expected component should be found.
    $this->assertSame(['found-me'], $ref);
  }

  /**
   * Tests finding a match deeper in the structure.
   */
  public function testFindsMatchDeepInStructure() {
    // Given a build array where the target component is not the first one.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'other',
          ],
          'component2' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['deep-value']],
          ],
        ],
      ],
    ];

    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    // Then the correct component's content should be found.
    $this->assertSame(['deep-value'], $ref);
  }

  /**
   * Tests returning the first match when multiple matches exist.
   */
  public function testReturnsFirstMatchIfMultiple() {
    // Given a build array with multiple components that match the criteria.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['first']],
          ],
          'component2' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['second']],
          ],
        ],
      ],
    ];

    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    // Then the content of the first matching component should be returned.
    $this->assertSame(['first'], $ref);
  }

  /**
   * Tests returning null if no match is found.
   */
  public function testReturnsNullIfNoMatch() {
    // Given a build array with no components matching the criteria.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'other',
          ],
        ],
      ],
    ];

    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    // Then the result should be null.
    $this->assertNull($ref);
  }

  /**
   * Tests returning null for an empty build array.
   */
  public function testReturnsNullForEmptyBuild() {
    // Given an empty build array.
    $build = [];
    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    // Then the result should be null.
    $this->assertNull($ref);
  }

  /**
   * Tests handling components without a derivative plugin ID.
   */
  public function testHandlesComponentsMissingDerivativeId() {
    // Given a build array with a component that is missing the derivative
    // plugin ID.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [],
        ],
      ],
    ];

    // When the media gallery images field is retrieved.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    // Then the result should be null.
    $this->assertNull($ref);
  }

  /**
   * Tests that mutating the returned reference updates the original array.
   */
  public function testReferenceBehaviorModifiesOriginal() {
    // Given a build array with a matching component.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['original']],
          ],
        ],
      ],
    ];

    // When a reference to the component's content is retrieved and modified.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    $ref = 'changed';

    // Then the original build array should be updated.
    $this->assertSame('changed', $build['section1']['region1']['component1']['content'][0]);
  }

  /**
   * Tests that modifying the original array updates the returned reference.
   */
  public function testReferenceBehaviorReflectsOriginalChanges() {
    // Given a build array with a matching component.
    $build = [
      'section1' => [
        'region1' => [
          'component1' => [
            '#derivative_plugin_id' => 'media_gallery:media_gallery:images',
            'content' => [['original']],
          ],
        ],
      ],
    ];

    // When a reference to the component's content is retrieved and then the
    // original array is modified.
    $ref =& MediaGalleryUtilities::getLayoutBuilderImagesField($build);
    $build['section1']['region1']['component1']['content'][0] = 'changed';

    // Then the change should be reflected in the reference.
    $this->assertSame('changed', $ref);
  }

}
