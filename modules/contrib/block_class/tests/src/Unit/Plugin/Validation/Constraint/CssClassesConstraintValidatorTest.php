<?php

declare(strict_types=1);

namespace Drupal\Tests\block_class\Unit\Plugin\Validation\Constraint;

use Drupal\block_class\Plugin\Validation\Constraint\CssClassesConstraint;
use Drupal\block_class\Plugin\Validation\Constraint\CssClassesConstraintValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Unit tests for CssClassesConstraintValidator.
 *
 * Uses Symfony's ConstraintValidatorTestCase helper to avoid manual mocking of
 * the execution context. No Drupal bootstrap is required: the validator only
 * calls Html::cleanCssIdentifier(), a pure static utility method.
 *
 * @group block_class
 */
#[CoversClass(CssClassesConstraintValidator::class)]
#[Group('block_class')]
class CssClassesConstraintValidatorTest extends ConstraintValidatorTestCase {

  /**
   * {@inheritdoc}
   */
  protected function createValidator(): CssClassesConstraintValidator {
    return new CssClassesConstraintValidator();
  }

  /**
   * Tests valid values produce no violations.
   *
   * @dataProvider provideValidValues
   */
  #[DataProvider('provideValidValues')]
  public function testValidValue(mixed $value): void {
    $this->validator->validate($value, new CssClassesConstraint());
    $this->assertNoViolation();
  }

  /**
   * Provides valid values for ::testValidValue().
   *
   * @return array<string, array<mixed>>
   *   The test cases.
   */
  public static function provideValidValues(): array {
    return [
      'null is skipped' => [NULL],
      'empty string is skipped' => [''],
      'single class' => ['my-block'],
      'hyphen prefix' => ['-my-block'],
      'underscore prefix' => ['_private'],
      'uppercase' => ['MyBlock'],
      'alphanumeric suffix' => ['block2'],
      'multiple classes' => ['my-block another-class'],
      'BEM double underscore' => ['block__element'],
      'BEM modifier' => ['block--modifier'],
      'full BEM' => ['block__element block--modifier'],
      'leading spaces are tolerated' => ['  my-block'],
      'trailing spaces are tolerated' => ['my-block  '],
      'multiple spaces between classes are tolerated' => ['my-block   another'],
    ];
  }

  /**
   * Tests that a single invalid token produces exactly one violation.
   *
   * @dataProvider provideInvalidValues
   */
  #[DataProvider('provideInvalidValues')]
  public function testInvalidValue(string $value, string $invalid_token): void {
    $constraint = new CssClassesConstraint();
    $this->validator->validate($value, $constraint);
    $this->buildViolation($constraint->message)
      ->setParameter('%token', $invalid_token)
      ->assertRaised();
  }

  /**
   * Provides invalid values for ::testInvalidValue().
   *
   * Each case must produce exactly one violation so that assertRaised() passes.
   *
   * @return array<string, array<string>>
   *   The test cases.
   */
  public static function provideInvalidValues(): array {
    return [
      'digit start' => ['1invalid', '1invalid'],
      'hyphen then digit start' => ['-1bad', '-1bad'],
      'special character' => ['has!bang', 'has!bang'],
      'invalid second token, valid first' => ['valid-class 2bad', '2bad'],
    ];
  }

  /**
   * Tests that multiple invalid tokens each produce a separate violation.
   */
  public function testMultipleInvalidTokensProduceMultipleViolations(): void {
    $constraint = new CssClassesConstraint();
    $this->validator->validate('1a !b', $constraint);
    $this->assertCount(2, $this->context->getViolations());
  }

}
