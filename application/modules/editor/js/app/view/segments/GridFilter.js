/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        boolProto = Ext.ux.grid.filter.BooleanFilter.prototype;
      return [{
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
          dataIndex: 'source'
      },{
          type: 'string',
          dataIndex: 'sourceEdit'
      },{
          type: 'string',
          dataIndex: 'target'
      },{
    	  type: 'string',
    	  dataIndex: 'relais'
      },{
          type: 'string',
          dataIndex: 'targetEdit'
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
      }];
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