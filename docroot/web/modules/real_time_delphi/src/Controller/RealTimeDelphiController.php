<?php

namespace Drupal\real_time_delphi\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Real Time Delphi routes.
 */
class RealTimeDelphiController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

  public function get_graphic($question_id ) {

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
