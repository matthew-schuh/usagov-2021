<?php

namespace Drupal\usagov_wizards\Services;

use Drupal\node\Entity\Node;

class WizardsService {

  /**
   * Constructs an array containing wizard tree data with nested children.
   * Builds the entire wizard tree.
   * 
   * @param bool $keyedChildren
   *   Whether the 'children' array should be associative (true)
   *   (keyed by child ID) or sequential (false). Default is true.
   * 
   * @return array
   *   Wizard tree data represented as an array.
   */
  public function buildWizardTree( bool $keyedChildren = true ) : array {
    $wizardTree = [];
    // Load all Wizards (top level entries in the wizard tree) that the user has access to.
    $wizards = $this->getAllWizards();
    // For each wizard, recursively generate its tree.
    foreach ($wizards as $wizard) {
      $wizardTree[$wizard->id()] = $this->buildWizardStep( $wizard, $keyedChildren );
    }

    return $wizardTree;
  }

  /**
   * Constructs an array containing wizard tree data with nested children.
   * Builds the wizard tree starting at the Node with the provided ID.
   * 
   * @param int $startNodeId
   *   The ID of the node to act as the root of the tree.
   * @param bool $keyedChildren
   *   Whether the 'children' array should be associative (true)
   *   (keyed by child ID) or sequential (false). Default is true.
   * 
   * @return array
   *   Wizard tree data represented as an array.
   */
  public function buildWizardTreeFromNodeId( int $startNodeId, bool $keyedChildren = true ) : array {
    $node = Node::load($startNodeId);
    if ( $this->isValidTreeNode($node) ) {
      return $this->buildWizardTreeFromNode( $node, $keyedChildren );
    }
    // TODO If the node doesn't exist, what do we return? Empty tree? The entire tree?
    return [];
  }

  /**
   * Constructs an array containing wizard tree data with nested children.
   * Builds the wizard tree starting at the provided Node.
   * 
   * @param Node|null $wizard
   *   The Node to act as the root of the tree.
   * @param bool $keyedChildren
   *   Whether the 'children' array should be associative (true)
   *   (keyed by child ID) or sequential (false). Default is true.
   * 
   * @return array
   *   Wizard tree data represented as an array.
   */
  public function buildWizardTreeFromNode( Node|null $wizard, bool $keyedChildren = true ) : array {
    if ( $this->isValidTreeNode($wizard) ) {
      return $this->buildWizardStep( $wizard, $keyedChildren );
    }
    // TODO If the node doesn't exist, what do we return? Empty tree? The entire tree?
    return [];
  }

  /**
   * Constructs an array containing flattened wizard tree data.
   * All node data is at the top level. Builds the entire wizard tree
   * for all wizards.
   * 
   * @return array
   *   Wizard tree data represented as a flattened array.
   */
  public function buildFlattenedWizardTree() {
    $wizardTree = [];
    $wizards = $this->getAllWizards();

    foreach ( $wizards as $wizard ) {
      $wizardTree[$wizard->id()] = $this->buildFlattenedWizardTreeFromNode( $wizard );
    }

    return $wizardTree;
  }

  /**
   * Constructs an array containing flattened wizard tree data. All node data
   * is at the top level. Builds the wizard tree starting at the Node with
   * the provided ID.
   * 
   * @param int $startNodeId
   *   The ID of the node to act as the root of the tree.
   * 
   * @return array
   *   Wizard tree data represented as a flattened array.
   */
  public function buildFlattenedWizardTreeFromNodeId( int $startNodeId ) : array {
    $node = Node::load($startNodeId);
    if ( $this->isValidTreeNode($node) ) {
      return $this->buildFlattenedWizardTreeFromNode( Node::load($startNodeId) );
    }
    // TODO If the node doesn't exist, what do we return? Empty tree? The entire tree?
    return [];
  }

