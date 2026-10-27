<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

require_once __DIR__ . '/includes/slider_aspect.inc';

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function bwtf_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state, $form_id = NULL)
{
  // Logo and favicon can also be picked from the media library; the chosen
  // file's URI is copied into the core path field before core validates it.
  foreach (['logo' => t('Logo'), 'favicon' => t('Favicon')] as $type => $label) {
    if (isset($form[$type]['settings'])) {
      $form[$type]['settings']["{$type}_media"] = [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => t('Choose @label from media library', ['@label' => strtolower((string) $label)]),
        '#cardinality' => 1,
        '#description' => t('Overrides the path above when an image is selected.'),
        '#weight' => -1,
      ];
    }
  }
  if (!in_array('bwtf_theme_settings_form_validate', $form['#validate'] ?? [], TRUE)) {
    $form['#validate'][] = 'bwtf_theme_settings_form_validate';
  }

  // Ensure file uploads are supported.
  $form['#attributes']['enctype'] = 'multipart/form-data';

  // Allow modern image formats for the logo upload (core only permits
  // png gif jpg jpeg apng svg).
  if (isset($form['logo']['settings']['logo_upload'])) {
    $form['logo']['settings']['logo_upload']['#upload_validators']['FileExtension']['extensions'] = 'png gif jpg jpeg apng svg webp avif';
  }

  // Read current config.
  $config = \Drupal::config('bwtf.settings');

  // Top-level group.
  $form['bwtf_settings'] = [
    '#type' => 'details',
    '#title' => t('Slider Settings'),
    '#open' => TRUE,
  ];

  // Display style
  $form['bwtf_settings']['slider_style'] = [
    '#type' => 'radios',
    '#title' => t('Slider view'),
    '#options' => array_map('t', bwtf_slider_styles()),
    '#default_value' => \Drupal::config('bwtf.settings')->get('slider_style') ?: 'standard',
    '#description' => t('<em>Standard view</em> fills the banner edge to edge and crops the image to fit. <em>Picture in picture</em> shows the whole image, centered, over a blurred copy of itself.'),
    '#parents' => ['slider_style'],
  ];

  // Image vertical alignment
  $form['bwtf_settings']['slider_valign'] = [
    '#type' => 'radios',
    '#title' => t('Image vertical alignment'),
    '#options' => array_map('t', bwtf_slider_valigns()),
    '#default_value' => \Drupal::config('bwtf.settings')->get('slider_valign') ?: 'center',
    '#description' => t('Which part of the image stays visible when the banner crops it. Applies to both views; has no visible effect when the image fits the banner exactly.'),
    '#parents' => ['slider_valign'],
  ];

  // Banner height
  $form['bwtf_settings']['slider_height'] = [
    '#type' => 'radios',
    '#title' => t('Slider height'),
    '#options' => array_map('t', bwtf_slider_heights()),
    '#default_value' => \Drupal::config('bwtf.settings')->get('slider_height') ?: 'medium',
    '#description' => t('The tallest the banner can get on desktop, relative to the screen height: about 26% (Compact), 34% (Medium) or 44% (Tall). A shorter banner leaves more page content visible without scrolling. The image is cropped, not squished.'),
    '#parents' => ['slider_height'],
  ];

  // Aspect ratio
  $form['bwtf_settings']['slider_aspect'] = [
    '#type' => 'radios',
    '#title' => t('Slider aspect ratio'),
    '#options' => array_map(static fn(array $r) => $r['label'], bwtf_slider_aspect_ratios()),
    '#default_value' => \Drupal::config('bwtf.settings')->get('slider_aspect') ?: '16:9',
    '#description' => t('The shape of the banner. The banner never grows taller than the Slider height setting, so on large desktop screens a tall ratio (4:3, 2:1) looks the same as a wide one. The ratio matters most on tablets and smaller windows, and it is not applied on phones. It also picks the image crop: 21:9, 3:1 and 4:1 use the ultrawide crop, the others use the 16:9 crop.'),
    '#parents' => ['slider_aspect'],
  ];

  // Overlay preset
  $form['bwtf_settings']['slider_overlay_preset'] = [
    '#type' => 'select',
    '#title' => t('Overlay style'),
    '#options' => [
      'overlay-none' => t('None'),
      'overlay-solid' => t('Solid'),
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
    '#markup' => t('Customize each slide below. Images come from the media library, so an image is only ever stored once.'),
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
      'title' => 'Slide Title',
      'desc' => 'Slide Description',
      'url' => 'Slide URL',
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

    // Pick from the media library instead of uploading a new copy each time.
    $stored = $config->get("slide{$i}_image");
    $current_fid = is_array($stored) ? (int) (reset($stored) ?: 0) : (int) ($stored ?: 0);

    $form['bwtf_settings']['slides'][$container]["slide{$i}_media"] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => t('Slide @num Image', ['@num' => $i]),
      '#default_value' => $current_fid ? bwtf_settings_media_id_for_fid($current_fid) : NULL,
      '#cardinality' => 1,
      '#description' => t('Select an image already in the library, or upload a new one once and reuse it anywhere.'),
    ];
  }

  $form['bwtf_settings']['note'] = [
    '#markup' => t('<strong>Image tips:</strong> Images should be at least 1440 px wide so they stay sharp on large screens. In <em>Standard view</em>, the image is cropped to fit the aspect ratio you chose, so keep faces and key details near the center. <em>Picture in picture</em> shows the whole image without cropping.'),
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
 * Validate handler: stash media selections and apply logo/favicon picks.
 *
 * Runs before core's validation. Media element values are removed from the
 * form values so core does not write them into the theme config.
 */
function bwtf_theme_settings_form_validate(array &$form, FormStateInterface $form_state)
{
  if ($form_state->get('bwtf_slide_media') !== NULL) {
    return;
  }
  $slides = [];
  for ($i = 1; $i <= 6; $i++) {
    $slides[$i] = bwtf_settings_first_id($form_state->getValue("slide{$i}_media"));
    $form_state->unsetValue("slide{$i}_media");
  }
  $form_state->set('bwtf_slide_media', $slides);

  foreach (['logo', 'favicon'] as $type) {
    $mid = bwtf_settings_first_id($form_state->getValue("{$type}_media"));
    $form_state->unsetValue("{$type}_media");
    if ($mid && ($fid = bwtf_settings_fid_for_media($mid)) && ($file = File::load($fid))) {
      $form_state->setValue("{$type}_path", $file->getFileUri());
      $form_state->setValue("default_{$type}", 0);
      if ($type === 'favicon') {
        $form_state->setValue('toggle_favicon', 1);
      }
    }
  }
}

/**
 * Normalizes a media_library element value to a single media ID.
 */
function bwtf_settings_first_id($value): ?int
{
  if (is_array($value)) {
    $value = $value['media_library_selection'] ?? reset($value);
  }
  $ids = array_filter(explode(',', (string) $value), 'is_numeric');
  return $ids ? (int) reset($ids) : NULL;
}

/**
 * Returns the file ID behind a media item, or 0.
 */
function bwtf_settings_fid_for_media(?int $mid): int
{
  $media = $mid ? Media::load($mid) : NULL;
  if (!$media) {
    return 0;
  }
  return (int) ($media->getSource()->getSourceFieldValue($media) ?: 0);
}

/**
 * Finds the image media item wrapping a file, creating one if needed.
 *
 * Slides saved before the media library existed only have a file ID; this
 * brings them into the library so they show as selected and can be reused.
 */
function bwtf_settings_media_id_for_fid(int $fid): ?int
{
  $storage = \Drupal::entityTypeManager()->getStorage('media');
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('bundle', 'image')
    ->condition('field_media_image.target_id', $fid)
    ->range(0, 1)
    ->execute();
  if ($ids) {
    return (int) reset($ids);
  }

  $file = File::load($fid);
  if (!$file instanceof FileInterface) {
    return NULL;
  }
  try {
    $media = Media::create([
      'bundle' => 'image',
      'name' => $file->getFilename(),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
      'field_media_image' => ['target_id' => $fid, 'alt' => $file->getFilename()],
    ]);
    $media->save();
    return (int) $media->id();
  }
  catch (\Exception $e) {
    \Drupal::logger('bwtf')->warning('Could not add file @fid to the media library: @msg', ['@fid' => $fid, '@msg' => $e->getMessage()]);
    return NULL;
  }
}

/**
 * Submit handler: make files permanent and write values to bwtf.settings.
 */
function bwtf_theme_settings_form_submit(array &$form, FormStateInterface $form_state)
{
  $config = \Drupal::configFactory()->getEditable('bwtf.settings');
  $file_usage = \Drupal::service('file.usage');

  // Save toggle.
  $config->set('slider_display', (bool) $form_state->getValue('slider_display'));
  $config = \Drupal::configFactory()->getEditable('bwtf.settings');

  // Existing saves...

  $config->set('slider_aspect', (string) ($form_state->getValue('slider_aspect') ?? '16:9'));
  $config->set('slider_style', (string) ($form_state->getValue('slider_style') ?? 'standard'));
  $config->set('slider_valign', (string) ($form_state->getValue('slider_valign') ?? 'center'));
  $config->set('slider_height', (string) ($form_state->getValue('slider_height') ?? 'medium'));
  $config->set('slider_overlay_preset', (string) ($form_state->getValue('slider_overlay_preset') ?? 'overlay-solid'));
  $config->set('slider_overlay_from', (string) ($form_state->getValue('slider_overlay_from') ?? 'rgba(0,0,0,.35)'));
  $config->set('slider_overlay_to', (string) ($form_state->getValue('slider_overlay_to') ?? 'rgba(0,0,0,.55)'));

  // Save autoplay delay.
  $delay = (int) ($form_state->getValue('slider_autoplay_delay') ?? 5000);
  if ($delay < 0) {
    $delay = 0;
  }
  $config->set('slider_autoplay_delay', $delay);


  for ($i = 1; $i <= 6; $i++) {
    // Save text fields.
    foreach (['title', 'desc', 'url', 'link_text'] as $suffix) {
      $key = "slide{$i}_{$suffix}";
      $config->set($key, (string) ($form_state->getValue($key) ?? ''));
    }

    // Resolve the selected media item to its underlying file ID.
    $media_ids = $form_state->get('bwtf_slide_media') ?? [];
    $fid = bwtf_settings_fid_for_media($media_ids[$i] ?? NULL);

    $old = $config->get("slide{$i}_image");
    $old_fid = is_array($old) ? (int) (reset($old) ?: 0) : (int) ($old ?: 0);
    if ($old_fid && $old_fid !== $fid && ($old_file = File::load($old_fid))) {
      // Media owns the file now; drop the legacy config usage.
      $file_usage->delete($old_file, 'bwtf', 'config', 'bwtf.settings');
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
