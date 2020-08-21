#!/usr/bin/env php
<?php

$root = dirname(dirname(__FILE__));

require_once $root.'/scripts/init/init-script.php';
init_script();

$args = new PhutilArgumentParser($argv);
$args->setTagline('Update policies');
$args->setSynopsis(<<<EOSYNOPSIS
    **update_policies.php** __command__ [options]

    Update policies of all type.
EOSYNOPSIS
);
$args->parseStandardArguments();

$workflows = id(new PhutilClassMapQuery())
  ->setAncestorClass('UpdatePolicies')
  ->execute();
$workflows[] = new PhutilHelpArgumentWorkflow();
$args->parseWorkflows($workflows);
