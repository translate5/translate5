Ext.define('TMMaintenance.view.main.SearchFormController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.searchform',

    listen: {
        global: {
            onApplicationLoad: 'onApplicationLoad',
        },
    },

    onTMChange: function () {
        let values = this.getView().getValues();
        let viewModel = this.getViewModel();
        viewModel.set('disabled', null === values.tm || null === values.searchField);
    },

    onSearchFieldChange: function () {
        let values = this.getView().getValues();
        this.getViewModel().set('disabled', null === values.tm || null === values.searchField);
    },

    onSearchPress: function () {
        if(!this.getView().isValid()) {
            return;
        }

        let values = this.getView().getValues();
        let store = Ext.getCmp('mainlist').store;
        let me = this;

        this.getViewModel().set('selectedTm', values.tm);
        store.load({
            params: values,
            callback: function(records, operation) {
                let offset = operation.getProxy().getReader().metaData.offset;

                me.getViewModel().set('lastOffset', offset);
                me.getViewModel().set('hasMoreRecords', null !== offset);
            },
        });

        this.updateUrl(values);
    },

    onApplicationLoad: function () {
        let me = this;
        // Setup default ajax headers
        Ext.Ajax.setDefaultHeaders({
            'Accept': 'application/json'
        });

        Ext.Ajax.request({
            url: '/editor/plugins_tmmaintenance_api/tm/list',
            async: false,
            method: 'GET',
            success: function (xhr) {
                let data = Ext.JSON.decode(xhr.responseText, true);

                if (!(data)) {
                    // TODO show an error
                    return;
                }

                me.getViewModel().setData({tms: data});
                me.getView().setValues(me.parseUrlParams());

                Ext.defer(function () {
                    if (!me.getView().down('[name=search]').isDisabled()) {
                        me.getView().down('[name=search]').buttonElement.dom.click();
                    }
                }, 500);
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
        return this.getViewModel().getData().tms.map(function (item) {
            return item.value;
        });
    },
});
