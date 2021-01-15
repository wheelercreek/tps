<?php

namespace Drupal\tb_megamenu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Security\TrustedCallbackInterface;

class TBMegaMenuController extends ControllerBase implements TrustedCallbackInterface {

  /**
   * Attach the number of columns into JS.
   * @throws \Exception
   */
  public static function tb_megamenu_attach_number_columns($childrens, $elements) {
    $number_columns = &drupal_static('column');
    $render_array = [];
    $render_array['#attached']['drupalSettings']['TBMegaMenu'] = [
      'TBElementsCounter' => ['column' => $number_columns],
    ];
    \Drupal::service('renderer')->render($render_array);

    return $childrens;
  }

  /**
  * {@inheritDoc}
  */
  public static function trustedCallbacks() {
    return ['tb_megamenu_attach_number_columns'];
  }

}
