<?php

namespace Drupal\usagov_wizards\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
final class WizardsCommands extends DrushCommands {

  /**
   * Constructs a WizardsCommands object.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'usagov_wizards:test')]
  #[CLI\Usage(name: 'usagov_wizards:test', description: 'run some testing code')]
  public function test() {
    $this->logger()->success("Test ran successfully.");
  }

}