  /**
   * Constructs an array containing flattened wizard tree data. All node data
   * is at the top level. Builds the wizard tree starting at the provided Node.
   * Essentially a breadth-first algorithm.
   * 
   * @param Node|null $wizard
   *   The Node to act as the root of the tree.
   * 
   * @return array
   *   Wizard tree data represented as a flattened array.
   */
  public function buildFlattenedWizardTreeFromNode( Node|null $wizard ) : array {
    $wizardTree = [];
    $ids = [];
    $treeQueue = [];
    
    if ( $this->isValidTreeNode($wizard) ) {
      // Create a queue of nodes to add to the return array and add the initial
      // Node to it.
      if ( $wizard != null ) {
        $treeQueue[] = [
          'node' => $wizard,
          'parent' => null,
        ];
      }

      // Continue processing until all Nodes have been handled.
      while ( !empty($treeQueue) ) {
        // Grab the first item from the queue.
        $treeNode = array_shift($treeQueue);
        $parent = $treeNode['parent'];
        $treeNode = $treeNode['node'];
        // Only handle the node if it hasn't already been handled.
        if ( !isset($wizardTree[$treeNode->id()]) ) {
          $ids[] = $treeNode->id();
          $wizardTree[$treeNode->id()] = $this->buildWizardDataFromStep( $treeNode, true );
          if ( $parent !== null ) {
            $wizardTree[$treeNode->id()]['parentStepId'] = $parent->id();
          } else {
            $wizardTree[$treeNode->id()]['parentStepId'] = null;
          }
          $children = $treeNode->get('field_wizard_step')->referencedEntities();
          foreach ( $children as $child ) {
            if ( !isset($wizardTree[$child->id()]) ) {
              $treeQueue[] = [
                'node' => $child,
                'parent' => $treeNode,
              ];
            }
          }
        }
      }
    }

    return [
      'entities' => $wizardTree,
      'ids' => $ids,
      'rootStepId' => $wizard?->id(),
      'availableLanguages' => $this->getAvailableLanguages()
    ];
  }

  /**
   * Recursively builds a nested array representing the wizard tree.
   * 
   * @param Node $wizardStep
   *   The Node acting as the root of the current tree.
   * @param bool $keyedChildren
   *   Whether the 'children' array should be associative (true)
   *   (keyed by child ID) or sequential (false). Default is true.
   * @param array $visited
   *   Keeps a list of visited node ids to prevent infinite recursion
   * 
   * @return array
   *   An array representing the wizard tree.
   */
  protected function buildWizardStep( Node $wizardStep, bool $keyedChildren = true, array &$visited = [] ) : array {
    // TODO infinite loop prevention. Maintain array of 'visited' nodes. While the React front-end won't
    // allow for this, Drupal technically does so we should watch for it.
    // Base case - the current step is null, so we return null.
    if ( $wizardStep == null ) {
      return null;
    }

    $visited[] = $wizardStep->id();

    $treeNode = $this->buildWizardDataFromStep( $wizardStep, false );

    $children = $wizardStep->get('field_wizard_step')->referencedEntities();

    foreach ($children as $child) {
      $childId = $child->id();
      if ( !in_array($childId, $visited) ) {
        $childStep = $this->buildWizardStep( $child, $keyedChildren, $visited );
        if ( $keyedChildren ) {
          $treeNode['children'][$childId] = $childStep;
        } else {
          $treeNode['children'][] = $childStep;
        }
      }
    }

    return $treeNode;
  }

  /**
   * Extracts necessary data from a Node into an array.
   * 
   * @param Node $wizardStep
   *   The node to pull data from.
   * @param bool $includeChildIds
   *   Whether children should be an empty array or an array of Node ids.
   *   Default is false (empty array).
   * 
   * @return array
   *   An array representing this step of the wizard tree.
   */
  function buildWizardDataFromStep( Node $wizardStep, bool $includeChildIds = false ) : array {
    $stepData = [];

    // Page Intro                     (field_page_intro)
    // Hide Page Intro                (field_hide_page_intro)
    // Meta Description               (field_meta_description)
    // Short Description              (field_short_description)
    // Page Type                      (field_page_type)
    // Language                       () TODO
    // Language Toggle                (field_language_toggle)
    // Body                           (body)
    // Text Format                    Not a field - this is related to body
    // Header HTML                    (field_header_html)
    // CSS Icon                       (field_css_icon)
    // Footer HTML (field)            (field_footer_html)
    // For contact center use only    (field_for_contact_center_only)
    // FAQ                            (field_faq_page)
    // Custom Twig Content            (field_custom_twig_content)
    // Exclude from contact center    (field_exclude_from_contact_cente)
    // Wizard Step                    (field_wizard_step)

    if ( $wizardStep ) {
      // TODO strip tags from text fields
      // TODO separate functions for getting wizard and wizard step field values - they have different fields available.
      $stepData = [
        'nodeType' => $wizardStep->bundle(),
        'name' => preg_replace('/[ -]/', '_', strtolower($wizardStep->getTitle() ?? 'wizard_step_' . $wizardStep->id())),
        'title' => $wizardStep->getTitle() ?? '',
        'id' => $wizardStep->id() ?? '',
        'pageIntro' => $this->getFieldValue($wizardStep, 'field_page_intro'),
        'hidePageIntro' => $this->getFieldValue($wizardStep, 'field_hide_page_intro'),
        'metaDescription' => $this->getFieldValue($wizardStep, 'field_meta_description'),
        'shortDescription' => $this->getFieldValue($wizardStep, 'field_short_description'),
        'pageType' => $this->getFieldValue($wizardStep, 'field_page_type'),
        'languageToggle' => $this->getFieldValue($wizardStep, 'field_language_toggle'),
        'language' => $wizardStep->language()->getName(),
        'body' => $this->getFieldValue($wizardStep, 'body'),
        'headerHTML' => $this->getFieldValue($wizardStep, 'field_header_html'),
        'cssIcon' => $this->getFieldValue($wizardStep, 'field_css_icon'),
        'footerHTML' => $this->getFieldValue($wizardStep, 'field_footer_html'),
        'forContactCenterUseOnly' => $this->getFieldValue($wizardStep, 'field_for_contact_center_use_only'),
        'faq' => $this->getFieldValue($wizardStep, 'field_faq_page'),
        'customTwigContent' => $this->getFieldValue($wizardStep, 'field_custom_twig_content'),
        'excludeFromContactCenter' => $this->getFieldValue($wizardStep, 'field_exclude_from_contact_cente'),
        'children' => []
      ];

      if ($includeChildIds) {
        $children = $wizardStep->get('field_wizard_step')->referencedEntities();
        foreach ($children as $child) {
          $stepData['children'][] = $child->id();
        }
      }
    }

    return $stepData;
  }

