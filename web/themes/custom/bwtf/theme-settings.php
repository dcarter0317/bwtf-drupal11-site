<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function bwtf_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state, $form_id = NULL) {
  // Ensure file uploads are supported.
  $form['#attributes']['enctype'] = 'multipart/form-data';

  // Managed upload directory for slider images.
  $dir = 'public://bwtf/slider/';
  \Drupal::service('file_system')->prepareDirectory(
    $dir,
    FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
  );

  // Read current config.
  $config = \Drupal::config('bwtf.settings');

  // Top-level group.
  $form['bwtf_settings'] = [
    '#type' => 'details',
    '#title' => t('Slider Settings'),
    '#open' => TRUE,
  ];

  // Aspect ratio (16:9 or 21:9)
$form['bwtf_settings']['slider_aspect'] = [
  '#type' => 'radios',
  '#title' => t('Slider aspect ratio'),
  '#options' => ['16:9' => '16:9', '21:9' => '21:9'],
  '#default_value' => \Drupal::config('bwtf.settings')->get('slider_aspect') ?: '16:9',
  '#parents' => ['slider_aspect'],
];

// Overlay preset
$form['bwtf_settings']['slider_overlay_preset'] = [
  '#type' => 'select',
  '#title' => t('Overlay style'),
  '#options' => [
    'overlay-none'     => t('None'),
    'overlay-solid'    => t('Solid'),
    'overlay-gradient' => t('Gradient'),
    'overlay-multiply' => t('Multiply (blend)'),
  ],
  '#default_value' => \Drupal::config('bwtf.settings')->get('slider_overlay_preset') ?: 'overlay-solid',
  '#parents' => ['slider_overlay_preset'],
  '#description' => t('Controls the background of the overlay layer.'),
];

// Overlay colors (CSS variables)
$form['bwtf_settings']['slider_overlay_from'] = [
  '#type' => 'textfield',
  '#title' => t('Overlay color (from)'),
  '#default_value' => \Drupal::config('bwtf.settings')->get('slider_overlay_from') ?: 'rgba(0,0,0,.35)',
  '#parents' => ['slider_overlay_from'],
  '#description' => t('Any CSS color (e.g. rgba(0,0,0,.35) or #00000059).'),
];

$form['bwtf_settings']['slider_overlay_to'] = [
  '#type' => 'textfield',
  '#title' => t('Overlay color (to)'),
  '#default_value' => \Drupal::config('bwtf.settings')->get('slider_overlay_to') ?: 'rgba(0,0,0,.55)',
  '#parents' => ['slider_overlay_to'],
  '#description' => t('Used by the gradient preset; ignored by others.'),
];

// Autoplay delay (ms). 0 disables autoplay.
$form['bwtf_settings']['slider_autoplay_delay'] = [
  '#type' => 'number',
  '#title' => t('Autoplay delay (ms)'),
  '#default_value' => (int) (\Drupal::config('bwtf.settings')->get('slider_autoplay_delay') ?? 5000),
  '#min' => 0,
  '#step' => 100,
  '#description' => t('Time between slides in milliseconds. Set 0 to disable autoplay.'),
  '#parents' => ['slider_autoplay_delay'],
];


  // Show/Hide slider toggle (flatten key).
  $form['bwtf_settings']['slider_display'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Slider'),
    '#default_value' => (bool) $config->get('slider_display'),
    '#parents' => ['slider_display'],
    '#attributes' => ['id' => 'edit-slider-display'],
    '#description' => t('Check to display the homepage slider.'),
  ];

  $form['bwtf_settings']['intro'] = [
    '#markup' => t('Customize each slide below. Remember to click <em>Upload</em> next to each image before saving.'),
  ];

  // Slides container.
  $form['bwtf_settings']['slides'] = [
    '#type' => 'container',
  ];

  for ($i = 1; $i <= 6; $i++) {
    $container = "slide{$i}";

    $form['bwtf_settings']['slides'][$container] = [
      '#type' => 'details',
      '#title' => t('Slide @num', ['@num' => $i]),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[id="edit-slider-display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Text fields (flattened keys).
    foreach ([
      'title'     => 'Slide Title',
      'desc'      => 'Slide Description',
      'url'       => 'Slide URL',
      'link_text' => 'Slide Link Text',
    ] as $suffix => $label) {
      $key = "slide{$i}_{$suffix}";
      $form['bwtf_settings']['slides'][$container][$key] = [
        '#type' => $suffix === 'desc' ? 'textarea' : 'textfield',
        '#title' => t($label),
        '#default_value' => (string) ($config->get($key) ?? ''),
        '#parents' => [$key], // flatten to top-level
      ];
    }

    // managed_file needs an array of FIDs (or NULL).
    $stored = $config->get("slide{$i}_image");
    if (is_scalar($stored) && $stored) {
      $stored = [(int) $stored];
    }
    $default_fids = is_array($stored) ? array_values(array_filter($stored)) : NULL;

    $form['bwtf_settings']['slides'][$container]["slide{$i}_image"] = [
      '#type' => 'managed_file',
      '#title' => t('Slide @num Image', ['@num' => $i]),
      '#default_value' => $default_fids,
      '#upload_location' => $dir,
      '#file_extensions' => 'svg webp png jpg jpeg',
      '#description' => t('Choose a file, click <em>Upload</em>, then Save configuration.'),
      '#parents' => ["slide{$i}_image"], // flatten to top-level
    ];
  }

  $form['bwtf_settings']['note'] = [
    '#markup' => t('Tip: Use large enough images. Aim for sources 1600×900 or higher so upscaling stays off.'),
  ];

  // Social Media Settings.
  $form['social_settings'] = [
    '#type' => 'details',
    '#title' => t('Social Media Settings'),
    '#open' => FALSE,
  ];

  $form['social_settings']['intro'] = [
    '#markup' => '<p>' . t('Configure up to 8 social media links. Only populated entries will be displayed.') . '</p>',
  ];

  // Platform options.
  $platform_options = [
    '' => t('- None -'),
    'facebook' => t('Facebook'),
    'x-twitter' => t('X (Twitter)'),
    'instagram' => t('Instagram'),
    'youtube' => t('YouTube'),
    'linkedin' => t('LinkedIn'),
    'tiktok' => t('TikTok'),
    'github' => t('GitHub'),
    'patreon' => t('Patreon'),
    'custom' => t('Custom'),
  ];

  for ($i = 1; $i <= 8; $i++) {
    $container = "social{$i}";

    $form['social_settings'][$container] = [
      '#type' => 'details',
      '#title' => t('Social Media Link @num', ['@num' => $i]),
      '#open' => FALSE,
    ];

    $form['social_settings'][$container]["social{$i}_platform"] = [
      '#type' => 'select',
      '#title' => t('Platform'),
      '#options' => $platform_options,
      '#default_value' => (string) ($config->get("social{$i}_platform") ?? ''),
      '#parents' => ["social{$i}_platform"],
    ];

    $form['social_settings'][$container]["social{$i}_url"] = [
      '#type' => 'url',
      '#title' => t('URL'),
      '#default_value' => (string) ($config->get("social{$i}_url") ?? ''),
      '#parents' => ["social{$i}_url"],
      '#description' => t('Enter the complete URL (e.g., https://facebook.com/yourpage)'),
    ];
  }

  // Ensure our submit runs with the form.
  $form['#submit'][] = 'bwtf_theme_settings_form_submit';
}

/**
 * Submit handler: make files permanent and write values to bwtf.settings.
 */
function bwtf_theme_settings_form_submit(array &$form, FormStateInterface $form_state) {
  $config = \Drupal::configFactory()->getEditable('bwtf.settings');
  $file_usage = \Drupal::service('file.usage');

  // Save toggle.
  $config->set('slider_display', (bool) $form_state->getValue('slider_display'));
  $config = \Drupal::configFactory()->getEditable('bwtf.settings');

  // Existing saves...

  $config->set('slider_aspect', (string) ($form_state->getValue('slider_aspect') ?? '16:9'));
  $config->set('slider_overlay_preset', (string) ($form_state->getValue('slider_overlay_preset') ?? 'overlay-solid'));
  $config->set('slider_overlay_from', (string) ($form_state->getValue('slider_overlay_from') ?? 'rgba(0,0,0,.35)'));
  $config->set('slider_overlay_to', (string) ($form_state->getValue('slider_overlay_to') ?? 'rgba(0,0,0,.55)'));

  // Save autoplay delay.
  $delay = (int) ($form_state->getValue('slider_autoplay_delay') ?? 5000);
  if ($delay < 0) { $delay = 0; }
  $config->set('slider_autoplay_delay', $delay);


  for ($i = 1; $i <= 6; $i++) {
    // Save text fields.
    foreach (['title', 'desc', 'url', 'link_text'] as $suffix) {
      $key = "slide{$i}_{$suffix}";
      $config->set($key, (string) ($form_state->getValue($key) ?? ''));
    }

    // Normalize managed_file value and save as an array of FIDs.
    $raw  = $form_state->getValue("slide{$i}_image") ?? [];
    $fids = is_array($raw) ? array_values(array_filter($raw)) : ($raw ? [(int) $raw] : []);
    $fid  = (int) ($fids[0] ?? 0);

    if ($fid) {
      $file = File::load($fid);
      if ($file instanceof FileInterface) {
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
        // Record usage so file_cleanup doesn't delete it.
        // module = 'bwtf', type = 'config', id = 'bwtf.settings'
        $file_usage->add($file, 'bwtf', 'config', 'bwtf.settings');
      }
    }

    $config->set("slide{$i}_image", $fid ? [$fid] : []);
  }

  // Save social media settings.
  for ($i = 1; $i <= 8; $i++) {
    $config->set("social{$i}_platform", (string) ($form_state->getValue("social{$i}_platform") ?? ''));
    $config->set("social{$i}_url", (string) ($form_state->getValue("social{$i}_url") ?? ''));
  }

  $config->save();
  \Drupal::messenger()->addStatus(t('Slider settings saved.'));
}
