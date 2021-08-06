
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
 * @class Editor.view.comments.Panel
 */
Ext.define('Editor.view.comments.Panel', {
    extend : 'Ext.panel.Panel',
    alias : 'widget.commentPanel',
    controller: 'commentPanel',

    requires : [ 
        'Editor.view.comments.Grid',
        'Editor.view.comments.PanelViewController' 
    ],

    title : '#UT#Kommentare zum aktuellen Segment',
    itemId : 'commentPanel',
    layout: 'fit',
    item_saveBtn : '#UT#Speichern',
    item_closeBtn: '#UT#Schließen',
    item_commentNew: '#UT#Kommentar neu',
    item_commentEdit: '#UT#Kommentar bearbeiten',
    item_addComment: '#UT#Neuer Kommentar',
    delete_confirm_title: '#UT#Löschen des Kommentars bestätigen',
    delete_confirm_msg: '#UT#Soll der Kommentar wirklich gelöscht werden?',

    listeners:{
        expand : 'onCommentPanelExpand'
    },

    /**
     * is the panel collapsable
     */
    isCollapsable:true,
    /**
     * show a confirm message box before the deletion of a comment
     * @param {Function} callback
     */
    showDeleteConfirm: function(callback) {
        var confirmWin = Ext.create('Ext.window.MessageBox');
        confirmWin.confirm(this.delete_confirm_title, this.delete_confirm_msg, callback);
        
        //we use defere so the messagebox info is always of top
        Ext.defer(function () {
            confirmWin.toFront();
        }, 50);
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
    initConfig : function(instanceConfig) {
        var me = this
        config = {
                title: me.title, //see EXT6UPD-9
            items : [ {
                xtype : 'container',
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
                        enterIsSpecial: true,
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
                            itemId : 'closeBtn',
                            listeners:{
                                click:'onCloseBtnClick'
                            },
                            text : me.item_closeBtn
                        }, {
                            xtype : 'button',
                            itemId : 'saveBtn',
                            listeners:{
                                click:'saveComment'
                            },
                            text : me.item_saveBtn
                        }]
                    }]
                },{
                    xtype : 'commentsGrid',
                    flex : 1
                }]
            } ]
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /**
     * Save the current opened comment
     * @return {Boolean} true if save request started, false if not (not valid or something)
     */
    save: function() {
        return this.getController().saveComment();
    },

    handleCollapse:function(){
        if(!this.isCollapsable){
            return;
        }
        this.collapse();
    },

    handleExpand:function(){
        var me=this;
        if(me.isCollapsable && me.collapsed){
            //FIXME everything with the expangin inside the comments controoler
            me.expand();
        }
    }
});