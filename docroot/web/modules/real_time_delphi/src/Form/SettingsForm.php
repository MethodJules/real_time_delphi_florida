<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Real Time Delphi settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_time_delphi_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['real_time_delphi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('real_time_delphi.settings');
    $form['title'] = array(
      '#type' => 'textarea',
      '#rows' => 1,
      '#resizable' => FALSE,
      '#title' => $this->t("Surveytitle"),
      '#maxlength' => 254,
      '#default_value' => $config->get('title'), 
    );

    $form['welcome'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Welcome Message'),
      '#default_value' => $config->get('welcome'), //TODO: 
      // '#format' => 'full_html',
      '#rows' => 16,
    );

    $form['end'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('End Message'),
      '#default_value' => $config->get('end'), // TODO:
      // '#format' => 'full_html',
      '#rows' => 6,
    );

    $form['threshold'] = array(
      '#type' => 'select',
      '#title' => $this->t('Threshold'),
      '#options' => array(
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
      ),
      '#description' => $this->t('This sets the threshold for displaying the boxplot.'),
      '#default_value' => $config->get('threshold'),
  );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('real_time_delphi.settings')
      ->set('title', $form_state->getValue('title'))
      ->set('welcome', $form_state->getValue('welcome'))
      ->set('end', $form_state->getValue('end'))
      ->set('threshold', $form_state->getValue('threshold'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
