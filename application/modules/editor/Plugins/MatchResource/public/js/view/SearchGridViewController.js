
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.plugins.MatchResource.view.SearchGridViewController
 * @extends Ext.app.ViewController
 */
Ext.define('Editor.plugins.MatchResource.view.SearchGridViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchResourceSearchGrid',
    listen: {
        component: {
            '#searchGridPanel textfield[name=source]': {
                keypress:'sourceSearchTextChange'
            },
            '#searchGridPanel textfield[name=target]': {
                keypress:'targetSearchTextChange'
            },
            '#searchGridPanel button[name=btnSubmit]': {
                click:'handleSubmitButtonClick'
            },
            '#searchGridPanel':{
                render:'searchGridPanelRender'
            },
            '#searchGridPanel actioncolumn':{
                click:'handleMoreColumnClick'
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
        serverErrorMsgDefault: '#UT#Die Anfrage an die Matchressource dauerte zu lange.',
        serverErrorMsg500: '#UT#Die Anfrage führte zu einem Fehler im angefragten Dienst.',
        serverErrorMsg408: '#UT#Die Anfrage an die Matchressource dauerte zu lange.'
    },
    assocStore : null,
    executedRequests:[],
    lastSearch: {
        query:'',
        field:null
    },
    lastActiveField:null,
    SERVER_STATUS: null,//initialized after search grid panel is rendered
    RESULTS_ROW_LIMIT: 5,//the limit of result rows for each 'tmmt'
    searchGridPanelRender: function(){
        var me=this;
        me.assocStore = me.getView().assocStore; 
        me.SERVER_STATUS=Editor.plugins.MatchResource.model.EditorQuery.prototype;
    },
    sourceSearchTextChange:function(field,event){
        var me=this;
        me.enterKeyPres(field, event);
        me.lastActiveField = field;
    },
    targetSearchTextChange:function(field,event){
        var me=this;
        me.enterKeyPres(field, event);
        me.lastActiveField = field;
    },
    enterKeyPres:function(field,event){
        var me=this;
        if (event.getKey() == event.ENTER){
            me.getViewModel().getStore('editorsearch').removeAll();
            me.lastSearch.query= field.value;
            me.lastSearch.field = field.name;
            me.closeTabs();
            me.search();
            me.clearTextField(me.lastSearch.field);
        }
    },
    handleSubmitButtonClick:function(){
        var me=this;
        if(me.lastActiveField && me.lastActiveField.value!=""){
            me.getViewModel().getStore('editorsearch').removeAll();
            me.lastSearch.query= me.lastActiveField.value;
            me.lastSearch.field = me.lastActiveField.name;
            me.closeTabs();
            me.search();
            me.clearTextField(me.lastActiveField.name);
        }
    },
    viewModeChangeEvent: function(controller){
        var me = this,
            tabPanel=me.getView().up('tabpanel')
        //isViewMode
        //isErgonomicMode
        //isEditMode
        me.getView().getView().refresh();
        tabPanel.getActiveTab().getView().refresh()
    },
    search:function(){
        var me=this;
        if(me.assocStore){
            me.abortAllRequests();
            me.assocStore.each(function(record){
                me.sendRequest(record.get('id'));
            });
        }
    },
    sendRequest:function(tmmtid){
        var me = this;
        me.executedRequests.push(Ext.Ajax.request({
            url:Editor.data.restpath+'plugins_matchresource_tmmt/'+tmmtid+'/search',
                method: "POST",
                params: {
                    query: me.lastSearch.query,
                    field: me.lastSearch.field,
                    limit:me.RESULTS_ROW_LIMIT + 1
                },
                success: function(response){
                    me.handleRequestSuccess(me, response,tmmtid, me.lastSearch.query);
                }, 
                failure: function(response){
                    me.handleRequestFailure(me, response, tmmtid);
                }
        }));
    },
    handleRequestSuccess: function(controller,response,tmmtid,query){
        var me = controller,
            resp = Ext.util.JSON.decode(response.responseText);
        
        //me.getView().getStore('editorsearch').remove(me.getView().getStore('editorsearch').findRecord('tmmtid',tmmtid));
        if(typeof resp.rows !== 'undefined' && resp.rows !== null && resp.rows.length){
            me.loadDataIntoGrid(resp);
            return;
        }
        var noresults = {
                rows: [{
                    source: me.strings.noresults,
                    tmmtid: tmmtid,
                    state:  me.SERVER_STATUS.SERVER_STATUS_NORESULT
                }]
            };
        me.loadDataIntoGrid(noresults);
    },
    handleRequestFailure: function(controller,response,tmmtid){
        var me = controller,
            respStatusMsg = me.strings.serverErrorMsgDefault,
            strState =  me.SERVER_STATUS.SERVER_STATUS_SERVERERROR,
            timeOut = null;


        switch(response.status){
            case -1:
                respStatusMsg = me.strings.serverErrorMsgDefault;
                break;
            case 408:
                respStatusMsg = me.strings.serverErrorMsg408;
                strState = me.SERVER_STATUS.SERVER_STATUS_CLIENTTIMEOUT;
                break;
            case 500:
                respStatusMsg = me.strings.serverErrorMsg500;
                break;
        }
        timeOut={
                rows: [{
                    source: respStatusMsg,
                    target: '',
                    tmmtid: tmmtid,
                    state: strState
                }]
            };
        me.loadDataIntoGrid(timeOut);
    },
    loadDataIntoGrid: function(resp) {
        var me = this;
        if(resp.rows && resp.rows.length > me.RESULTS_ROW_LIMIT){
            resp.rows.splice(resp.rows.length-1,1);
            resp.rows[resp.rows.length-1].showMoreIcon= true;
        }
        me.getViewModel().getStore('editorsearch').loadRawData(resp.rows,true);
    },
    handleMoreColumnClick: function(view, item, colIndex, rowIndex, e, record, row){
        var me = this,
            tabPanel=me.getView().up('tabpanel'),
            tmmtname =me.assocStore.findRecord('id',record.get('tmmtid')).get('name');
        
        if(!record.get('showMoreIcon')){
            return;
        }
        if(tabPanel.down('#searchResultTab-'+record.get('tmmtid'))){ //provide the tmmtid
            tabPanel.setActiveTab(tabPanel.down('#searchResultTab-'+record.get('tmmtid')));
            return;
        }
        tabPanel.add({
            xtype:'matchResourceSearchResultGrid',
            assocStore:me.assocStore,
            title: tmmtname+" "+"Ergebnisse",
            closable :true,
            hidden: false,
            itemId: 'searchResultTab-'+record.get('tmmtid'),
            query: me.lastSearch.query,
            field: me.lastSearch.field,
            tmmtid:record.get('tmmtid')
        });
        tabPanel.setActiveTab(tabPanel.down('#searchResultTab-'+record.get('tmmtid')));
    },
    abortAllRequests:function(){
        var me=this;
        if(me.executedRequests && me.executedRequests.length>0){
            for(var i=0;i<me.executedRequests.length;i++){
                console.log("Request aborted!");
            }
            me.executedRequests=[];
        }
    },
    clearTextField:function(name){
        if(name=="source"){
            Ext.getCmp("targetSearch").setValue("");
            return;
        }
        Ext.getCmp("sourceSearch").setValue(""); 
    },
    closeTabs:function(){
        var me=this,
            tabPanel=me.getView().up('tabpanel');
        if(tabPanel.items.getCount()>2){
            me.assocStore.each(function(record){
                if(tabPanel.down('#searchResultTab-'+record.get('id'))){
                    tabPanel.remove(tabPanel.down('#searchResultTab-'+record.get('id')).itemId);
                }
            });
        }
    },
    handleViwMode:function(ergoMode){
        if(ergoMode){
            Ext.select('.searchGrid .x-grid-row .x-grid-cell').each(function(el){
                Ext.fly(el).addCls('ergonomic-font');
            });
            return;
        }
        Ext.select('.searchGrid .x-grid-row .x-grid-cell').each(function(el){
            Ext.fly(el).removeCls('ergonomic-font');
            Ext.fly(el).addCls('view-editor-font-size');
        });
    }
});
