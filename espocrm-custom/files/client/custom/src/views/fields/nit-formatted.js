define('custom:views/fields/nit-formatted', [
    'views/fields/varchar',
    'custom:helpers/nit-input',
], function (Dep, NitInput) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (!this.isEditMode()) {
                return;
            }

            var self = this;
            var $input = this.$el.find('input.main-element');

            if (!$input.length) {
                $input = this.$el.find('input[data-name="' + this.name + '"]');
            }

            if ($input.val()) {
                $input.val(NitInput.format($input.val()));
            }

            NitInput.bindToInput($input, {
                onInvalid: function ($target) {
                    self.showValidationMessage(NitInput.INVALID_MESSAGE, $target);
                },
            });
        },

        getDetailStringValue: function () {
            var value = this.model.get(this.name);

            return value ? (NitInput.format(value) || value) : '';
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (data[this.name] != null && data[this.name] !== '') {
                data[this.name] = NitInput.format(data[this.name]);
            }

            return data;
        },
    });
});
