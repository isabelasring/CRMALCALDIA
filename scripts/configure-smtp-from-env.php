<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\InjectableFactory;

$app = new Application();
$app->setupSystemUser();

$settings = [
    'smtpServer' => getenv('ESPOCRM_SMTP_SERVER') ?: null,
    'smtpPort' => (int) (getenv('ESPOCRM_SMTP_PORT') ?: 587),
    'smtpAuth' => filter_var(getenv('ESPOCRM_SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'smtpSecurity' => getenv('ESPOCRM_SMTP_SECURITY') ?: 'TLS',
    'smtpUsername' => getenv('ESPOCRM_SMTP_USERNAME') ?: null,
    'smtpPassword' => getenv('ESPOCRM_SMTP_PASSWORD') ?: null,
    'outboundEmailFromAddress' => getenv('ESPOCRM_OUTBOUND_EMAIL_FROM_ADDRESS') ?: null,
    'outboundEmailFromName' => getenv('ESPOCRM_OUTBOUND_EMAIL_FROM_NAME') ?: 'CRM Alcaldia',
];

if (!$settings['smtpServer'] || !$settings['smtpUsername'] || !$settings['smtpPassword']) {
    echo "Faltan variables SMTP en el entorno.\n";
    echo "Agrega ESPOCRM_SMTP_SERVER, ESPOCRM_SMTP_USERNAME y ESPOCRM_SMTP_PASSWORD al .env\n";
    exit(1);
}

/** @var InjectableFactory $injectableFactory */
$injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);
$configWriter = $injectableFactory->create(ConfigWriter::class);

foreach ($settings as $key => $value) {
    $configWriter->set($key, $value);
}

$configWriter->save();

echo "SMTP configurado: {$settings['smtpServer']}:{$settings['smtpPort']}\n";
echo "Remitente: {$settings['outboundEmailFromAddress']}\n";
