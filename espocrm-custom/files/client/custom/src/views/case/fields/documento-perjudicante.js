define('custom:views/case/fields/documento-perjudicante', [
    'custom:views/fields/party-document',
], function (Dep) {

    return Dep.extend({
        tipoField: 'cTipoPersonaPerjudicante',
    });
});
