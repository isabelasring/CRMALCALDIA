define('custom:views/case/fields/documento-peticionario', [
    'custom:views/fields/party-document',
], function (Dep) {

    return Dep.extend({
        tipoField: 'cTipoPersonaPeticionario',
    });
});
