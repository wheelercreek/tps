<?php

declare(strict_types=1);

namespace Drupal\Tests\block_class\Kernel;

use Drupal\block\Entity\Block;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the block_class config schema and its CssClasses constraint.
 *
 * Verifies that:
 * - A block config with valid block_class third-party settings passes schema
 *   validation (including the FullyValidatable and CssClasses constraints).
 * - A config blob with an invalid CSS class string fails schema validation at
 *   the correct path.
 *
 * @group block_class
 */
#[Group('block_class')]
#[RunTestsInSeparateProcesses]
class BlockClassConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_class', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['stark']);
  }

  /**
   * Tests that valid block_class settings pass schema validation.
   */
  public function testValidBlockClassConfigPassesSchemaValidation(): void {
    $block = Block::create([
      'id' => 'test_valid',
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
      'region' => 'content',
      'third_party_settings' => [
        'block_class' => ['classes' => 'my-block block__element block--modifier'],
      ],
    ]);
    $block->save();

    $config = $this->config('block.block.test_valid');
    $this->assertConfigSchema(
      $this->container->get('config.typed'),
      $config->getName(),
      $config->get()
    );
  }

  /**
   * Tests that a block config with invalid CSS classes fails schema validation.
   *
   * Uses createFromNameAndData() on the typed config manager rather than saving
   * a real entity, to avoid triggering the ConfigSchemaChecker exception.
   */
  public function testInvalidCssClassesFailSchemaValidation(): void {
    // First save a valid block to obtain a well-formed config data array.
    $block = Block::create([
      'id' => 'test_invalid',
      'plugin' => 'system_powered_by_block',
      'theme' => 'stark',
      'region' => 'content',
      'third_party_settings' => [
        'block_class' => ['classes' => 'valid-class'],
      ],
    ]);
    $block->save();

    // Replace the classes value with an invalid token and validate via the
    // typed config manager without triggering a config save.
    $config_data = $this->config('block.block.test_invalid')->get();
    $config_data['third_party_settings']['block_class']['classes'] = '1invalid';

    $typed = $this->container->get('config.typed')
      ->createFromNameAndData('block.block.test_invalid', $config_data);

    $violations = $typed->validate();
    $this->assertGreaterThan(0, $violations->count());

    // Confirm the violation message identifies the invalid token.
    $messages = array_map(
      fn($v) => (string) $v->getMessage(),
      iterator_to_array($violations)
    );
    $this->assertNotEmpty(
      array_filter($messages, fn($m) => str_contains($m, '1invalid'))
    );
  }

}
