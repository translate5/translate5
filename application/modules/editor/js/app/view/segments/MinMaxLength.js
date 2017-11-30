
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
Ext.define('Editor.view.segments.MinMaxLength', {
    extend: 'Ext.Component',
    alias: 'widget.segment.minmaxlength',
    itemId:'segmentMinMaxLength',

    //tpl: '<div class="{status}" data-qtip="{info}">{}</div>',
    
    //controller: 'segment.minmaxlength',
    //icon: Editor.data.moduleFolder+'images/comment_edit.png',
    //iconAlign: 'right',
    hidden:true,
    //style: {
    //    backgroundColor:'red',
    //    border:'none',
    //},
    //bind:{
    //    value:'{}'
    //},
    tooltip:"aaaaaaaaa",
    //autoEl: {
    //    tag: 'div',
    //    'data-qtip': Ext.String.htmlEncode("aceeeeeeeee")
    //},

    //FIXME use the htmleditor chanbge ebvent
    
    handleElementVisible:function(record){
        var me=this,
            metaCache=record.get('metaCache'),
            isItemVisible=false;
        debugger;
        if(!metaCache){
            return isItemVisible;
            return;    
        }
        var charactersCount=me.getSegmentCharactersCount(record.get('targetEdit'));
        if(charactersCount<1 || (metaCache.minWidth===null && metaCache.maxWidth===null)){
            return false;
        }
        return true;
    },

    /***
     * Update the component
    */
    updateComponent:function(record){
        console.log("min max call");
    },

    updateLabel:function(record,charactersCount){
        var me=this,
            metaCache=record.get('metaCache'),
            minWidth=metaCache.minWidth,
            maxWidth=metaCache.maxWidth;

        if(charactersCount>=minWidth &&  charactersCount<=maxWidth){
            me.setStyle({
                backgroundColor:'green'
            })
        }else{
            me.setStyle({
                backgroundColor:'red'
            })
        }

        //me.setFieldLabel("{"+charactersCount+"} | min:{"+minWidth+"},max:{"+maxWidth+"}");
    },

    getSegmentCharactersCount:function(segmentText){
        return segmentText.length;
    },

    
});