<?php

final class TotalPointUnusedManiphestCustomField extends ManiphestCustomField {

  //Core Properties and Field Identity
  public function getFieldKey() {
    return 'match:TotalPointUnused';
  }

  public function getFieldName() {
    return pht('Total Point Unused');
  }

  public function getFieldDescription() {
    return pht('Display the total of the unused charge');
  }

  public function canDisableField() {
    return true;
  }

  public function getFieldValue() {
    $estimatedPoints = $this->getObject()->getPoints();;
    $usedPoints = null;

    $fields = PhabricatorCustomField::getObjectFields($this->getObject(), PhabricatorCustomField::ROLE_TASKCARD);
    if ($fields) {
      $fields->setViewer($this->getViewer());
      $fields->readFieldsFromStorage($this->getObject());

      foreach ($fields->getFields() as $field) {
        if ($field->getFieldKey() == 'std:maniphest:match:points_consomme') {
          $usedPoints = $field->getProxy()->getFieldValue();
        } else if ($usedPoints === null && $field->getFieldKey() == 'match:childTotalPointUsed') {
          $usedPoints = $field->getFieldValue();
        } else if ($estimatedPoints === null && $field->getFieldKey() == 'match:childTotalPoint') {
          $estimatedPoints = $field->getFieldValue();
        }
      }
    }

    if ($estimatedPoints === null) {
      return null;
    }

    if ($usedPoints === null) {
      return $estimatedPoints;
    }
    return $estimatedPoints - $usedPoints;
  }
  //Core Properties and Field Identity

  //Integration with Property Views
  public function shouldAppearInPropertyView() {
    return false;
  }

  public function renderPropertyViewLabel() {
    return pht("Unused charge");
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
    return $this->getIsEnabled();
  }

  public function renderTaskCardValue() {
    $value = $this->getFieldValue();
    if (!strlen($value)) {
      return null;
    }
    $color = PHUITagView::COLOR_YELLOW;
    if ($value <= 0) {
      $color = PHUITagView::COLOR_RED;
    }

    return id(new PHUITagView())
      ->setType(PHUITagView::TYPE_SHADE)
      ->setColor($color)
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

    $color = PHUITagView::COLOR_YELLOW;
    if ($value <= 0) {
      $color = PHUITagView::COLOR_RED;
    }

    $label = pht('%s %s',
      $value,
      $this->renderPropertyViewLabel());

    return id(new PHUITagView())
      ->setType(PHUITagView::TYPE_SHADE)
      ->setColor($color)
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