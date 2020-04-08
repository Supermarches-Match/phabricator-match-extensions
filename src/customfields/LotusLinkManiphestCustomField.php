<?php


final class LotusLinkManiphestCustomField extends ManiphestCustomField {
  const FORMAT = '{"code":"CSP1123456XYZ", "url":"/folder/Maintenance.nsf/0/123456789001234567890123456789"}';
  const LOTUS_ID_KEY = 'code';
  const LOTUS_URL_KEY = 'url';

  private $value;
  private $fieldError;

  public function setValue($value) {
    $this->value = $value;
    return $this;
  }

  public function getValue() {
    return $this->value;
  }

  public function setFieldError($field_error) {
    $this->fieldError = $field_error;
    return $this;
  }

  public function getFieldError() {
    return $this->fieldError;
  }

  //Core Properties and Field Identity
  public function getFieldKey() {
    return 'match:lotusLink';
  }

  public function getFieldName() {
    return pht('Lotus Link');
  }

  public function getFieldDescription() {
    return pht('Lotus link define when "demande" is created on lotus');
  }

  public function canDisableField() {
    return false;
  }
  //Core Properties and Field Identit

  //Field Storage
  public function shouldUseStorage() {
    return true;
  }

  public function getValueForStorage() {
    return $this->getValue();
  }

  public function setValueFromStorage($value) {
    $this->setValue($value);
    return $this;
  }
  //Field Storage

  //Integration with Edit Views
  public function shouldAppearInEditView() {
    return true;
  }

  public function shouldAppearInEditEngine() {
    return true;
  }

  public function renderEditControl($handles) {
    return id(new AphrontFormTextControl())
      ->setLabel($this->getFieldName())
      ->setCaption(
        pht('Example: %s', phutil_tag('tt', array(), self::FORMAT)))
      ->setName($this->getFieldKey())
      ->setValue($this->getValue());
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $value = $request->getStr($this->getFieldKey());
    if (!strlen($value)) {
      $value = null;
    }
    $this->setValue($value);
  }
  //Integration with Edit Views

  //Integration with Property Views
  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewLabel() {
    return $this->getFieldName();
  }

  public function renderPropertyViewValue(array $handles) {
    if ($this->getValue() === null) {
      return null;
    }
    $val = json_decode($this->getValue(), true);
    if ($val[self::LOTUS_ID_KEY] === null || trim($val[self::LOTUS_ID_KEY]) === "" || $val[self::LOTUS_URL_KEY] === null || trim($val[self::LOTUS_URL_KEY]) === "") {
      return null;
    }

    return phutil_tag(
      'a',
      array(
        //TODO add server in conf
        'href' => 'notes://PLAM0078/'.$val[self::LOTUS_URL_KEY].'?opendocument'
      ),
      pht(trim($val[self::LOTUS_ID_KEY])));
  }
  //Integration with Property Views

  //Integration with ApplicationTransactions
  public function shouldAppearInApplicationTransactions() {
    return true;
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getValue();
  }

  public function getNewValueForApplicationTransactions() {
    return $this->getValue();
  }

  public function validateApplicationTransactions(
    PhabricatorApplicationTransactionEditor $editor,
    $type,
    array $xactions) {

    $this->setFieldError(null);

    $errors = array();

    foreach ($xactions as $xaction) {
      $value = $xaction->getNewValue();
      if (strlen($value)) {
        $val = json_decode($value, true);
        if ($val[self::LOTUS_ID_KEY] === null || trim($val[self::LOTUS_ID_KEY]) === "" || $val[self::LOTUS_URL_KEY] === null || trim($val[self::LOTUS_URL_KEY]) === "") {

          $error = new PhabricatorApplicationTransactionValidationError(
            $type,
            pht('Invalid'),
            pht('%s : The format must be %s', $this->getFieldName(), self::FORMAT),
            $xaction);
          $errors[] = $error;
          $this->setFieldError(pht('Invalid'));
        }
        break;
      }
    }
    return $errors;
  }
//Integration with ApplicationTransactions

//Integration with ApplicationSearch
//Integration with ApplicationSearch

//conduit
  public
  function shouldAppearInConduitDictionary() {
    return true;
  }

  public
  function getModernFieldKey() {
    return 'match.lotusLinks';
  }

  public
  function getConduitDictionaryValue() {
    return $this->getValue();
  }
//conduit
}