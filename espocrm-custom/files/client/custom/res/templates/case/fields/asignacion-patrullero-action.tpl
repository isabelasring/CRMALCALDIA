{{#if showPanel}}
<div class="case-asignacion-inline">
    <div class="form-group">
        <label class="control-label">{{assignedUserLabel}}</label>
        <div class="case-asignacion-inline-user" data-role="assigned-user-cell"></div>
    </div>
    {{#if showMotivo}}
    <div class="form-group case-asignacion-inline-motivo" data-role="motivo-wrap">
        <label class="control-label">{{motivoLabel}}</label>
        <div data-role="motivo-cell"></div>
    </div>
    {{/if}}
    <button type="button" class="btn btn-primary btn-sm" data-action="saveAsignacion">
        <span class="fas fa-user-check"></span> {{saveLabel}}
    </button>
</div>
{{/if}}
