<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Views;

/**
 * Tests the comment link field handlers.
 *
 * @group comment
 */
class CommentLinksTest extends CommentViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests the comment approve link.
   */
  public function testLinkApprove(): void {
    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();

    // Create an unapproved comment.
    $comment = $this->commentStorage->create([
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'status' => 0,
    ]);
    $comment->save();

    $view = Views::getView('test_comment');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'approve_comment' => [
        'table' => 'comment',
        'field' => 'approve_comment',
        'id' => 'approve_comment',
        'plugin_id' => 'comment_link_approve',
      ],
    ]);
    $view->save();

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo($this->adminUser);

    $view->preview();

    // Check if I can see the comment approve link on an unapproved comment.
    $approve_comment = $view->style_plugin->getField(0, 'approve_comment');
    $options = ['query' => ['destination' => '/']];
    $url = Url::fromRoute('comment.approve', ['comment' => $comment->id()], $options);
    $this->assertSame((string) $approve_comment, (string) Link::fromTextAndUrl('Approve', $url)->toString(), 'Found a comment approve link for an unapproved comment.');

    // Approve the comment.
    $comment->setPublished();
    $comment->save();
    $view = Views::getView('test_comment');
    $view->preview();

    // Check if I can see the comment approve link on an approved comment.
    $approve_comment = $view->style_plugin->getField(1, 'approve_comment');
    $this->assertEmpty((string) $approve_comment, "Didn't find a comment approve link for an already approved comment.");

    // Check if I can see the comment approve link on an approved comment as an
    // anonymous user.
    $account_switcher->switchTo(new AnonymousUserSession());
    // Set the comment as unpublished again.
    $comment->setUnpublished();
    $comment->save();

    $view = Views::getView('test_comment');
    $view->preview();
    $replyto_comment = $view->style_plugin->getField(0, 'approve_comment');
    $this->assertEmpty((string) $replyto_comment, "I can't approve the comment as an anonymous user.");
  }

  /**
   * Tests the comment reply link.
   */
  public function testLinkReply(): void {
    $this->enableModules(['field']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['field']);

    $field_storage_comment = FieldStorageConfig::create([
      'field_name' => 'comment',
      'type' => 'comment',
      'entity_type' => 'entity_test',
    ]);
    $field_storage_comment->save();
    // Create a comment field which allows threading.
    $field_comment = FieldConfig::create([
      'field_name' => 'comment',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'default_mode' => CommentManagerInterface::COMMENT_MODE_THREADED,
      ],
    ]);
    $field_comment->save();

    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();
    // Attach an unapproved comment to the test entity.
    $comment = $this->commentStorage->create([
      'uid' => $this->adminUser->id(),
      'entity_type' => 'entity_test',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'field_name' => $field_storage_comment->getName(),
      'status' => 0,
    ]);
    $comment->save();

    $view = Views::getView('test_comment');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'replyto_comment' => [
        'table' => 'comment',
        'field' => 'replyto_comment',
        'id' => 'replyto_comment',
        'plugin_id' => 'comment_link_reply',
        'entity_type' => 'comment',
      ],
    ]);
    $view->save();

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo($this->adminUser);
    $view->preview();

    // Check if I can see the reply link on an unapproved comment.
    $replyto_comment = $view->style_plugin->getField(0, 'replyto_comment');
    $this->assertEmpty((string) $replyto_comment, "I can't reply to an unapproved comment.");

    // Approve the comment.
    $comment->setPublished();
    $comment->save();
    $view = Views::getView('test_comment');
    $view->preview();

    // Check if I can see the reply link on an approved comment.
    $replyto_comment = $view->style_plugin->getField(0, 'replyto_comment');
    $url = Url::fromRoute('comment.reply', [
      'entity_type' => 'entity_test',
      'entity' => $host->id(),
      'field_name' => 'comment',
      'pid' => $comment->id(),
    ]);
    $this->assertSame((string) $replyto_comment, (string) Link::fromTextAndUrl('Reply', $url)->toString(), 'Found the comment reply link as an admin user.');

    // Check if I can see the reply link as an anonymous user.
    $account_switcher->switchTo(new AnonymousUserSession());
    $view = Views::getView('test_comment');
    $view->preview();
    $replyto_comment = $view->style_plugin->getField(0, 'replyto_comment');
    $this->assertEmpty((string) $replyto_comment, "Didn't find the comment reply link as an anonymous user.");
  }

}
