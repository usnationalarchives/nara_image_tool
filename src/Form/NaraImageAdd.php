<?php

namespace Drupal\nara_image_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\Media;

/**
 * Build the simple form.
 *
 * @param array $form
 *   Default form array structure.
 * @param FormStateInterface $form_state
 *   Object containing current form state.
 */
class NaraImageAdd extends FormBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nara_image_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['naid'] = [
      '#type' => 'number',
      '#title' => $this->t('ID of Item'),
      '#description' => $this->t('Must be a number'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $client = new \GuzzleHttp\Client();
    $naId = $form_state->getValue('naid');
    $res = json_decode($client->get('https://catalog.archives.gov/api/v1?naIds=' . $naId)->getBody()->getContents());
    $url = $res->opaResponse->results->result[0]->objects->object->file->{'@url'};
    $data = system_retrieve_file($url, NULL, TRUE, FILE_EXISTS_REPLACE);

    $image_media = Media::create([
      'bundle' => 'image',
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'field_media_image' => [
        'target_id' => $data->id(),
        'alt' => t('Placeholder image'),
        'title' => t('Placeholder image'),
      ],
    ]);
    $image_media->save();
  }

}
