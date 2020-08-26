<?php

class UserService extends Phobject {

  /**
   * UserService constructor.
   */
  public function __construct() {
  }

  /**
   * @param string $name
   * @return PhabricatorProject|null
   */
  public function getUserByName(string $name): ?PhabricatorUser {
    return id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUsernames(array($name))
      ->executeOne();
  }

  public function createBotUser(string $name): PhabricatorUser {
    try {
      $user = id(new PhabricatorUser())
        ->setUsername($name)
        ->setRealName($name)
        ->setIsApproved(1);

      $email = id(new PhabricatorUserEmail())
        ->setAddress(pht("%s@supermarchexmatch.com", $name))
        ->setIsVerified(1);

      id(new PhabricatorUserEditor())
        ->setActor(PhabricatorUser::getOmnipotentUser())
        ->createNewUser($user, $email);

      id(new PhabricatorUserEditor())
        ->setActor(PhabricatorUser::getOmnipotentUser())
        ->makeSystemAgentUser($user, true);
      return $user;
    } catch (Exception $ex) {
      echo pht(
          'Failed to create robot user: "%s": %s.',
          $name,
          $ex->getMessage())."\n";
      die;
    }
  }
}



