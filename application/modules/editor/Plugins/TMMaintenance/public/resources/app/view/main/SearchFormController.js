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

        const values = this.getView().getValues();
        const store = mainList.store;

        store.removeAll();

        this.getViewModel().set('selectedTm', values.tm);
        this.getViewModel().set('lastOffset', null);
        this.getViewModel().set('totalAmount', null);
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
            params: {data: JSON.stringify(this.getView().getValues())},
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
            params: {data: JSON.stringify({...this.getView().getValues(), onlyCount: true})},
            async: true,
            method: 'POST',
            timeout: 900000,
            success: xhr => {
                this.getViewModel().set('totalAmount', JSON.parse(xhr.responseText).totalAmount);
            },
            error: xhr => {
                console.log('Error reading total amount');
                console.log(xhr);
            },
            failure: xhr => {
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
    },

    loadPageByChunks: function(pageSize, chunkSize, append, abortPrev) {
        let me = this,
            vm = this.getViewModel(),
            view = Ext.getCmp('mainlist'),
            store = view.getStore(),
            values = Ext.ComponentQuery.query('searchform').pop().getValues(),
            offset = me.getViewModel().get('lastOffset'),
            loadingId = 'TM-offset-' + offset;

        if (abortPrev || !append) {
            vm.set('loadedQty', 0);
        }

        if (abortPrev) {
            store.getProxy().abortByPurpose = true;
            store.getProxy().abort();
        }

        // Add dummy loading entry
        store.loadRawData([{
            id: loadingId,
            metaData: {
                internalKey: null,
                sourceLang: '',
                targetLang: '',
                author: '',
                timestamp: '',
                documentName: '',
                additionalInfo: ''
            },
            source: vm.get('l10n.list.loadingSegmentPlaceholder'),
            target: vm.get('l10n.list.loadingSegmentPlaceholder'),
        }], true);

        //
        vm.set('loadingRecordNumber', store.getCount());

        if (vm.get('loadedQty') === 0) {
            view.getScrollable().suspendEvent('scrollend');
            view.ensureVisible(store.last());
            setTimeout(() => view.getScrollable().resumeEvent('scrollend'), 100);
        }

        store.load({
            params: {data: JSON.stringify({...values, offset: offset})},
            limit: chunkSize,
            addRecords: append,
            callback: (records, operation, success) => {
                var loaderRec = store.getById(loadingId);

                // Remove dummy loading entry
                if (loaderRec) {
                    store.remove(loaderRec, false, false);
                }

                if (!success) {

                    if (operation.getError().statusText !== 'transaction aborted' || !operation.getProxy().abortByPurpose) {
                        me.showServerError(operation.getError());
                    }
                    if (operation.getProxy().abortByPurpose) {
                        delete operation.getProxy().abortByPurpose;
                    }

                    return;
                }

                const offset = operation.getProxy().getReader().metaData.offset;
                vm.set('loadedQty', vm.get('loadedQty') + records.length);

                vm.set('lastOffset', offset);
                vm.set('hasMoreRecords', null !== offset);

                if (store.getCount() === pageSize) {
                    vm.set('hasRecords', records.length > 0);
                    vm.set('loadingTotalAmount', true);
                    me.readTotalAmount();
                } else {
                    me.getViewModel().set('loadingTotalAmount', false);
                }
                if (null !== offset && vm.get('loadedQty') < pageSize) {
                    me.loadPageByChunks(pageSize, 1,true);
                } else {
                    vm.set('loadingRecordNumber', false);
                }
            },
        });
    },

    onContainerScrollEnd: function () {
        var me = this;

        if (!me.getViewModel().get('hasMoreRecords')) {
            return;
        }

        clearTimeout(me.loadPageByChunksTimeout);
        me.loadPageByChunksTimeout = setTimeout(function(){
            me.loadPageByChunks(20,1, true, true);
        }, 100);
    },
});
