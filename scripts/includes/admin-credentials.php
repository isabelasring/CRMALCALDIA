<?php

/**
 * Lee credenciales admin: archivo JSON del deploy → variables de entorno.
 */
function alcaldiaAdminCredentialsPath(): string
{
    return '/var/www/html/data/.alcaldia-admin-credentials.json';
}

function alcaldiaAdminUsername(): string
{
    $fromFile = alcaldiaReadCredentialsFile();

    if ($fromFile['userName'] !== '') {
        return $fromFile['userName'];
    }

    return alcaldiaEnv('ESPOCRM_ADMIN_USERNAME', 'admin');
}

function alcaldiaAdminPassword(): string
{
    $fromFile = alcaldiaReadCredentialsFile();

    if ($fromFile['password'] !== '') {
        return $fromFile['password'];
    }

    return alcaldiaEnv('ESPOCRM_ADMIN_PASSWORD', '');
}

/** @return array{userName: string, password: string} */
function alcaldiaReadCredentialsFile(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = ['userName' => '', 'password' => ''];
    $path = alcaldiaAdminCredentialsPath();

    if (!is_readable($path)) {
        return $cache;
    }

    $raw = file_get_contents($path);

    if ($raw === false || trim($raw) === '') {
        return $cache;
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return $cache;
    }

    $cache['userName'] = trim((string) ($data['userName'] ?? ''));
    $cache['password'] = (string) ($data['password'] ?? '');

    return $cache;
}

function alcaldiaEnv(string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return trim((string) $value);
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return trim((string) $_ENV[$key]);
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return trim((string) $_SERVER[$key]);
    }

    return $default;
}

function alcaldiaWriteAdminCredentialsFile(string $userName, string $password): void
{
    $path = alcaldiaAdminCredentialsPath();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($path, json_encode([
        'userName' => $userName,
        'password' => $password,
    ], JSON_UNESCAPED_UNICODE));

    chmod($path, 0600);
}
