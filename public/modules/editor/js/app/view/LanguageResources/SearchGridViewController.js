
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.LanguageResources.SearchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.view.LanguageResources.SearchGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.languageResourceSearchGrid',
    listen: {
        component: {
            '#searchGridPanel textfield[name=source]': {
                keypress:'textFieldTextChange',
                change: 'onTextFieldChange'
            },
            '#searchGridPanel textfield[name=target]': {
                keypress:'textFieldTextChange',
                change: 'onTextFieldChange'
            },
            '#searchGridPanel button[name=btnSubmit]': {
                click:'handleSearchAll'
            },
            '#searchGridPanel button[name=btnSubmit] menuitem': {
                click:'handleSearchSingle',
                afterrender: 'renderSearchItem'
            },
            '#searchGridPanel':{
                render:'searchGridPanelRender',
            },
            '#searchGridPanel tableview':{
                scrollbottomreached: 'handleScrollBottomReached'
            }
        },
        controller:{
            '#ViewModes':{
                viewModeChanged:'viewModeChangeEvent'
            }
        }
    },
    refs:[{
        ref: 'resultTab',
        selector:'#searchResultTab'
    }],
    strings: {
        loading: '#UT#wird geladen...',
        noresults: '#UT#Es wurden keine Ergebnisse gefunden.',
        searchResultGridTitle: '#UT#{0} Ergebnisse',
        serverErrorMsgDefault: '#UT#Die Anfrage an die Sprachressource dauerte zu lange.',
        serverErrorMsg500: '#UT#Die Anfrage führte zu einem Fehler im angefragten Dienst.',
        serverErrorMsg502: '#UT#Es gibt Probleme mit dem angefragten Dienst.',
        serverErrorMsg408: '#UT#Die Anfrage an die Sprachressource dauerte zu lange.'
    },
    assocStore : null,
    executedRequests:new Ext.util.HashMap(),
    lastSearch: {
        query:'',
        languageResourceid: null,
        field: null
    },
    offset: new Ext.util.HashMap(),
    lastActiveField:null,
    SERVER_STATUS: null,//initialized after search grid panel is rendered
    searchGridPanelRender: function(){
        var me=this;
        me.assocStore = me.getView().assocStore; 
        me.SERVER_STATUS=Editor.model.LanguageResources.EditorQuery.prototype;
    },
    textFieldTextChange:function(field,event){
        var me=this;
        if (event.getKey() == event.ENTER){
            me.startSearch(field.value, field.name);
        }
        me.lastActiveField = field;
    },
    onTextFieldChange: function (field){
        var me = this;
        me.lastActiveField = field;
    },
    handleSearchAll:function(){
        var me=this;
        if(me.lastActiveField && me.lastActiveField.value!=""){
            me.startSearch(me.lastActiveField.value, me.lastActiveField.name);
        }
    },
    renderSearchItem: function(menuitem) {
        menuitem.el.select('.coloricon').first().setStyle({backgroundColor: '#'+menuitem.service.get('color')});
    },
    handleSearchSingle: function(menuitem) {
        var me=this;
        if(me.lastActiveField && me.lastActiveField.value!=""){
            me.startSearch(me.lastActiveField.value, me.lastActiveField.name, menuitem.service.get('languageResourceId'));
        }
    },
    /**
     * @param {String} querystring the string searched for in the TM
     * @param {String} field source / target
     * @param {Integer} languageResourceid optional, integer to restrict search to this languageResourceid, or falsy to search in all languageResources
     */
    startSearch: function(querystring, field, languageResourceid) {
        var me = this;
        if(!querystring) {
            return;
        }
        me.getViewModel().getStore('editorsearch').removeAll();
        me.lastSearch.query= querystring;
        me.lastSearch.field = field;
        //needed when searching only one languageResource, otherwise a falsy value
        me.lastSearch.languageResourceid = languageResourceid;
        me.resultsCounter = 0;
        me.search();
        me.clearTextField(field);
    },
    viewModeChangeEvent: function(controller){
        var me = this,
            tabPanel=me.getView().up('tabpanel');
        me.getView().getView().refresh();
        tabPanel.getActiveTab().getView().refresh()
    },
    handleScrollBottomReached: function(){
        var me = this;
        if(me.executedRequests.getCount() > 0) {
            return;
        }
        me.resultsCounter = 0;
        me.search(true);
    },
    /**
     * @param {Boolean} [resume] 
     */
    search:function(resume){
        var me=this;
        if(!me.assocStore){
            return;
        }
        me.abortAllRequests();
        if(!resume) {
            me.offset.clear();
        }
        me.assocStore.each(function(record){
            var searchable = record.get('searchable'),
                searchInTm = !me.lastSearch.languageResourceid || (record.get('languageResourceId') == me.lastSearch.languageResourceid);
            if(searchable && searchInTm){
                me.sendRequest(record.get('languageResourceId'));
            }
        });
    },
    sendRequest:function(languageResourceid){
        var me = this,
            offset = me.offset.get(languageResourceid),
            loadingId;

        //No more results for this LanguageResource
        if(offset === null) {
            return;
        }
        //this is the first call, so we have to send null as offset to the server
        if(offset === undefined) {
            offset = null;
        }


        loadingId = 'TM-'+languageResourceid+'-offset-'+offset;
        me.loadDataIntoGrid({rows: [{
            id: loadingId,
            source: me.strings.loading,
            target: me.strings.loading,
            languageResourceid: languageResourceid,
            state: me.SERVER_STATUS.SERVER_STATUS_LOADING
        }]}, true);

        me.executedRequests.add(languageResourceid,Ext.Ajax.request({
            url:Editor.data.restpath+'languageresourceinstance/'+languageResourceid+'/search',
                method: "POST",
                scope: this,
                params: {
                    query: me.lastSearch.query,
                    field: me.lastSearch.field,
                    offset: offset
                },
                success: function(response){
                    me.removeLoadingEntry(loadingId);
                    me.handleRequestSuccess(response, languageResourceid, me.lastSearch.query, offset);
                    me.executedRequests.removeAtKey(languageResourceid);
                    me.continueSearchToFillGrid();
                }, 
                failure: function(response){
                    me.removeLoadingEntry(loadingId);
                    me.handleRequestFailure(response, languageResourceid);
                    me.executedRequests.removeAtKey(languageResourceid);
                    me.continueSearchToFillGrid();
                }
        }));
    },
    continueSearchToFillGrid: function() {
        var view = this.getView().getView();
        if(this.executedRequests.getCount() > 0) {
            return;
        }
        if(this.resultsCounter < 20) {
            this.search(true);
        }
    },
    handleRequestSuccess: function(response, languageResourceid, query, offset){
        var me = this,
            resp = Ext.util.JSON.decode(response.responseText);

        if(resp.rows && resp.rows.length){            
            me.offset.add(languageResourceid, resp.nextOffset);
            me.resultsCounter += resp.rows.length;
            me.loadDataIntoGrid(resp);
            return;
        }

        //when its a resumed search (with offset) then we don't have to show the "noresult" entry
        if(offset) {
            me.offset.add(languageResourceid, resp.nextOffset);
            return;
        }

        //when displaying the noresults text, no more entries exist, set offset to null
        me.offset.add(languageResourceid, null);
        me.loadDataIntoGrid({rows: [{
            source: me.strings.noresults,
            languageResourceid: languageResourceid,
            state:  me.SERVER_STATUS.SERVER_STATUS_NORESULT
        }]});
    },
    removeLoadingEntry: function(loadingId) {
        var me = this,
            store = me.getView().getStore('editorsearch'),
            loader = store.getById(loadingId);

        //remove dummy loading entry
        if(loader){
            //must be silent, otherwise the grid scrolls back to the top
            store.remove(loader, false, true);
            me.getView().getView().refreshView();
        }
    },
    handleRequestFailure: function(response,languageResourceid){
        var me = this,
            errorEntry = {
                source: me.strings.serverErrorMsgDefault,
                target: '',
                languageResourceid: languageResourceid,
                state: me.SERVER_STATUS.SERVER_STATUS_SERVERERROR
            },
            errors = [errorEntry];
        //no more loadings in the case of an error
        me.offset.add(languageResourceid, null);
        switch (response.status) {
            case -1:
                errorEntry.source = me.strings.serverErrorMsgDefault;
                break;
            case 408:
                errorEntry.source = me.strings.serverErrorMsg408;
                errorEntry.state = me.SERVER_STATUS.SERVER_STATUS_CLIENTTIMEOUT;
                break;
            case 500:
            case 502:
                try {
                    var json = Ext.JSON.decode(response.responseText);
                } catch {
                    var json = {
                        errorMessage: 'No proper answer from the server <span style="font-size:9px;display:block;line-height:10px;">' + response?.request?.url + '</span>'
                    };
                    console.log('No proper answer from the server:' + response.responseText);
                }

                errorEntry.source = me.strings.serverErrorMsg500;
                errorEntry.target = json.errorMessage;
                break;
        }
        me.loadDataIntoGrid({rows: errors});
    },
    /**
     * @param {Object} resp contains the resultset to be loaded into the store
     * @param {Boolean} [scrollToBottom] if true, the grid scrolls to the bottom after adding the data
     */
    loadDataIntoGrid: function(resp, scrollToBottom) {
        var me = this,
            view = me.getView(),
            store = me.getViewModel().getStore('editorsearch');
        store.loadRawData(resp.rows,true);
        if(scrollToBottom) {
            view.ensureVisible(store.last());
        }
    },
    abortAllRequests:function(){
        var me=this;
        if(me.executedRequests && me.executedRequests.length>0){
            me.executedRequests.each(function(key, value, length){
                    me.executedRequests.get(key).abort();
            });
            me.executedRequests.clear();
        }
    },
    clearTextField:function(name){
        if(name=="source"){
            Ext.getCmp("targetSearch").setValue("");
            return;
        }
        Ext.getCmp("sourceSearch").setValue(""); 
    },

    /***
     * Set the active search field
     * @param field
     */
    setLastActiveField: function (field){
        this.lastActiveField = field;
    },

    /**
     * Highlight text in a string - wrap the text to highlight in a span with a class of 'highlight'
     *
     * @param {String} value
     * @param {String} textToHighlight
     *
     * @returns {String}
     */
    highlight: function (value, textToHighlight) {
        const root = this.stringToDom(value);

        const highlightSpan = `<span class="highlight">$&</span>`;
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

        return root.innerHTML;
    },

    /**
     * TODO This method should be removed when Richtexteditor refactoring is merged
     * Convert a template string into HTML DOM nodes
     * @param  {String} str The template string
     * @return {Node}       The template HTML
     */
    stringToDom: function(str) {
        const support = (function () {
            if (!window.DOMParser) {
                return false;
            }

            let parser = new DOMParser();

            try {
                parser.parseFromString('x', 'text/html');
            } catch (error) {
                return false;
            }

            return true;
        })();

        // If DOMParser is supported, use it
        if (support) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(str, 'text/html');

            return doc.body;
        }

        // Otherwise, fallback to old-school method
        let dom = document.createElement('div');
        dom.innerHTML = str;

        return dom;
    }
});
