<?php

namespace Drupal\headsup\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the headsup module.
 */
class HeadsupListControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "headsup HeadsupListController's controller functionality",
      'description' => 'Test Unit for module headsup and controller HeadsupListController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests headsup functionality.
   */
  public function testHeadsupListController() {
    // Check that the basic functions of module headsup.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
