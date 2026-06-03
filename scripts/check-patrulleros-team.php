<?php
require_once '/var/www/html/bootstrap.php';
$em = (new Espo\Core\Application())->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$team = $em->getRDBRepository('Team')->where(['name' => 'Patrulleros'])->findOne();
if (!$team) {
    echo "Equipo Patrulleros NO existe\n";
    exit(1);
}
echo "Equipo Patrulleros id=" . $team->getId() . "\n";
foreach ($em->getRDBRepository('User')->where(['isActive' => true])->find() as $u) {
    $teams = $u->getLinkMultipleIdList('teams') ?? [];
    if (in_array($team->getId(), $teams, true)) {
        echo ' - ' . $u->get('userName') . "\n";
    }
}
$julian = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
$n = $em->getRDBRepository('Notification')->where(['userId' => $julian->getId(), 'read' => false])->count();
echo "Notificaciones no leídas Julian: $n\n";
