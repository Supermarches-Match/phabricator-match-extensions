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
    return id(new PhabricatorProjectQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withNames(array($name))
      ->withIcons(array('group'))
      ->needMembers(true)
      ->executeOne();
  }

  /**
   * @param string $phid
   * @return PhabricatorProject|null
   */
  public function getGroupByPhid(string $phid): ?PhabricatorProject {
    return id(new PhabricatorProjectQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($phid))
      ->withIcons(array('group'))
      ->executeOne();
  }

  public function joinProject(PhabricatorProject $project, PhabricatorUser $user, PhabricatorUser $userAdmin): void {
    $this->joinOrLeaveProject($project, $user, $userAdmin, '+');
  }

  public function leaveProject(PhabricatorProject $project, PhabricatorUser $user, PhabricatorUser $userAdmin): void {
    $this->joinOrLeaveProject($project, $user, $userAdmin, '-');
  }

  private function joinOrLeaveProject(PhabricatorProject $project, PhabricatorUser $user, PhabricatorUser $userAdmin, $operation): void {
    $spec = array(
      $operation => array($user->getPHID() => $user->getPHID()),
    );

    $xactions = array();
    $xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setMetadataValue('edge:type', PhabricatorProjectProjectHasMemberEdgeType::EDGECONST)
      ->setNewValue($spec);

    id(new PhabricatorProjectTransactionEditor())
      ->setActor($userAdmin)
      ->setContentSource(PhabricatorContentSource::newForSource(PhabricatorScriptContentSource::SOURCECONST))
      ->setContinueOnNoEffect(true)
      ->applyTransactions($project, $xactions);
  }
}