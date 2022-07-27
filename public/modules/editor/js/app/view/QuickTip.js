
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
 * This Class extends the standard QuickTip Class to handle ajax-content as well. This content is provided by setting a data-qtipurl attribute with the URL instead of data-qtip with the text
 */
Ext.define('Editor.view.QuickTip', {
    
    extend: 'Ext.tip.QuickTip',
    alias: 'widget.editorquicktip',
    loadedTipText: null,
    strings: {
        loading: '#UT#Lade...'
    },
    /**
     * Overridden to return true if data-qtipurl is set & to create & set the loader
     */
    delegate: function(target) {

        if(target.getAttribute('data-qtipurl')){
            
            if(this.loader && this.loader.getUrl() == target.getAttribute('data-qtipurl')){
                return true;
            }
            this.removeLoader();
            // reset our width to a fixed width
            this.width = (target.getAttribute('data-qwidth')) ? parseInt(target.getAttribute('data-qwidth')) : 200; 
            this.loader = Ext.create('Editor.view.QuickTipLoader', {
                url: target.getAttribute('data-qtipurl'),
                target: this
            });
            return true;

        } else {
            
            this.removeLoader();
        }
        // We can now only activate on elements which have the required attributes
        var text = target.getAttribute('data-qtip') || (this.interceptTitles && target.title);
        return !!text;
    },
    /**
     * Removes our loader and all other configs used for laoding our content
     */
    removeLoader: function(){
        if(this.loader){
            this.loader.destroy();
            this.loadedTipText = null;
            delete this.loader;
        }
    },
    /**
     * Applies the rendered content to any phase we may are in
     */
    setTipText: function(responseText){
        if(this.items.getCount() > 0){
            var items = Ext.decode(response.responseText);
            this.suspendLayouts();
            this.removeAll();
            this.add(items);
            this.resumeLayouts(true);
        } else if(this.isVisible()){
            this.update(responseText);
        } else if(this.activeTarget && this.activeTarget.text){
            this.activeTarget.text = responseText;
        }
        this.loadedTipText = responseText;
    },
    /**
     * Overridden to return a text anyway what starts the rendering in case of a loader-based rendering cycle
     */
    getTipText: function (target) {
        if(this.loadedTipText){
            return this.loadedTipText;
        }
        if(this.loader){
            return this.strings.loading;
        }
        return this.callParent(arguments);
    },
    /**
     * Overridden to evaluate correctly
     */
    targetTextEmpty: function(){
        var me = this,
            text, url;
        if (me.activeTarget && me.activeTarget.el) {
            text = me.activeTarget.el.getAttribute('data-qtip');
            url = me.activeTarget.el.getAttribute('data-qtipurl');
             // Note that the quicktip could also have been registered with the QuickTipManager.
             // If this was the case, then we don't want to veto showing it.
             // Simply do a lookup in the registered targets collection.
            if (!(text || url) && !me.targets[Ext.id(me.activeTarget.el.dom)]){
                return true;
            }
        }
        return false;
    }
});
