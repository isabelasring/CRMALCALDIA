<?php

namespace Espo\Custom\Tools\CaseObj;

class CaseCreateDefaultsService
{
    /**
     * @return array<string, string>
     */
    public function build(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'));

        return [
            'cFechaCaso' => $now->format('Y-m-d H:i'),
        ];
    }
}
