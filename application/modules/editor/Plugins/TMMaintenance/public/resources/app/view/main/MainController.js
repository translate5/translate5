Ext.define('TMMaintenance.view.main.MainController', {
    extend: 'Ext.app.ViewController',

    alias: 'controller.main',

    searchForm: null,
    gridList: null,

    mixins: ['TMMaintenance.mixin.ErrorMessage'],

    listen: {
        global: {
            onApplicationLoad: 'onApplicationLoad'
        }
    },

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

    onApplicationLoad: function () {
        this.loadData();
    },

    loadData: function (newLocale = null) {
        let me = this;
        // Setup default ajax headers
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json',
            'csrfToken': window.csrfToken,
        });

        let url = '/editor/plugins_tmmaintenance_api/data';
        if (newLocale) {
            url += '?locale=' + newLocale
        }

        Ext.Ajax.request({
            url: url,
            async: false,
            method: 'GET',
            success: function (xhr) {
                let data = Ext.JSON.decode(xhr.responseText, true);

                if (!(data)) {
                    // TODO show an error
                    return;
                }

                // Load locale file given by extjs
                Ext.Loader.loadScriptsSync(['/editor/plugins/resources/TMMaintenance/locales/' + data.locale + '.js']);

                data.uiLocale = data.locale;
                me.getViewModel().setData(data);

                const localeField = me.getView().down('[reference=locale]');
                localeField.skipChangeHandler = true;
                localeField.setValue(data.locale + '');
                delete localeField.skipChangeHandler;
            }
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

    onCreatePress: function (button) {
        let view = button.up('mainlist');
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
        const vm = this.getViewModel(),
            l10n = vm.data.l10n;

        Ext.Msg.confirm(
            l10n.deleteSegment.title,
            l10n.deleteSegment.text,
            (buttonPressed) => {
                if ('no' === buttonPressed) {
                    return;
                }

                gridLocation.record.set({tm: vm.get('selectedTm')});
                gridLocation.record.erase({
                    success: () => {
                        if (vm.get('loadingRecordNumber') === false) {
                            vm.set('totalAmount', vm.get('totalAmount') - 1);
                        }
                    },
                    failure: (record, operation) => {
                        this.showServerError(operation.getError());
                    },
                });
            },
            this
        );
    },

    onDeleteBy: function(grid, location) {
        const vm = this.getViewModel(),
            l10n = vm.data.l10n,
            type = location.tool.type,
            store = location.grid.getStore(),
            rec = location.record,
            src = rec.get('source'),
            trg = rec.get('target'),
            emo = '<em style="font-weight: 700;">',
            emc = '</em>';

        const normalizeText = (value) => {
            if (!value) {
                return value;
            }

            // Parse the HTML and replace special tag divs with the span.short title wrapped in angle brackets
            const container = document.createElement('div');
            container.innerHTML = value;

            const candidates = container.querySelectorAll('div.open, div.close, div.single');
            candidates.forEach(div => {
                const short = div.querySelector('span.short[title]');
                if (!short) {
                    return;
                }

                const title = short.getAttribute('title') || '';
                // Create a text node with <title> so when serialized via innerHTML it becomes &lt;title&gt;
                const replacement = document.createTextNode(`##${title}##`);
                if (div.parentNode) {
                    div.parentNode.replaceChild(replacement, div);
                }
            });

            return container.innerHTML;
        };

        const normalizedSrc = normalizeText(src);
        const normalizedTrg = normalizeText(trg);

        var queryFn, title, message;

        if (type === 'same-source') {
            title = l10n.deleteSegment.bySource.title;
            message = Ext.String.format(l10n.deleteSegment.bySource.text, emo + src + emc);
            queryFn = rec => normalizeText(rec.get('source')) === normalizedSrc;
        } else {
            title = l10n.deleteSegment.bySourceAndTarget.title;
            message = Ext.String.format(l10n.deleteSegment.bySourceAndTarget.text, emo + src + emc, emo + trg + emc)
            queryFn = rec => normalizeText(rec.get('source')) === normalizedSrc && normalizeText(rec.get('target')) === normalizedTrg;
        }

        // Show confirmation prompt
        Ext.Msg.confirm(title, message, proceed => {

            // If deletion cancelled - do nothing
            if ('no' === proceed) {
                return;
            }

            // Else do deletion
            Ext.Ajax.request({
                method: 'POST',
                url: '/editor/plugins_tmmaintenance_api/delete-similar',
                params: {
                    data: JSON.stringify({
                        tm: vm.get('selectedTm'),
                        type: type,
                        source: src,
                        target: trg,
                        metaData: rec.get('metaData')
                    })
                },
                success: xhr => {
                    store.queryBy(queryFn).each(rec => store.remove(rec));
                },
                failure: xhr => {
                    this.showServerError(Ext.JSON.decode(xhr.responseText, true))
                }
            });
        });
    },

    onEditSource: function(grid, gridLocation) {
        this.startEdit(gridLocation.record, 'source');
    },

    onEditTarget: function(grid, gridLocation) {
        this.startEdit(gridLocation.record, 'target');
    },

    startEditing: function (column) {
        let editingPlugin = this.getEditingPlugin();

        if (editingPlugin.editing) {
            return;
        }

        let grid = this.getListGrid();

        if (!grid.getLastSelected()) {
            return;
        }

        this.startEdit(grid.getLastSelected(), column);
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

    startEdit: function (record, column) {
        let grid = this.getListGrid();
        let editingPlugin = this.getEditingPlugin();
        column = column || 'target';

        editingPlugin.startEdit(record, grid.down('[dataIndex=' + column + ']'));

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
