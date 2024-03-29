<?php

namespace Drupal\usagov_wizards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller to handle route theming for the USAgov Wizards module.
 */
class WizardController extends ControllerBase {

  /**
   * Sets theme and variables needed to generate a wizard page.
   *
   * @param string $wizardId
   *   The ID of the wizard being viewed.
   *
   * @return array
   */
  public function wizardPage(string $wizardId) : array {
    // Generate the flattened wizard tree
    $wizardTree = \Drupal::service('usagov_wizards.wizard')->buildFlattenedWizardTreeFromNodeId( $wizardId );
    // Determine the Wizard update path.
    // TODO make this absolute instead of relative.
    $wizardUpdatePath = \Drupal\Core\Url::fromRoute('usagov_wizards.wizard_tree.update.v1')->toString();

    // Set the theme to allow for a custom template to be loaded, set JS variables,
    // and attach the necessary library.
    return [
      '#theme' => 'usagov_wizards_wizard',
      '#attached' => [
        'library' => [
          'usagov_wizards/usagov_wizards.react_wizard_viewer',
        ],
        // JS variables go here
        'drupalSettings' => [
          'wizardTree' => $wizardTree,
          'wizardUpdateUrl' => $wizardUpdatePath
        ]
      ]
    ];
  }

  /**
   * Callback function to set the Wizard Page's title.
   *
   * @param string $wizardId
   *   The ID of the wizard being viewed.
   *
   * @return string
   *   The title of the current webpage.
   */
  public function wizardPageTitle(string $wizardId) : string {
    $title = 'No Wizard Data';

    $wizard = Node::load($wizardId);
    if ( $wizard != null ) {
      // TODO better page title
      $title = $wizard->getTitle();
    }

    return $title;
  }

  /**
   * Sets theme and variables needed to create the wizard selection page.
   *
   * @return array
   */
  public function wizardSelectPage() : array {
    // Load all wizards
    $availableWizards = \Drupal::service('usagov_wizards.wizard')->getAllWizards();
    $reactWizards = [];
    // Format them for the front end
    // TODO change this from default Drupal data format to a more friendly format.
    foreach ($availableWizards as $key => $val) {
      $reactWizards[$key] = $val->toArray();
    }

    // Set the theme, JS variables, and library
    return [
      '#theme' => 'usagov_wizards_wizard_select',
      '#attached' => [
        'library' => [
          'usagov_wizards/usagov_wizards.react_wizard_select',
        ],
        // JS variables go here
        'drupalSettings' => [
          'wizards' => $reactWizards
        ],
      ],
      '#wizards' => $availableWizards
    ];
  }

}
