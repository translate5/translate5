Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    listen: {
        global: {
            onApplicationLoad: 'onApplicationLoad',
        },
    },

    onSelectTmPress: function () {
        this.getView().up('app-main').down('#selectTmDialog').show();
    },

    onSearchFieldChange: function () {
        let values = this.getView().getValues();
        this.getViewModel().set('disabled', null === values.tm || null === values.searchField);
    },

    onSearchPress: function () {
        if(!this.getView().isValid()) {
            return;
        }

        const values = this.getView().getValues();
        const store = Ext.getCmp('mainlist').store;
        const me = this;

        this.getViewModel().set('selectedTm', values.tm);
        store.load({
            params: values,
            callback: function(records, operation, success) {
                if (!success) {
                    // TODO show error
                    console.log('Error loading store');

                    return;
                }

                let offset = operation.getProxy().getReader().metaData.offset;

                me.getViewModel().set('lastOffset', offset);
                me.getViewModel().set('hasMoreRecords', null !== offset);
            },
        });

        this.updateUrl(values);
    },

    onApplicationLoad: function () {
        const store = Ext.ComponentQuery.query('#selecttm')[0].store;
        const me = this;

        store.load({
            callback: (records, operation, success) => {
                if (!success) {
                    // TODO show error
                    console.log('Error loading store');

                    return;
                }

                me.getView().setValues(me.parseUrlParams());
                Ext.defer(function () {
                    if (!me.getView().down('[name=search]').isDisabled()) {
                        me.getView().down('[name=search]').buttonElement.dom.click();
                    }
                }, 500);
            },
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
        return this.getTmValues().includes(value) ? true : 'Invalid value';
    },

    /**
     * @returns {{searchField: (string|null), searchCriteria: (string|null), tm: (string|null)}}
     */
    parseUrlParams: function () {
        debugger;
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
});
