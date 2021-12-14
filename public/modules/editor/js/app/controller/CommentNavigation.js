
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
 * Editor.controller.Comments encapsulates the comment functionality
 * @class Editor.controller.Comments
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.CommentNavigation', {
    extend: 'Ext.app.Controller',
    models: ['Comment'],
    stores: ['comments.AllComments'],
    views: ['comments.Navigation'],

    refs: [{
        selector: '#commentList',
        ref: 'commentList',
    }],
    control: {
        commentNavigation: {
            'expand': 'loadStore'
        }
    },
    listen: {
        component: {
            '#commentList': {
                itemclick: 'jumpThere'
            }
        },
        messagebus: {
            '#translate5 task': {
                commentChanged: function handleCommentChange({comment,connectionId,typeOfChange}) {
                    switch(typeOfChange) {
                        case 'afterPostAction':
                        case 'afterPutAction':
                            this.updateStore(new (this.getCommentList().store.model)(comment))
                            break;
                        case 'beforeDeleteAction':
                            this.removeRemark(comment.id, comment.type)
                    }
                }
            }
        }
    },

    loadStore: function() {
        var cl = this.getCommentList();
        cl.store.load();
    },
    jumpThere: function(origin, record, item, index, e) {
        switch(record.data.type) {
            case 'segmentComment':
                var
                    grid = Ext.getCmp('segment-grid'),
                    view = grid.view,
                    rec = grid.store.getById(record.data.segmentId);
                //grid.scrollTo(recIdx); // does not animate upwards direction
                var rowTableEl = view.el.getById(view.getRowId(rec)); //table has bgColor set for end of animation
                grid.setSelection(rec);
                grid.getScrollable().scrollIntoView(rowTableEl, false, true, true);
                break;

            case 'visualAnnotation':
                var
                    vr = Ext.first('visualReviewPanel'),
                    vc = vr && vr.getController();
                if(vc){
                    vc.scrollToAnnotation(record);
                }
                break;
        }
    },

    /**
     * Change existing remark in store or add new one
     */
    updateStore: function updateStore(remark, typeOfChange){
        var cl=this.getCommentList();
        var store=cl.store;
        var vr=Ext.first('visualReviewPanel'); 
        var existing =typeOfChange==='afterPostAction' ? store.getById(remark.id) : null;
        /** Update exsiting record - Why this way?
        * store.data.replace() does not replace (at least in ExtJS-6.2.0)
        * store.update(...) triggers request
        * less sort operations
        */
        if(existing) {
            var old = existing.data, fresh = updated.data, changedProps = [], setOpts = {silent: true,commit: false}, prop;
            for(prop in old) {
                if(old[prop] !== fresh[prop]) {
                    existing.set(prop, fresh[prop], setOpts);
                    changedProps.push(prop);
                }
            }
            if(changedProps.length) {
                existing.commit(false,changedProps);
            }
            remark = existing;
        } else {
            store.addSorted(remark);
            if(remark.data.type === 'visualAnnotation') {
                Ext.getStore('visualReviewAnnotations').add(remark)
                var page = vr.iframeController.getPageByIndex(remark.get('page')-1);
                vr.getAnnotationController().renderAnnotations(page);
            }
        }
        cl.scrollable.doHighlight(cl.getNodeByRecord(remark));
    },
    /**
     * Remove remark from commentnav and where else it appears
     */
    removeRemark: function removeRemark(remarkId, remarkType){
        var cl=this.getCommentList();
        var store=cl.store;
        store.data.removeByKey(remarkId);
        if(remarkType === 'visualAnnotation') {
            Ext.getStore('visualReviewAnnotations').data.removeByKey(remarkId)
            var vr=Ext.first('visualReviewPanel');
            var domEl = vr.iframeController.document.getElementById('pageAnnotation'+remarkId)
            if(domEl) domEl.remove();
        }
    }
})