
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
 * @class Editor.view.segments.MetaPanel
 * @extends Editor.view.ui.segments.MetaPanel
 * @initalGenerated
 */
Ext.define('Editor.view.segments.MetaPanel', {
    alias: 'widget.segmentsMetapanel',
    extend: 'Ext.panel.Panel',
    bodyPadding: 10,
    scrollable: 'y',
    frameHeader: false,
    id: 'segment-metadata',
    bind: {
        title: '{l10n.metaPanel.title}'
    },
    segmentStateId: -1, // caches the segment state to safely capture user originating radio changes. ExtJs suspendEvent && resumeEvent do not work for radios :-(
    layout: 'auto',

    initComponent: function() {
        var me = this,
            showStatus = Editor.app.getTaskConfig('segments.showStatus'),
            isSegmentQmVisible = Editor.app.getTaskConfig('autoQA.enableQm');

        Ext.applyIf(me, {
            title:me.title,
            items: [{
                xtype: 'form',
                border: 0,
                itemId: 'metaInfoForm',
                items: [{
                      xtype: 'falsePositives',
                      itemId: 'falsePositives',
                      collapsible: true
                  },{
                      xtype: 'segmentQm',
                      itemId: 'segmentQm',
                      hidden:  !isSegmentQmVisible,
                      collapsible: true
                  },{
                      xtype: 'fieldset',
                      itemId: 'metaStates',
                      collapsible: true,
                      defaultType: 'radio',
                      hidden:  !showStatus,
                      bind: {
                          title: '{l10n.metaPanel.metaStates_title}'
                      }
                  }]
              }
          ]
      });
      me.callParent(arguments);
      me.addSegmentStateFlags();
      me.down('#segmentQm').startTaskEditing();
    },
    /**
     * Fügt anhand der php2js Daten die Status Felder hinzu
     */
    addSegmentStateFlags: function() {
        var me = this,
            stati = me.down('#metaStates'),
            flags = Editor.data.segments.stateFlags,
            counter = 1,
            metaStates_tooltip = Editor.data.l10n.metaPanel.metaStates_tooltip,
            metaStates_tooltip_nokey = Editor.data.l10n.metaPanel.metaStates_tooltip_nokey;

        Ext.each(flags, function(item){
            var tooltip;
            if(counter < 10) {
                tooltip = Ext.String.format(metaStates_tooltip, counter++);
            } else {
                tooltip = metaStates_tooltip_nokey;
            }
            stati.add({
                xtype: 'radio',
                name: 'stateId',
                anchor: '100%',
                inputValue: item.id,
                boxLabel: '<span data-qtip="'+tooltip+'">'+item.label+'</span>',
                listeners:{
                    change: function(control, checked){
                        if(checked && me.segmentStateId != control.inputValue){
                            me.fireEvent('segmentStateChanged', control.inputValue, me.segmentStateId);
                            me.segmentStateId = control.inputValue;
                        }
                    }
                }
            });
        });
    },
    /**
     * Has to be called before our form's record update to be able to distinguish user generated change events from programmatical ones. Sadly, suspending/resuming change events does not work
     */
    setSegmentStateId: function(stateId){
        this.segmentStateId = stateId;
    },
    /**
     * Sets the state by keyboard value
     */
    showSegmentStateId: function(stateId){
        var boxes = this.query('#metaStates radio');
        Ext.each(boxes, function(box){
            box.setValue(box.inputValue == stateId);
        });
    }
  });