<?php

declare(strict_types=1);

namespace Drupal\Tests\block_class\Kernel\Plugin\Validation\Constraint;

use Drupal\block_class\Plugin\Validation\Constraint\CssClassesConstraint;
use Drupal\block_class\Plugin\Validation\Constraint\CssClassesConstraintValidator;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for the CssClasses constraint plugin discovery and wiring.
 *
 * Complements the unit test by verifying the constraint is correctly
 * registered in Drupal's typed data plugin system and produces the expected
 * violations when invoked through the TypedDataManager service.
 *
 * @group block_class
 */
#[CoversClass(CssClassesConstraint::class)]
#[CoversClass(CssClassesConstraintValidator::class)]
#[Group('block_class')]
#[RunTestsInSeparateProcesses]
class CssClassesConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_class'];

  /**
   * Tests that valid CSS class strings produce no violations.
   */
  public function testValidClassesProduceNoViolations(): void {
    $typed_data = $this->container->get('typed_data_manager');
    $definition = DataDefinition::create('string')->addConstraint('CssClasses');

    foreach ([
      'my-block',
      'block__element block--modifier',
      '-prefixed _underscored',
    ] as $value) {
      $data = $typed_data->create($definition, $value);
      $this->assertCount(0, $data->validate(), "Expected no violations for '$value'.");
    }
  }

  /**
   * Tests that invalid CSS class strings produce violations.
   */
  public function testInvalidClassProducesViolation(): void {
    $typed_data = $this->container->get('typed_data_manager');
    $definition = DataDefinition::create('string')->addConstraint('CssClasses');

    // Digit-starting token is invalid per the CSS 2.1 spec.
    $data = $typed_data->create($definition, '1invalid');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('1invalid', (string) $violations->get(0)->getMessage());
  }

  /**
   * Tests that multiple invalid tokens produce separate violations.
   */
  public function testMultipleInvalidTokensProduceMultipleViolations(): void {
    $typed_data = $this->container->get('typed_data_manager');
    $definition = DataDefinition::create('string')->addConstraint('CssClasses');

    $data = $typed_data->create($definition, '1a !b');
    $this->assertCount(2, $data->validate());
  }

}
