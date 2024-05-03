<?php

namespace Drupal\headsup;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\Entity\Node;
use Drupal;

/**
 * Repository for database-related helper methods for this module.
 *
 * This repository is a service named 'headsup.repository'.
 * You can see how the service is defined in
 * headsup/headsup.services.yml.
 *
 * For projects where there are many specialized queries, it can be useful to
 * group them into 'repositories' of queries. We can also architect this
 * repository to be a service, so that it gathers the database connections it
 * needs. This way other classes which use the repository don't need to concern
 * themselves with database connections, only with business logic.
 *
 * This repository demonstrates basic CRUD behaviors, and also has an advanced
 * query which performs a join with the user table.
 *
 * @ingroup headsup
 */
class HeadsupRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config collection
   */
  protected $config;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $config = Drupal::config('headsup.headsupsettings');
    $this->config = $config;
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Save an entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record, ie nid,
   *   uid and date in the form of a timestamp.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function acknowledge(array $entry) {
    try {
      $return_value = $this->connection->upsert('headsup_acknowledgements')
        ->fields($entry)
        ->key('nid')
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Delete entries from the database.
   *
   * @param int $nid
   *   The nid of the headsup deleted.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function cleanup(int $nid) {
    $this->connection->delete('headsup_acknowledgements')
      ->condition('nid', $nid)
      ->execute();
  }

  /**
   * Load Relevant Heads-ups.
   *
   * In one query we retrieve headsups that have not been
   * acknowledged by the current user, were created after the user
   * account was created and are either unrestricted or are set for the
   * user's role(s).
   *
   * @param object $user
   *   A user entity, usually the current user.
   *
   * @return array
   *   An array containing the loaded node objects if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function loadRelevantHeadsups($user) {
    // Pseudocode:
    /*
    SELECT n.*, nbr.field_headsup_recipients_target_id, poa.*, nr.nid, nr.vid, nr.revision_timestamp
    FROM node n
    LEFT JOIN node__field_headsup_recipients nbr ON n.nid = nbr.entity_id
    LEFT JOIN headsup_acknowledgements poa ON poa.nid = n.nid AND poa.uid = 1
    LEFT JOIN node_revision nr ON n.nid = nr.nid AND n.vid = nr.vid AND nr.revision_timestamp > 1593383862
    WHERE n.type="headsup"
    AND (nbr.field_headsup_recipients_target_id IN('firefighter', 'administrator')
    OR nbr.field_headsup_recipients_target_id IS NULL)
    AND poa.nid IS NULL
    );

    SELECT
    n.nid AS n_nid,
    n.vid AS n_vid,
    nbr.entity_id AS nbr_entity_id,
    nbr.field_headsup_recipients_target_id AS nbr_role,
    poa.nid AS poa_nid,
    poa.uid AS poa_uid,
    nr.nid AS nr_nid,
    nr.revision_timestamp AS nr_revision_timestamp
    FROM
    node n
    LEFT JOIN node__field_headsup_recipients nbr ON n.nid = nbr.entity_id
    LEFT JOIN headsup_acknowledgements poa ON n.nid = poa.nid AND poa.uid = 1
    LEFT JOIN node_revision nr ON n.nid = nr.nid AND n.vid = nr.vid AND nr.revision_timestamp > 1590763426
    WHERE (n.type = 'headsup')
    AND (
    (nbr.field_headsup_recipients_target_id IS NULL)
    OR
    (nbr.field_headsup_recipients_target_id IN ('firefighter', 'administrator', 'content_editors'))
    )
    AND poa.nid IS NULL;
     */
    $database = \Drupal::database();

    $query = $database->select('node', 'n');
    // Join the two other databases.
    $query->leftJoin('node__field_headsup_recipients', 'nbr', 'n.nid = nbr.entity_id');
    $query->leftJoin('node__field_headsup_start_date', 'nbd', 'n.nid = nbd.entity_id');
    $query->leftJoin('node__field_headsup_stop_date', 'nbds', 'n.nid = nbds.entity_id');
    $query->leftJoin('node__field_headsup_priority', 'nbhp', 'n.nid = nbhp.entity_id');
    $query->leftJoin('taxonomy_term__field_hup_weight', 'thw', 'nbhp.field_headsup_priority_target_id = thw.entity_id');
    $query->leftJoin('headsup_acknowledgements', 'poa', 'n.nid = poa.nid AND poa.uid = :current_user_id', [':current_user_id' => $user->id()]);

    // Only bring back headsup nodes.
    $query->condition('n.type', 'headsup', '=');
    $query->addField('n', 'nid');
    $query->addField('nbr', 'entity_id');
    $query->addField('nbd', 'entity_id');
    $query->addField('poa', 'nid');
    $query->addField('poa', 'uid');
    $query->addField('thw', 'field_hup_weight_value');
    $query->addField('nbd', 'field_headsup_start_date_value');

    // A couple of OR conditions grouped.
    $orGroup = $query->orConditionGroup()
      ->condition('nbr.field_headsup_recipients_target_id', $user->getRoles(), 'IN')
      ->isNull('nbr.field_headsup_recipients_target_id');

    // Add the group to the query.
    $query->condition($orGroup);

    // Exclude any row that is not in poa.
    $query->isNull('poa.nid');

    // Exclude any headsups dated from before the current user was created.
    $query->condition('nbd.field_headsup_start_date_value', date('Y-m-d\TH:i:s', $user->getCreatedTime()), '>');

    // Exclude any headsups dated in the future.
    $query->condition('nbd.field_headsup_start_date_value', date('Y-m-d\TH:i:s'), '<');

    // Another or group to stop `expired` heads-ups from being shown.
    $orExpiredGroup = $query->orConditionGroup()
      ->condition('nbds.field_headsup_stop_date_value', date('Y-m-d\TH:i:s'), '>')
      ->isNull('nbds.field_headsup_stop_date_value');

    $query->condition($orExpiredGroup);

    $query->orderBy('field_hup_weight_value', 'ASC');
    $query->orderBy('nbd.field_headsup_start_date_value', 'ASC');

    // Return the result in object format.
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $nids = [];
    foreach ($result as $key => $value) {
      $nids[] = $value['nid'];
    }

    // If there are any returns, we load the node objects and return them.
    if (count($nids)) {
      $return = Node::loadMultiple($nids);
    }
    else {
      $return = [];
    }
    return $return;
  }

  /**
   * Load All Heads-ups for a user.
   *
   * In one query we retrieve headsups that were created after the user
   * account was created and are either unrestricted or are set for the
   * user's role(s). This is for the headsup-list page.
   *
   * @param object $user
   *   A user entity, usually the current user.
   *
   * @return array
   *   An array containing the necessary fields to render the node.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function loadAllMyHeadsups($user) {
    // Pseudocode:
    /*
    SELECT n.*, nbr.field_headsup_recipients_target_id, poa.*, nr.nid, nr.vid, nr.revision_timestamp
    FROM node n
    LEFT JOIN node__field_headsup_recipients nbr ON n.nid = nbr.entity_id
    LEFT JOIN node_revision nr ON n.nid = nr.nid AND n.vid = nr.vid AND nr.revision_timestamp > 1593383862
    WHERE n.type="headsup"
    AND (nbr.field_headsup_recipients_target_id IN('firefighter', 'administrator')
    OR nbr.field_headsup_recipients_target_id IS NULL)
    );

    SELECT
    n.nid AS n_nid,
    n.vid AS n_vid,
    nbr.entity_id AS nbr_entity_id,
    nbr.field_headsup_recipients_target_id AS nbr_role,
    nr.nid AS nr_nid,
    nr.revision_timestamp AS nr_revision_timestamp
    FROM
    node n
    LEFT JOIN node__field_headsup_recipients nbr ON n.nid = nbr.entity_id
    LEFT JOIN node_revision nr ON n.nid = nr.nid AND n.vid = nr.vid AND nr.revision_timestamp > 1590763426
    WHERE (n.type = 'headsup')
    AND (
    (nbr.field_headsup_recipients_target_id IS NULL)
    OR
    (nbr.field_headsup_recipients_target_id IN ('firefighter', 'administrator', 'content_editors'))
    );
     */
    $database = \Drupal::database();

    $query = $database->select('node', 'n');
    // Join the two other databases.
    $query->leftJoin('node__field_headsup_recipients', 'nbr', 'n.nid = nbr.entity_id');
    $query->leftJoin('node__field_headsup_start_date', 'nbd', 'n.nid = nbd.entity_id');
    $query->leftJoin('headsup_acknowledgements', 'poa', 'n.nid = poa.nid AND poa.uid = :current_user_id', [':current_user_id' => $user->id()]);

    // Only bring back headsup nodes.
    $query->condition('n.type', 'headsup', '=');
    $query->addField('n', 'nid');
    $query->addField('nbr', 'entity_id');
    $query->addField('nbd', 'entity_id');
    $query->addField('poa', 'nid');
    $query->addField('poa', 'uid');

    // A couple of OR conditions grouped.
    $orGroup = $query->orConditionGroup()
      ->condition('nbr.field_headsup_recipients_target_id', $user->getRoles(), 'IN')
      ->isNull('nbr.field_headsup_recipients_target_id');

    // Add the group to the query.
    $query->condition($orGroup);

    // Exclude any headsups dated from before the current user was created.
    $query->condition('nbd.field_headsup_start_date_value', date('Y-m-d\TH:i:s', $user->getCreatedTime()), '>');

    // Exclude any headsups dated in the future.
    $query->condition('nbd.field_headsup_start_date_value', date('Y-m-d\TH:i:s'), '<');

    // Tack a pager thing on the end.
    $pager = $query
      ->extend(PagerSelectExtender::class)
      ->limit($this->config->get('headsup_list_pager_limit'));

    // Return the result in object format.
    $result = $pager->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $nids = [];
    foreach ($result as $key => $value) {
      $nids[] = $value['nid'];
    }

    // If there are any returns, we load the node objects and return them.
    if (count($nids)) {
      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $nkey => $nvalue) {
        $fh_start_date = strtotime($nvalue->get('field_headsup_start_date')->value);
        $returned_headsups[$nkey] = [
          'nid' => $nkey,
          'title' => $nvalue->title->value,
          'body' => $nvalue->body->value,
          'field_headsup_start_date' => \Drupal::service('date.formatter')->format($fh_start_date, 'short'),
          'acknowledged' => 'unacknowledged',
        ];
      }
      foreach ($result as $key => $value) {
        if ($value['poa_nid'] != NULL) {
          $returned_headsups[$value['nid']]['acknowledged'] = 'acknowledged';
        }
      }

      $return = $returned_headsups;
    }
    else {
      $return = [];
    }
    return $return;
  }

}