  /**
   * Saves the wizard tree or portion of a wizard tree provided.
   * Note this can create, delete, or edit nodes provided
   * the user is logged in and has permission to do so.
   * 
   * @param array
   *   Array containing tree data to be saved.
   */
  // TODO return status of some sort. E.g. nodes deleted, updated, created.
  // Whether it succeeded, etc.
  public function saveWizardTree( array $tree ) : void {
    // TODO validate tree
    // TODO validate user permissions
    // TODO delete wizard steps if not present in given tree.
    
    if ($this->validateUserWizardTreePermissions()) {
      // Support data structure being wrapped in top-level objects
      // 'entities' and 'ids' - see buildFlattenedWizardTree
      if ( isset($tree['entities']) ) {
        // Convert to associative array keyed by id
        $treeArray = $tree['entities'];
        $tree = [];
        foreach ( $treeArray as $treeNode ) {
          $tree[$treeNode['id']] = $treeNode;
        }
      }
      // Determine format - nested vs. flattened
      // TODO make sure this is correct
      $nested = false;
      foreach ( $tree as $treeNode ) {
        $children = $treeNode['children'];
        foreach ( $children as $child ) {
          if ( !is_numeric($child) ) {
            $nested = true;
          }
          break;
        }
        break;
      }

      if ( $nested ) {
        $this->saveWizardTreeNested($tree);
      } else {
        $this->saveWizardTreeFlattened($tree);
      }
    } else {
      // TODO
    }
  }

  /**
   * Validates whether the user has necessary permissions to modify the wizard tree.
   * 
   * @return bool
   */
  public function validateUserWizardTreePermissions() : bool {
    // TODO properly load currently logged in user
    $user = \Drupal::currentUser();
    // TODO determine correct permissions
    if ( $user->isAuthenticated() ) {//&& $user->hasPermission('')) {
      return true;
    }
    return false;
  }

  /**
   * Helper function to save wizard tree data in nested format.
   * 
   * @param array $tree
   *   The nested wizard tree data.
   */
  protected function saveWizardTreeNested(array $tree) : void {
    $this->saveWizardStep($tree);
  }

