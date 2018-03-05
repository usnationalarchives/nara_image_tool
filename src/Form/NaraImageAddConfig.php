<?php

namespace Drupal\nara_image_tool\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure NARA Image Tool fields.
 */
class NaraImageAddConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nara_image_tool_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nara_image_tool.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nara_image_tool.settings');
    $form_state->set('image_tool_config', $config);
    $currentFields = $form_state->get('image_tool_config')->get('fields');
    $formFields = self::getFields('media', 'image', $currentFields);
    $linkOptions = [];

    $apiFieldOptions = [
      '' => 'Do not connect',
      'naId' => 'Nara ID',
      'title' => 'Title',
      'scopeAndContentNote' => 'Scope and Content Note',
    ];

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => FALSE,
    ];

    $form['general']['api_url'] = [
      '#default_value' => $config->get('api_url'),
      '#description' => $this->t('The API endpoint to submit responses'),
      '#required' => TRUE,
      '#title' => $this->t('API Url'),
      '#type' => 'textfield',
    ];

    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields when creating Media'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach ($formFields as $fieldName => $fieldData) {
      if ($fieldData['field_type'] == 'link') {
        $linkOptions[$fieldName] = $fieldData['label'];
      }

      $form['fields'][$fieldName]['added'] = [
        '#type' => 'checkbox',
        '#title' => $fieldData['label'],
        '#default_value' => $fieldData['added'],
      ];

      $form['fields'][$fieldName]['api_field_name'] = [
        '#type' => 'select',
        '#title' => $this
          ->t('Select API field to connect'),
        '#default_value' => $fieldData['api_field_name'],
        '#options' => $apiFieldOptions,
      ];

      $form['fields'][$fieldName]['label'] = [
        '#type' => 'hidden',
        '#title' => $fieldData['label'],
        '#value' => $fieldData['label'],
      ];

      $form['fields'][$fieldName]['field_type'] = [
        '#type' => 'hidden',
        '#title' => $fieldData['field_type'],
        '#value' => $fieldData['field_type'],
      ];
    }

    if (isset($linkOptions)) {
      $form['default_link'] = [
        '#type' => 'details',
        '#title' => $this->t('Default Link for Back to Catalog'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      $form['default_link']['title'] = [
        '#type' => 'textfield',
        '#default_value' => $config->get('default_link_title'),
        '#title' => $this->t('Title for Link to Archives Field'),
      ];
      $form['default_link']['field'] = [
        '#type' => 'radios',
        '#default_value' => $config->get('default_link_field'),
        '#title' => $this->t('Default Link field to Archives'),
        '#options' => $linkOptions,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function getFields($entity, $bundle, $configFields) {
    $_fields = $configFields;
    $entityFields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity, $bundle);

    if ($entityFields == NULL || !isset($entityFields)) {
      return NULL;
    }

    foreach ($entityFields as $entityField => $entityFieldData) {
      if (strpos($entityField, 'field_') === 0 && $entityField != 'field_media_image') {
        if (!array_key_exists($entityField, $_fields)) {
          $_fields[$entityField]['added'] = 0;
          $_fields[$entityField]['label'] = $entityFieldData->getLabel();
          $_fields[$entityField]['field_type'] = $entityFieldData->getType();
        }
      }
    }

    return $_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nara_image_tool.settings');
    $config
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('fields', $form_state->getValue('fields'))
      ->set('default_link_title', $form_state->getValue(['default_link', 'title']))
      ->set('default_link_field', $form_state->getValue(['default_link', 'field']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
