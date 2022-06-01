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

    $config = \Drupal::config('real_time_delphi.settings');
    $welcome_message = $config->get('welcome');

    $build['content'] = [
      '#type' => 'markup',
      '#markup' => $welcome_message,
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

  




}
