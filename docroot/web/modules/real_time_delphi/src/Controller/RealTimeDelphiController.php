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

}
