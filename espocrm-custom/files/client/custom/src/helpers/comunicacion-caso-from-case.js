define('custom:helpers/comunicacion-caso-from-case', [], function () {

    const buildDefaultsFromCase = function (caseModel) {
        var radicado = String(caseModel.get('cNumeroRadicado') || '').trim();
        var now = new Date();
        var pad = function (n) {
            return n < 10 ? '0' + n : String(n);
        };
        var fecha = now.getFullYear() + '-'
            + pad(now.getMonth() + 1) + '-'
            + pad(now.getDate()) + ' '
            + pad(now.getHours()) + ':'
            + pad(now.getMinutes()) + ':'
            + pad(now.getSeconds());

        return {
            caseId: caseModel.id,
            caseName: radicado || caseModel.get('name') || caseModel.id,
            numeroRadicado: radicado || null,
            fecha: fecha,
            esRespuestaFinal: false,
        };
    };

    return {
        buildDefaultsFromCase: buildDefaultsFromCase,
    };
});
