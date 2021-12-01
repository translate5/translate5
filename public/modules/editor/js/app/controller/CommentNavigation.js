
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
    ref: 'commentList',
    selector: '#commentList'
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
                commentChanged: function carryChangeToStore({comment, connectionId}){
                    console.log(arguments)
                    cl = this.getCommentList();
                    store = cl.store;
                    var updated = new store.model(comment);
                    var existing = store.getById(comment.id);
                    if(existing){
                        //store.update({records:[new store.model(comment)]}) //trigers request
                        var changed = [];
                        for(prop in existing.data){
                            if(existing.get(prop) !== updated.get(prop)){
                                existing.set(prop, updated.get(prop), {silent:true, commit:false});
                                changed.push(prop);
                            }
                            if(changed.length){
                                existing.commit(false, changed);
                            }
                        }
                        updated = existing;
                        //store.data.replace(existing, );
                        //store.data.removeAt(store.data.indexOf(existing))
                        //cl.refresh();
                    } else {
                        store.add(updated)
                    }
                    cl.scrollable.doHighlight(cl.getNodeByRecord(updated));
                }
            }
        }
  },

  loadStore: function () {
    var cl = this.getCommentList();
    cl.store.load();
  },
  jumpThere: function (origin, record, item, index, e) {
    switch (record.data.type) {
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

  }


}
)