  /**
   * Helper function to save wizard tree data in flattened format.
   * 
   * @param array $tree
   *   The flattened wizard tree data.
   */
  protected function saveWizardTreeFlattened(array $tree) : void {
    // As a flattened tree, iterate through each item in the tree and update that node.
    // If the item has no parent, it is a wizard. If it has a parent, it is a wizard step.
    foreach ( $tree as $wizardStepId => $wizardStep ) {
      $wizardStep = $tree[$wizardStepId];
      if ( $wizardStep !== null ) {
        // Attempt to load the node. If the ID is null, negative,
        // or a node doesn't exist with that ID, then $node will
        // be null.
        if ( isset($wizardStep['id']) ) {
          $node = Node::load($wizardStep['id']);
        }

        if ( $wizardStep['delete'] == true ) {
          if ( $node !== null ) {
            // If needed, remove this item as a child of the parent.
            // If the parent's data still lives in the tree, then it hasn't yet been
            // saved. If it doesn't, then it has been saved and the parent node will need to
            // be loaded and modified.
            if ( isset($tree[$wizardStep['parentStepId']] )) {
              $parent = $tree[$wizardStep['parentStepId']];
              $index = array_search($wizardStep['id'], $parent['children']);
              if ($index !== false) {
                unset($parent['children'][$index]);
              }
            } else {
              $parentNode = Node::load($wizardStep['parentStepId']);
              if ( $parentNode !== null ) {
                $parentChildSteps = $parentNode->get('field_wizard_step')->referencedEntities();
                $parentNewChildSteps = [];
                foreach ( $parentChildSteps as $parentChildStep ) {
                  if ( $parentChildStep->id() != $wizardStep['id'] ) {
                    $parentNewChildSteps[] = [
                      'target_id' => $parentChildStep->id()
                    ];
                  }
                  $parentNode->set('field_wizard_step', $parentNewChildSteps);
                  $parentNode->save();
                }
              }
            }

            // The parent has been updated, and the current node has been deleted.
            // If a step is deleted, all of its children should also be deleted.
            $toDelete = [];
            $childQueue = [$wizardStep['id']];
            while ( !empty($childQueue) ) {
              $currentNodeId = array_shift($childQueue);
              // If not already, set the current node id to be deleted.
              if ( !in_array($currentNodeId, $toDelete) ) {
                // Add the current node's child items from the tree to the delete queue.
                $toDelete[] = $currentNodeId;
                if ( isset($tree[$currentNodeId]) && isset($tree[$currentNodeId]['children']) ) {
                  foreach ($tree[$currentNodeId]['children'] as $childNodeId ) {
                    if ( !in_array($childNodeId, $toDelete) && !in_array($childNodeId, $childQueue) ) {
                      $childQueue[] = $childNodeId;
                    }
                  }
                }
                // Add the current node's child items from the database to the delete queue.
                $childNode = Node::load($currentNodeId);
                if ( $childNode !== null ) {
                  $childNodeIds = $childNode->get('field_wizard_step')->getValue();
                  foreach ( $childNodeIds as $childNodeId) {
                    $childNodeId = $childNodeId['target_id'];
                    if ( !in_array($childNodeId, $toDelete) && !in_array($childNodeId, $childQueue)) {
                      $childQueue[] = $childNodeId;
                    }
                  }
                }
              }
            }

            // Now that the node and all of its children (from the passed in tree data as well as Drupal)
            // are marked for deletion, delete them all and remove them from the tree data.
            foreach ( $toDelete as $toDeleteId ) {
              // Delete node and unset in tree.
              $toDeleteNode = Node::load($toDeleteId);
              if ( $toDeletenode !== null ) {
                $toDeleteNode->delete();
              }

              if ( isset($toDeleteId, $tree) ) {
                unset($tree[$toDeleteId]);
              }
            }
          }
        } else {
          $isNewNode = false;
          if ( $node === null ) {
            $isNewNode = true;
            if ( isset($wizardStep['parentStepId']) ) {
              $node = Node::create([
                'type' => 'wizard_step'
              ]);
            } else {
              $node = Node::create([
                'type' => 'wizard'
              ]);
            }
          }

          // TODO handle node creation/updating in separate protected function?
          $node->setTitle($wizardStep['title']);
          
          // TODO set correct format
          $node->set('body', [
            'value' => $wizardStep['body'],
            'format' => 'full_html'
          ]);

          $node->set('field_wizard_primary_utterance', $wizardStep['primaryUtterance'] ?? '');

          $node->set('field_wizard_aliases', $wizardStep['aliases'] ?? '');

          $node->setOwnerId(\Drupal::currentUser()->id());

          $fieldWizardStep = [];
          foreach ( $wizardStep['children'] as $childId ) {
            if ($childId > 0) {
              $fieldWizardStep[] = [
                'target_id' => $childId
              ];
            }
          }

          // TODO if not new node, compare new child step array with array 
          // from node and delete any children that aren't referenced by the new array.

          $node->set('field_wizard_step', $fieldWizardStep);
          
          // Save the node.
          $node->save();

          // TODO better way to handle this?
          // Currently, an ID of -1 means the step is a new step.
          // Because of this, the node must first be created so an ID is generated,
          // then the parent Node (or tree data) should be updated to point to this ID.

          $newId = $node->id();
          $parentStepId = $wizardStep['parentStepId'];
          if ( $isNewNode ) {
            // TODO insert into correct spot based on weight.
            if ( isset($tree[$parentStepId]) ) {
              // Check to make sure the node ID (e.g. possibly -1) is not already set in parent.
              // If it is, swap it out with the correct ID. If not, add it in.
              $index = array_search($wizardStep['id'], $tree[$parentStepId]['children']);
              if ($index !== false) {
                $tree[$parentStepId]['children'][$index] = $newId;
              } else {
                $tree[$parentStepId]['children'][] = $newId;
              }
            } else {
              // If the parent isn't in the tree, the node must be loaded,
              // the new ID added as a child step, then the node must be saved.
              $parentNode = Node::load($parentStepId);
              if ( $parentNode != null ) {
                $newChildren = [];
                $referencedEntities = $parentNode->get('field_wizard_step')->referencedEntities();
                foreach ( $referencedEntities as $referencedEntity ) {
                  $newChildren[] = [
                    'target_id' => $referencedEntity->id()
                  ];
                }
                $newChildren[] = $newId;
                $parentNode->set('field_wizard_step', $newChildren);
                $parentNode->save();
              }
            }
          }

          unset($tree[$wizardStep['id']]);
        }
      }
    }
  }

