<?php

namespace Drupal\Tests\block_class\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

// The migrate_drupal core module was removed in Drupal 12. This conditional
// defines a base class that extends MigrateDrupalTestBase when available
// (Drupal 11 and below), or falls back to KernelTestBase and marks tests as
// skipped on Drupal 12+.
if (class_exists(MigrateDrupalTestBase::class)) {

  /**
   * Base class for block class migration tests (Drupal 11 and below).
   */
  abstract class BlockClassMigrationTestBase extends MigrateDrupalTestBase {
  }

}
else {

  /**
   * Fallback base class: skips migration tests on Drupal 12+.
   *
   * Migrate_drupal was removed from Drupal core in Drupal 12.
   */
  abstract class BlockClassMigrationTestBase extends KernelTestBase {

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
      parent::setUp();
      $this->markTestSkipped('migrate_drupal is not available in Drupal 12+.');
    }

  }
}
