<?php

namespace Drupal\nara_image_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
    $nara_items = $form_state->get('nara_items');

    $form['naid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IDs of Item'),
      '#description' => $this->t('Enter a single id or a comma separated list.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#submit' => ['::getArchiveItems'],
      '#ajax' => [
        'callback' => '::returnArchiveItems',
        'wrapper' => 'media-gallery-wrapper',
      ],
      '#value' => $this->t('Find Archive Item'),
    ];

    $form['media_gallery'] = [
      '#type' => 'container',
      '#prefix' => '<div id="media-gallery-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    foreach ($form_state->get('nara_items') as $item) {
      $form['media_gallery']['naid_tabs'] = [
        '#type' => 'vertical_tabs',
      ];

      $form['media_gallery'][$item->{'@id'}] = [
        '#type' => 'details',
        '#title' => $item->{'@id'},
        '#group' => 'naid_tabs',
      ];

      $form['media_gallery'][$item->{'@id'}]['set'] = [
        '#type' => 'fieldset',
        '#title' => $item->file->{'@name'},
      ];

      $form['media_gallery'][$item->{'@id'}]['set']['addMedia'] = [
        '#type' => 'checkbox',
        '#title' => '<img src="' . $item->thumbnail->{'@url'} . '" alt="' . $item->file->{'@name'} . ' thumbnail" />',
        '#suffix' => '<a href="' . $item->file->{'@url'} . '" target="_blank" rel="noopener noreferrer">Open image in new window</a>',
      ];

      $form['media_gallery'][$item->{'@id'}]['set']['media_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Media Title'),
        '#default_value' => $item->file->{'@name'},
        '#description' => $this->t('Appears when searching for Media entities in Drupal.'),
        '#required' => TRUE,
      ];

      $form['media_gallery'][$item->{'@id'}]['set']['image_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image Title'),
        '#description' => $this->t('Only needed if different than Media title. This appears when hovering over an image in the browser.'),
        '#required' => FALSE,
      ];

      $form['media_gallery'][$item->{'@id'}]['set']['alt_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Image Alt Text'),
        '#description' => $this->t('Alt image required for accessibility.'),
        '#required' => FALSE,
      ];

    }

    if (count($form_state->get('nara_items')) > 0) {
      $form['media_gallery']['actions'] = [
        '#type' => 'actions',
      ];

      $form['media_gallery']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create Media'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['media_gallery'][0])) {
      $item_selected = FALSE;
      // For each possible item created,
      // check through each and see if at least one is checked
      // 
      // ksm($form['media_gallery']);.
    }

    if (isset($form['naid'])) {
      $naidValid = self::checkSearchQuery($form_state->getValue('naid'));
      if ($naidValid) {
        $form_state->setErrorByName('IDs of Items', t('There is something wrong with the input. Please make sure it is a comma separated list of ids.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function returnArchiveItems(array &$form, FormStateInterface $form_state) {
    return $form['media_gallery'];
  }

  /**
   * {@inheritdoc}
   */
  public function checkSearchQuery($value) {
    $_errors = [];
    $_ids = explode(',', $value);
    foreach ($_ids as $_id) {
      if (!is_numeric($_id)) {
        $_errors[] = $_id;
      }
    }

    if (!empty($_errors)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveItems(array &$form, FormStateInterface $form_state) {
    $client = \Drupal::httpClient();
    $naId = $form_state->getValue('naid');
    $_nara_items = $form_state->get('nara_items');
    $res = json_decode($client->request('GET', 'https://catalog.archives.gov/api/v1?naIds=' . $naId)->getBody()->getContents());
    $nara_object;

    if (!$res->opaResponse->results->result) {
      drupal_set_message(t('No results for for your current query.'), 'error', TRUE);
      return $form['media_gallery'];
    }
    foreach ($res->opaResponse->results->result as $result) {
      $nara_object = $result->objects->object;
      $object_naid = $result->naId;
      if (is_array($nara_object)) {
        foreach ($nara_object as $key => $value) {
          if ($value->file->{'@mime'} === 'image/jpeg') {
            $_nara_items[$object_naid] = $value;
          }
        }
      }
      else {
        $_nara_items[$object_naid] = $nara_object;
      }
    }
    $form_state->set('nara_items', $_nara_items);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_media = 0;
    for ($i = 0; $i < count($form_state->get('nara_items')); $i++) {
      $item = $form_state->get('nara_items')[$i];
      if ($form_state->getValue(['media_gallery', (string) $i, 'addMedia']) == 1) {
        $new_media++;
        $data = system_retrieve_file($item->file->{'@url'}, NULL, TRUE, FILE_EXISTS_REPLACE);
        $image_media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'name' => $form_state->getValue(['media_gallery', (string) $i, 'media_title']),
          'field_media_image' => [
            'target_id' => $data->id(),
            'alt' => $form_state->getValue(['media_gallery', (string) $i, 'alt_text']),
            'title' => ($form_state->getValue(['media_gallery', (string) $i, 'image_title'])) ?: $form_state->getValue(['media_gallery', (string) $i, 'media_title']),
          ],
        ]);
        $image_media->save();
      }
    }
    drupal_set_message(t('@media new Media added.', ['@media' => $new_media]), 'status');
  }

}
