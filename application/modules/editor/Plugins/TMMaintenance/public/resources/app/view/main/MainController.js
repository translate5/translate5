Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    searchForm: null,

    onContainerScrollEnd: function () {
        if (!this.getViewModel().get('hasMoreRecords')) {
            return;
        }

        let values = Ext.getCmp('searchform').getValues();
        let store = Ext.getCmp('mainlist').store;
        let me = this;

        store.load({
            params: {
                tm: values.tm,
                searchField: values.searchField,
                searchCriteria: values.searchCriteria,
                offset: this.getViewModel().get('lastOffset'),
            },
            addRecords: true,
            callback: function(records, operation) {
                let offset = operation.getProxy().getReader().metaData.offset;

                me.getViewModel().set('lastOffset', offset);
                me.getViewModel().set('hasMoreRecords', null !== offset);
            },
        });
    },

    sourceTargetRenderer: function (value, record, cell) {
        let tagHelper = Ext.create('TMMaintenance.helper.Tag');
        let result = tagHelper.transform(value);

        if (this.getSearchForm().getSearchFieldValue() !== cell) {
            return result;
        }

        return tagHelper.highlight(result, this.getSearchForm().getSearchCriteriaValue());
    },

    onCreatePress: function () {
        let view = this.getView();
        let dialog = this.getViewModel().get('dialog');

        if (!dialog) {
            dialog = Ext.apply({
                ownerCmp: view
            }, view.dialog);

            dialog = Ext.create(dialog);

            this.getViewModel().set('dialog', dialog);
        }

        dialog.show();
    },

    /**
     * @param {TMMaintenance.view.main.List} grid
     * @param {Ext.dataview.Location} gridLocation
     */
    onDeletePress: function (grid, gridLocation) {
        Ext.Msg.confirm(
            'Confirm',
            'Do you really want to delete a segment?',
            (buttonPressed) => {
                if ('no' === buttonPressed) {
                    return;
                }

                gridLocation.record.set({tm: this.getViewModel().get('selectedTm')});
                gridLocation.record.erase({
                    success: () => {
                        // TODO what to do here?
                    }
                });
            },
            this
        );
    },

    /**
     * @param {TMMaintenance.view.main.List} grid
     * @param {Ext.dataview.Location} gridLocation
     */
    onEditPress: function (grid, gridLocation) {
        let celledit = grid.getPlugin('cellediting');
        celledit.startEdit(gridLocation.record, grid.down('[dataIndex=target]'));

        Ext.defer(function () {
            let editor = celledit.getActiveEditor().getEditor();
            editor.focus();
        }, 200);
    },

    hideForm: function () {
        this.getViewModel().get('dialog').hide();
    },

    getSearchForm: function () {
        if (null === this.searchForm) {
            this.searchForm = Ext.getCmp('searchform');
        }

        return this.searchForm;
    },
});
