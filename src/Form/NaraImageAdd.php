<?php

namespace Drupal\nara_image_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\Media;
use Drupal\Core\Config\Config;
use Drupal\Core\Url;

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

    if (!isset($nara_items)) {
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
    }
    $form['media_gallery'] = [
      '#type' => 'container',
      '#prefix' => '<div id="media-gallery-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    if (!isset($nara_items)) {
      $form['media_gallery']['default_text'] = [
        '#type' => 'inline_template',
        '#template' => '<p>Find items above to begin.</p>',
      ];
    }
    if (isset($nara_items)) {
      $form['media_gallery']['naid_tabs'] = [
        '#type' => 'vertical_tabs',
      ];
      foreach ($nara_items as $naId => $item) {
        /*
         * Create Item tab and name.
         */
        $form['media_gallery'][$naId] = [
          '#type' => 'details',
          '#title' => $item->description->title . ' (' . $naId . ')',
          '#group' => 'naid_tabs',
          '#open' => TRUE,
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
                $max_length_description = $this->t('Max Length: @max_length characters.', ['@max_length' => $media_field_data['max_length']]);
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'textfield',
                  '#title' => $media_field_data['label'],
                  '#default_value' => $item->description->{$media_field_data['api_field_name']} ?: '',
                  '#required' => FALSE,
                  '#maxlength' => $media_field_data['max_length'],
                  '#description' => $media_field_data['show_help'] == 1 ? $media_field_data['description'] : $max_length_description,
                ];
                break;

              case 'link':
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'url',
                  '#title' => $media_field_data['label'],
                  '#required' => FALSE,
                  '#description' => $media_field_data['show_help'] == 1 ? $media_field_data['description'] : NULL,
                ];
                break;

              case 'integer':
                $integer_description = $this->t('Prefix: @prefix | Suffix: @suffix', ['@prefix' => $media_field_data['prefix'], '@suffix' => $media_field_data['suffix']]);
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'number',
                  '#title' => $media_field_data['label'],
                  '#required' => FALSE,
                  '#min' => $media_field_data['min'],
                  '#max' => $media_field_data['max'],
                  '#description' => $media_field_data['show_help'] == 1 ? $media_field_data['description'] : $integer_description,
                ];
                break;

              case 'string_long':
                $form['media_gallery'][$naId][$item->{'@id'}]['non_core'][$media_field] = [
                  '#type' => 'textarea',
                  '#title' => $media_field_data['label'],
                  '#required' => FALSE,
                  '#description' => $media_field_data['show_help'] == 1 ? $media_field_data['description'] : NULL,
                ];
                break;
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
    $nara_items = $form_state->get('nara_items');
    if (isset($nara_items)) {
      $media_gallery = $form_state->getValue('media_gallery');
      $checked_item = FALSE;
      foreach ($media_gallery as $key => $value) {
        foreach ($value as $key2 => $value2) {
          if (array_key_exists('addMedia', $value2)) {
            if ($value2['addMedia'] == 1) {
              $checked_item = TRUE;
            }
          }
        }
      }
      if ($checked_item == FALSE) {
        $form_state->setErrorByName('no_item_checked', $this->t('Please check the items you wish to import.'));
      }
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
            $_nara_items[$object_naid]->{'version'} = $result->objects->{'@version'};
          }
        }
      }
      else {
        $_nara_items[$object_naid] = $nara_object;
        $_nara_items[$object_naid]->{'version'} = $result->objects->{'@version'};
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
    $items = $form_state->get('nara_items');
    $mediaCount = 0;
    foreach ($items as $item => $itemData) {
      if (\Drupal::entityTypeManager()->getStorage('nara_object')->load($itemData->{'@id'}) === NULL) {
        self::createImageMedia($item, $itemData, $form_state);
        $mediaCount++;
      }
      else {
        $media_id = \Drupal::entityTypeManager()->getStorage('nara_object')->load($itemData->{'@id'})->getIterator()['media_id']->getIterator()[0]->getValue()['target_id'];
        $media_link = '<a href="/media/' . $media_id . '/edit">/media/' . $media_id . '/edit</a>';
        $link = Url::fromRoute('entity.media.canonical', ['media' => $media_id])->toString();
        drupal_set_message(t('NARA Object @objectId already imported. <a href="@link" target="_blank">Click here to view in new tab.</a>', ['@link' => $link, '@objectId' => $itemData->{'@id'}]), 'warning');
      }

    }
    drupal_set_message(t('@media new Media added.', ['@media' => $mediaCount]), 'status');
  }

  /**
   * {@inheritdoc}
   */
  private function createImageMedia($item, $itemData, $form_state) {

    $non_core_fields = $form_state
      ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'non_core']);
    if ($form_state->getValue(['media_gallery', $item, $itemData->{'@id'}, 'addMedia']) == 1) {
      // Create the file in the file system.
      $data = system_retrieve_file($itemData->file->{'@url'}, NULL, TRUE, FILE_EXISTS_REPLACE);

      // Create the Media Image entity.
      $mediaData = [
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'name' => $form_state->getValue(
          ['media_gallery', $item, $itemData->{'@id'}, 'media_title']),
        'field_media_image' => [
          'target_id' => $data->id(),
          'alt' => $form_state
            ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'alt_text']),
          'title' => ($form_state
            ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'image_title'])) ?:
          $form_state
            ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'media_title']),
        ],
      ];
      if ($form_state->get('default_link_field')) {
        $mediaData[$form_state->get('default_link_field')] = [
          'uri' => $form_state
            ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'default_link_uri']),
          'title' => $form_state
            ->getValue(['media_gallery', $item, $itemData->{'@id'}, 'default_link_title']),
        ];
      }
      foreach ($non_core_fields as $key => $value) {
        $mediaData[$key] = $value;
      }
      $image_media = Media::create($mediaData);
      $image_media->save();
      self::createNaraObject($item, $itemData, $data->id(), $image_media->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  private function createNaraObject($item, $itemData, $fid, $mid) {
    $naraObject = [
      'id' => $itemData->{'@id'},
      'version' => $itemData->{'version'},
      'naid' => $item,
      'file_id' => $fid,
      'media_id' => $mid,
    ];

    $naraObject = \Drupal::entityTypeManager()->getStorage('nara_object')->create($naraObject);
    $naraObject->save();
  }

}
