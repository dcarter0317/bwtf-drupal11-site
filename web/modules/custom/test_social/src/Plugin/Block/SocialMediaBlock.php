<?php

namespace Drupal\test_social\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Social Media Links' block (test copy).
 *
 * @Block(
 *   id = "social_media_block",
 *   admin_label = @Translation("Social Media Links (test)"),
 * )
 */
class SocialMediaBlock extends BlockBase {
  public function build() {
    $social_links = function_exists('bwtf_get_social_links') ? bwtf_get_social_links() : [];

    return [
      '#theme' => 'social',
      '#social_links' => $social_links,
      '#cache' => [
        'tags' => ['config:bwtf.settings'],
      ],
    ];
  }
}
