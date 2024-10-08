<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests workflow UI when there are no types.
 *
 * @group workflows
 */
class WorkflowUiNoTypeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // We're testing local actions.
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests the creation of a workflow through the UI.
   */
  public function testWorkflowUiWithNoType(): void {
    $this->drupalLogin($this->createUser(['access administration pages', 'administer workflows']));
    $this->drupalGet('admin/config/workflow/workflows/add');
    // There are no workflow types so this should be a 403.
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/workflow/workflows');
    $this->assertSession()->pageTextContains('There are no workflow types available. In order to create workflows you need to install a module that provides a workflow type. For example, the Content Moderation module provides a workflow type that enables workflows for content entities.');
    $this->assertSession()->linkExists('Content Moderation');
    $this->assertSession()->pageTextNotContains('Add workflow');

    $this->clickLink('Content Moderation');

    $modules_list_url_absolute = Url::fromRoute('system.modules_list', [], [
      'fragment' => 'module-content-moderation',
      'absolute' => TRUE,
    ])->toString();
    $this->assertSame($this->getSession()->getCurrentUrl(), $modules_list_url_absolute);
    // The current user does not have the 'administer modules' permission.
    $this->assertSession()->statusCodeEquals(403);

    $this->container->get('module_installer')->install(['workflow_type_test']);
    // The render cache needs to be cleared because although the cache tags are
    // correctly set the render cache does not pick it up.
    \Drupal::cache('render')->deleteAll();

    $this->drupalGet('admin/config/workflow/workflows');
    $this->assertSession()->pageTextNotContains('There are no workflow types available. In order to create workflows you need to install a module that provides a workflow type. For example, the Content Moderation module provides a workflow type that enables workflows for content entities.');
    $this->assertSession()->linkExists('Add workflow');
    $this->assertSession()->pageTextContains('There are no workflows yet.');
  }

}
