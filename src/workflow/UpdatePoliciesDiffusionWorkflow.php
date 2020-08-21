<?php

final class UpdatePoliciesDiffusionWorkflow
  extends UpdatePolicies {

  private $force = false;
  private $updateAuthorization = false;

  private $diffusionService;
  private $projectService;

  protected function didConstruct() {
    $this
      ->setName('diffusion')
      ->setExamples('**diffusion** [options]')
      ->setSynopsis(pht('Update policies on diffusion document'))
      ->setArguments(
        array(
          array(
            'name' => 'force',
            'param' => 'bool',
            'help' => pht('Use to force rewriting policies'),
            'default' => 'false',
          ),
          array(
            'name' => 'update-authorization',
            'param' => 'bool',
            'help' => pht('Use to force allow dangerous change and prevent enormous change'),
            'default' => 'false',
          ),
        ))
      ->setDiffusionService(new DiffusionService())
      ->setProjectService(new ProjectService());

  }

  public function execute(PhutilArgumentParser $args) {
    $this->force = $args->getArg('force') === 'true';
    $this->updateAuthorization = $args->getArg('update-authorization') === 'true';

    $start = new DateTimeImmutable();
    echo pht("Started at: %s\n", $start->format("Y-m-d H:i:s"));

    ScriptUtils::separator();

    $spaces = PhabricatorSpacesNamespaceQuery::getAllSpaces();
    foreach ($spaces as $space) {
      echo pht("Space: %s - %s: %s\n", $space->getID(), $space->getNamespaceName(), $space->getPHID());

      if ($space->getIsDefaultNamespace() == true) {
        $group = $this->projectService->getGroupByName($space->getNamespaceName());
      } else {
        $group = $this->projectService->getGroupByName('Equipe '.$space->getNamespaceName());
      }

      echo pht("Group: %s\n", $group->getDisplayName());
      ScriptUtils::separator();
      echo pht("    Default Policies:\n");
      echo pht("      Visible To: All Users: global\n");
      echo pht("      Editable By: Administrateurs: global\n");
      echo pht("      Pushable By: Custom Policy: custom \n");
      echo pht("        Rule: PhabricatorAdministratorsPolicyRule - Action allow \n");
      echo pht("        Rule: PhabricatorProjectsPolicyRule - Action allow [%s,] \n", $group->getDisplayName());
      ScriptUtils::separator();

      $repositories = $this->diffusionService->getRepositoriesForSpace($space->getPHID());
      if ($repositories == null) {
        continue;
      }

      //Custom policy
      $defaultPushPolicy = id(new PhabricatorPolicy())
        ->setRules(
          array(
            array(
              'action' => PhabricatorPolicy::ACTION_ALLOW,
              'rule' => 'PhabricatorAdministratorsPolicyRule',
              'value' => null,
            ),
            array(
              'action' => PhabricatorPolicy::ACTION_ALLOW,
              'rule' => 'PhabricatorProjectsPolicyRule',
              'value' => array($group->getPHID()),
            ),
          ))
        ->save();

      foreach ($repositories as $repository) {
        $changed = $this->force ? $this->force : $this->updateAuthorization;

        echo pht("  Repository: %s - %s: %s\n", $repository->getID(), $repository->getName(), $repository->getPHID());
        ScriptUtils::separator(2);
        echo pht("    Global authorization:\n");
        echo pht("      Allow Dangerous Changes: %s\n", $repository->shouldAllowDangerousChanges());
        echo pht("      Allow Enormous Changes:  %s\n", $repository->shouldAllowEnormousChanges());

        $change = $this->editChangeParameter($repository);
        $changed = $changed || $change;

        $policies = $this->diffusionService->getPolicies($repository);

        echo pht("    Policies:\n");

        foreach ($policies as $view => $policy) {
          $change = $this->resetPolicies($repository, $policy, $view, $defaultPushPolicy);
          $changed = $changed || $change;
        }

        echo pht("  => Result: ");
        if ($changed) {
          $repository->update();
          echo pht("Updated\n");
        } else {
          echo pht("No change\n");
        }
      }
      ScriptUtils::separator();
    }

    ScriptUtils::separator();
    $end = new DateTimeImmutable();
    $execution = $end->diff($start);
    echo pht("Finished at: %s\n", $end->format("Y-m-d H:i:s"));
    echo pht("Executed in: %s\n", $execution->format("%ss %Imin %Hh"));
    return 0;
  }

  /**
   * @param PhabricatorRepository $repository
   * @param PhabricatorPolicy     $policy
   * @param string                $view
   * @param PhabricatorPolicy     $defaultPushPolicy
   * @return bool
   * @throws Exception
   */
  private function resetPolicies(PhabricatorRepository $repository, PhabricatorPolicy $policy, string $view, PhabricatorPolicy $defaultPushPolicy): bool {
    $change = false;

    $this->displayPolicyAction($view);
    echo pht("%s: %s\n ", $policy->getName(), $policy->getType());
    $this->displayRules($policy);

    if ($this->force === true || ($policy->isCustomPolicy() === false && strpos($policy->getName(), 'Admin') === false)) {
      switch ($view) {
        case DiffusionPushCapability::CAN_VIEW:
          $viewPolicy = PhabricatorPolicyQuery::getGlobalPolicy(PhabricatorPolicies::POLICY_USER);
          if ($policy->getPHID() !== $viewPolicy->getPHID()) {
            $repository->setViewPolicy($viewPolicy->getPHID());
            $change = true;
          }
          break;
        case DiffusionPushCapability::CAN_EDIT:
          $editPolicy = PhabricatorPolicyQuery::getGlobalPolicy(PhabricatorPolicies::POLICY_ADMIN);
          if ($policy->getPHID() !== $editPolicy->getPHID()) {
            $repository->setEditPolicy($editPolicy->getPHID());
            $change = true;
          }
          break;
        case DiffusionPushCapability::CAPABILITY:
          $repository->setPushPolicy($defaultPushPolicy->getPHID());
          $change = true;
          break;
      }
      return $change;
    }
    return false;
  }

  /**
   * @param string $view
   */
  private static function displayPolicyAction(string $view): void {
    switch ($view) {
      case DiffusionPushCapability::CAN_VIEW:
        echo pht("       Visible To: ");
        break;
      case DiffusionPushCapability::CAN_EDIT:
        echo pht("      Editable By: ");
        break;
      case DiffusionPushCapability::CAPABILITY:
        echo pht("      Pushable By: ");
        break;
    }
  }

  /**
   * @param $repository
   * @return bool
   */
  private function editChangeParameter($repository): bool {
    if ($this->updateAuthorization) {
      $repository->setDetail('allow-dangerous-changes', true);
      $repository->setDetail('allow-enormous-changes', false);
      return true;
    }
    return false;
  }

  /**
   * @param PhabricatorPolicy $policy
   */
  public function displayRules(PhabricatorPolicy $policy): void {
    if ($policy->isCustomPolicy()) {
      foreach ($policy->getRules() as $rule) {
        echo pht("        Rule: %s - Action %s ", $rule['rule'], $rule['action']);

        if ($rule['value'] != null) {
          echo '[';
          foreach ($rule['value'] as $phid) {
            $group = $this->projectService->getGroupByPhid($phid);
            if ($group != null) {
              echo $group->getDisplayName().", ";
            }
          }
          echo ']';
        }
        echo "\n";
      }
    }
  }

  /**
   * @param DiffusionService $diffusionService
   * @return UpdatePoliciesDiffusionWorkflow
   */
  public function setDiffusionService(DiffusionService $diffusionService): UpdatePoliciesDiffusionWorkflow {
    $this->diffusionService = $diffusionService;
    return $this;
  }

  /**
   * @param ProjectService $projectService
   * @return UpdatePoliciesDiffusionWorkflow
   */
  public function setProjectService(ProjectService $projectService): UpdatePoliciesDiffusionWorkflow {
    $this->projectService = $projectService;
    return $this;
  }
}
