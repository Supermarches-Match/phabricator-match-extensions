<?php


class MatchPhutilHTTPEngineExtension extends PhutilHTTPEngineExtension {

  const EXTENSIONKEY = 'match.https.proxy';

  public function getExtensionName() {
    return pht('Match proxy url');
  }

  /**
   * Return a @{class:PhutilURI} to proxy requests.
   *
   * If some or all outbound HTTP requests must be proxied, you can return
   * the URI for a proxy to use from this method.
   *
   * @return null|PhutilURI Proxy URI.
   * @task config
   * @throws Exception
   */
  public function getHTTPProxyURI(PhutilURI $uri) {
    $httpsProxy = PhabricatorEnv::getEnvConfig('match.https.proxy');
    $httpProxy = PhabricatorEnv::getEnvConfig('match.http.proxy');
    $noProxy = PhabricatorEnv::getEnvConfig('match.no.proxy');

    if(in_array($uri->getDomain(), $noProxy)){
      return null;
    }

    if ($httpsProxy !== null && $uri->getProtocol() === 'https') {
      return new PhutilURI($httpsProxy);
    } else if ($httpProxy !== null && $uri->getProtocol() === 'http') {
      return new PhutilURI($httpProxy);
    }
    return null;
  }
}