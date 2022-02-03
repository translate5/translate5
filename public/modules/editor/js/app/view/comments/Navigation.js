
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

/**
 * @class Editor.view.comments.Navigation
 */
Ext.define('Editor.view.comments.Navigation', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.commentNavigation',

    collapsible: true,
    items: [{
        xtype: 'dataview',
        itemId: 'commentList',
        id: 'commentNavList',
        store: {
            type: 'AllComments',
            autoLoad: true,
            sorters: [ // must sort here in frontend for new comments' correct position
                { property: 'reviewFileId', direction: 'ASC' },
                { property: 'pageNum', direction: 'ASC' },
                { property: 'timecode', direction: 'ASC' },
                { property: 'type', direction: 'DESC' },
                { property: 'segmentId', direction: 'ASC' },
                { property: 'y', direction: 'ASC' },
                { property: 'id', direction: 'ASC' },
            ]
        },
        scrollable: true,
        itemSelector: 'div.x-grid-item',
        tpl: [
            `<tpl for=".">
                <div class="x-grid-item x-grid-cell-inner {[this.iconClass[values.type]]} {[this.getUserColor(values.userGuid)]} " tabindex="-1"> {comment}</div>
            </tpl>`,
            {
                iconClass: {
                    'segmentComment': 'x-fa fa-comment-o',
                    'visualAnnotation': 'x-fa fa-map-marker',
                    'videoAnnotation': 'x-fa fa-video-camera',
                },
                /**  @property trackingUserNumberCache:
                 * Internal user tracking number cache used for assigning unique css annotation class(when anonymized users is active we need to augo generate one).
                 * see store berforeload below for setup.
                 */
                 trackingUserNumberCache:{},
                getUserColor: function(userGuid){
                    return 'usernr' + (this.trackingUserNumberCache[userGuid] || 'X');
                }
            }
        ],
        highlightRemark: function(remark){
            // TODO FIXME: these are mostly private methods ...
            var node = this.getNodeByRecord(remark),
                scroller = this.getScrollable();
            if(node && this.isVisible() && scroller && typeof scroller === 'object'){
                scroller.doHighlight(node);
            }
        }
    }],
    tipTpl: new Ext.XTemplate([
        `<div style="padding:10px;font-size:16px;font-weight:normal;line-height:1.5">{comment}</div>
         <hr>
         <small><i>{userName} {modified}</i></small>
         `
    ]),

    //title: 'Kommentare', // see EXT6UPD-9 + localizedjsstrings.phtml
    itemId: 'commentNavigation',
    
    layout: 'fit',
    
    initComponent: function(){
        this.callParent(arguments);
        var dataview = this.down('dataview');
        /* Initialize XTemplate with neccessary user mapping, unanonymize own user */
        dataview.getStore().addListener('beforeload', function(){
            var userGuid = Editor.app.authenticatedUser.getUserGuid();
            var cache = dataview.tpl.trackingUserNumberCache;
            var tracking = Editor.data.task.userTracking();

            tracking.each(function(rec) {
                cache[rec.getId()]=rec.get('taskOpenerNumber');
            });

            var ownTrackinRecord = tracking.findRecord('userGuid',userGuid);
            if(ownTrackinRecord){
                cache[userGuid] = ownTrackinRecord.get('taskOpenerNumber');
            }
        }, dataview.getStore(), { single:true });
    },

    afterRender: function(){
        var me = this;
        me.callParent(arguments);
        var view = me.down('dataview');
        view.tip = Ext.create('Ext.tip.ToolTip', {
            target: view.getEl(),
            delegate: view.itemSelector,
            trackMouse: true,
            mouseOffset: [30, 1],
            listeners: {
                beforeshow: function updateTipBody(tip) {
                    var rec = view.getRecord(tip.triggerElement);
                    if(tip.pointerEvent.clientX <= 25){ // left side: show type of annotation as tooltip
                         tip.update(me.tipTpl.type[rec.get('type')])
                    } else { // show regular tooltip
                        tip.update(me.tipTpl.apply(rec.getData()));
                    }
                }
            }
        });
    }

});