define('custom:helpers/patrullero-acta', [], function () {

    const TEAM_PATRULLEROS = 'Patrulleros';

    const isPatrulleroUser = function (user) {
        if (!user || user.isAdmin()) {
            return false;
        }

        const teams = user.get('teamsNames') || {};

        return Object.values(teams).includes(TEAM_PATRULLEROS);
    };

    const shouldShowActaVisita = function (user, model) {
        if (!user || !model) {
            return false;
        }

        if (!isPatrulleroUser(user)) {
            return false;
        }

        if (model.get('status') !== 'Radicado') {
            return false;
        }

        return model.get('assignedUserId') === user.id;
    };

    return {
        isPatrulleroUser: isPatrulleroUser,
        shouldShowActaVisita: shouldShowActaVisita,
    };
});
