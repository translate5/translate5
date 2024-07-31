Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    mixins: ['TMMaintenance.mixin.ErrorMessage'],

    listen: {
        global: {
            onApplicationLoad: 'onApplicationLoad',
        },
    },

    filterSourceLanguage: function (candidateRecord) {
        const value = Ext.ComponentQuery.query('searchform')[0].getValues().sourceLanguage;

        if (!value) {
            return true;
        }

        return candidateRecord.get('label').toLowerCase().includes(
            value.toLowerCase()
        );
    },

    filterTargetLanguage: function (candidateRecord) {
        const value = Ext.ComponentQuery.query('searchform')[0].getValues().targetLanguage;

        if (!value) {
            return true;
        }

        return candidateRecord.get('label').toLowerCase().includes(
            value.toLowerCase()
        );
    },

    onSelectTmPress: function () {
        this.getView().up('app-main').down('#selectTmDialog').show();
    },

    onDeleteBatchPress: function () {
        this.getView().up('app-main').down('#deleteBatchDialog').show();
    },

    onSearchFieldChange: function () {
        let values = this.getView().getValues();
        this.getViewModel().set('disabled', null === values.tm || null === values.searchField);
    },

    onSearchPress: function () {
        if(!this.getView().isValid()) {
            return;
        }

        this.getView().up('app-main').controller.cancelEditing();

        const mainList = Ext.getCmp('mainlist');
        const strings = this.getStrings();
        mainList.setTitle(strings.title + ' - ' + strings.totalAmount +' ' + strings.calculating);

        const values = this.getView().getValues();
        const store = mainList.store;

        store.removeAll();

        this.getViewModel().set('selectedTm', values.tm);
        this.getViewModel().set('lastOffset', null);
        this.loadPageByChunks(20,1, false, true);
        this.updateUrl(values);
    },

    onApplicationLoad: function () {
        const urlParams = this.parseUrlParams();

        if (!!urlParams.tm) {
            this.getViewModel().set('selectedTm', urlParams.tm);
        }

        Ext.defer(() => {
            this.getView().setValues(urlParams);

            if (!this.getView().down('[name=search]').isDisabled()) {
                this.getView().down('[name=search]').buttonElement.dom.click();
            }
        }, 500);
    },

    onDeleteBatch: function () {
        const me = this;
        const dialog = me.getView().up('app-main').down('#deleteBatchDialog');
        dialog.mask();

        Ext.Ajax.request({
            url: '/editor/plugins_tmmaintenance_api/delete-batch/',
            params: this.getView().getValues(),
            async: false,
            method: 'POST',
            success: function (xhr) {
                const mainList = Ext.getCmp('mainlist');
                mainList.setTitle(me.getStrings().title);
                mainList.store.removeAll();
                dialog.unmask();
                dialog.hide();
            },
            error: function (xhr) {
                dialog.hide();
                me.showServerError(JSON.parse(xhr.responseText));
                console.log('Error deleting batch');
                console.log(xhr);
            },
            failure: function (xhr) {
                dialog.hide();
                me.showServerError(JSON.parse(xhr.responseText));
                console.log('Error deleting batch');
                console.log(xhr);
            }
        });
    },

    readTotalAmount: function () {
        Ext.Ajax.request({
            url: '/editor/plugins_tmmaintenance_api/read-amount/',
            params: {...this.getView().getValues(), onlyCount: true},
            async: true,
            method: 'POST',
            success: (xhr) => {
                const data = JSON.parse(xhr.responseText);
                const strings = this.getStrings();
                Ext.getCmp('mainlist').setTitle(strings.title + ' - ' + strings.totalAmount + ' ' + data.totalAmount);
            },
            error: (xhr) => {
                console.log('Error reading total amount');
                console.log(xhr);
            },
            failure: (xhr) => {
                console.log('Error reading total amount');
                console.log(xhr);
            }
        });
    },

    /**
     * @param {string} value
     * @returns {boolean|string}
     */
    validateSearchField: function (value) {
        return this.getSearchFieldValues().includes(value) ? true : 'Invalid value';
    },

    /**
     * @param {string} value
     * @returns {boolean|string}
     */
    validateTmField: function (value) {
        return this.getTmValues().includes(value.toString()) ? true : 'Invalid value';
    },

    /**
     * @returns {Object}
     */
    parseUrlParams: function () {
        return this.stringToObject(window.location.hash.slice(1));
    },

    /**
     * @param {Object} values
     */
    updateUrl: function (values) {
        window.location.hash = this.objectToStringEncoded(values);
    },

    /**
     * @returns {string[]}
     */
    getSearchFieldValues: function () {
        return this.getView().getFields().searchField.getStore().getData().items.map(function (item) {
            return item.data.value;
        });
    },

    /**
     * @returns {string[]}
     */
    getTmValues: function () {
        return Ext.ComponentQuery.query('#selecttm')[0].store.getData().items.map(function (item) {
            return item.id;
        });
    },

    getTmNameById: function (id) {
        const tm = Ext.ComponentQuery.query('#selecttm')[0].store.getById(id);
        return tm ? tm.data.name : null;
    },

    objectToStringEncoded: function (object) {
        const keyValuePairs = Object.entries(object).map(
            ([key, value]) => {
                if (value instanceof Date) {
                    value = value.toISOString();
                }

                return `${encodeURIComponent(key)}=${encodeURIComponent(value ?? '')}`;
            }
        );

        return keyValuePairs.join('/');
    },

    stringToObject: function (str) {
        const object = {};
        const dateFields = ['creationDateFrom', 'creationDateTo'];

        str.split('/').forEach(pair => {
            const [key, value] = pair.split('=');
            const decodedKey = decodeURIComponent(key);
            const decodedValue = decodeURIComponent(value);

            if (dateFields.includes(decodedKey) && decodedValue !== '') {
                object[decodedKey] = new Date(decodedValue);
            } else {
                object[decodedKey] = decodedValue;
            }
        });

        return object;
    },

    getStrings: function () {
        return this.getViewModel().get('l10n').list;
    }
}, function() {
    this.borrow(TMMaintenance.view.main.MainController, ['loadPageByChunks']);
});
