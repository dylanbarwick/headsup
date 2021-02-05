<?php

namespace Drupal\headsup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for headsup.
 *
 * @ingroup headsup
 */
class HeadsupAcknowledgeController extends ControllerBase {

  /**
   * Request stack made by client.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack made by client.
   */
  public function __construct(
    RequestStack $request_stack
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("request_stack")
    );
  }

  /**
   * Get the POST data and prep it before passing it to the save function.
   *
   * @return array
   *   A render array containing the id of the log entry just created.
   */
  public function getAcknowledgeParameters($nid, $uid, Request $request) {

    $built_data = [
      'nid' => $nid->id(),
      'uid' => $uid->id(),
      'date' => time(),
    ];

    $return = $this->setClickCapture($built_data);

    return [
      '#markup' => $return,
    ];
  }

  /**
   * Take the prepped data and insert into the headsup_acknowledgements table.
   *
   * @param array $data
   *   $data - prepped POST data.
   */
  public function setClickCapture(array $data) {
    $insert = Database::getConnection()->upsert('headsup_acknowledgements');
    $insert->fields(['nid', 'uid', 'date'], $data);
    $insert->key('nid');
    $return = $insert->execute();
    return $return;
  }

}
