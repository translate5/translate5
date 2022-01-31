
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
                itemclick: 'handleItemClick'
            }
        },
        messagebus: {
            '#translate5 task': {
                commentChanged: function handleCommentChange({comment, connectionId, typeOfChange}) {
                    switch(typeOfChange) {
                        case 'afterPostAction':
                        case 'afterPutAction':
                            this.updateStore(new (this.getCommentList().getStore().getModel())(Ext.clone(comment)), typeOfChange);
                            break;
                        case 'beforeDeleteAction':
                            this.getCommentList().getStore().getData().removeByKey(comment.id);
                    }
                }
            }
        }
    },

    loadStore: function() {
        this.getCommentList().getStore().load();
    },
    
    handleItemClick: function(origin, remarkRecord){
        switch(remarkRecord.get('type')){
            
            case 'segmentComment':
                var segmentIndex = remarkRecord.get('segmentNrInTask') - 1; // segment grid indexes from 0 on, segmentNrInTask from 1
                Ext.getCmp('segment-grid').scrollTo(segmentIndex);
                break;  

            case 'visualAnnotation':
                this.fireEvent('visualRemarkClicked', remarkRecord);
                break;
        }
    },

    /**
     * Change existing remark in store or add new one
     */
    updateStore: function(remark, typeOfChange){
        var cl = this.getCommentList();
        var store = cl.getStore();
        var existing = (typeOfChange === 'afterPutAction') ? store.getById(remark.id) : null; // in ZF1 Put is UPDATE, Post is CREATE

        /** Update exsiting record - Why this way?
         * store.data.replace() does not replace (at least in ExtJS-6.2.0)
         * store.update(...) triggers request
         * less sort operations
        */
        if(existing) {
            var old = existing.data, fresh = remark.data, changedProps = [], setOpts = { silent: true, commit: false }, prop;
            for(prop in old) {
                if(old[prop] !== fresh[prop]) {
                    existing.set(prop, fresh[prop], setOpts);
                    changedProps.push(prop);
                }
            }
            if(changedProps.length) {
                existing.commit(false, changedProps);
            }
            remark = existing;
        } else {
            store.addSorted(remark);
        }
        cl.highlightRemark(remark);
    }
})