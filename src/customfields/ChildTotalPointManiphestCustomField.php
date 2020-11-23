<?php

final class ChildTotalPointManiphestCustomField extends ManiphestCustomField {

  //Core Properties and Field Identity
  public function getFieldKey() {
    return 'match:childTotalPoint';
  }

  public function getFieldName() {
    return pht('Total Point of Child');
  }

  public function getFieldDescription() {
    return pht('Display the total of the estimation charge');
  }

  public function canDisableField() {
    return true;
  }

  public function getFieldValue() {
    $edge_type = ManiphestTaskDependsOnTaskEdgeType::EDGECONST;

    $graph = id(new PhabricatorEdgeGraph())
      ->setEdgeType($edge_type)
      ->addNodes(
        array(
          '<seed>' => array($this->getObject()->getPHID()),
        ))
      ->loadGraph();

    $nodes = $graph->getNodes();
    unset($nodes['<seed>']);

    if (count($nodes) == 1) {
      return null;
    }

    $phids = array_keys($nodes);
    $tasks = id(new ManiphestTaskQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($phids)
      ->execute();

    $total = null;

    foreach ($tasks as $task) {
      $points = $task->getPoints();
      if ($points != null) {
        $total += $points;
      }
    }

    if ($total === null) {
      return null;
    }
    return $total;
  }
  //Core Properties and Field Identity

  //Integration with Property Views
  public function shouldAppearInPropertyView() {
    return false;
  }

  public function renderPropertyViewLabel() {
    return pht("Total %s", $this->getPointsLabel());
  }

  public function renderPropertyViewValue(array $handles) {
    $value = $this->getFieldValue();
    if ($value == null) {
      return null;
    }
    return phutil_tag('span', array(), $value);
  }

  public function getIconForPropertyView() {
    return 'fa-battery-full';
  }
  //Integration with Property Views


  //Integration with BoardTaskCard
  public function shouldAppearInTaskCard() {
    return $this->getIsEnabled();;
  }

  public function renderTaskCardValue() {
    $value = $this->getFieldValue();
    if (!strlen($value)) {
      return null;
    }

    return id(new PHUITagView())
      ->setType(PHUITagView::TYPE_SHADE)
      ->setColor(PHUITagView::COLOR_GREY)
      ->setSlimShady(true)
      ->setName($value)
      ->addClass('phui-workcard-points');
  }
  //Integration with BoardTaskCard

  //Integration with BoardTaskCard
  public function shouldAppearInTaskHeader() {
    return $this->getIsEnabled();;
  }

  public function renderTaskHeaderValue() {
    $value = $this->getFieldValue();
    if (!strlen($value)) {
      return null;
    }

    $label = pht('%s %s',
      $value,
      $this->renderPropertyViewLabel());

    return id(new PHUITagView())
      ->setType(PHUITagView::TYPE_SHADE)
      ->setColor(PHUITagView::COLOR_BLUE)
      ->setName($label);
  }
  //Integration with BoardTaskCard


  // -------------- Utils ----------------
  public static function getIsEnabled() {
    $config = self::getPointsConfig();
    return idx($config, 'enabled');
  }

  public static function getPointsLabel() {
    $config = self::getPointsConfig();
    return idx($config, 'label', pht('Points'));
  }

  public static function getPointsActionLabel() {
    $config = self::getPointsConfig();
    return idx($config, 'action', pht('Change Points'));
  }

  private static function getPointsConfig() {
    return PhabricatorEnv::getEnvConfig('maniphest.points');
  }

  public static function validateConfiguration($config) {
    if (!is_array($config)) {
      throw new Exception(
        pht(
          'Configuration is not valid. Maniphest points configuration must '.
          'be a dictionary.'));
    }

    PhutilTypeSpec::checkMap(
      $config,
      array(
        'enabled' => 'optional bool',
        'label' => 'optional string',
        'action' => 'optional string',
      ));
  }
}