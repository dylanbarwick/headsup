<?php

namespace Drupal\headsup\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class comment.
 */
class HeadsupAcknowledgeCommand implements CommandInterface {
  /**
   * A message.
   *
   * @var string
   */
  protected $message;

  /**
   * The node ID of the headsup being acknowledged.
   *
   * @var int
   */
  protected $nid;

  /**
   * The user ID of the current user.
   *
   * @var int
   */
  protected $uid;

  /**
   * Constructs a HeadsupAcknowledgeCommand object.
   */
  public function __construct($message, $nid, $uid) {
    $this->message = $message;
    $this->nid = $nid;
    $this->uid = $uid;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'acknowledgeHeadsup',
      'message' => $this->message,
      'nid' => $this->nid,
      'uid' => $this->uid,
    ];
  }

}
