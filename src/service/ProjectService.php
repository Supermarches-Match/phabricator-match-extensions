<?php

class ProjectService extends Phobject {

  /**
   * PhrictionService constructor.
   */
  public function __construct() {
  }

  /**
   * @param string $name
   * @return PhabricatorProject|null
   */
  public function getGroupByName(string $name): ?PhabricatorProject {
    $groups = id(new PhabricatorProjectQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withNames(array($name))
      ->withIcons(array('group'))
      ->execute();

    if ($groups !== null && count($groups) > 0) {
      return reset($groups);
    }
    return null;
  }

  /**
   * @param string $phid
   * @return PhabricatorProject|null
   */
  public function getGroupByPhid(string $phid): ?PhabricatorProject {
    $groups = id(new PhabricatorProjectQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($phid))
      ->withIcons(array('group'))
      ->execute();

    if ($groups !== null && count($groups) > 0) {
      return reset($groups);
    }
    return null;
  }
}