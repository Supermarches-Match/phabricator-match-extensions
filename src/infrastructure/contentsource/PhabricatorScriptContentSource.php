<?php

final class PhabricatorScriptContentSource
  extends PhabricatorContentSource {

  const SOURCECONST = 'script';

  public function getSourceName() {
    return pht('Script');
  }

  public function getSourceDescription() {
    return pht('Content created from script.');
  }

}
