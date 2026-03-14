<?php

namespace Drupal\bwtf\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Social Media Links' block.
 *
 * @Block(
 *   id = "social_media_block",
 *   admin_label = @Translation("Social Media Links"),
 *   category = @Translation("BWTF Theme"),
 * )
 */
class SocialMediaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get social links from helper function.
    $social_links = bwtf_get_social_links();

    // Return render array using the social template.
    return [
      '#theme' => 'social',
      '#social_links' => $social_links,
      '#cache' => [
        'tags' => ['config:bwtf.settings'],
      ],
    ];
  }

}
