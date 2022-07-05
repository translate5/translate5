Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    searchForm: null,
    gridList: null,

    init: function() {
        let me = this;

        const keymap = {
            'escape':           [Ext.event.Event.ESC,   this.cancelEditing],
            'f2':               [Ext.event.Event.F2,    this.startEditing],
            'ctrl+s':           [Ext.event.Event.S,     this.saveCurrent,               {ctrl: true}],
            'ctrl+enter':       [Ext.event.Event.ENTER, this.saveCurrentGoToNext,       {ctrl: true, shift: false}],
            'ctrl+shift+enter': [Ext.event.Event.ENTER, this.saveCurrentGoToPrevious,   {ctrl: true, shift: true}],
            'ctrl+alt+up':      [Ext.event.Event.UP,    this.goToPrevious,              {ctrl: true, alt: true}],
            'ctrl+alt+down':    [Ext.event.Event.DOWN,  this.goToNext,                  {ctrl: true, alt: true}],
        };

        let bindings = [];

        Ext.Object.each(keymap, function (key, item) {
            item
            bindings.push(Ext.applyIf(item[2] || {}, {
                key: item[0],
                handler: item[1],
                scope: item[3] || me,
            }));
        });

        new Ext.util.KeyMap({
            target: this.getListGrid().element,
            binding: bindings,
        });
    },

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
        this.startEdit(gridLocation.record);
    },

    startEditing: function () {
        let editingPlugin = this.getEditingPlugin();

        if (editingPlugin.editing) {
            return;
        }

        let grid = this.getListGrid();

        if (!grid.getLastSelected()) {
            return;
        }

        this.startEdit(grid.getLastSelected());
    },

    cancelEditing: function () {
        let editingPlugin = this.getEditingPlugin();

        if (!editingPlugin.editing) {
            return;
        }

        editingPlugin.getActiveEditor().cancelEdit();
    },

    saveCurrent: function() {
        let editingPlugin = this.getEditingPlugin();

        if (!editingPlugin.editing) {
            return;
        }

        editingPlugin.getActiveEditor().completeEdit();
    },

    saveCurrentGoToNext: function () {
        let currentEditingRecord = this.getEditingPlugin().getActiveEditor().currentEditingRecord;
        if (!currentEditingRecord) {
            return;
        }

        this.saveCurrent();

        let store = this.getListGrid().getStore();
        let nextEditingIndex = store.indexOf(currentEditingRecord) + 1;
        if (!store.getAt(nextEditingIndex)) {
            return;
        }

        this.startEdit(store.getAt(nextEditingIndex));
    },

    saveCurrentGoToPrevious: function () {
        let currentEditingRecord = this.getEditingPlugin().getActiveEditor().currentEditingRecord;
        if (!currentEditingRecord) {
            return;
        }

        this.saveCurrent();

        let store = this.getListGrid().getStore();
        let nextEditingIndex = store.indexOf(currentEditingRecord) - 1;
        if (!store.getAt(nextEditingIndex)) {
            return;
        }

        this.startEdit(store.getAt(nextEditingIndex));
    },

    goToNext: function () {
        let currentEditingRecord = this.getEditingPlugin().getActiveEditor().currentEditingRecord;
        if (!currentEditingRecord) {
            return;
        }

        this.cancelEditing();

        let store = this.getListGrid().getStore();
        let nextEditingIndex = store.indexOf(currentEditingRecord) + 1;
        if (!store.getAt(nextEditingIndex)) {
            return;
        }

        this.startEdit(store.getAt(nextEditingIndex));
    },

    goToPrevious: function () {
        let currentEditingRecord = this.getEditingPlugin().getActiveEditor().currentEditingRecord;
        if (!currentEditingRecord) {
            return;
        }

        this.cancelEditing();

        let store = this.getListGrid().getStore();
        let nextEditingIndex = store.indexOf(currentEditingRecord) - 1;
        if (!store.getAt(nextEditingIndex)) {
            return;
        }

        this.startEdit(store.getAt(nextEditingIndex));
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

    /**
     * @returns {TMMaintenance.view.main.List}
     */
    getListGrid: function () {
        if (null === this.gridList) {
            this.gridList = Ext.getCmp('mainlist');
        }

        return this.gridList;
    },

    getEditingPlugin: function () {
        return this.getListGrid().getPlugin('cellediting');
    },

    startEdit: function (record) {
        let grid = this.getListGrid();
        let editingPlugin = this.getEditingPlugin();

        editingPlugin.startEdit(record, grid.down('[dataIndex=target]'));

        Ext.defer(function () {
            let editor = editingPlugin.getActiveEditor().getEditor();
            editor.focus();
        }, 200);
    },
});
