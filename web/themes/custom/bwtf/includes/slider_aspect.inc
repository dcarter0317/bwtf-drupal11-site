<?php

/**
 * @file
 * Slider aspect ratio options, shared by theme settings and preprocess.
 */

/**
 * Returns the available slider aspect ratios.
 *
 * Keys are the values stored in bwtf.settings:slider_aspect.
 * - label: shown in theme settings.
 * - css:   value of the --hero-aspect custom property (the displayed ratio).
 * - src:   which image style set to use ('16' or '21').
 *
 * @return array<string, array{label: string, css: string, src: string}>
 */
function bwtf_slider_aspect_ratios(): array {
  return [
    '16:9' => ['label' => '16:9 (wide)', 'css' => '24/9', 'src' => '16'],
    '21:9' => ['label' => '21:9 (ultrawide)', 'css' => '32/9', 'src' => '21'],
    '4:3' => ['label' => '4:3 (standard)', 'css' => '4/3', 'src' => '16'],
    '2:1' => ['label' => '2:1 (panorama)', 'css' => '2/1', 'src' => '16'],
    '3:1' => ['label' => '3:1 (banner)', 'css' => '3/1', 'src' => '21'],
    '4:1' => ['label' => '4:1 (slim banner)', 'css' => '4/1', 'src' => '21'],
  ];
}
