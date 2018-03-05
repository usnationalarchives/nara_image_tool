<?php

namespace Drupal\nara_image_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\Media;
use Drupal\Core\Config\Config;

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
    $nara_image_tool_config = \Drupal::config('nara_image_tool.settings');
    $media_fields = $nara_image_tool_config->get('fields');
    $form_state->set('default_link_field', $nara_image_tool_config->get('default_link_field'));
    $form_state->set('default_link_title', $nara_image_tool_config->get('default_link_title'));

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

    if (isset($nara_items)) {
      foreach ($nara_items as $naId => $item) {
        $form['media_gallery']['naid_tabs'] = [
          '#type' => 'vertical_tabs',
        ];

        /*
         * Create Item tab and name.
         */
        $form['media_gallery'][$naId] = [
          '#type' => 'details',
          '#title' => $naId,
          '#group' => 'naid_tabs',
        ];

        $form['media_gallery'][$naId][$item->{'@id'}] = [
          '#type' => 'fieldset',
          '#title' => $item->file->{'@name'},
        ];

        /*
         * Create Item Checkbox and thumbnail.
         */
        $form['media_gallery'][$naId][$item->{'@id'}]['addMedia'] = [
          '#type' => 'checkbox',
          '#title' => '<img src="' . $item->thumbnail->{'@url'} . '" alt="' . $item->file->{'@name'} . ' thumbnail" />',
          '#suffix' => '<a href="' . $item->file->{'@url'} . '" target="_blank" rel="noopener noreferrer">Open image in new window</a>',
        ];

        /*
         * Required Fields of Media name, Image Name, Alt Text
         */
        $form['media_gallery'][$naId][$item->{'@id'}]['media_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Media Title'),
          '#default_value' => $item->file->{'@name'},
          '#description' => $this->t('Appears when searching for Media entities in Drupal.'),
          '#required' => TRUE,
        ];

        $form['media_gallery'][$naId][$item->{'@id'}]['image_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Image Title'),
          '#description' => $this->t('Only needed if different than Media title. This appears when hovering over an image in the browser.'),
          '#required' => FALSE,
        ];

        $form['media_gallery'][$naId][$item->{'@id'}]['alt_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Image Alt Text'),
          '#description' => $this->t('Alt image required for accessibility.'),
          '#required' => FALSE,
        ];

        if ($form_state->get('default_link_field')) {
          $form['media_gallery'][$naId][$item->{'@id'}]['default_link_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Link Back to Catalog Title'),
            '#default_value' => $form_state->get('default_link_title'),
            '#description' => $this->t('Alt image required for accessibility.'),
            '#required' => FALSE,
          ];
          $form['media_gallery'][$naId][$item->{'@id'}]['default_link_uri'] = [
            '#type' => 'url',
            '#default_value' => 'https://catalog.archives.gov/id/' . $naId,
            '#title' => 'Link back to Catalog URL',
            '#required' => FALSE,
          ];
        }

        /*
         * Add Custom fields from Config Settings
         */
        foreach ($media_fields as $media_field => $media_field_data) {
          if ($media_field_data['added'] === 1 && $media_field != $form_state->get('default_link_field')) {
            switch ($media_field_data['field_type']) {
              case 'string':
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'textfield',
                  '#title' => $media_field_data['label'],
                  '#default_value' => $item->description->{$media_field_data['api_field_name']},
                  '#required' => FALSE,
                ];
                break;

              case 'link':
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'url',
                  '#title' => $media_field_data['label'],
                  '#required' => FALSE,
                ];
            };
          }
        }
      }
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
      // check through each and see if at least one is checked.
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
      $nara_decription = $result;
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
      $_nara_items[$object_naid]->description = $result->description->item;
    }
    $form_state->set('nara_items', $_nara_items);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_media = 0;
    $items = $form_state->get('nara_items');

    foreach ($items as $item => $itemData) {
      $non_core_fields = $form_state->getValue(
          ['media_gallery', $item, $itemData->{'@id'}, 'non_core']);
      if ($form_state->getValue(
          ['media_gallery', $item, $itemData->{'@id'}, 'addMedia']) == 1) {
        $new_media++;
        $data = system_retrieve_file($itemData->file->{'@url'}, NULL, TRUE, FILE_EXISTS_REPLACE);
        $mediaData = [
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'name' => $form_state->getValue(
            ['media_gallery', $item, $itemData->{'@id'}, 'media_title']),
          'field_media_image' => [
            'target_id' => $data->id(),
            'alt' => $form_state->getValue(
              ['media_gallery', $item, $itemData->{'@id'}, 'alt_text']),
            'title' => ($form_state->getValue(
                ['media_gallery', $item, $itemData->{'@id'}, 'image_title'])) ?: $form_state->getValue(
                    ['media_gallery', $item, $itemData->{'@id'}, 'media_title']),
          ],
        ];
        if ($form_state->get('default_link_field')) {
          $mediaData[$form_state->get('default_link_field')] = [
            'uri' => $form_state->getValue(
                ['media_gallery', $item, $itemData->{'@id'}, 'default_link_uri']),
            'title' => $form_state->getValue(
                ['media_gallery', $item, $itemData->{'@id'}, 'default_link_title']),
          ];
        }
        foreach ($non_core_fields as $key => $value) {
          $mediaData[$key] = $value;
        }
        $image_media = Media::create($mediaData);
        $image_media->save();
      }
    }
    drupal_set_message(t('@media new Media added.', ['@media' => $new_media]), 'status');
  }

}
