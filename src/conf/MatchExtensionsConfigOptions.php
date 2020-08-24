<?php


class MatchExtensionsConfigOptions extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Match Extensions');
  }

  public function getDescription() {
    return pht('Configuration for Match Extensions.');
  }

  public function getFontIcon() {
    return 'fa-opencart';
  }

  public function getGroup() {
    return 'apps';
  }

  public function getOptions() {
    return array(
      $this->newOption('match.lotus-uri', 'string', null)
        ->setSummary(pht('Lotus Server url.'))
        ->setDescription(
          pht(
            'Set the URI where Lotus server is accessible.'))
        ->addExample('plam00048', pht('Valid Setting')),
      $this->newOption('match.kroki-uri', 'string', null)
        ->setSummary(pht('Kroki API url.'))
        ->setDescription(
          pht(
            'Set the URI where kroki api server is accessible.'))
        ->addExample('https://kroki.io/', pht('Valid Setting')
        ->setDefault('https://kroki.io/')),
    );
  }
}