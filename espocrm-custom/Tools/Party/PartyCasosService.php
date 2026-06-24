<?php

namespace Espo\Custom\Tools\Party;

use Espo\Custom\Tools\CaseObj\CasePartyNameHelper;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class PartyCasosService
{
    private const PERSONA_NATURAL = 'Persona natural';
    private const PERSONA_JURIDICA = 'Persona jurídica';

    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function findCasosForAccount(string $accountId): Collection
    {
        /** @var ?Entity $account */
        $account = $this->entityManager->getEntityById('Account', $accountId);

        if (!$account) {
            return $this->emptyCollection();
        }

        $or = [
            ['accountId' => $accountId],
            ['cPerjudicanteCuentaId' => $accountId],
        ];

        $this->appendDocumentConditionsForAccount($or, $account);

        return $this->entityManager
            ->getRDBRepository('Case')
            ->where(['OR' => $or])
            ->order('createdAt', 'DESC')
            ->find();
    }

    public function findCasosForContact(string $contactId): Collection
    {
        /** @var ?Entity $contact */
        $contact = $this->entityManager->getEntityById('Contact', $contactId);

        if (!$contact) {
            return $this->emptyCollection();
        }

        $or = [
            ['contactId' => $contactId],
            ['cPerjudicanteContactId' => $contactId],
        ];

        $this->appendDocumentConditionsForContact($or, $contact);

        return $this->entityManager
            ->getRDBRepository('Case')
            ->where(['OR' => $or])
            ->order('createdAt', 'DESC')
            ->find();
    }

    public function resolveRolForAccount(Entity $case, string $accountId): string
    {
        if ($case->get('accountId') === $accountId) {
            return 'Peticionario';
        }

        if ($case->get('cPerjudicanteCuentaId') === $accountId) {
            return 'Infractor';
        }

        return $this->resolveRolByAccountDocument($case, $accountId);
    }

    public function resolveRolForContact(Entity $case, string $contactId): string
    {
        if ($case->get('contactId') === $contactId) {
            return 'Peticionario';
        }

        if ($case->get('cPerjudicanteContactId') === $contactId) {
            return 'Infractor';
        }

        return $this->resolveRolByContactDocument($case, $contactId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function serializeCasosForAccount(string $accountId): array
    {
        $list = [];

        foreach ($this->findCasosForAccount($accountId) as $case) {
            $list[] = $this->serializeCaseForAccount($case, $accountId);
        }

        return $list;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function serializeCasosForContact(string $contactId): array
    {
        $list = [];

        foreach ($this->findCasosForContact($contactId) as $case) {
            $list[] = $this->serializeCaseForContact($case, $contactId);
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCaseForAccount(Entity $case, string $accountId): array
    {
        return $this->serializeCase($case, $this->resolveRolForAccount($case, $accountId));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCaseForContact(Entity $case, string $contactId): array
    {
        return $this->serializeCase($case, $this->resolveRolForContact($case, $contactId));
    }

    /**
     * @param array<int, array<string, mixed>> $or
     */
    private function appendDocumentConditionsForContact(array &$or, Entity $contact): void
    {
        $documento = trim((string) $contact->get('cNumeroDeDocumento'));

        if ($documento === '') {
            return;
        }

        foreach (DocumentNormalizer::candidates($documento) as $candidate) {
            $or[] = [
                'AND' => [
                    ['cTipoPersonaPeticionario' => self::PERSONA_NATURAL],
                    ['cDocumentoPeticionario' => $candidate],
                ],
            ];
            $or[] = [
                'AND' => [
                    ['cTipoPersonaPerjudicante' => self::PERSONA_NATURAL],
                    ['cDocumentoPerjudicante' => $candidate],
                ],
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $or
     */
    private function appendDocumentConditionsForAccount(array &$or, Entity $account): void
    {
        $nit = trim((string) $account->get('cNit'));

        if ($nit === '') {
            return;
        }

        foreach (DocumentNormalizer::candidates($nit) as $candidate) {
            $or[] = [
                'AND' => [
                    ['cTipoPersonaPeticionario' => self::PERSONA_JURIDICA],
                    ['cDocumentoPeticionario' => $candidate],
                ],
            ];
            $or[] = [
                'AND' => [
                    ['cTipoPersonaPerjudicante' => self::PERSONA_JURIDICA],
                    ['cDocumentoPerjudicante' => $candidate],
                ],
            ];
        }
    }

    private function resolveRolByContactDocument(Entity $case, string $contactId): string
    {
        /** @var ?Entity $contact */
        $contact = $this->entityManager->getEntityById('Contact', $contactId);
        $contactDoc = $contact
            ? DocumentNormalizer::normalize((string) $contact->get('cNumeroDeDocumento'))
            : '';

        if ($contactDoc === '') {
            return 'Peticionario';
        }

        if (
            trim((string) $case->get('cTipoPersonaPerjudicante')) === self::PERSONA_NATURAL
            && DocumentNormalizer::normalize((string) $case->get('cDocumentoPerjudicante')) === $contactDoc
        ) {
            return 'Infractor';
        }

        return 'Peticionario';
    }

    private function resolveRolByAccountDocument(Entity $case, string $accountId): string
    {
        /** @var ?Entity $account */
        $account = $this->entityManager->getEntityById('Account', $accountId);
        $accountNit = $account
            ? DocumentNormalizer::normalize((string) $account->get('cNit'))
            : '';

        if ($accountNit === '') {
            return 'Peticionario';
        }

        if (
            trim((string) $case->get('cTipoPersonaPerjudicante')) === self::PERSONA_JURIDICA
            && DocumentNormalizer::normalize((string) $case->get('cDocumentoPerjudicante')) === $accountNit
        ) {
            return 'Infractor';
        }

        return 'Peticionario';
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCase(Entity $case, string $rol): array
    {
        return [
            'id' => $case->getId(),
            'cNumeroRadicado' => (string) $case->get('cNumeroRadicado'),
            'cExpediente' => (string) $case->get('cExpediente'),
            'cFechaCaso' => (string) $case->get('cFechaCaso'),
            'status' => (string) $case->get('status'),
            'cPeticionario' => CasePartyNameHelper::getPeticionarioFullName($case),
            'cPerjudicante' => CasePartyNameHelper::getPerjudicanteFullName($case),
            'rol' => $rol,
        ];
    }

    private function emptyCollection(): Collection
    {
        return $this->entityManager
            ->getRDBRepository('Case')
            ->where(['id' => null])
            ->find();
    }
}
