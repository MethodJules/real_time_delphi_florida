<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Real Time Delphi form.
 */
class SurveyAnswerForm extends FormBase {

    /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_time_delphi_survey_answer';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $question_id = NULL, $user_pass = NULL) {
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $form_state->setRedirect('<front>');
  }
}