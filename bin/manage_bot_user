#!/usr/bin/env php
<?php

$root = dirname(dirname(__FILE__));

require_once $root.'/scripts/init/init-script.php';
init_script();

$args = new PhutilArgumentParser($argv);
$args->setTagline('Create bot user');
$args->setSynopsis(<<<EOSYNOPSIS
    **create_bot_user.php** __command__ [options]

    Create bot user.
EOSYNOPSIS
);
$args->parseStandardArguments();

$workflows = id(new PhutilClassMapQuery())
  ->setAncestorClass('ManageBotUser')
  ->execute();
$workflows[] = new PhutilHelpArgumentWorkflow();
$args->parseWorkflows($workflows);
