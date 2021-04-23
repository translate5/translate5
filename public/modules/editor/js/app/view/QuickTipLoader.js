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

/**
 * /**
 * This Class is ONLY a loader for Editor.view.QuickTip and not intended nor capable to be used somewhere else
 */
Ext.define('Editor.view.QuickTipLoader', {
    
    extend: 'Ext.ElementLoader', 
    target: null, 
    loadOnRender: true, 
    loadMask: false,
 
    setTarget: function(target) {
        var me = this;
 
        if (Ext.isString(target)) {
            target = Ext.getCmp(target);
        }
 
        if (me.target && me.target !== target) {
            me.abort();
        }
        me.target = target;
        if (target && me.loadOnRender) {
            if (target.rendered) {
                me.doLoadOnRender();
            } else {
                me.mon(target, 'render', me.doLoadOnRender, me);
            }
        }
    },
    getUrl(){
        return this.url;
    },
    doLoadOnRender: function() {
        this.load(null);
    },
    removeMask: function(){
        this.target.setLoading(false);
    },
    addMask: function(mask){
        this.target.setLoading(mask);
    },
    setOptions: function(active, options){
        active.removeAll = Ext.isDefined(options.removeAll) ? options.removeAll : this.removeAll;
        active.rendererScope = options.rendererScope || this.rendererScope || this.target;
    },
    getRenderer: function(renderer){
        var renderer = function(loader, response, active){
            loader.getTarget().setTipText(response.responseText);
            return true;
        }
        return renderer;
    }
});