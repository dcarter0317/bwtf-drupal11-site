<?php

namespace Drupal\media_gallery\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the media gallery entity edit forms.
 */
class MediaGalleryForm extends ContentEntityForm {

  /**
   * The renderer service.
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $timeRendererInterface, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $timeRendererInterface);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['items_per_page']['#states'] = [
      'visible' => [
        ':input[name="use_pager[value]"]' => ['checked' => TRUE],
      ],
      'required' => [
        ':input[name="use_pager[value]"]' => ['checked' => TRUE],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->renderer->render($link)];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New media gallery %label has been created.', $message_arguments));
      $this->logger('media_gallery')->notice('Created new media gallery %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The media gallery %label has been updated.', $message_arguments));
      $this->logger('media_gallery')->notice('Updated new media gallery %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.media_gallery.canonical', ['media_gallery' => $entity->id()]);

    return $result;
  }

}
