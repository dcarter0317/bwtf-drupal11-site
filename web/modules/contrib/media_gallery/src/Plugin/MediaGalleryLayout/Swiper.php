<?php

namespace Drupal\media_gallery\Plugin\MediaGalleryLayout;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_gallery\Attribute\MediaGalleryLayout;
use Drupal\media_gallery\MediaGalleryItemRenderer;
use Drupal\media_gallery\Plugin\MediaGalleryLayoutBase;
use Drupal\swiper_formatter\Service\SwiperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a swiper layout for the media gallery.
 *
 * This layout uses the Swiper library to create a touch-enabled slider of
 * media items. It depends on the Swiper module for the core slider
 * functionality and configuration. If the Swiper module is not available,
 * this layout will not be usable.
 *
 * The layout allows the user to select a Swiper configuration entity, which
 * defines the behavior and appearance of the slider.
 */
#[MediaGalleryLayout(
  id: 'swiper',
  label: new TranslatableMarkup('Swiper'),
  description: new TranslatableMarkup('A touch-enabled slider.'),
  preview_icon: 'swiper.svg'
)]
class Swiper extends MediaGalleryLayoutBase {

  /**
   * The Swiper service.
   *
   * This service is provided by the Swiper module and is used to render the
   * slider. It is nullable to prevent errors if the Swiper module is not
   * installed.
   *
   * @var \Drupal\swiper_formatter\Service\SwiperInterface|null
   */
  protected $swiper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a new Swiper object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\media_gallery\MediaGalleryItemRenderer $item_renderer
   *   The media gallery item renderer.
   * @param \Drupal\swiper_formatter\Service\SwiperInterface|null $swiper
   *   The Swiper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MediaGalleryItemRenderer $item_renderer,
    ?SwiperInterface $swiper,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    UuidInterface $uuid_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $item_renderer);
    $this->swiper = $swiper;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_gallery.item_renderer'),
      $container->has('swiper_formatter.base') ? $container->get('swiper_formatter.base') : NULL,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('media_gallery'),
      $container->get('uuid'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'swiper_template' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (!$this->swiper) {
      $form['swiper_warning'] = $this->getSwiperModuleNotInstalledWarning();
      return $form;
    }

    $options = [];
    $swiper_entities = $this->entityTypeManager->getStorage('swiper_formatter')->loadMultiple();
    foreach ($swiper_entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    $form['swiper_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Swiper template'),
      '#description' => $this->t('Select the Swiper configuration to use for the slider.'),
      '#options' => $options,
      '#default_value' => $this->configuration['swiper_template'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $media_items, array $gallery_attributes = []): array {
    if (!$this->swiper) {
      return ['error' => $this->getSwiperModuleNotInstalledWarning()];
    }

    /** @var \Drupal\swiper_formatter\Entity\SwiperFormatter $swiperFormatter */
    $swiperFormatter = $this->entityTypeManager
      ->getStorage('swiper_formatter')
      ->load($this->configuration['swiper_template']);

    if (!$swiperFormatter) {
      $this->logger->warning('The configured Swiper template "@template" could not be found.', ['@template' => $this->configuration['swiper_template']]);
      return [
        'error' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['messages', 'messages--warning']],
          '#markup' => $this->t('The swiper template could not be found, please reconfigure the block and select an existing swiper template.'),
        ],
      ];
    }

    $slides = [];
    $thumbnail_style = $this->configuration['thumbnail_image_style'];
    $photoswipe_image_style = $this->configuration['photoswipe_image_style'];

    foreach ($media_items as $media_item) {
      $slides[] = $this->itemRenderer->getRenderable($media_item, $thumbnail_style, $photoswipe_image_style);
    }

    if (empty($slides)) {
      return [];
    }

    $options = $swiperFormatter->getSwiperOptions() + ['id' => 'swiper-' . $this->uuidService->generate()];
    $options['template'] = $this->configuration['swiper_template'];
    $options['field_type'] = 'media';

    // The swiper service expects a single entity to be passed for caching
    // purposes. We can just pass the first media item.
    $swiperRenderArray = $this->swiper->renderSwiper(reset($media_items), $slides, $options);
    $swiperRenderArray['#attributes']['class'][] = 'photoswipe-gallery';

    if (!empty($gallery_attributes)) {
      $swiperRenderArray['#attributes'] = array_merge_recursive($swiperRenderArray['gallery']['#attributes'], $gallery_attributes);
    }

    return $swiperRenderArray;
  }

  /**
   * Gets the warning block to indicate swiper is not installed.
   *
   * @return array
   *   Render array for the swiper not installed warning
   */
  private function getSwiperModuleNotInstalledWarning() : array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['messages', 'messages--warning']],
      '#markup' => $this->t('The Swiper module is not installed. This layout will not be available without it.'),
    ];
  }

}
