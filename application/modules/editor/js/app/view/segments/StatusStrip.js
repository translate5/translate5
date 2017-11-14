
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

Ext.define('Editor.view.segments.StatusStrip', {
    extend: 'Ext.container.Container',
    alias: 'widget.segments.statusstrip',
    itemId:'segmentStatusStrip',
    
    controller: 'segmentstatusstrip',
    requires:[
        'Editor.view.segments.StatusStripViewController',
    ],
    framed: false,
    style: 'background: #e4edf4;',
    layout:'hbox',

    /***
     * For each child element in the status strip, set the record instance
     */
    setItemsRecord:function(record){
        var me=this,
            items=null;
        if(me.items && me.items.items.length<1){
            return;
        }
        items=me.items.items;
        
        for (var index = 0; index < items.length; index++) {
            var element = items[index];
            if(element.handleElementVisible){
                element.handleElementVisible(record);
            }
        }
    },

    /***
     * Check if there is visible child element in the status strip
     */
    isItemVisible:function(){
        var me = this;
        if(!me.items || me.items.items.length < 1){
            return false;
        }

        var items=me.items.items;
        
        for (var index = 0; index < items.length; index++) {
            var element = items[index];
            if(element.isVisible()){
                return true;
                break;
            }
        }

        return false;
    }

});