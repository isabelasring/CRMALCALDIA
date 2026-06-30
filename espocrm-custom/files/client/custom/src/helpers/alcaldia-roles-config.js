define('custom:helpers/alcaldia-roles-config', [], function () {

    /** true = flujos Inspección/Radicación/Asignación/Patrullero desactivados */
    const ROLES_DISABLED = true;

    return {
        ROLES_DISABLED: ROLES_DISABLED,
        isDisabled: function () {
            return ROLES_DISABLED;
        },
    };
});
