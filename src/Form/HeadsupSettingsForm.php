<?php

namespace Drupal\headsup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HeadsupSettingsForm.
 */
class HeadsupSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'headsup.headsupsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'headsup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('headsup.headsupsettings');

    $list_page_title = $config->get('headsup_list_page_title');
    $list_page_title ? $list_page_title : $list_page_title = 'Heads-up messages';
    $form['headsup_list_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List page title'),
      '#description' => $this->t('The title at the top of the list page.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $list_page_title,
    ];

    $list_pager_limit = $config->get('headsup_list_pager_limit');
    $list_pager_limit ? $list_pager_limit : $list_pager_limit = 10;
    $form['headsup_list_pager_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Pager limit'),
      '#description' => $this->t('The number of heads-ups shown per page.'),
      '#size' => 12,
      '#default_value' => $list_pager_limit,
    ];

    $form['headsup_button_labels'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Button labels'),
    ];

    $readmore_label = $config->get('headsup_readmore_label');
    $readmore_label ? $readmore_label : $readmore_label = 'Read more';
    $form['headsup_button_labels']['readmore'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read More button'),
      '#maxlength' => 64,
      '#size' => 12,
      '#default_value' => $readmore_label,
    ];

    $readless_label = $config->get('headsup_readless_label');
    $readless_label ? $readless_label : $readless_label = 'Read less';
    $form['headsup_button_labels']['readless'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read Less button'),
      '#maxlength' => 64,
      '#size' => 12,
      '#default_value' => $readless_label,
    ];

    $acknowledge_label = $config->get('headsup_acknowledge_label');
    $acknowledge_label ? $acknowledge_label : $acknowledge_label = 'I acknowledge';
    $form['headsup_button_labels']['acknowledge'] = [
      '#type' => 'textfield',
      '#title' => $this->t('I Acknowledge button'),
      '#maxlength' => 64,
      '#size' => 12,
      '#default_value' => $acknowledge_label,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('headsup.headsupsettings')
      ->set('headsup_list_page_title', $form_state->getValue('headsup_list_page_title'))
      ->set('headsup_list_pager_limit', $form_state->getValue('headsup_list_pager_limit'))
      ->set('headsup_readmore_label', $form_state->getValue('readmore'))
      ->set('headsup_readless_label', $form_state->getValue('readless'))
      ->set('headsup_acknowledge_label', $form_state->getValue('acknowledge'))
      ->save();
  }

}