  /**
   * Recursively saves each step of the wizard tree provided.
   * 
   * @param array $wizardStep
   *   The data for the current wizard step that needs to be saved. 
   * @param Node $parent
   *   The parent Node of the current wizard step being saved.
   */
  private function saveWizardStep( array $wizardStep, Node $parent = null ) : void {
    // TODO validate step
    // TODO validate user permissions

    if ($wizardStep) {
      // If the id is set, try to load it.
      if ($wizardStep['id']) {
        $node = Node::load($wizardStep['id']);
      }
      if ( $wizardStep['delete'] === true ) {
        foreach ( $wizardStep['children'] as $childData ) {
          $childData['delete'] = true;
          $this->saveWizardStep( $childData, $node );
        }

        if ( $node ) {
          $node->delete();
        }
      } else {
        // If it failed to load or isn't set, then create a new node.
        // TODO type depends on parent? i.e. no parent means 'wizard'
        // type, parent means 'wizard_step' type?
        if (!$node) {
          $node = Node::create([
            'type' => 'wizard_step'
          ]);
        }

        $node->setTitle($wizardStep['title']);

        $node->set('body', [
          'value' => $wizardStep['body'],
          'format' => 'full_html'
        ]);

        $node->set('field_wizard_primary_utterance', $wizardStep['primaryUtterance'] ?? '');

        $node->set('field_wizard_aliases', $wizardStep['aliases'] ?? '');

        $node->setOwnerId(\Drupal::currentUser()->id());

        $node->set('field_wizard_step', []);
        
        // Save the node.
        $node->save();

        foreach ($wizardStep['children'] as $childData) {
          $this->saveWizardStep( $childData, $node );
        }

        if ($parent) {
          // TODO set children here.
          $currentChildren = $parent->get('field_wizard_step')->referencedEntities();
          $currentChildren[] = [
            'target_id' => $node->id()
          ];
          $parent->set('field_wizard_step', $currentChildren);
          $parent->save();
        }
      }
    }

  }

  /**
   * Get all Wizard content as Nodes.
   * 
   * @return array
   */
  public function getAllWizards() : array {
    $availableWizards = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'wizard')
        ->accessCheck(TRUE)
        ->execute();
    if ( !empty($availableWizards) ) {
        return Node::loadMultiple($availableWizards);
    }
    return [];
  }

  /**
   * Helper function to return a value for a node.
   * 
   * @param Node $obj
   *   The Node to load the value from.
   * @param string $fieldName
   *   The field to try to load.
   * 
   * @return mixed
   *   The field's value if it exists, otherwise an empty string.
   */
  private function getFieldValue( Node $obj, string $fieldName ) : mixed {
    return $obj->hasField($fieldName) ? ($obj->get($fieldName)?->value ?? '') : '';
  }

  /**
   * Determines whether a given node is a valid wizard tree node.
   * E.g. is it a Wizard or Wizard Step node.
   * 
   * @param Node|null $node
   *   The node to be checked.
   * 
   * @return bool
   *   true if this node can be part of a wizard tree, otherwise false
   */
  private function isValidTreeNode( Node|null $node ) : bool {
    if ( $node !== null ) {
      if ( $node->bundle() === "wizard" || $node->bundle() === "wizard_step" ) {
        return true;
      }
    }

    return false;
  }

  private function getAvailableLanguages() {
    $availableLanguages = array_map(function($el) {
      return [
        'name' => $el->getName(),
        'id' => $el->getId(),
        'weight' => $el->getWeight(),
      ];
    }, \Drupal::languageManager()->getNativeLanguages());
    return $availableLanguages;
  }

}