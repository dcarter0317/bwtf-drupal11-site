<?php

namespace Drupal\bwtf_social_icons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'BWTF Social Icons' block.
 *
 * @Block(
 *   id = "bwtf_social_icons_block",
 *   admin_label = @Translation("BWTF Social Icons Block"),
 * )
 */
class BwtfSocialIconsBlock extends BlockBase {
  public function build() {
    $cfg = \Drupal::config('bwtf.settings');

    $icon_map = [
      'facebook' => 'fa-brands fa-facebook',
      'x-twitter' => 'fa-brands fa-x-twitter',
      'instagram' => 'fa-brands fa-instagram',
      'youtube' => 'fa-brands fa-youtube',
      'linkedin' => 'fa-brands fa-linkedin',
      'tiktok' => 'fa-brands fa-tiktok',
      'github' => 'fa-brands fa-github',
      'patreon' => 'fa-brands fa-patreon',
      'custom' => 'fa-solid fa-link',
    ];

    $label_map = [
      'facebook' => 'Facebook',
      'x-twitter' => 'X (Twitter)',
      'instagram' => 'Instagram',
      'youtube' => 'YouTube',
      'linkedin' => 'LinkedIn',
      'tiktok' => 'TikTok',
      'github' => 'GitHub',
      'patreon' => 'Patreon',
      'custom' => 'Website',
    ];

    $social_links = [];
    for ($i = 1; $i <= 8; $i++) {
      $platform = (string) ($cfg->get("social{$i}_platform") ?? '');
      $url = (string) ($cfg->get("social{$i}_url") ?? '');
      if ($platform && $url) {
        $social_links[] = [
          'platform' => $platform,
          'url' => $url,
          'label' => $label_map[$platform] ?? ucfirst($platform),
          'icon' => $icon_map[$platform] ?? 'fa-solid fa-link',
        ];
      }
    }

    return [
      '#theme' => 'social',
      '#social_links' => $social_links,
      '#cache' => [
        'tags' => ['config:bwtf.settings'],
      ],
    ];
  }
}
