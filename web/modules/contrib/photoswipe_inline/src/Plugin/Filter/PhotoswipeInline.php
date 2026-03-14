<?php

namespace Drupal\photoswipe_inline\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'PhotoSwipe Inline' filter.
 *
 * @Filter(
 *   id = "photoswipe_inline",
 *   title = @Translation("PhotoSwipe Inline Text Filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = -10
 * )
 */
class PhotoswipeInline extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = '<div class="photoswipe-gallery">' . $text . '</div>';
    $dom = Html::load($text);

    /** @var \Dom\NodeList $elements */
    $elements = $dom->getElementsByTagName('img');
    if ($elements->length === 0) {
      return new FilterProcessResult(Html::serialize($dom));
    }

    /** @var \Dom\Node $element */
    foreach ($elements as $element) {
      // If the editor already added a class in the ckeditor we skip adding
      // photoswipe since the image is intended for a different use-case.
      $parent = $element->parentNode;
      while ($parent) {
        if ($parent->nodeName === 'a') {
          // Skip to the next $element in the outer loop.
          continue 2;
        }
        $parent = $parent->parentNode;
      }

      if (!$element->hasAttribute('class') || str_contains($element->getAttribute('class'), 'photoswipe') === FALSE) {
        $anchor = $dom->createElement('a');
        $element->parentNode->insertBefore($anchor, $element);
        $anchor->appendChild($element);

        // Set the source image.
        $source = $element->getAttribute('src');
        $anchor->setAttribute('href', $source);

        // Image size logic.
        if (isset(parse_url($source)['host'])) {
          [$img_width, $img_height, $type, $attr] = getimagesize($source);
        }
        else {
          $path = urldecode(strtok($source, '?'));
          [$img_width, $img_height, $type, $attr] = getimagesize(DRUPAL_ROOT . $path);
        }
        $anchor->setAttribute('data-pswp-width', $img_width ?: $element->getAttribute('width'));
        $anchor->setAttribute('data-pswp-height', $img_height ?: $element->getAttribute('height'));

        // Add photoswipe class.
        if ($element->hasAttribute('class')) {
          $anchor->setAttribute('class', $element->getAttribute('class') . ' photoswipe');
        }
        else {
          $anchor->setAttribute('class', 'photoswipe');
        }
      }
    }

    return new FilterProcessResult(Html::serialize($dom));
  }

}
