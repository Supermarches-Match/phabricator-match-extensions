<?php

final class PhabricatorUserManagementDaemon extends PhabricatorDaemon {

  private $usersProcessed = array();
  private $userService;

  protected function run() {
    $this->setUserService();

    do {
      PhabricatorCaches::destroyRequestCache();

      $lock = PhabricatorGlobalLock::newLock('UserManagement');

      try {
        $lock->lock(5);
      } catch (PhutilLockException $ex) {
        throw new PhutilProxyException(
          pht(
            'Another process is holding the UserManagement lock. Usually, this '.
            'means another copy of the user management daemon is running elsewhere. '.
            'Multiple processes are not permitted to update users '.
            'simultaneously.'),
          $ex);
      }

      $this->log(pht('Retrieving provider config'));

      $configs = id(new PhabricatorAuthProviderConfigQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withIsEnabled(true)
        ->execute();
      $configs = msortv($configs, 'getSortVector');

      foreach ($configs as $config) {
        $provider = $config->getProvider();
        $this->log(pht('Provider: %s', $provider->getProviderName()));

        if (!$provider->shouldAllowAccountLink()) {
          continue;
        }

        if ($provider instanceof PhabricatorLDAPAuthProvider) {
          $this->log(pht('Process creating user with LDAP'));

          $conf = $provider->getProviderConfig();
          if ($conf === null) {
            continue;
          }

          $this->log(pht('Connecting...'));
          $host = $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_HOSTNAME);
          $port = $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_PORT);
          $conn = @ldap_connect($host, $port);

          if (!$conn) {
            $this->log(pht('Error Connecting'));
          } else {
            $options = array(
              LDAP_OPT_PROTOCOL_VERSION => (int)$conf->getProperty(PhabricatorLDAPAuthProvider::KEY_VERSION),
              LDAP_OPT_REFERRALS => (int)$conf->getProperty(PhabricatorLDAPAuthProvider::KEY_REFERRALS),
            );

            foreach ($options as $name => $value) {
              $ok = @ldap_set_option($conn, $name, $value);
              if (!$ok) {
                phlog(pht("Unable to set LDAP option '%s' to value '%s'!", $name, $value), $conn);
              }
            }

            if ($conf->getProperty(PhabricatorLDAPAuthProvider::KEY_START_TLS)) {
              // NOTE: This boils down to a function call to ldap_start_tls_s() in
              // C, which is a service call.
              $ok = @ldap_start_tls($conn);

              if (!$ok) {
                phlog(pht('Unable to start TLS connection when connecting to LDAP.'), $conn);
              }
            }

            $this->log(pht('linking...'));
            $user = $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_ANONYMOUS_USERNAME);
            $pass = new PhutilOpaqueEnvelope($conf->getProperty(PhabricatorLDAPAuthProvider::KEY_ANONYMOUS_PASSWORD));
            $ldapBind = @ldap_bind($conn, $user, $pass->openEnvelope());

            // Vérification de l'authentification
            if ($ldapBind) {
              $this->log(pht("Connexion LDAP successful"));
            } else {
              $this->log(pht("Connexion LDAP failed"));
              continue;
            }

            $searchExpression = str_replace("\${login}", "*", $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_SEARCH_ATTRIBUTES));
            $search = ldap_search($conn, $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_DISTINGUISHED_NAME), $searchExpression);
            $this->log(pht('There are %s users authorised', @ldap_count_entries($conn, $search)));

            $entries = @ldap_get_entries($conn, $search);
            $this->log(pht("Listing users"));

            $login = strtolower($conf->getProperty(PhabricatorLDAPAuthProvider::KEY_USERNAME_ATTRIBUTE));
            $realNames = array_map('strtolower', $conf->getProperty(PhabricatorLDAPAuthProvider::KEY_REALNAME_ATTRIBUTES));

            for ($i = 0; $i < $entries["count"]; $i++) {
              $username = $this->readLDAPData($entries[$i], $login);
              $email = $this->readLDAPData($entries[$i], "mail");

              if (StringUtils::isEmpty($username) || StringUtils::isEmpty($email) || in_array($email, $this->usersProcessed)) {
                continue;
              }

              $user = $this->userService->getUserByMail($email);
              if (!$user) {
                $realName = "";
                foreach ($realNames as $name) {
                  $realName .= $this->readLDAPData($entries[$i], $name)." ";
                }
                $this->log(pht("Create user : %s", $username));
                $user = $this->userService->createUser($username, trim($realName), $email, true);
              }

              $accounts = id(new PhabricatorExternalAccountQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withUserPHIDs(array($user->getPHID()))
                ->withProviderConfigPHIDs(array($provider->getProviderConfig()->getPHID()))
                ->execute();

              if (!$accounts) {
                $this->log(pht("Linking user with provider : %s - %s", $username, $provider->getProviderName()));
                $this->userService->linkUserWithProvider($provider, $user);
              }

              array_push($this->usersProcessed, $email);
            }

            @ldap_close($conn);
            $this->log(pht('Disconnected'));
          }
        }
      }

      $lock->unlock();

      $sleep_duration = phutil_units('60 minutes in seconds');
      if ($this->shouldHibernate($sleep_duration)) {
        break;
      }

      $this->sleep($sleep_duration);
    } while (!$this->shouldExit());
  }

  private function readLDAPData(array $data, $key, $default = null) {
    $list = idx($data, $key);
    if ($list === null) {
      // At least in some cases (and maybe in all cases) the results from
      // ldap_search() are keyed in lowercase. If we missed on the first
      // try, retry with a lowercase key.
      $list = idx($data, phutil_utf8_strtolower($key));
    }

    // NOTE: In most cases, the property is an array, like:
    //
    //   array(
    //     'count' => 1,
    //     0 => 'actual-value-we-want',
    //   )
    //
    // However, in at least the case of 'dn', the property is a bare string.

    if (is_scalar($list) && strlen($list)) {
      return $list;
    } else if (is_array($list)) {
      return $list[0];
    } else {
      return $default;
    }
  }

  /**
   * @param mixed $userService
   */
  public function setUserService(): void {
    if ($this->userService === null) {
      $this->userService = new UserService();
    }
  }
}