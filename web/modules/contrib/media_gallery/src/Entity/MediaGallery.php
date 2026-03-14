<?php

namespace Drupal\media_gallery\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\media_gallery\MediaGalleryConstants;
use Drupal\media_gallery\MediaGalleryInterface;
use Drupal\user\UserInterface;

/**
 * Defines the media gallery entity class.
 *
 * @ContentEntityType(
 *   id = "media_gallery",
 *   label = @Translation("Media Gallery"),
 *   label_collection = @Translation("Media Galleries"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\media_gallery\MediaGalleryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\media_gallery\Form\MediaGalleryForm",
 *       "edit" = "Drupal\media_gallery\Form\MediaGalleryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\media_gallery\Access\MediaGalleryEntityAccessControlHandler"
 *   },
 *   base_table = "media_gallery",
 *   data_table = "media_gallery_field_data",
 *   translatable = TRUE,
 *   admin_permission = "access media gallery overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/media-gallery/add",
 *     "canonical" = "/media_gallery/{media_gallery}",
 *     "edit-form" = "/admin/content/media-gallery/{media_gallery}/edit",
 *     "delete-form" = "/admin/content/media-gallery/{media_gallery}/delete",
 *     "collection" = "/admin/content/media-gallery"
 *   },
 *   field_ui_base_route = "entity.media_gallery.settings"
 * )
 */
class MediaGallery extends ContentEntityBase implements MediaGalleryInterface {

  use EntityChangedTrait;

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new media gallery entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title): MediaGalleryInterface {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status): MediaGalleryInterface {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp): MediaGalleryInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(\t('Title'))
      ->setDescription(\t('The title of the media gallery entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(\t('Description'))
      ->setDescription(\t('A description of the media gallery.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'hidden',
        'weight' => 1,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $media_bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
    $media_bundle_machine_names = array_keys($media_bundle_info);
    // This format is expected in places like
    // Drupal/Core/Field/Plugin/Field/FieldType/EntityReferenceItem.php
    // ::getRandomBundle.
    $media_bundle_machine_name_assoc = array_combine(
      array_keys($media_bundle_info), array_keys($media_bundle_info));

    $fields['images'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(\t('Gallery images'))
      ->setSetting('target_type', 'media')
      ->SetCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 2,
        'settings' => [
          'media_library_widget' => [
            'media_types' => $media_bundle_machine_names,
          ],
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'photoswipe_field_formatter',
        'weight' => 2,
        'label' => 'hidden',
        'settings' => [
          'photoswipe_image_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_IMAGE_STYLE,
          'photoswipe_thumbnail_style' => MediaGalleryConstants::DEFAULT_PHOTOSWIPE_THUMBNAIL_STYLE,
        ],
      ])
      ->setSetting('handler_settings', [
        'target_bundles' => $media_bundle_machine_name_assoc,
      ]);

    $fields['use_pager'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use pager'))
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', FALSE);

    $fields['items_per_page'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Items per page'))
      ->setName('items_per_page')
      ->setDescription(t('The number of items to show per page.'))
      ->setDefaultValue(12)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['reverse'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show photos in reverse order'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(\t('Author'))
      ->setDescription(\t('The user ID of the media gallery author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(\t('Authored on'))
      ->setDescription(\t('The time that the media gallery was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(\t('Changed'))
      ->setDescription(\t('The time that the media gallery was last edited.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(\t('Published'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Published')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
        'label' => 'hidden',
        'weight' => 12,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
