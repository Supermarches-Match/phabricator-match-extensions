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
        ->setDescription(pht('Set the URI where Lotus server is accessible.'))
        ->addExample('plam00048', pht('Valid Setting')),
      $this->newOption('match.kroki-uri', 'string', 'https://kroki.io/')
        ->setSummary(pht('Kroki API url.'))
        ->setDescription(pht('Set the URI where kroki api server is accessible.'))
        ->addExample('https://kroki.io/', pht('Valid Setting')),
      $this->newOption('match.http.proxy', 'string', null)
        ->setSummary(pht('Http proxy.'))
        ->setDescription(pht('Set the URL of the http proxy'))
        ->addExample('http://proxy.match.com/', pht('Valid Setting')),
      $this->newOption('match.https.proxy', 'string', null)
        ->setSummary(pht('Https proxy.'))
        ->setDescription(pht('Set the URL of the https proxy'))
        ->addExample('https://proxy.match.com/', pht('Valid Setting')),
      $this->newOption('match.no.proxy', 'list<string>', array())
        ->setDescription(pht('Set the URL for by pass proxy'))
        ->addExample(
          array(
            'localhost',
            '127.0.0.1',
          ),
          pht('Url wich by passs proxy')),
    );
  }
}