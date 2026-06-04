<div class="panel-body">
    {{#if visible}}
        <p class="text-muted small">{{translate 'formatoSolicitudHelp' category='Case'}}</p>
        <button type="button" class="btn btn-default btn-block" data-action="downloadFormato" data-format="doc">
            <span class="fas fa-file-word"></span> {{translate 'downloadFormatoWord' category='Case'}}
        </button>
        <button type="button" class="btn btn-default btn-block" data-action="downloadFormato" data-format="pdf">
            <span class="fas fa-file-pdf"></span> {{translate 'downloadFormatoPdf' category='Case'}}
        </button>
    {{else}}
        <p class="text-muted small">{{translate 'formatoSolicitudUnavailable' category='Case'}}</p>
    {{/if}}
</div>
