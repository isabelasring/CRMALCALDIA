define('custom:views/case/fields/asignacion-unificada', [
    'custom:views/case/fields/assigned-user',
    'custom:helpers/post-radicacion-fields',
    'custom:helpers/radicacion-fields',
], function (Dep, PostRadicacionFields, RadicacionFields) {

    const isAssignmentEditing = function (recordView) {
        if (!recordView) {
            return false;
        }

        return !!recordView._asignacionEditMode
            || !!recordView._asignarMode
            || recordView.layoutName === 'asignar'
            || !!(recordView.options && recordView.options.asignar);
    };

    const isAsignadorDetailContext = function (fieldView) {
        const user = fieldView.getUser();

        if (!user || user.isAdmin()) {
            return false;
        }

        if (!PostRadicacionFields.shouldShowAsignacion(user, fieldView.model)) {
            return false;
        }

        return RadicacionFields.canAssignCase(user)
            || RadicacionFields.isAsignadorUser(user);
    };

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.inlineEditDisabled = true;

            this.listenTo(this.model, 'change:cMotivoReasignacion', () => {
                if (this.isRendered && this.isRendered() && !isAssignmentEditing(this.getRecordView())) {
                    this.renderMotivoSection();
                }
            });
        },

        isBatchEditMode: function () {
            return isAssignmentEditing(this.getRecordView());
        },

        shouldShowMotivoBlock: function () {
            const recordView = this.getRecordView();

            if (!recordView) {
                return false;
            }

            if (this.isBatchEditMode()) {
                return PostRadicacionFields.hadPreviousAssignee(recordView._initialAssignedUserId);
            }

            const motivo = String(this.model.get('cMotivoReasignacion') || '').trim();

            return !!motivo;
        },

        hideInlineControls: function () {
            this.inlineEditDisabled = true;

            if (typeof this.removeInlineEditLinks === 'function') {
                this.removeInlineEditLinks();
            }

            if (!this.$el || !this.$el.length) {
                return;
            }

            this.$el.find('.inline-edit-link, .inline-save-link, .inline-cancel-link').remove();
            this.$el.closest('.cell, .field')
                .find('.inline-edit-link, .inline-save-link, .inline-cancel-link')
                .remove();
        },

        addInlineEditLinks: function () {
            return;
        },

        inlineEditSave: function () {
            this.hideInlineControls();

            if (typeof this.fetch === 'function') {
                this.model.set(this.fetch());
            }
        },

        enableAssignmentSelect: function () {
            if (!this.isBatchEditMode()) {
                const recordView = this.getRecordView();

                if (recordView && typeof recordView.enterAsignacionEditMode === 'function') {
                    recordView.enterAsignacionEditMode();
                }

                return;
            }

            Dep.prototype.enableAssignmentSelect.call(this);
        },

        isReadOnly: function () {
            if (this.isBatchEditMode()) {
                return false;
            }

            return Dep.prototype.isReadOnly.call(this);
        },

        onBatchEditModeChanged: function () {
            if (!this.isRendered || !this.isRendered()) {
                return;
            }

            if (this.isBatchEditMode()) {
                this.mode = 'edit';
                this.readOnly = false;

                if (typeof this.setNotReadOnly === 'function') {
                    this.setNotReadOnly();
                }

                this.reRender();

                return;
            }

            this.mode = 'detail';
            this.readOnly = true;
            this.reRender();
        },

        renderEditTrigger: function () {
            if (this.isBatchEditMode() || !isAsignadorDetailContext(this)) {
                this.$el.find('.alcaldia-asignacion-edit-trigger').remove();

                return;
            }

            const recordView = this.getRecordView();
            let $wrap = this.$el.find('.alcaldia-asignacion-edit-actions').first();

            if (!$wrap.length) {
                $wrap = $('<div class="alcaldia-asignacion-edit-actions"></div>');
                this.$el.prepend($wrap);
            }

            let $btn = $wrap.find('.alcaldia-asignacion-edit-trigger');

            if (!$btn.length) {
                $btn = $('<button type="button" class="btn btn-link btn-sm alcaldia-asignacion-edit-trigger"></button>');
                $wrap.append($btn);
            }

            const label = this.translate('editarAsignacion', 'labels', 'Case')
                || this.translate('Edit', 'labels', 'Global');

            $btn.text(label).show().off('click').on('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (recordView && typeof recordView.enterAsignacionEditMode === 'function') {
                    recordView.enterAsignacionEditMode();
                }
            });
        },

        getMotivoContainer: function () {
            let $container = this.$el.find('.alcaldia-asignacion-motivo-block').first();

            if (!$container.length) {
                $container = $('<div class="alcaldia-asignacion-motivo-block"></div>');
                this.$el.append($container);
            }

            return $container;
        },

        renderMotivoSection: function () {
            const $container = this.getMotivoContainer();

            $container.empty();

            if (!this.shouldShowMotivoBlock()) {
                $container.hide();

                return;
            }

            $container.show();

            const label = this.translate('cMotivoReasignacion', 'fields', 'Case');
            const optional = this.translate('optional', 'labels', 'Global') || 'opcional';

            if (this.isBatchEditMode()) {
                const value = String(this.model.get('cMotivoReasignacion') || '');
                const placeholder = this.translate('cMotivoReasignacionPlaceholder', 'messages', 'Case');

                $container.append(
                    $('<label class="control-label"></label>')
                        .text(label + ' (' + optional + ')')
                );

                this.$motivoTextarea = $('<textarea class="form-control main-element" rows="2"></textarea>')
                    .attr('maxlength', 500)
                    .attr('placeholder', placeholder)
                    .val(value);

                this.$motivoTextarea.on('input change', () => {
                    this.model.set('cMotivoReasignacion', this.$motivoTextarea.val());
                });

                $container.append(this.$motivoTextarea);

                return;
            }

            const motivo = String(this.model.get('cMotivoReasignacion') || '').trim();

            if (!motivo) {
                $container.hide();

                return;
            }

            $container.append(
                $('<div class="alcaldia-asignacion-motivo-read"></div>')
                    .append($('<div class="text-soft small"></div>').text(label))
                    .append($('<div class="alcaldia-asignacion-motivo-value"></div>').text(motivo))
            );
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.$el.addClass('alcaldia-asignacion-unificada');
            this.$el.closest('.cell, .field').addClass('alcaldia-asignacion-unificada-cell');

            this.hideInlineControls();

            if (this.isBatchEditMode()) {
                this.mode = 'edit';
                this.readOnly = false;
                this.$el.find('.alcaldia-asignacion-edit-actions').remove();
                this.showSelectControls();
                this.hideInlineControls();
            } else {
                this.renderEditTrigger();
            }

            this.renderMotivoSection();

            window.setTimeout(() => {
                this.hideInlineControls();

                if (this.isBatchEditMode()) {
                    this.showSelectControls();
                    this.renderMotivoSection();
                }
            }, 0);
        },

        fetch: function () {
            const data = Dep.prototype.fetch.call(this) || {};

            if (this.$motivoTextarea && this.$motivoTextarea.length) {
                data.cMotivoReasignacion = this.$motivoTextarea.val();
            }

            return data;
        },
    });
});
