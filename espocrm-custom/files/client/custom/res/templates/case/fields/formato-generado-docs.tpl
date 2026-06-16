{{#if hasDocumentos}}
<div class="case-formato-generado">
    {{#each documentos}}
    <a href="{{url}}" class="case-formato-generado-doc" target="_blank" rel="noopener">
        <span class="case-formato-generado-doc-icon {{icon}}"></span>
        <span class="case-formato-generado-doc-body">
            <span class="case-formato-generado-doc-label">{{label}}</span>
            <span class="case-formato-generado-doc-name">{{name}}</span>
        </span>
        <span class="fas fa-download case-formato-generado-doc-action" aria-hidden="true"></span>
    </a>
    {{/each}}
</div>
{{else}}
<p class="text-muted small case-formato-generado-empty">{{emptyText}}</p>
{{/if}}
