<?php

namespace Drupal\media_gallery\Plugin\Block;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\media_gallery\MediaGalleryConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that shows a selected media gallery.
 */
#[Block(
  id: 'media_gallery_gallery',
  admin_label: new TranslatableMarkup("Media Gallery"),
  category: new TranslatableMarkup("Media Gallery")
)]
class GalleryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new GalleryBlock instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly PluginManagerInterface $layoutManager,
    protected readonly ExtensionPathResolver $pathResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('media_gallery.layout_manager'),
      $container->get('extension.path.resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'gallery_id' => '',
      'item_count' => 5,
      'thumbnail_image_style' => 'thumbnail',
      'photoswipe_image_style' => '',
      'layout' => 'grid',
      'layout_configuration' => [],
      'view_all_show_link' => FALSE,
      'view_all_text' => 'View galleries',
      'view_all_position' => 'bottom',
      'view_all_link_classes' => 'button button--primary button--small',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'media_gallery/admin';

    $form['gallery_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Media Gallery'),
      '#description' => $this->t('Select the media gallery to display.'),
      '#options' => $this->getGalleryOptions(),
      '#default_value' => $this->configuration['gallery_id'],
      '#required' => TRUE,
    ];

    $form['item_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Item count'),
      '#description' => $this->t('The number of recent items to show in the block.'),
      '#default_value' => $this->configuration['item_count'],
      '#min' => 1,
      '#required' => TRUE,
    ];

    $this->buildLayoutSettingsFieldset($form, $form_state);
    $this->buildImageStylesFieldset($form, $form_state);
    $this->buildViewAllLinkFieldset($form, $form_state);

    return $form;
  }

  /**
   * Builds the 'View all' link fieldset.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildViewAllLinkFieldset(&$form, FormStateInterface $form_state) {
    $form['view_all'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View all link'),
    ];

    $form['view_all']['view_all_show_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "View all" link'),
      '#description' => $this->t('When checked, a link to the galleries page will be displayed in the block.'),
      '#default_value' => $this->configuration['view_all_show_link'],
    ];

    $form['view_all']['view_all_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('The text to display for the link.'),
      '#default_value' => $this->configuration['view_all_text'],
      '#states' => [
        'visible' => [
          ':input[name="settings[view_all][view_all_show_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['view_all']['view_all_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Link position'),
      '#description' => $this->t('Where to display the link in the block.'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'under_title' => $this->t('Under Title'),
      ],
      '#default_value' => $this->configuration['view_all_position'],
      '#states' => [
        'visible' => [
          ':input[name="settings[view_all][view_all_show_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['view_all']['view_all_link_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link classes'),
      '#description' => $this->t('The CSS classes to apply to the link, separated by spaces. The class `media-gallery-latest-items-view-all` is always added.'),
      '#default_value' => $this->configuration['view_all_link_classes'],
      '#states' => [
        'visible' => [
          ':input[name="settings[view_all][view_all_show_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * Builds the layout settings fieldset.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildLayoutSettingsFieldset(&$form, FormStateInterface $form_state) {
    $form['layout_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout settings'),
    ];

    $layout_options = $this->getLayoutOptions();
    $layout_id = $this->getSelectedLayoutId($form_state, $layout_options);

    $form['layout_settings']['layout_wrapper'] = $this->buildLayoutSelector($layout_id, $layout_options);
    $form['layout_settings']['settings_wrapper'] = $this->buildLayoutConfiguration($layout_id, $form, $form_state);
  }

  /**
   * Builds the layout selector.
   *
   * @param string $layout_id
   *   The layout ID.
   * @param array $layout_options
   *   The layout options.
   *
   * @return array
   *   The layout selector render array.
   */
  private function buildLayoutSelector(string $layout_id, array $layout_options): array {
    $definitions = $this->layoutManager->getDefinitions();
    $current_definition = $definitions[$layout_id];
    $icon_path = '';
    if (!empty($current_definition['preview_icon'])) {
      $icon_path = $this->pathResolver->getPath('module', $current_definition['provider']) . '/icons/' . $current_definition['preview_icon'];
    }

    $layout_wrapper = [
      '#type' => 'container',
      '#attributes' => ['class' => ['media-gallery-layout-selector-wrapper']],
    ];

    $layout_wrapper['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#default_value' => $layout_id,
      '#options' => $layout_options,
      '#ajax' => [
        'callback' => [$this, 'layoutSettingsCallback'],
        'wrapper' => 'media-gallery-layout-settings-wrapper',
        'event' => 'change',
      ],
    ];

    $layout_wrapper['preview'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'media-gallery-layout-preview'],
      'image' => [
        '#theme' => 'image',
        '#uri' => $icon_path,
        '#alt' => $this->t('Layout preview'),
      ],
    ];

    return $layout_wrapper;
  }

  /**
   * Builds the layout configuration fields for the provided layout_id.
   *
   * Each layout plugin can have its own configuration fields.
   *
   * @param string $layout_id
   *   The layout ID.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The layout configuration render array.
   */
  private function buildLayoutConfiguration(string $layout_id, array $form, FormStateInterface $form_state): array {
    $settings_wrapper = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'media-gallery-layout-settings-wrapper',
      ],
    ];

    $plugin_config = ($this->configuration['layout_configuration'] ?? []) + $this->configuration;
    if ($layout_id && ($plugin = $this->layoutManager->createInstance($layout_id, $plugin_config))) {
      $subform_state = SubformState::createForSubform($settings_wrapper, $form, $form_state);
      $settings_wrapper = $plugin->buildConfigurationForm($settings_wrapper, $subform_state);
    }

    return $settings_wrapper;
  }

  /**
   * Get the ID of the layout currently selected by the user.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $layout_options
   *   The available layout options.
   *
   * @return string
   *   The selected layout ID.
   */
  private function getSelectedLayoutId(FormStateInterface $form_state, array $layout_options): string {
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement && $triggeringElement['#name'] === 'settings[layout_settings][layout_wrapper][layout]') {
      $layout_id = $triggeringElement['#value'];
    }
    else {
      $layout_id = $this->configuration['layout'];
    }

    if (!array_key_exists($layout_id, $layout_options)) {
      return MediaGalleryConstants::DEFAULT_BLOCK_LAYOUT;
    }

    return $layout_id;
  }

  /**
   * Gets all available gallery block layout options, alphabetically sorted.
   *
   * @return array
   *   The layout options.
   */
  private function getLayoutOptions(): array {
    $layout_options = [];
    $definitions = $this->layoutManager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $layout_options[$id] = $definition['label'];
    }
    asort($layout_options);
    return $layout_options;
  }

  /**
   * Builds the image styles fieldset.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildImageStylesFieldset(&$form, FormStateInterface $form_state) {
    $form['image_styles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image Styles'),
      '#tree' => TRUE,
    ];

    $image_style_options = image_style_options(FALSE);
    asort($image_style_options);

    $form['image_styles']['thumbnail_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail image style'),
      '#description' => $this->t('Select the image style for the thumbnail display. Clicking a thumbnail will open the Photoswipe layer.'),
      '#options' => $image_style_options,
      '#default_value' => $this->configuration['thumbnail_image_style'],
      '#empty_option' => $this->t('None (Original image)'),
    ];

    $form['image_styles']['photoswipe_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Photoswipe modal image style'),
      '#description' => $this->t('Select the image style to display the image in the Photoswipe modal.'),
      '#options' => $image_style_options,
      '#default_value' => $this->configuration['photoswipe_image_style'],
      '#empty_option' => $this->t('None (Original image)'),
    ];
  }

  /**
   * AJAX callback for the layout settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function layoutSettingsCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // The form has already been rebuilt at this point, so the settings wrapper
    // and the preview container have been updated by our build* methods. We
    // just need to send them back to the browser to be replaced.
    $response->addCommand(new ReplaceCommand('#media-gallery-layout-settings-wrapper', $form['settings']['layout_settings']['settings_wrapper']));
    $response->addCommand(new ReplaceCommand('#media-gallery-layout-preview', $form['settings']['layout_settings']['layout_wrapper']['preview']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Update the block's own configuration fields.
    $this->configuration['gallery_id'] = $form_state->getValue('gallery_id');
    $this->configuration['item_count'] = $form_state->getValue('item_count');
    $image_styles = $form_state->getValue('image_styles');
    $this->configuration['thumbnail_image_style'] = $image_styles['thumbnail_image_style'];
    $this->configuration['photoswipe_image_style'] = $image_styles['photoswipe_image_style'];

    $view_all = $form_state->getValue('view_all');
    $this->configuration['view_all_show_link'] = $view_all['view_all_show_link'];
    $this->configuration['view_all_text'] = $view_all['view_all_text'];
    $this->configuration['view_all_position'] = $view_all['view_all_position'];
    $this->configuration['view_all_link_classes'] = $view_all['view_all_link_classes'];

    $layout_id = $form_state->getValue(['layout_settings', 'layout_wrapper', 'layout']);
    $this->configuration['layout'] = $layout_id;

    // Create an instance of the selected plugin. Pass the block configuration.
    $plugin = $this->layoutManager->createInstance($layout_id, $this->configuration);

    // Let the plugin update its configuration from the form submission.
    $subform_state = SubformState::createForSubform($form['settings']['layout_settings']['settings_wrapper'], $form['settings'], $form_state);
    $plugin->submitConfigurationForm($form['settings']['layout_settings']['settings_wrapper'], $subform_state);

    // Get the plugin's configuration. It's a merge of the block config and
    // its own settings.
    $plugin_config = $plugin->getConfiguration();

    // We only want to store the layout-specific configuration. So, we'll get
    // the keys for the layout's default configuration.
    $layout_specific_keys = array_keys($plugin->defaultConfiguration());

    // Create the layout_configuration array with only the layout-specific
    // values.
    $this->configuration['layout_configuration'] = [];
    foreach ($layout_specific_keys as $key) {
      if (isset($plugin_config[$key])) {
        $this->configuration['layout_configuration'][$key] = $plugin_config[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $latestPublishedMediaEntities = $this->getLatestMediaEntities();
    if (empty($latestPublishedMediaEntities)) {
      return [];
    }

    $gallery_build = $this->buildGalleryRenderable($latestPublishedMediaEntities);

    $build = [
      '#attributes' => [
        'class' => [
          'media-gallery-layout--' . $this->getLayout(),
        ],
      ],
      '#attached' => [
        'library' => ['media_gallery/media_gallery_block'],
      ],
    ];

    if (!$this->configuration['view_all_show_link']) {
      $build['gallery'] = $gallery_build;
      return $build;
    }

    $link_renderable = $this->buildViewAllLinkRenderable();
    $position = $this->configuration['view_all_position'];
    $build['#attributes']['class'][] = 'view-all-position-' . $position;

    if ($position === 'left' || $position === 'right') {
      $build['items_wrapper'] = [
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-gallery-latest-items-wrapper',
            ],
          ],
          'gallery' => $gallery_build,
          'view_all_link_wrapper' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['view-all-link-wrapper']],
            'link' => $link_renderable,
          ],
        ],
      ];
      return $build;
    }

    // Handle top, bottom, and under_title.
    $build['gallery'] = $gallery_build;
    $build['view_all_link'] = $link_renderable;

    if ($position === 'top') {
      // Make 'view_all_link' come before 'gallery'.
      $build = array_reverse($build, TRUE);
    }

    return $build;
  }

  /**
   * Fetches the latest published media entities from the selected gallery.
   *
   * @return array
   *   An array of media entities, or an empty array if none are found.
   */
  protected function getLatestMediaEntities(): array {
    $gallery_id = $this->configuration['gallery_id'];
    if (empty($gallery_id)) {
      return [];
    }

    $mediaIdsInGallery = $this->getMediaIdsFromGallery($gallery_id);
    if (empty($mediaIdsInGallery)) {
      return [];
    }
    return $this->getOnlyPublishedMedia($mediaIdsInGallery);
  }

  /**
   * Builds the gallery render array using the configured layout plugin.
   *
   * @param array $entities
   *   The media entities to render.
   *
   * @return array
   *   The gallery render array.
   */
  protected function buildGalleryRenderable(array $entities): array {
    $layout_id = $this->getLayout();

    /** @var \Drupal\media_gallery\Plugin\MediaGalleryLayoutInterface $plugin */
    $plugin_config = ($this->configuration['layout_configuration'] ?? []) + $this->configuration;
    $plugin = $this->layoutManager->createInstance($layout_id, $plugin_config);
    $gallery_build = $plugin->build($entities);

    if (!array_key_exists('error', $gallery_build)) {
      // The field view builder will add cache tags for individual entities and
      // files.  We also need to add list cache tags so the block is invalidated
      // when any media gallery or media item is added, updated, or deleted.
      $gallery_build['#cache']['tags'] = Cache::mergeTags(
        $gallery_build['#cache']['tags'] ?? [],
        $this->getCacheTags(),
        ['media_gallery_list', 'media_list']
      );
    }

    return $gallery_build;
  }

  /**
   * Builds the "View all" link render array.
   *
   * @return array
   *   The link render array.
   */
  protected function buildViewAllLinkRenderable(): array {
    $link = Link::fromTextAndUrl($this->configuration['view_all_text'], Url::fromUri('internal:/galleries'));
    $link_renderable = $link->toRenderable();
    $custom_classes = [];
    if (!empty($this->configuration['view_all_link_classes'])) {
      $custom_classes = explode(' ', $this->configuration['view_all_link_classes']);
    }
    $link_renderable['#attributes']['class'] = array_merge(
      $link_renderable['#attributes']['class'] ?? [],
      ['media-gallery-latest-items-view-all'],
      $custom_classes
    );
    return $link_renderable;
  }

  /**
   * Gets the saved layout for the block.
   *
   * @return string
   *   The layout.
   */
  private function getLayout(): string {
    return $this->configuration['layout'] ?? MediaGalleryConstants::DEFAULT_BLOCK_LAYOUT;
  }

  /**
   * Gets all media IDs from a specific media gallery.
   *
   * @param int $gallery_id
   *   The media gallery ID.
   *
   * @return array
   *   An array of media IDs.
   */
  protected function getMediaIdsFromGallery(int $gallery_id) {
    // We query the field table directly for performance, as a reverse
    // entity query is not straightforward.
    $query = $this->database->select('media_gallery__images', 'mgi');
    $query->fields('mgi', ['images_target_id']);
    $query->condition('entity_id', $gallery_id);
    return $query->execute()->fetchCol();
  }

  /**
   * Gets only published media from a list of media IDs.
   *
   * @param array $mediaIdsInGalleries
   *   An array of media IDs to filter.
   *
   * @return array
   *   An array of published media entities.
   */
  protected function getOnlyPublishedMedia(array $mediaIdsInGalleries) {
    $media_storage = $this->entityTypeManager->getStorage('media');
    $query = $media_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('mid', $mediaIdsInGalleries, 'IN')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $this->configuration['item_count']);

    $latestPublishedMediaIds = $query->execute();

    if (empty($latestPublishedMediaIds)) {
      return [];
    }

    $mediaEntities = $media_storage->loadMultiple($latestPublishedMediaIds);

    // Sort the loaded entities to match the query's sort order, as
    // loadMultiple() does not guarantee order.
    $sortedMediaEntities = [];
    foreach ($latestPublishedMediaIds as $id) {
      if (isset($mediaEntities[$id]) && $mediaEntities[$id]->access('view')) {
        $sortedMediaEntities[$id] = $mediaEntities[$id];
      }
    }

    return $sortedMediaEntities;
  }

  /**
   * Gets all media galleries as options for a select list.
   *
   * @return array
   *   An array of media gallery options.
   */
  protected function getGalleryOptions(): array {
    $options = [];
    $galleries = $this->entityTypeManager->getStorage('media_gallery')->loadMultiple();
    foreach ($galleries as $gallery) {
      $options[$gallery->id()] = $gallery->label();
    }
    return $options;
  }

}
