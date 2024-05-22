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
        const urlParams = window.location.hash.slice(1).split('/');

        return {
            tm: urlParams[0] !== undefined ? urlParams[0] : null,
            searchField: urlParams[1] !== undefined ? urlParams[1] : null,
            searchCriteria: urlParams[2] !== undefined ? decodeURI(urlParams[2]) : null,
        };
    },

    /**
     * @param {Object} values
     */
    updateUrl: function (values) {
        window.location.hash = values.tm + '/' + values.searchField + '/' + encodeURI(values.searchCriteria);
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
    }
});
