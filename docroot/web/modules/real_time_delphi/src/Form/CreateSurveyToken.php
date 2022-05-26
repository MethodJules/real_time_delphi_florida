<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Real Time Delphi form.
 */
class CreateSurveyToken extends FormBase {


  public function getFormId()
  {
    return 'survey_token_form';
  }

  public function buildForm(array $form,FormStateInterface $form_state) {
    $form['token_create'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Create Token'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['token_create']['token_quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => 1,
    ];

    $$form['token_create']['submit'] = [
      '#type' => 'submit',
      '#title' => $this->t('Create Tokens'),
    ];

    // $tokens = $this->_get_all_respondent_identifieres();
    $tokens = ['sdsdshdishdsds', 'sidhisdhishdisdh'];

    if (count($tokens) > 0) {
      $form['token_delete'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Created Tokens'),
        '#collapisble' => TRUE,
        '#collapse' => FALSE,
      ];

      //$rows = [];
      foreach($tokens as $token) {
        //$rows[] = $token->user_pw;
        $rows[] = ['token' => $token];
      }

      $form['token_delete']['tokensdel_table'] = [
        '#theme' => 'table',
        '#header' => [$this->t('Token'),],
        '#rows' => $rows,
      ];
    }


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $form_state->setRebuild();
  }

  public function _get_all_respondent_identifieres()
  {
    $query = "SELECT user_pw FROM {survey_users} GROUP BY user_pw ORDER BY user_id ASC;";
    $result = db_query($query);
    $respondent_identifieres = $result->fetchAll();

    return $respondent_identifieres;
  }

  //Diese Funktion generiert eine einzigartige ID für jeden Nutzer die 30 Stellen beinhaltet.
  public function survey_generate_random_string($length)
  {
    $valid_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $valid_characters = '0123456789abc';
    $characters_length = strlen($valid_characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $valid_characters[rand(0, $characters_length - 1)];
    }
    return $random_string;
  }



}
