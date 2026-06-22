define('custom:views/calendar/modals/edit', ['crm:views/calendar/modals/edit'], function (Dep) {

    return Dep.extend({

        scopeList: ['Meeting', 'Task'],

        setup: function () {
            var scopeList = (this.options.enabledScopeList || this.options.scopeList || ['Meeting', 'Task'])
                .filter(function (scope) {
                    return scope !== 'Case' && scope !== 'CaseMore';
                });

            if (!scopeList.length) {
                scopeList = ['Meeting', 'Task'];
            }

            this.options.scopeList = scopeList;
            this.options.enabledScopeList = scopeList;

            if (!scopeList.includes(this.options.scope)) {
                this.options.scope = scopeList[0];
            }

            Dep.prototype.setup.call(this);
        },
    });
});
