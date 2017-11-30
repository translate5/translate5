
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
        'Editor.view.segments.MinMaxLength'
    ],
    framed: false,
    style: 'background: #e4edf4;',
    layout:'hbox',

    /***
     * Flag if there is a visible item in the status strip
     */
    isChildVisible:false,

    /***
     * Html editor instance
     * @cfg {Editor.view.segments.HtmlEditor} htmlEditor
     */
    htmlEditor:null,

    initConfig : function(instanceConfig) {
        var me = this,
            config = {
            //FIXME use configuration to disable this component
            items : [{
                xtype:'segment.minmaxlength',
                htmlEditor:instanceConfig.htmlEditor
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * For each child element in the status strip, set the record instance
     */
    setRecordAndVisible:function(record){
        var me=this;
        me.isChildVisible=false;
        me.items.each(function(item){
            //check if the element needs to be visible, if no visibility handler is defined
            //hide the component
            if(item.handleElementVisible && item.handleElementVisible(record)){
                item.setVisible(true);
                //update the component (add css, tooltips, etc..), 
                me.isChildVisible=true;
            }else{
                item.setVisible(false);
            }
            item.setSegmentRecord && item.setSegmentRecord(record);
        });  
    },

    /***
     * Flag if there is a visible item in the status strip
     */
    isItemVisible:function(){
        return this.isChildVisible;
    }

});