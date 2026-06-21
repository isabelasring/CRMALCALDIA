{{#if hasPartes}}
    <div class="destinatario-sugerencias">
        <p class="text-muted small margin-bottom-sm">Seleccione del caso o busque otro tercero abajo:</p>
        <div class="btn-group-vertical width-full" role="group">
            {{#each partes}}
                <button type="button" class="btn btn-default btn-sm text-left" data-action="pickDestinatarioParte" data-index="{{@index}}">
                    <strong>{{label}}:</strong> {{name}}
                    {{#if tipoLabel}}
                        <span class="text-muted">({{tipoLabel}})</span>
                    {{else}}
                        <span class="text-muted">(sin vínculo en terceros)</span>
                    {{/if}}
                </button>
            {{/each}}
        </div>
    </div>
{{else}}
    {{#if loadError}}
        <p class="text-muted small">No se pudieron cargar los terceros del caso. Use el buscador de abajo.</p>
    {{else}}
        <p class="text-muted small">Busque el destinatario abajo (persona natural o jurídica).</p>
    {{/if}}
{{/if}}
