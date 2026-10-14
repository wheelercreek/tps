<?php

declare(strict_types=1);

namespace Drupal\block_class\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\block_class\Constants\BlockClassConstants;

/**
 * Hook implementations for block_class.
 */
class BlockClassHooks {

  use StringTranslationTrait;

  public function __construct(
    // Inject the default autowired current user service as it is needed to
    // check permissions in the block form alter hook.
    private readonly AccountInterface $currentUser,
    // Inject the typed data manager to reuse the CssClasses constraint
    // for form-level validation without duplicating logic.
    private readonly TypedDataManagerInterface $typedDataManager,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name) : ?string {
    switch ($route_name) {
      // Main module help for the block_class module.
      case 'help.page.block_class':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t("Block Class allows users to add classes to any block through the block's configuration interface. Hooray for more powerful block theming!") . '</p>';

        $output .= '<h3>' . $this->t('Installation note') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Enable the module on <a href=":extend_link">extend menu</a>.', [':extend_link' => Url::fromRoute('system.modules_list')->toString()]) . '</dt>';
        $output .= '</dl>';

        $output .= '<h3>' . $this->t('Usage') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t("To add a class to a block, simply visit that block's configuration page at Administration > Structure > Block Layout and click on Configure of the desired block.") . '</dt>';
        $output .= '</dl>';

        return $output;
    }

    return NULL;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the block_form form.
   */
  #[Hook('form_block_form_alter')]
  public function formBlockFormAlter(array &$form, FormStateInterface $form_state) : void {
    if ($this->currentUser->hasPermission(BlockClassConstants::BLOCK_CLASS_PERMISSION)) {
      $form_object = $form_state->getFormObject();
      if ($form_object instanceof EntityFormInterface) {
        /** @var \Drupal\block\BlockInterface $block */
        $block = $form_object->getEntity();

        // This will automatically be saved in the third party settings.
        $form['third_party_settings']['#tree'] = TRUE;
        $form['third_party_settings']['block_class']['classes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('CSS class(es)'),
          '#description' => $this->t('Customize the styling of this block by adding CSS classes. Separate multiple classes by spaces.'),
          '#default_value' => $block->getThirdPartySetting('block_class', 'classes'),
          '#maxlength' => 255,
          '#element_validate' => [[$this, 'validateCssClasses']],
        ];
      }
    }
  }

  /**
   * Validates that the CSS classes field contains valid CSS identifiers.
   *
   * Reuses the CssClasses config schema constraint via TypedDataManager so
   * form validation and config schema validation share identical logic.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateCssClasses(
    array &$element,
    FormStateInterface $form_state,
  ): void {
    $value = $element['#value'] ?? '';
    if ($value === '') {
      return;
    }

    $definition = DataDefinition::create('string')
      ->addConstraint('CssClasses');
    $data = $this->typedDataManager->create($definition, $value);
    foreach ($data->validate() as $violation) {
      $form_state->setError($element, $violation->getMessage());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for block entities.
   *
   * Ensure the block class property is removed when a block is saved without
   * any custom CSS classes, to prevent stale data.
   */
  #[Hook('block_presave')]
  public function blockPresave(BlockInterface $entity) : void {
    if (empty($entity->getThirdPartySetting('block_class', 'classes'))) {
      $entity->unsetThirdPartySetting('block_class', 'classes');
    }
  }

  /**
   * Implements hook_block_view_alter().
   *
   * If the block has any classes set in the 'block_class' third party settings,
   * add them to the block's attributes to be rendered by the theme.
   */
  #[Hook('block_view_alter')]
  public function blockViewAlter(array &$build) : void {
    if (
        !empty($build['#block']) &&
        $build['#block'] instanceof BlockInterface &&
        $classes = $build['#block']->getThirdPartySetting('block_class', 'classes')
        ) {
      // Clean the classes to ensure they are valid CSS class names and add them
      // to the block's attributes classes.
      $classes_array = explode(' ', $classes);
      foreach ($classes_array as $class) {
        $build['#attributes']['class'][] = Html::cleanCssIdentifier($class, []);
      }
    }
  }

  /**
   * Implements hook_migration_plugins_alter().
   */
  #[Hook('migration_plugins_alter')]
  public static function migrationPluginsAlter(array &$definitions) {
    $d7_block_migrations = array_filter($definitions, function (array $definition) {
        return $definition['id'] === 'd7_block';
    });
    foreach (array_keys($d7_block_migrations) as $d7_block_migration_id) {
      $definitions[$d7_block_migration_id]['process']['third_party_settings/block_class/classes'] = [
        'plugin' => 'default_value',
        'source' => 'css_class',
        'default_value' => NULL,
      ];
    }
  }

}
