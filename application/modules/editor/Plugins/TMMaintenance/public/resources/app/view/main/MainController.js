Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    searchForm: null,
    gridList: null,

    mixins: ['TMMaintenance.mixin.ErrorMessage'],

    init: function() {
        const keymap = {
            'escape': [Ext.event.Event.ESC, this.cancelEditing],
            'f2': [Ext.event.Event.F2, this.startEditing],
            'ctrl+s': [Ext.event.Event.S, this.saveCurrent, {ctrl: true, alt: false}],
            'ctrl+enter': [Ext.event.Event.ENTER, this.saveCurrentGoToNext, {ctrl: true, shift: false}],
            'ctrl+shift+enter': [Ext.event.Event.ENTER, this.saveCurrentGoToPrevious, {ctrl: true, shift: true}],
            'ctrl+alt+up': [Ext.event.Event.UP, this.goToPrevious, {ctrl: true, alt: true}],
            'ctrl+alt+down': [Ext.event.Event.DOWN, this.goToNext, {ctrl: true, alt: true}],
        };

        let bindings = [];

        for (const [key, item] of Object.entries(keymap)) {
            bindings.push({
                ...(item[2] || {}),
                key: item[0],
                handler: item[1],
                scope: item[3] || this,
            });
        }

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
                ...values,
                offset: this.getViewModel().get('lastOffset'),
            },
            addRecords: true,
            callback: function(records, operation, success) {
                if (!success) {
                    me.showServerError(operation.getError());
                    console.log('Error loading store');

                    return;
                }

                let offset = operation.getProxy().getReader().metaData.offset;

                me.getViewModel().set('lastOffset', offset);
                me.getViewModel().set('hasMoreRecords', null !== offset);
            },
        });
    },

    sourceTargetRenderer: function (value, record, cell, gridcell, gridcolumn) {
        gridcell.element.dom.removeAttribute('data-qoverflow');
        gridcell.element.dom.childNodes[0].removeAttribute('data-qoverflow');

        const entered = this.getSearchForm().getValues()[cell];

        if (entered === '') {
            return value;
        }

        const root = RichTextEditor.stringToDom(value);
        this.highlight(root, entered);

        return root.innerHTML;
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
        const l10n = this.getViewModel().data.l10n;

        Ext.Msg.confirm(
            l10n.deleteSegment.title,
            l10n.deleteSegment.text,
            (buttonPressed) => {
                if ('no' === buttonPressed) {
                    return;
                }

                gridLocation.record.set({tm: this.getViewModel().get('selectedTm')});
                gridLocation.record.erase({
                    success: () => {
                        // TODO what to do here?
                    },
                    failure: (record, operation) => {
                        this.showServerError(operation.getError());
                    },
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

    saveCurrent: function(keyCode, event) {
        let editingPlugin = this.getEditingPlugin();

        if (!editingPlugin.editing) {
            return;
        }

        if (event && !event.record) {
            event.preventDefault();
            event.stopPropagation();
        }

        editingPlugin.getActiveEditor().completeEdit();

        return false;
    },

    saveCurrentGoToNext: function () {
        let currentEditingRecord = this.getEditingPlugin().getActiveEditor().currentEditingRecord;
        if (!currentEditingRecord) {
            return;
        }

        this.saveCurrent();

        if (this.getEditingPlugin().editing) {
            return;
        }

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

        if (this.getEditingPlugin().editing) {
            return;
        }

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
            editingPlugin.getActiveEditor().focus();
        }, 200);
    },

    highlight: function (root, textToHighlight) {
        const highlightSpan = `<span class="highlight" style="background-color: yellow;">$&</span>`;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        const textNodes = [];
        let node;

        // Collect text nodes
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        // Modify text nodes
        textNodes.forEach(node => {
            const regex = new RegExp(textToHighlight, 'gi'); // Word boundary regex to match whole words only
            if (regex.test(node.nodeValue)) {
                const newNode = document.createElement('span');
                newNode.innerHTML = node.nodeValue.replace(regex, highlightSpan);
                node.parentNode.replaceChild(newNode, node);
            }
        });
    }
});
