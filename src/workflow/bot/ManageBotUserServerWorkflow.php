<?php

final class ManageBotUserServerWorkflow
  extends ManageBotUser {

  private $projectService;
  private $userService;

  protected function didConstruct() {
    $this
      ->setName('server')
      ->setExamples('**server** [options]')
      ->setSynopsis(pht('Create Robot for server'))
      ->setArguments(
        array(
          array(
            'name' => 'name-format',
            'param' => 'text',
            'help' => pht('Use to define name format'),
            'default' => 'Match-Serveur-%s',
          ),
          array(
            'name' => 'robot-names',
            'param' => 'array',
            'help' => pht('List the robot name used with format to create user. Name separated by coma'),
          ),
          array(
            'name' => 'group-name',
            'param' => 'name',
            'help' => pht('Group of user apply to the new server bot'),
          ),
          array(
            'name' => 'ssh-directory',
            'param' => 'path',
            'help' => pht('Write output to a file. If omitted, no ssh will be generate'),
          ),
        ))
      ->setProjectService(new ProjectService())
      ->setUserService(new UserService());
  }

  public function execute(PhutilArgumentParser $args) {
    $nameFormat = $args->getArg('name-format');
    $groupName = $args->getArg('group-name');
    $directory = $args->getArg('ssh-directory');

    $start = new DateTimeImmutable();
    echo pht("Started at: %s\n", $start->format("Y-m-d H:i:s"));

    ScriptUtils::separator();

    $userAdmin = $this->userService->getUserByName('Match-Script-Admin');

    $robotNames = $args->getArg('robot-names');
    $names = preg_split("/[\s,]+/", $robotNames);

    foreach ($names as $name) {
      $finalName = '';

      if ($nameFormat !== '') {
        $finalName = pht($nameFormat, $name);
      }
      echo pht("Create user bot %s\n", $finalName);

      $user = $this->userService->getUserByName($finalName);
      if ($user !== null) {
        echo pht("User bot %s already exists\n", $finalName);
      } else {
        $user = $this->userService->createBotUser($finalName);
      }

      if ($groupName !== '') {
        $group = $this->projectService->getGroupByName($groupName);
        if ($group !== null) {
          if ($group->isUserMember($user->getPHID())) {
            echo pht("User bot %s already member of group %s\n", $finalName, $groupName);
          } else {
            $this->projectService->joinProject($group, $user, $userAdmin);
            echo pht("User bot %s added to group %s\n", $finalName, $groupName);
          }
        }
      }

      if ($directory !== null && $directory !== '') {
        $key = $this->newKeyForObjectPHID($user, $userAdmin);
        if ($key === null) {
          echo pht("User bot %s cannot have ssh key\n", $finalName);
          die;
        }

        $default_name = $key->getObject()->getSSHKeyDefaultName()."_".$name;

        $keys = PhabricatorSSHKeyGenerator::generateKeypair();
        list($public_key, $private_key) = $keys;

        $key_name = $default_name.'.key';

        $this->storePublicKey($public_key, $default_name, $userAdmin, $key);
        $this->downloadPrivateKey($directory, $key_name, $private_key);
      }

      ScriptUtils::separator();
    }


    ScriptUtils::separator();
    $end = new DateTimeImmutable();
    $execution = $end->diff($start);
    echo pht("Finished at: %s\n", $end->format("Y-m-d H:i:s"));
    echo pht("Executed in: %s\n", $execution->format("%ss %Imin %Hh"));
    return 0;
  }

  protected function newKeyForObjectPHID(PhabricatorUser $user, PhabricatorUser $userAdmin) {
    $object = id(new PhabricatorObjectQuery())
      ->setViewer($userAdmin)
      ->withPHIDs(array($user->getPHID()))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();

    if (!$object) {
      return null;
    }

    // If this kind of object can't have SSH keys, don't let the viewer
    // add them.
    if (!($object instanceof PhabricatorSSHPublicKeyInterface)) {
      return null;
    }

    return PhabricatorAuthSSHKey::initializeNewSSHKey($userAdmin, $object);
  }


  /**
   * @param ProjectService $projectService
   * @return ManageBotUserServerWorkflow
   */
  public function setProjectService(ProjectService $projectService): ManageBotUserServerWorkflow {
    $this->projectService = $projectService;
    return $this;
  }

  /**
   * @param UserService $userService
   * @return ManageBotUserServerWorkflow
   */
  public function setUserService(UserService $userService): ManageBotUserServerWorkflow {
    $this->userService = $userService;
    return $this;
  }

  /**
   * @param        $public_key
   * @param string $default_name
   * @param        $userAdmin
   * @param        $key
   * @throws Exception
   */
  private function storePublicKey($public_key, string $default_name, $userAdmin, $key): void {
    $public_key = PhabricatorAuthSSHPublicKey::newFromRawKey($public_key);

    $type = $public_key->getType();
    $body = $public_key->getBody();
    $comment = pht('Generated');

    $entire_key = "{$type} {$body} {$comment}";

    $xactions = array();

    $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);

    $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
      ->setTransactionType(PhabricatorAuthSSHKeyTransaction::TYPE_NAME)
      ->setNewValue($default_name);

    $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
      ->setTransactionType(PhabricatorAuthSSHKeyTransaction::TYPE_KEY)
      ->setNewValue($entire_key);

    id(new PhabricatorAuthSSHKeyEditor())
      ->setActor($userAdmin)
      ->setContentSource(PhabricatorContentSource::newForSource(PhabricatorScriptContentSource::SOURCECONST))
      ->setContinueOnNoEffect(true)
      ->setIsAdministrativeEdit(true)
      ->setContinueOnMissingFields(true)
      ->applyTransactions($key, $xactions);
  }

  /**
   * @param string $directory
   * @param string $key_name
   * @param        $private_key
   */
  private function downloadPrivateKey(string $directory, string $key_name, $private_key): void {
    file_put_contents($directory.DIRECTORY_SEPARATOR.$key_name, $private_key);
  }
}
