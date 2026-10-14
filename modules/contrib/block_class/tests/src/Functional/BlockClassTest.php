<?php

declare(strict_types=1);

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the custom CSS classes for blocks.
 *
 * @group block_class
 */
#[Group('block_class')]
#[RunTestsInSeparateProcesses]
class BlockClassTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block_class'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests the custom CSS classes for main page content and user menu blocks.
   */
  public function testBlockClass() : void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Define classes used for testing.
    $tested_blocks = [
      'block_main' => [
        // Ignore block HTML ID suffixes used in the tests.
        // cspell:disable-next-line
        'id' => 'mainpagecontent',
        'classes' => 'TestClass_content1 TestClass_content2 TestClass_content3',
      ],
      'block_user_menu' => [
        // cspell:disable-next-line
        'id' => 'useraccountmenu',
        'classes' => 'TestClass_menu1 TestClass_menu2 TestClass_menu3',
      ],
    ];

    $admin_user = $this->drupalCreateUser([
      BlockClassConstants::BLOCK_CLASS_PERMISSION,
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    // Add a main content block with custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_main_block/' . $this->defaultTheme, ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'content',
      'third_party_settings[block_class][classes]' => $tested_blocks['block_main']['classes'],
    ];
    $this->submitForm($edit, 'Save block');

    // Add a user account menu with a custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_menu_block:account/' . $this->defaultTheme, ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'secondary_menu',
      'third_party_settings[block_class][classes]' => $tested_blocks['block_user_menu']['classes'],
    ];
    $this->submitForm($edit, 'Save block');

    // Go to the front page and check for the custom classes in the block
    // attributes in the rendered HTML.
    $this->drupalGet('<front>');
    // Assert the custom classes are found in the content block.
    $assert->elementExists('xpath', '//div[contains(@class, "' . $tested_blocks['block_main']['classes'] . '") and substring(@id, string-length(@id) - 14)  = "' . $tested_blocks['block_main']['id'] . '"]');
    // Assert the custom classes are found in the user menu.
    $assert->elementExists('xpath', '//nav[contains(@class, "' . $tested_blocks['block_user_menu']['classes'] . '") and substring(@id, string-length(@id) - 14)  = "' . $tested_blocks['block_user_menu']['id'] . '"]');
  }

  /**
   * Tests that invalid CSS class values are rejected with a form error.
   *
   * @param string $classes
   *   The classes value to submit.
   * @param string $expected_message
   *   The expected violation message fragment.
   *
   * @dataProvider provideInvalidCssClasses
   */
  #[DataProvider('provideInvalidCssClasses')]
  public function testBlockClassFormValidationRejectsInvalidClasses(
    string $classes,
    string $expected_message,
  ): void {
    $admin_user = $this->drupalCreateUser([
      BlockClassConstants::BLOCK_CLASS_PERMISSION,
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet(
      'admin/structure/block/add/system_main_block/' . $this->defaultTheme,
      ['query' => ['region' => 'content']]
    );
    $this->submitForm(
      [
        'region' => 'content',
        'third_party_settings[block_class][classes]' => $classes,
      ],
      'Save block'
    );

    // The form must not redirect on validation failure.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_message);
  }

  /**
   * Data provider for testBlockClassFormValidationRejectsInvalidClasses().
   *
   * @return array<string, array<string>>
   *   The test cases.
   */
  public static function provideInvalidCssClasses(): array {
    return [
      'digit start' => [
        '1invalid',
        "'1invalid' is not a valid CSS class name.",
      ],
      'special character' => [
        'has!bang',
        "'has!bang' is not a valid CSS class name.",
      ],
      'one invalid token among valid ones' => [
        'valid-class 2bad',
        "'2bad' is not a valid CSS class name.",
      ],
    ];
  }

}
