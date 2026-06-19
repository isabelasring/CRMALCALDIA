<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Custom\Tools\Party\PartyCasosService;
use Espo\Modules\Crm\Controllers\Account as BaseAccount;

class Account extends BaseAccount
{
    /**
     * GET Account/action/casosAsociados?accountId=...
     *
     * @return array<string, mixed>
     */
    public function getActionCasosAsociados(Request $request): array
    {
        if (!$this->acl->check('Account', 'read')) {
            throw new Forbidden();
        }

        if (!$this->acl->check('Case', 'read')) {
            throw new Forbidden();
        }

        $accountId = trim((string) $request->getQueryParam('accountId'));

        if ($accountId === '') {
            throw new BadRequest('accountId requerido.');
        }

        if (!$this->entityManager->getEntityById('Account', $accountId)) {
            throw new BadRequest('Persona jurídica no encontrada.');
        }

        $service = new PartyCasosService($this->entityManager);

        return $this->buildCaseListResponse($service->findCasosForAccount($accountId));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCaseListResponse(iterable $cases): array
    {
        $list = [];

        foreach ($cases as $case) {
            if (!$this->acl->checkEntityRead($case)) {
                continue;
            }

            $list[] = $case->getValueMap();
        }

        return [
            'total' => count($list),
            'list' => $list,
        ];
    }
}
