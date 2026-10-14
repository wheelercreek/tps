<?php

declare(strict_types=1);

namespace Drupal\block_class\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates a space-separated list of CSS class names.
 *
 * Each space-separated token must be a valid CSS identifier per the
 * CSS 2.1 specification (https://www.w3.org/TR/CSS21/syndata.html#characters).
 * Validation uses the same logic as Html::cleanCssIdentifier() to stay
 * consistent with how classes are sanitized at render time.
 *
 * @see \Drupal\Component\Utility\Html::cleanCssIdentifier()
 */
#[Constraint(
  id: 'CssClasses',
  label: new TranslatableMarkup('CSS classes', [], ['context' => 'Validation']),
)]
class CssClassesConstraint extends SymfonyConstraint {

  /**
   * Violation message when a token is not a valid CSS class name.
   *
   * @var string
   */
  public string $message = "'%token' is not a valid CSS class name.";

}
