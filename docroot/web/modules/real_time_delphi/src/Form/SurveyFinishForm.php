<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\real_time_delphi\Traits\QuestionTrait;
use Drupal\real_time_delphi\Graphic\Boxplot;

/**
 * Provides a Real Time Delphi form.
 */
class SurveyFinishForm extends FormBase {

    public function getFormId()
    {
        return 'survey_finish_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = \Drupal::config('real_time_delphi.settings');

        $form['message'] = [
          '#type' => 'markup',
          '#markup' => $config->get('end'),
          '#title' => $this->t('Fnish Message'),
          '#required' => TRUE,
        ];
    
        $form['actions'] = [
          '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Finish'),
        ];
    
        return $form;
    }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $form_state->setRebuild();
  }
}