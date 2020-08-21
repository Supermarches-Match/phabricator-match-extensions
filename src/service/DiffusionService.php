<?php

class DiffusionService extends Phobject {

  /**
   * PhrictionService constructor.
   */
  public function __construct() {
  }

  /**
   * @param string $spacePHID
   * @return array
   */
  public function getRepositoriesForSpace(string $spacePHID): array {
    try {
      $repository_query = id(new PhabricatorRepositoryQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withSpacePHIDs(array($spacePHID))
        ->execute();

      return $repository_query;
    } catch (Exception $e) {
      phlog($e);
      return array();
    }
  }

  /**
   * @param PhabricatorPolicyInterface $repository
   * @return array
   */
  public function getPolicies(PhabricatorPolicyInterface $repository): array {
    try {
      $policies = PhabricatorPolicyQuery::loadPolicies(PhabricatorUser::getOmnipotentUser(), $repository);
      if ($policies != null) {
        return $policies;
      }
      return array();
    } catch (Exception $e) {
      phlog($e);
      return array();
    }
  }
}
