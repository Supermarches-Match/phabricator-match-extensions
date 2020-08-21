<?php

function init_script(array $options = array()) {
  error_reporting(E_ALL | E_STRICT);
  ini_set('display_errors', 1);

  $rootdir = dirname(dirname(dirname(dirname(__FILE__))));

  $include_path = ini_get('include_path');
  ini_set(
    'include_path',
    $include_path.PATH_SEPARATOR.$rootdir);


  $ok = @include_once 'arcanist/support/init/init-script.php';
  if (!$ok) {
    echo
      'FATAL ERROR: Unable to load the "Arcanist" library. '.
      'Put "arcanist/" next to "phabricator/" on disk.';
    echo "\n";

    exit(1);
  }

  $root = dirname(dirname(dirname(__FILE__)));
  phutil_load_library($root);
  phutil_load_library('arcanist/src');
  phutil_load_library('phabricator/src');

  PhabricatorEnv::initializeScriptEnvironment(false);
}
