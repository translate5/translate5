
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.changealike.Window
 * @extends Ext.window.Window
 */
Ext.define('Editor.view.changealike.Window', {
    extend: 'Ext.window.Window',
    alias: 'widget.changealikeWindow',
    requires: [
        'Editor.view.changealike.Grid'
    ],
    itemId: 'changealikeWindow',

    title: 'Aktuelles Segment für die folgenden Segmente übernehmen?',
    height: 570,
    width: 955,
    layout: {
        align: 'stretch',
        type: 'vbox'
    },
    closeAction: 'hide',
    modal: true,

    strings: {
        cancelBtn: 'Keine Änderungen durchführen [ESC]',
        saveBtn: 'Änderungen übernehmen [STRG+S]',
        help: 'Hilfe:',
        segmentData: [
            '<h1>aktuell bearbeitetes Segment</h1>',
            '{edited}'
        ],
        overwriteSource: '<b><i>#UT#Überschreibe Quelltext mit:</i></b> ',
        overwriteTarget: '<b><i>#UT#Überschreibe Zieltext mit:</i></b> '
    },

    loadedAlikes: null,
    tools: [{
        type:'help'
    }],
    id: 'change-alike-window',
    openedFor: null,

    initConfig: function(instanceConfig) {
        var me = this,
            config,
            segTpl = Ext.create('Ext.XTemplate', me.strings.segmentData);
        segTpl.compile();

        config = {
            title: me.title, //see EXT6UPD-9
            items: [
                {
                    xtype: 'container',
                    padding:10,
                    height:100,
                    autoScroll: true,
                    cls: 'segment-tag-container',
                    tpl: segTpl,
                    itemId: 'infoText'
                },
                {
                    xtype: 'changealikeGrid',
                    flex: 1
                }
            ],
            dockedItems: [
                {
                    xtype: 'toolbar',
                    ui: 'footer',
                    flex: 1,
                    dock: 'bottom',
                    layout: {
                        pack: 'end',
                        type: 'hbox'
                    },
                    items: [
                        {
                            xtype: 'button',
                            itemId: 'cancelBtn',
                            text: me.strings.cancelBtn
                        },
                        {
                            xtype: 'button',
                            itemId: 'saveBtn',
                            text: me.strings.saveBtn
                        }
                    ]
                }
            ]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    initComponent: function() {
        var me = this;

        me.setKeyMap({
            scope: 'this',
            "CmdOrCtrl+s": 'onCtrlS'
        });

        me.callParent(arguments);
        Ext.apply(me.tools[0], {
            tooltip: me.strings.help,
            renderData: {
                label:  me.strings.help
            },
            handler: me.showHelp,
            scope: me
        });
    },
    onEsc: function(e) {
        e.stopEvent();
        this.fireEvent('onEscape', this);
    },
    onCtrlS: function(e) {
        e.stopEvent();
        this.fireEvent('onCtrlS', this);
    },
  /**
   * @param {Editor.model.Segment} rec
   */
  show: function(rec) {
      //@todo SourceMatch Filterung im WDHE
      //und entsprechende Spalten im WDHE Grid ausblenden.
      //Editor.data.task.get('enableSourceEditing');
      var me = this,
          grid = me.down('gridpanel'),
          id = rec.get('id');
      me.openedFor = id;
      me.callParent();
      me.updateInfoText(rec);
      if(me.loadedAlikes) {
          grid.setAlikes(me.loadedAlikes);
      }
      else {
          me.setLoading(true);
          me.down('toolbar').disable();
      }
      me.loadedAlikes = false; //reset flag after usage
  },
  /**
   * @param {Integer} id
   * @param {Array} alikes
   */
  setAlikes: function(id, alikes) {
      var me = this, 
          grid = me.down('gridpanel');
      me.loadedAlikes = alikes;
      if(grid.rendered){
          grid.setAlikes(alikes);
      }
      me.setLoading(false);
      me.down('toolbar').enable();
  },
  /**
   * updates the text shown about the loaded segment in the Change Alike Editor
   * @param {Editor.model.Segment} segmentRecord
   */
  updateInfoText: function(segmentRecord) {
      var segField = Editor.model.segment.Field,
          sourceEdit = segmentRecord.get('sourceEdit'),
          targetEdit = segmentRecord.get('targetEdit'),
          format = function(type, text) {
              var dir = segField.isDirectionRTL(type) ? 'rtl' : 'ltr',
                  style = 'direction:'+dir+';';
              if(type == 'source') {
                  style += 'margin-bottom:5px;';
              }
              return '<div style="'+style+'" dir="'+dir+'">'+text+'</div>';
          };

      if(sourceEdit) {
          targetEdit = this.strings.overwriteSource+format('source',sourceEdit)+this.strings.overwriteTarget+format('target',targetEdit);
      }
      else {
          targetEdit = format('target',targetEdit);
      }
      
      this.down('#infoText').update({
          edited: targetEdit,
          addition: '',
          id: segmentRecord.get('id'),
          autoStateId: segmentRecord.get('autoStateId')
      });
  },
  showHelp: function() {
    var help = Ext.ComponentMgr.create({
      xtype: 'window',
      closable:true,
      modal: true,
      height:510,
      bodyPadding: 5,
      width:400,
      loader: {
        url: Editor.data.pathToRunDir+'/editor/index/wdhehelp',
        autoLoad: true
      }
    });
    help.show();
  }
});
