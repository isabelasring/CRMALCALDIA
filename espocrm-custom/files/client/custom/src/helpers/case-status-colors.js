define('custom:helpers/case-status-colors', [], function () {

    /**
     * Paleta única por etapa — fondo, texto y borde bien separados en el círculo cromático.
     * @type {Object<string, {bg: string, text: string, border: string, kanban: string}>}
     */
    const PALETTE = {
        'Pendiente de radicacion': {
            bg: '#fed7aa',
            text: '#9a3412',
            border: '#ea580c',
            kanban: '#ea580c',
        },
        'Radicado': {
            bg: '#bfdbfe',
            text: '#1e3a8a',
            border: '#2563eb',
            kanban: '#2563eb',
        },
        'Asignado': {
            bg: '#a5f3fc',
            text: '#155e75',
            border: '#0891b2',
            kanban: '#0891b2',
        },
        'En proceso': {
            bg: '#f5d0fe',
            text: '#86198f',
            border: '#c026d3',
            kanban: '#c026d3',
        },
        'Visita realizada': {
            bg: '#d9f99d',
            text: '#365314',
            border: '#65a30d',
            kanban: '#65a30d',
        },
        'Visita aprobada': {
            bg: '#bbf7d0',
            text: '#14532d',
            border: '#16a34a',
            kanban: '#16a34a',
        },
        'Finalizado': {
            bg: '#99f6e4',
            text: '#115e59',
            border: '#0d9488',
            kanban: '#0d9488',
        },
        'Proceso cerrado': {
            bg: '#e7e5e4',
            text: '#44403c',
            border: '#78716c',
            kanban: '#78716c',
        },
    };

    const LABEL_CLASS = {
        'Pendiente de radicacion': 'casePendiente',
        'Radicado': 'caseRadicado',
        'Asignado': 'caseAsignado',
        'En proceso': 'caseEnProceso',
        'Visita realizada': 'caseVisitaRealizada',
        'Visita aprobada': 'caseVisitaAprobada',
        'Finalizado': 'caseFinalizado',
        'Proceso cerrado': 'caseCerrado',
    };

    const GENERIC_LABEL_CLASSES = [
        'label-primary',
        'label-success',
        'label-info',
        'label-warning',
        'label-danger',
        'label-default',
    ].join(' ');

    const get = function (status) {
        return PALETTE[String(status || '').trim()] || null;
    };

    const getLabelClass = function (status) {
        const key = String(status || '').trim();

        return LABEL_CLASS[key] ? 'label-' + LABEL_CLASS[key] : null;
    };

    const applyToLabel = function ($label, status) {
        if (!$label || !$label.length) {
            return;
        }

        const colors = get(status);
        const labelClass = getLabelClass(status);

        if (!colors) {
            return;
        }

        $label
            .attr('data-case-status', status)
            .removeClass(GENERIC_LABEL_CLASSES)
            .css({
                backgroundColor: colors.bg,
                color: colors.text,
                borderLeft: '3px solid ' + colors.border,
                fontWeight: '700',
            });

        if (labelClass) {
            $label.addClass(labelClass);
        }
    };

    return {
        PALETTE: PALETTE,
        get: get,
        getLabelClass: getLabelClass,
        applyToLabel: applyToLabel,
    };
});
