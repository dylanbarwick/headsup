<?php

declare(strict_types=1);

namespace Drupal\headsup\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @todo Add plugin description here.
 *
 * @EntityReferenceSelection(
 *   id = "headsup_role_selection",
 *   label = @Translation("Role selection"),
 *   group = "headsup_role_selection",
 *   entity_types = {"user_role"},
 * )
 */
final class RoleSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'];
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    $options = array_map(function ($entity) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      return $entity->label();
    }, $storage->loadMultiple());

    $form['target_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed roles'),
      '#options' => $options,
      '#default_value' => isset($configuration['target_roles']) ? (array) $configuration['target_roles'] : [],
      '#multiple' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS'): QueryInterface {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'] ?: [];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);
    $query = parent::buildEntityQuery($match, $match_operator);

    // Add condition, if set of roles selected.
    if (isset( $configuration['target_roles'])) {
      $query->condition($entity_type->getKey('id'), $configuration['target_roles'], 'IN');
    }
    return $query;
  }

}
