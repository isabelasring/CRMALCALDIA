<div class="panel-body comunicaciones-caso-panel">
    <p class="text-muted small">{{helpText}}</p>

    {{#if canCreate}}
        <button type="button" class="btn btn-primary btn-sm btn-block margin-bottom" data-action="registrarComunicacion">
            <span class="fas fa-plus"></span> Registrar comunicación
        </button>
    {{/if}}

    {{#if loadError}}
        <p class="text-danger small">No se pudo cargar las comunicaciones.</p>
    {{else}}
        {{#if isEmpty}}
            <p class="text-muted small">Sin comunicaciones registradas.</p>
        {{else}}
            <div class="table-responsive">
                <table class="table table-condensed table-striped table-comunicaciones-caso">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Destinatario</th>
                            <th>Asunto</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{#each items}}
                            <tr>
                                <td>
                                    <a href="#" data-action="verComunicacion" data-id="{{id}}">{{fecha}}</a>
                                    {{#if esRespuestaFinal}}
                                        <span class="label label-success" title="Respuesta final">Final</span>
                                    {{/if}}
                                </td>
                                <td>{{tipo}}</td>
                                <td>{{destinatario}}</td>
                                <td>{{asunto}}</td>
                            </tr>
                        {{/each}}
                    </tbody>
                </table>
            </div>
        {{/if}}
    {{/if}}
</div>
