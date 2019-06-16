<?php

namespace Drupal\brickset_connect\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines BricksetConnectController class.
 */
class BricksetConnectController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Brickset Connect!'),
    ];
  }

}