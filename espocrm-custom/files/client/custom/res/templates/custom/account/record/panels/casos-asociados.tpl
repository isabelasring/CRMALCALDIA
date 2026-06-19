<div class="casos-asociados-panel">
    {{#if list.length}}
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th style="width: 18%;">{{translate 'cNumeroRadicado' category='fields' scope='Case'}}</th>
                <th style="width: 12%;">{{translate 'cFechaCaso' category='fields' scope='Case'}}</th>
                <th style="width: 14%;">{{translate 'status' category='fields' scope='Case'}}</th>
                <th style="width: 12%;">Rol</th>
                <th>{{translate 'cPeticionario' category='fields' scope='Case'}}</th>
                <th>{{translate 'cPerjudicante' category='fields' scope='Case'}}</th>
            </tr>
        </thead>
        <tbody>
            {{#each list}}
            <tr>
                <td>
                    <a href="#Case/view/{{id}}" title="{{cNumeroRadicado}}">
                        {{#if cNumeroRadicado}}{{cNumeroRadicado}}{{else}}(sin radicado){{/if}}
                    </a>
                </td>
                <td>{{cFechaCaso}}</td>
                <td>{{translate status category='options' scope='Case'}}</td>
                <td>{{rol}}</td>
                <td>{{cPeticionario}}</td>
                <td>{{cPerjudicante}}</td>
            </tr>
            {{/each}}
        </tbody>
    </table>
    {{else}}
    <div class="text-muted">{{translate 'noData'}}</div>
    {{/if}}
</div>
