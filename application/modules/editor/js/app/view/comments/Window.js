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
 * @class Editor.view.changealike.Window
 * @extends Editor.view.ui.changealike.Window
 */
Ext.define('Editor.view.comments.Window', {
    extend : 'Ext.window.Window',
    alias : 'widget.commentWindow',
    requires : [ 'Editor.view.comments.Grid' ],
    title : '#UT#Kommentare zum Segment Nr. {0}',
    height : 500,
    width : 795,
    itemId : 'commentWindow',
    layout: 'fit',
    closeAction : 'hide',
    modal : true,
    item_cancelBtn : '#UT#Abbrechen',
    item_saveBtn : '#UT#Speichern',
    item_closeBtn: '#UT#Schließen',
    item_commentLabel: '#UT#Kommentar neu / bearbeiten:',
    item_addComment: '#UT#Neuer Kommentar',
    delete_confirm_title: '#UT#Löschen des Kommentars bestätigen',
    delete_confirm_msg: '#UT#Soll der Kommentar wirklich gelöscht werden?',

    /**
     * updates the info text panel with the current segment record.
     * 
     * @param {Editor.model.Segment} record
     */
    updateInfoText : function(record) {
        var me = this;
        me.setTitle(Ext.String.format(me.self.prototype.title, record.get('segmentNrInTask')));
        me.down('#infoText').update(record.data);
    },

    /**
     * show a confirm message box before the deletion of a comment
     * @param {Function} callback
     */
    showDeleteConfirm: function(callback) {
        Ext.Msg.confirm(this.delete_confirm_title, this.delete_confirm_msg, callback);
    },
    //in original Method beforeclose is not captured on escaping with ESC
    onEsc: function(k, e) {
        e.stopEvent(); 
        this.close();
    },
    initComponent : function() {
        var me = this;
        Ext.applyIf(me, {
            dockedItems : [ {
                dock : 'top',
                xtype : 'container',
                padding : 10,
                height : 100,
                autoScroll : true,
                cls : 'segment-tag-container',
                tpl : '{targetEdit}', //FIXME if target edit is hidden, what to show the user?
                itemId : 'infoText'
            },{
                xtype : 'toolbar',
                ui : 'footer',
                flex : 1,
                dock : 'bottom',
                layout : {
                    pack : 'end',
                    type : 'hbox'
                },
                items : [{
                    xtype: 'button',
                    itemId: 'commentAddBtn',
                    iconCls: 'ico-comment-add',
                    text: me.item_addComment
                },{
                    xtype : 'button',
                    itemId : 'closeBtn',
                    //iconCls: 'ico-loading',
                    text : me.item_closeBtn
                }]
            }],
            items : [ {
                xtype : 'container',
                layout: {
                    align: 'stretch',
                    type: 'hbox'
                },
                items : [ {
                    xtype : 'commentsGrid',
                    flex : 1
                },{
                    xtype : 'form',
                    itemId: 'commentForm',
                    bodyPadding: 5,
                    width: 300,
                    disabled: true,
                    //dock : 'right',
                    items : [{
                        labelAlign: 'top',
                        xtype : 'textarea',
                        name: 'comment',
                        height: 200,
                        fieldLabel: me.item_commentLabel,
                        anchor: '100%'
                    },{
                        xtype : 'toolbar',
                        ui : 'footer',
                        flex : 1,
                        //dock : 'bottom',
                        layout : {
                            pack : 'end',
                            type : 'hbox'
                        },
                        items : [ {
                            xtype : 'button',
                            itemId : 'cancelBtn',
                            text : me.item_cancelBtn
                        }, {
                            xtype : 'button',
                            itemId : 'saveBtn',
                            text : me.item_saveBtn
                        }]
                    }]
                }]
            } ]
        });

        me.callParent(arguments);
    }
});