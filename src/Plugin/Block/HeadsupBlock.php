<?php

namespace Drupal\headsup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\headsup\HeadsupRepository;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Displays any unacknowledged, applicable heads-ups.
 *
 * @Block(
 *   id = "headsupblock",
 *   admin_label = @Translation("Heads-up Block."),
 *   category = @Translation("Other"),
 * )
 */
class HeadsupBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The repository for our specialized queries.
   *
   * @var \Drupal\headsup\HeadsupRepository
   */
  protected $repository;

  /**
   * The current user.
   *
   * We'll need this service in order to check if the user is logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Create something.
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the repository service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('headsup.repository'),
      $container->get('current_user')
    );
  }

  /**
   * Constructor class.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\headsup\HeadsupRepository $repository
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HeadsupRepository $repository, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->repository = $repository;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = \Drupal::config('headsup.headsupsettings');
    $headsup_acknowledge_label = $config->get('headsup_acknowledge_label', []);

    if (!$this->currentUser->hasPermission('view headsup messages')) {
      return NULL;
    }
    $user = User::load($this->currentUser->id());
    $headsups = $this->repository->loadRelevantHeadsups($user);

    if (count($headsups) === 0) {
      return NULL;
    }

    // Extract the necessary fields of each headsup and build an array of each.
    foreach ($headsups as $key => $value) {
      $fh_start_date = strtotime($value->get('field_headsup_start_date')->value);
      $render_headsups[$key] = [
        'nid' => $key,
        'title' => $value->title->value,
        'body' => $value->body->value,
        'field_headsup_start_date' => \Drupal::service('date.formatter')->format($fh_start_date, 'short'),
        'this_link' => [
          '#type' => 'link',
          '#title' => $headsup_acknowledge_label,
          '#attributes' => [
            'class' => [
              'use-ajax',
              'headsup-button',
              'headsup-acknowledge-button',
            ],
            'id' => 'headsup-more-' . $key,
            'rel' => $key,
          ],
          '#url' => Url::fromRoute('headsup.headsup_acknowedge', [
            'nid' => $key,
            'uid' => $user->id(),
          ]),
        ],
      ];
    }

    // Wrap the headsup nodes in a carousel-like set of divs.
    $build = [
      '#theme' => 'headsup_carousel',
      '#headsups' => $render_headsups,
    ];
    $build['#attached']['library'][] = 'headsup/headsup-library';
    $build['#attached']['library'][] = 'core/drupal.ajax';
    $hu_settings = [
      'uid' => $user->id(),
      'readmore' => $config->get('headsup_readmore_label', []),
      'readless' => $config->get('headsup_readless_label', []),
    ];
    $build['#attached']['drupalSettings']['headsup']['huvalues'] = $hu_settings;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
