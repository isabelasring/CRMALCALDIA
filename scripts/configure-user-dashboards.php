<?php

/**
 * Configura el Home (dashboard) por perfil de usuario.
 *
 * docker cp scripts/configure-user-dashboards.php espocrm:/tmp/configure-user-dashboards.php
 * docker exec espocrm php /tmp/configure-user-dashboards.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

function dashId(string $prefix): string
{
    return $prefix . substr(uniqid('', true), -7);
}

function casesLayout(): array
{
    return [
        'rows' => [
            [
                ['name' => 'cNumeroRadicado', 'link' => true],
                ['name' => 'cNombrePeticionario'],
                ['name' => 'cApellidoPeticionario'],
                ['name' => 'status'],
            ],
            [
                ['name' => 'cExpediente'],
                ['name' => 'assignedUser'],
                ['name' => 'cFechaCaso'],
            ],
        ],
    ];
}

function casesOptions(string $title, string $primaryFilter, int $displayRecords = 10): array
{
    return [
        'title' => $title,
        'autorefreshInterval' => 0,
        'displayRecords' => $displayRecords,
        'includeShared' => false,
        'orderBy' => 'cFechaCaso',
        'order' => 'desc',
        'searchData' => [
            'primary' => $primaryFilter,
            'bool' => (object) [],
        ],
        'expandedLayout' => casesLayout(),
    ];
}

function iframeOptions(string $title, string $url, ?int $cacheBuster = null): array
{
    $v = $cacheBuster ?? time();
    $separator = str_contains($url, '?') ? '&' : '?';

    return [
        'title' => $title,
        'url' => $url . $separator . 'v=' . $v,
        'autorefreshInterval' => 0,
    ];
}

function buildDashboard(array $widgets): array
{
    $layout = [];
    $options = [];
    $y = 0;

    foreach ($widgets as $widget) {
        $id = dashId($widget['prefix'] ?? 'w');
        $height = $widget['height'] ?? 2;

        $layout[] = [
            'id' => $id,
            'name' => $widget['name'],
            'x' => 0,
            'y' => $y,
            'width' => 2,
            'height' => $height,
        ];

        $options[$id] = $widget['options'];
        $y += $height;
    }

    return [
        'dashboardLayout' => [
            [
                'name' => 'My Espo',
                'layout' => $layout,
            ],
        ],
        'dashletsOptions' => $options,
    ];
}

function detectProfile(EntityManager $em, User $user): string
{
    return $user->isAdmin() ? 'admin' : 'default';
}

function profileWidgets(string $profile, string $userId): array
{
    return [
        [
            'prefix' => 'tablero',
            'name' => 'Iframe',
            'height' => 9,
            'options' => iframeOptions('Tablero de control', '/client/custom/dashboard.html'),
        ],
        [
            'prefix' => 'casos',
            'name' => 'Cases',
            'height' => 4,
            'options' => casesOptions('Casos', 'all', 15),
        ],
    ];
}

$users = $em->getRDBRepository('User')
    ->where([
        'type' => ['regular', 'admin'],
        'isActive' => true,
        'deleted' => false,
    ])
    ->find();

$unwantedDashlets = ['Memo', 'Records'];

$sanitizeDashboard = static function (array $dashboardLayout) use ($unwantedDashlets): array {
    foreach ($dashboardLayout as &$tab) {
        if (!isset($tab['layout']) || !is_array($tab['layout'])) {
            $tab['layout'] = [];
            continue;
        }

        $tab['layout'] = array_values(array_filter(
            $tab['layout'],
            static function ($item) use ($unwantedDashlets): bool {
                $name = is_array($item) ? ($item['name'] ?? '') : '';

                return !in_array($name, $unwantedDashlets, true);
            }
        ));
    }
    unset($tab);

    return $dashboardLayout;
};

$sanitizeDashletsOptions = static function (array $dashboardLayout, array $dashletsOptions): array {
    $allowedIds = [];

    foreach ($dashboardLayout as $tab) {
        foreach ($tab['layout'] ?? [] as $item) {
            if (!empty($item['id'])) {
                $allowedIds[$item['id']] = true;
            }
        }
    }

    return array_intersect_key($dashletsOptions, $allowedIds);
};

foreach ($users as $user) {
    $userName = (string) $user->get('userName');
    $profile = detectProfile($em, $user);

    $prefs = $em->getEntityById('Preferences', $user->getId());

    if (!$prefs) {
        echo "Sin preferencias: {$userName}\n";
        continue;
    }

    // Vacío a propósito: tablero + listas los inyecta custom:views/home (evita crash de dashlets Cases).
    $prefs->set('dashboardLayout', [
        [
            'name' => 'My Espo',
            'layout' => [],
        ],
    ]);
    $prefs->set('dashletsOptions', new stdClass());
    $prefs->set('dashboardLocked', false);
    $em->saveEntity($prefs);

    echo "{$userName} → dashboard estándar\n";
}

$statePath = '/var/www/html/data/state.php';

if (is_readable($statePath)) {
    $state = include $statePath;
    $state['appTimestamp'] = time();
    $state['cacheTimestamp'] = time();
    $state['microtimeState'] = microtime(true);
    file_put_contents($statePath, "<?php\nreturn " . var_export($state, true) . ";\n");
    echo "appTimestamp actualizado.\n";
}

echo "Listo. Recarga el navegador (Cmd+Shift+R).\n";
