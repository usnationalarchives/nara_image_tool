<?php

namespace Drupal\nara_image_tool\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the NARA Object entity.
 *
 * @ingroup nara_object
 *
 * @ContentEntityType(
 *   id = "nara_object",
 *   label = @Translation("nara_object"),
 *   base_table = "nara_object",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class NaraObject extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the NARA Object.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the NARA Object.'))
      ->setReadOnly(TRUE);

    // Standard field, used to store version of object from API.
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setDescription(t('The version number from NARA API'))
      ->setReadOnly(FALSE);

    // Date created in Drupal.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setReadOnly(TRUE);

    // Date modified in Drupal.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setReadOnly(FALSE);

    // naId of Item in NARA API.
    $fields['naid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Version'))
      ->setDescription(t('The version number from NARA API'))
      ->setReadOnly(FALSE);

    // FID of image in Drupal.
    $fields['file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File Entity'))
      ->setDescription(t('The File Id for the Drupal entity.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default');

    // MID of Media entity in Drupal.
    $fields['media_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Media Entity'))
      ->setDescription(t('The Media Id for the Drupal entity.'))
      ->setSetting('target_type', 'media')
      ->setSetting('handler', 'default');

    return $fields;
  }

}
