
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
 * @class Editor.plugins.MatchResource.view.SearchGrid
 * @extends Ext.grid.Panel
 */
Ext.define('Editor.plugins.MatchResource.view.SearchResultGrid', {
    extend : 'Ext.grid.Panel',
    requires: [
               'Editor.plugins.MatchResource.view.SearchGridViewController',
               'Editor.plugins.MatchResource.view.SearchGridViewModel'
               ],
    alias : 'widget.matchResourceSearchResultGrid',
    itemId:'searchResultGrid',
    assocStore : [],
    strings: {
        source: '#UT#Quelltext',
        target: '#UT#Zieltext',
        match: '#UT#Matchrate'
    },
    viewConfig: {
        enableTextSelection: true,
        getRowClass: function(record) {
            var me=this,
            result = ['match-state-'+record.get('state')],
            viewModesController = Editor.getApplication().getController('ViewModes').self;
            if(viewModesController.isErgonomicMode()){
                result.push('ergonomic-font');
            }
            if(viewModesController.isEditMode() || viewModesController.isViewMode()){
                result.push('view-editor-font-size');
            }
            return result.join(' ');
        }
    },
    initConfig: function(instanceConfig) {
        var me = this,
        store = new Ext.data.Store({
            autoLoad: true,
            model: 'Editor.plugins.MatchResource.model.EditorQuery',
            pageSize:20,
            sorters: [{
                property: 'service',
                direction: 'DESC'
            }],
            proxy : {
                type : 'ajax',
                url: Editor.data.restpath+'plugins_matchresource_tmmt/'+instanceConfig.tmmtid+'/search',
                reader : {
                    rootProperty: 'rows',
                    type : 'json'
                },
                extraParams:{
                    query: instanceConfig.query,
                    field: instanceConfig.field
                },
                actionMethods:{
                    read: 'POST'
                },
                listeners:{
                    exception: function (proxy, request, operation){
                        if (request.responseText != undefined){
                            // responseText was returned, decode it
                            responseObj = Ext.decode(request.responseText,true);
                            if (responseObj != null && responseObj.msg != undefined){
                                // message was returned
                                Ext.Msg.alert('Error',responseObj.msg);
                            }else{
                                // responseText was decoded, but no message sent
                                Ext.Msg.alert('Error','Unknown error: The server did not send any information about the error.');
                            }
                        }else{
                            // no responseText sent
                            Ext.Msg.alert('Error','Unknown error: Unable to understand the response from the server');
                        }
                    }
                }
            }
        }), //just pass and set tmmtid and lastquery),
        config = {
            //bind: {
            //    store: '{editorsearch}'
            //},
          store: store,
          columns: [{
              xtype: 'gridcolumn',
              flex: 33/100,
              dataIndex: 'source',
              cellWrap: true,
              text: me.strings.source
          },{
              xtype: 'gridcolumn',
              flex: 33/100,
              dataIndex: 'target',
              cellWrap: true,
              text: me.strings.target
          },{
              xtype: 'gridcolumn',
              flex: 33/100,
              dataIndex: 'service',
              renderer: function(val, meta, record) {
                  var str =me.assocStore.findRecord('id',record.get('tmmtid'));
                  meta.tdStyle ="background-color:#"+str.get('color')+" !important;";
                  return str.get('name')+' ('+str.get('serviceName')+')';
              },
              text: me.strings.tmresource
          }],
          dockedItems: [{
              xtype: 'pagingtoolbar',
              itemId: 'pagingtoolbar',
              store:store,
              dock: 'bottom',
              displayInfo: true,
              listeners: {
                  afterrender : function() {
                      this.child('#last').hide();
                  }
              }
          }]
        };
        delete instanceConfig.query;
        delete instanceConfig.field;
        
        me.assocStore = instanceConfig.assocSt0ore;
        if (instanceConfig) {
            me.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
      },
      initComponent: function() {
          this.callParent(arguments);
          Ext.override(this.down('#pagingtoolbar'),{
              updateInfo: function() {
                  this.callParent(arguments);
                  var element = this.child('#afterTextItem'),
                      pageData =this.getPageData();
                  if(!element){
                      return;
                  }
                  if(pageData.currentPage === pageData.pageCount){
                      element.setText(" ");
                      return;
                  }
                  element.setText("...");
              }
          });
      }
});