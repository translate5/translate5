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
Ext.define('Editor.view.comments.Window', { //FIXME move from Window to panel
    extend : 'Ext.panel.Panel',
    alias : 'widget.commentWindow',  //FIXME move from Window to panel
    requires : [ 'Editor.view.comments.Grid' ],
    title : '#UT#Kommentare zum aktuellen Segment',
    itemId : 'commentWindow',
    layout: 'fit',
    item_cancelBtn : '#UT#Abbrechen',
    item_saveBtn : '#UT#Speichern',
    item_closeBtn: '#UT#Schließen',
    item_commentNew: '#UT#Kommentar neu',
    item_commentEdit: '#UT#Kommentar bearbeiten',
    item_addComment: '#UT#Neuer Kommentar',
    delete_confirm_title: '#UT#Löschen des Kommentars bestätigen',
    delete_confirm_msg: '#UT#Soll der Kommentar wirklich gelöscht werden?',

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
    /**
     * set the formfield in edit mode: preset the value to edit
     */
    setComment: function(comment) {
        var me = this,
            area = me.down('textarea');
        area.setValue(comment);
        area.labelEl && area.labelEl.update(me.item_commentEdit+':');
    },
    /**
     * cancel the actual edited comment
     */
    cancel: function() {
        var me = this,
            area = me.down('textarea');
        area.setValue('');
        area.labelEl && area.labelEl.update(me.item_commentNew+':');
    },
    initComponent : function() {
        var me = this;
        Ext.applyIf(me, {
            items : [ {
                xtype : 'container',
                hidden: true,
                itemId: 'commentContainer',
                layout: {
                    align: 'stretch',
                    type: 'vbox'
                },
                items : [ {
                    xtype : 'form',
                    itemId: 'commentForm',
                    bodyPadding: 5,
                    //width: 300,
                    //dock : 'right',
                    items : [{
                        labelAlign: 'top',
                        xtype : 'textarea',
                        name: 'comment',
                        height: 100,
                        fieldLabel: me.item_commentNew,
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
                },{
                    xtype : 'commentsGrid',
                    flex : 1
                }]
            } ]
        });

        me.callParent(arguments);
    }
});