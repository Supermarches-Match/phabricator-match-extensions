<?php

class UserService extends Phobject {

  /**
   * UserService constructor.
   */
  public function __construct() {
  }

  /**
   * @param string $name
   * @return PhabricatorProject|null
   */
  public function getUserByName(string $name): ?PhabricatorUser {
    return id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUsernames(array($name))
      ->executeOne();
  }

  /**
   * @param string $mail
   * @return PhabricatorUser|null
   */
  public function getUserByMail(string $mail): ?PhabricatorUser {
    return id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withEmails(array($mail))
      ->executeOne();
  }

  public function createBotUser(string $name): PhabricatorUser {
    try {
      $user = $this->newUser($name, $name, pht("%s@supermarchexmatch.com", $name));

      id(new PhabricatorUserEditor())
        ->setActor(PhabricatorUser::getOmnipotentUser())
        ->makeSystemAgentUser($user, true);

      return $user;
    } catch (Exception $ex) {
      echo pht(
          'Failed to create robot user: "%s": %s.',
          $name,
          $ex->getMessage())."\n";
      die;
    }
  }

  /**
   * @param string $userName
   * @param string $userRealName
   * @param string $userEmail
   * @param bool   $welcomeMail
   * @return PhabricatorUser
   */
  public function createUser(string $userName, string $userRealName, string $userEmail, bool $welcomeMail): PhabricatorUser {
    try {
      $user = $this->newUser($userRealName, $userName, $userEmail);

      // When creating a new test user, we prefill their setting cache as empty.
      // This is a little more efficient than doing a query to load the empty
      // settings.
      $user->attachRawCacheData(
        array(
          PhabricatorUserPreferencesCacheType::KEY_PREFERENCES => '[]',
        ));

      if ($welcomeMail) {
        $welcome_engine = id(new PhabricatorPeopleWelcomeMailEngine())
          ->setSender(PhabricatorUser::getOmnipotentUser())
          ->setRecipient($user);

        if ($welcome_engine->canSendMail()) {
          $welcome_engine->sendMail();
        }
      }
      return $user;
    } catch (Exception $ex) {
      echo pht(
          'Failed to create robot user: "%s": %s.',
          $userName,
          $ex->getMessage())."\n";
      die;
    }
  }

  public function linkUserWithProvider(PhabricatorAuthProvider $provider, PhabricatorUser $user) {
    if (!$provider->shouldAllowAccountLink()) {
      throw new Exception(pht('This provider is not configured to allow linking: %s.', $provider->getProviderName()));
    }

    $accounts = id(new PhabricatorExternalAccountQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUserPHIDs(array($user->getPHID()))
      ->withProviderConfigPHIDs(array($provider->getProviderConfig()->getPHID()))
      ->execute();


    if ($accounts) {
      throw new Exception(pht('Your Phabricator account %s is already linked to this provider : %s', $user->getUserName(), $provider->getProviderName()));
    }

    $email = $email = id(new PhabricatorUserEmail())->loadOneWhere(
      'userPHID = %s AND isPrimary = 1',
      $user->getPHID());

    $account = id(new PhabricatorExternalAccount())
      ->setProviderConfigPHID($provider->getProviderConfig()->getPHID())
      ->setUserPHID($user->getPHID())
      ->setUsername($user->getUserName())
      ->setRealName($user->getRealName())
      ->setEmail($email->getAddress())
      ->setEmailVerified(1)
      ->attachAccountIdentifiers(array());

    // TODO: Remove this when these columns are removed. They no longer have
    // readers or writers (other than this callsite).

    $account
      ->setAccountType($provider->getAdapter()->getAdapterType())
      ->setAccountDomain($provider->getAdapter()->getAdapterDomain());

    // TODO: Remove this when "accountID" is removed; the column is not
    // nullable.

    $account->setAccountID('');

    $registration_key = Filesystem::readRandomCharacters(32);
    $account->setProperty(
      'registrationKey',
      PhabricatorHash::weakDigest($registration_key));

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
    $account->save();
    unset($unguarded);


    id(new PhabricatorExternalAccountIdentifier())
      ->setProviderConfigPHID($provider->getProviderConfig()->getPHID())
      ->setIdentifierRaw($user->getUserName())
      ->setExternalAccountPHID($account->getPHID())
      ->save();
  }

  /**
   * @param string $userRealName
   * @param string $userName
   * @param string $userEmail
   * @return mixed
   */
  private function newUser(string $userRealName, string $userName, string $userEmail) {
    $user = id(new PhabricatorUser())
      ->setRealName($userRealName)
      ->setUserName($userName)
      ->setIsApproved(1)
      ->setIsEmailVerified(1);

    $email = id(new PhabricatorUserEmail())
      ->setAddress($userEmail)
      ->setIsVerified(1);

    id(new PhabricatorUserEditor())
      ->setActor(PhabricatorUser::getOmnipotentUser())
      ->createNewUser($user, $email);
    return $user;
  }
}



