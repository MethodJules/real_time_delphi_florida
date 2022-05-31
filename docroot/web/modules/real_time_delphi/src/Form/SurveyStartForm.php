<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Real Time Delphi form.
 */
class SurveyStartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_time_delphi_survey_start';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('real_time_delphi.settings');

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => $config->get('welcome'),
      '#title' => $this->t('Start Message'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Survey'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $form_state->setRedirect('real_time_delphi.survey_answer', ['question_id' => 1, 'user_pass' => 'admin']);
  }

}
