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
      $form['fields'][$fieldName]['added'] = [
        '#type' => 'checkbox',
        '#title' => $fieldData['label'],
        '#default_value' => $fieldData['added'],
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
      ->save();

    parent::submitForm($form, $form_state);
  }

}
