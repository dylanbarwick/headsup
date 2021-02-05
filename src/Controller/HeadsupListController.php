<?php

namespace Drupal\headsup\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\headsup\HeadsupRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Retrieves all relevant heads-ups for the logged-in user.
 */
class HeadsupListController extends ControllerBase {

  /**
   * The repository for our specialized queries.
   *
   * @var \Drupal\headsup\HeadsupRepository
   */
  protected $repository;

  /**
   * Database connection.
   *
   * @var connection
   */
  protected $connection;

  /**
   * Config settings.
   *
   * @var config
   */
  protected $config;

  /**
   * The current user.
   *
   * We'll need this service in order to check if the user is logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = Drupal::config('headsup.headsupsettings');
    $controller = new static(
      $container->get('headsup.repository'),
      $config,
      $container->get('current_user')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct a new controller.
   *
   * @param \Drupal\headsup\HeadsupRepository $repository
   *   The repository service.
   * @param object $config
   *   The configuration settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user(ish).
   */
  public function __construct(HeadsupRepository $repository, $config, AccountProxyInterface $current_user) {
    $this->repository = $repository;
    $this->config = $config;
    $this->currentUser = $current_user;
  }

  /**
   * Title callback function.
   */
  public function title() {
    return $this->config->get('headsup_list_page_title', $this->t('Heads-up list (default title)'));
  }

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function list() {
    $user = User::load($this->currentUser->id());
    $headsups = $this->repository->loadAllMyHeadsups($user);

    $hu_settings = [
      'uid' => $user->id(),
      'readmore' => $this->config->get('headsup_readmore_label', []),
      'readless' => $this->config->get('headsup_readless_label', []),
    ];
    $render_headsups = [
      '#theme' => 'headsup_list',
      '#type' => 'markup',
      '#attached' => [
        'library' => 'headsup/headsup-library',
        'drupalSettings' => [
          'headsup' => [
            'huvalues' => $hu_settings,
          ],
        ],
      ],
    ];
    // Extract the necessary fields of each headsup and build an array of each.
    // foreach ($headsups as $key => $value) {
    //   $fh_date = strtotime($value->get('field_headsup_date')->value);
    //   $render_headsups['#headsups'][$key] = [
    //     'nid' => $key,
    //     'title' => $value->title->value,
    //     'body' => $value->body->value,
    //     'field_headsup_date' => \Drupal::service('date.formatter')->format($fh_date, 'short'),
    //   ];
    // }
    $render_headsups['#headsups'] = $headsups;
    // Finally add the pager.
    $render_headsups['#pager'] = [
      '#type' => 'pager',
      '#weight' => 10,
    ];

    return $render_headsups;
  }

}
