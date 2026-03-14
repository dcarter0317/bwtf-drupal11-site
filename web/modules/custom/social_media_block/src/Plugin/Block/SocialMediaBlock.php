<?php

namespace Drupal\social_media_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;

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
