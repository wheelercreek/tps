<?php

declare(strict_types=1);

namespace Drupal\block_class\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Html;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the CssClasses constraint.
 */
class CssClassesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof CssClassesConstraint);

    // NULL and empty string are handled by NotBlank / NotNull constraints.
    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }

    // Split on any whitespace run; filter removes empty tokens produced by
    // leading, trailing, or multiple consecutive spaces.
    $tokens = array_filter((array) preg_split('/\s+/', $value));

    foreach ($tokens as $token) {
      // A token is valid when cleaning it as a CSS identifier leaves it
      // unchanged. The empty $filter array disables the default character
      // replacements (e.g. space-to-hyphen), so only truly invalid characters
      // are stripped, making the comparison exact.
      if (Html::cleanCssIdentifier($token, []) !== $token) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%token', $token)
          ->addViolation();
      }
    }
  }

}
