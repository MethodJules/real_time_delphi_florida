<?php

namespace Drupal\real_time_delphi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
 * Provides a Real Time Delphi form.
 */
class CreateSurveyToken extends FormBase {


  public function getFormId()
  {
    return 'survey_token_form';
  }

  public function buildForm(array $form,FormStateInterface $form_state) {

    $data = 'Delete';

    $qrcode = [
      '#type' => 'markup',
      '#markup' => '<img src="' . (new QRCode)->render($data) . '" alt="QR Code" />',
    ];
    $linkText = 'http://www.google.de';
    $link = [
      '#type' => 'link',
        '#title' => Markup::create($linkText),
        '#attributes' => [
            'id' => 'goto-step-three',
            'class' => [
            'btn',
            'btn-green',
            'btn--goback-step-one',
            ],
            'data-twig-id' => 'goto-step-one',
        ],
        '#weight' => 1,
        '#url' => Url::fromRoute('real_time_delphi.add_question', ['answer_quantity_id' => 1]),
        ];
    
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Tokens'),
    ];

    $tokens = $this->_get_all_respondent_identifieres();
    // $tokens = ['sdsdshdishdsds', 'sidhisdhishdisdh'];

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
        $rows[] = [
          'token' => $token->user_pw,
          'link' => 'sdsodksoksdo',
          'qrcode' => 'hier kommt der QR CODE',
        ];
      }

      $form['token_delete']['tokensdel_table'] = [
        '#theme' => 'table',
        '#header' => [$this->t('Token'), 'Action', 'QR Code'],
        '#rows' => $rows,
      ];
    }


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $token_quantity = $form_state->getValue('token_quantity');
    for ( $i=0; $i < $token_quantity; $i++) {
      $this->createToken();
    }
    \Drupal::messenger()->addMessage($i . ' Tokens generated.');

    

    $form_state->setRebuild();
  }

  public function _get_all_respondent_identifieres() {
    //$query = "SELECT user_pw FROM {survey_users} GROUP BY user_pw ORDER BY user_id ASC;";
    //$result = db_query($query);
    $database = \Drupal::database();
    $query = $database->select('survey_users', 'su');
    $query->addField('su', 'user_pw');
    $query->groupBy('user_pw');
    $query->orderBy('user_id');
    $result = $query->execute();
    $respondent_identifieres = $result->fetchAll();

    return $respondent_identifieres;
  }

  //Diese Funktion generiert eine einzigartige ID für jeden Nutzer die 30 Stellen beinhaltet.
  public function survey_generate_random_string($length) {
    $valid_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $valid_characters = '0123456789abc';
    $characters_length = strlen($valid_characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $valid_characters[rand(0, $characters_length - 1)];
    }
    return $random_string;
  }
  
  // Generate a user token and save it to the database
  public function createToken() {
    $database = \Drupal::database();
    $user_check_string = $this->survey_generate_random_string(30);
    // TODO: Try catch block
      $query = $database->insert('survey_users')
                        ->fields(['user_pw' => $user_check_string])
                        ->execute();
    \Drupal::messenger()->addMessage('Token: "' . $user_check_string . '" was generated');

    return ['#markup' => '<p>Token: "' . $user_check_string . '" was generated</p>'];
  }





}
