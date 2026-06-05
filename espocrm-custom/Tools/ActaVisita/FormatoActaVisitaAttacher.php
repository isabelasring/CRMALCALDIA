<?php

namespace Espo\Custom\Tools\ActaVisita;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Log;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class FormatoActaVisitaAttacher
{
    private const FIELD = 'cFormatoActaVisitaPdf';

    public function __construct(
        private EntityManager $entityManager,
        private InjectableFactory $injectableFactory,
        private Log $log
    ) {}

    public function attachToActa(Entity $acta): bool
    {
        if (!$acta->getId()) {
            return false;
        }

        try {
            $generator = $this->injectableFactory->create(FormatoActaVisitaGenerator::class);
            $file = $generator->generate($acta->getId(), 'pdf', true);

            $this->removePreviousAttachment($acta);

            $contents = (string) file_get_contents($file['path']);

            $attachment = $this->entityManager
                ->getRDBRepositoryByClass(Attachment::class)
                ->getNew();

            $attachment
                ->setName($this->buildFileName($acta))
                ->setType('application/pdf')
                ->setRole(Attachment::ROLE_ATTACHMENT)
                ->setTargetField(self::FIELD)
                ->set('parentType', 'ActaVisita')
                ->set('parentId', $acta->getId())
                ->setContents($contents);

            $this->entityManager->saveEntity($attachment);

            $acta->set(self::FIELD . 'Id', $attachment->getId());
            $acta->set(self::FIELD . 'Name', $attachment->getName());

            $this->entityManager->saveEntity($acta, [
                'skipFormatoActaVisita' => true,
            ]);

            @unlink($file['path']);
            $workDir = dirname($file['path']);

            if (is_dir($workDir)) {
                $this->removeDirectory($workDir);
            }

            return true;
        } catch (\Throwable $e) {
            $this->log->error(
                'Formato acta visita PDF: {message}',
                ['message' => $e->getMessage(), 'actaId' => $acta->getId()]
            );

            return false;
        }
    }

    private function removePreviousAttachment(Entity $acta): void
    {
        $attachmentId = $acta->get(self::FIELD . 'Id');

        if (!$attachmentId) {
            return;
        }

        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $attachmentId);

        if ($attachment) {
            $this->entityManager->removeEntity($attachment);
        }
    }

    private function buildFileName(Entity $acta): string
    {
        $radicado = trim((string) $acta->get('numeroRadicado'));
        $slug = preg_replace('/[^\w\-]+/u', '_', $radicado) ?: 'acta';

        return 'ActaVisita-' . $slug . '.pdf';
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
