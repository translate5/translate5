
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
 * Editor.view.segments.GridFilter definiert zum einen die Filter für das Segment Grid. 
 * Zum anderen werden einige Methoden des Orginal Filters für Bugfixing überschrieben. 
 * @class Editor.view.segments.GridFilter
 * @extends Ext.ux.grid.FiltersFeature
 */
Ext.define('Editor.view.segments.GridFilter', {
  extend: 'Ext.ux.grid.FiltersFeature',
  alias: 'feature.editorGridFilter',
  requires: [
             'Ext.ux.grid.FiltersFeature',
             'Ext.ux.grid.menu.ListMenu',
             'Ext.ux.grid.menu.RangeMenu',
             'Ext.ux.grid.filter.BooleanFilter',
             'Ext.ux.grid.filter.DateFilter',
             'Ext.ux.grid.filter.ListFilter',
             'Ext.ux.grid.filter.NumericFilter',
             'Ext.ux.grid.filter.StringFilter',
             'Editor.view.admin.task.WorkflowStepFilter'
             ],
  constructor: function(config) {
    var me = this,
    defaults = {
        encode: true,
        local: false,
        filters: me.getFilterForGridFeature()
    },
    configAdd = Editor.data.initialGridFilters;
    me.initialActive = [];

    //initial filters
    if(configAdd && configAdd[config.ftype]) {
        configAdd = configAdd[config.ftype];
        Ext.each(defaults.filters, function(item){
            if(configAdd[item.dataIndex]) {
                me.initialActive.push(item.dataIndex);
                Ext.applyIf(item, configAdd[item.dataIndex]);
            }
        });
    }
    config = Ext.apply({},config,defaults);
    me.callParent([config]);
  },
  /**
   * override createFilters to inject the ability to display initial set columns
   */
  createFilters: function() {
      this.callParent(arguments);
      var header = this.view.getHeaderCt(),
          col;
      Ext.Array.each(this.initialActive, function(idx) {
          col = header.down('.gridcolumn[dataIndex="'+idx+'"]');
          col && col.show();
      });
  },
  /**
   * Gibt die Definitionen der Grid Filter zurück.
   * @returns {Array}
   */
    getFilterForGridFeature: function() {
        var autoStates = Ext.Array.filter(Editor.data.segments.autoStateFlags, function(item) {
                return item.id != 999;
            }),
            userPref = Editor.data.task.userPrefs().first(),
            boolProto = Ext.ux.grid.filter.BooleanFilter.prototype,
            fields = [{
                type: 'numeric',
                dataIndex: 'segmentNrInTask'
            },{
                type: 'list',
                dataIndex: 'stateId',
                labelField: 'label',
                phpMode: false,
                options: Editor.data.segments.stateFlags
            },{
                type: 'list',
                dataIndex: 'qmId',
                labelField: 'label',
                phpMode: false,
                options: Editor.data.segments.qualityFlags
            },{
                type: 'string',
                dataIndex: 'comments'
            },{
                type: 'numeric',
                dataIndex: 'matchRate'
            },{
                type: 'list',
                dataIndex: 'autoStateId',
                labelField: 'label',
                phpMode: false,
                options: autoStates
            },{
                type: 'workflowStep',
                dataIndex: 'workflowStep'
            },{
                type: 'string',
                dataIndex: 'userName'
            },{
                type: 'boolean',
                //wording in frontend is not editable but locked, so the yes/no buttons has to be changed:
                yesText: boolProto.noText, 
                noText: boolProto.yesText,
                dataIndex: 'editable'
            },{
                type: 'boolean',
                defaultValue : false,
                yesText: boolProto.noText, 
                noText: boolProto.yesText,
                dataIndex: 'isWatched'
            }];
        Editor.data.task.segmentFields().each(function(rec) {
            if(!rec.isTarget() || ! userPref.isNonEditableColumnDisabled()) {
                fields.push({dataIndex: rec.get('name'), type: 'string'});
            }
            if(rec.get('editable')) {
                fields.push({dataIndex: rec.get('name')+'Edit', type: 'string'});
            }
        });
        return fields;
  }
}, function(){
    //initiere die Fixes für diverse Ext 3 Bugs in den Filtern
    Ext.override(Ext.ux.grid.FiltersFeature, {
        //The following override fixes http://www.sencha.com/forum/showthread.php?131042
        onBeforeLoad: function(store, operations) {
          if(! operations){
            //if no operations are given exit here. 
            //the handler will then be called on beforeprefetch
            //see bindStore overriding
            return;
          }
          var options = {};
          this.callOverridden([store, options]);
          operations.params = operations.params || {};
          Ext.apply(operations.params, options.params);
        },
        //fixing http://www.sencha.com/forum/showthread.php?163733-4.0.7-inconsistent-beforeload-handler-calling-in-store&p=695927
        // attaching the local onBeforeLoad to the prefetch handler, because this handler is called correctly 
        bindStore: function(store, initial){
          this.callOverridden([store, initial]);
          if(initial){
            store.on('beforeprefetch', this.onBeforeLoad, this);
          }
        },
        /**
         * Darstellung des Arrows im Menü, per gefakten Submenu Daten
         * überschreibt die komplette Funktion, anders lässt sich der arrow nicht bewerkstelligen 
         * @override
         */
        createMenuItem: function(menu) {
          var me = this;
          me.sep  = menu.add('-');
          me.menuItem = menu.add({
              checked: false,
              itemId: 'filters',
              text: me.menuFilterText,
              // die folgenden drei Zeilen sind für den Arrow relevant
              renderData: {
                menu: true
              },
              listeners: {
                  scope: me,
                  checkchange: me.onCheckChange,
                  beforecheckchange: me.onBeforeCheck
              }
          });
        }
      });
});