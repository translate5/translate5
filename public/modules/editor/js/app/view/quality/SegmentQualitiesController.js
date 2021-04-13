
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
 * View Controller for the segment quality panel
 * When the editor changes the falsePositive prop of a quality. the change is done immediately in case of a quality without tags in the editor
 * This contradicts the behaviour of the other panels in the east panel, which only save on commit of the editor
 * For qualities with tags visible in the HtmlEditor the change is done via Ajax AND in the HtmlEditor so it is done when saving or canceling the edit
 */
Ext.define('Editor.view.quality.SegmentQualitiesController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentQualities',
    falsePositiveCssClass: 't5qfalpos', // as defined in editor_segment_Tag. TODO FIXME: add to Editor.data ?
    listen: {
        store: {
            '#SegmentQualities': {
                load: 'onStoreLoaded'
            }
        }
    },
    /**
     * Creates the checkbox components after a store load & evaluates the visibility of our view
     */
    onStoreLoaded: function(store){
        console.log('onStoreLoaded', store);
        var me = this, view = me.getView(), added = false;
        if(store.getCount() > 0){
            store.each(function(record, idx){
                if(record.get('falsifiable')){
                    view.addCheckbox(record);
                    added = true;
                }
            });
        }
        if(added){
            view.show();
        } else {
            view.endEditing();
        }
    },
    onFalsePositiveChanged: function(checkbox, checked){
        var qualityId = checkbox.inputValue, record = checkbox.qrecord, falsePositiveVal = (checked) ? 1 : 0;
        // if there are tags in the editor we need to decorate them (otherwise saving the editor would set the falsePositive value back to it's original state!)
        if(record.get('hasTag') && !this.decoratefalsePositive(record, qualityId, checked)){
            console.log('DECORATE FALSE POSITIVE FAILED: ', qualityId, falsePositiveVal, record);
        }
        Ext.Ajax.request({
            url: Editor.data.restpath+'quality/falsepositive',
            method: 'GET',
            params: { id: qualityId, falsePositive: falsePositiveVal },
            success: function(response){
                record.set('falsePositive', falsePositiveVal); // updating store currently meaningless but who knows ...
                console.log('CHANGED FALSE POSITIVE: ', qualityId, falsePositiveVal, record);
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    /**
     * Changes the decoration-class in the HtmlEditor of the tag
     */
    decoratefalsePositive: function(record, qualityId, checked){ 
        // reference to htmlEditor is somehow dirty, may add a global API to achieve this ? Hint: we're created too late to catch the HtmlEditors init event
        var htmlEditor = Editor.app.getController('Editor').htmlEditor, fpc = this.falsePositiveCssClass;
        if(htmlEditor){
            var elements = htmlEditor.getElementsByProps(record.get('tagName'), record.get('cssClass'), [{ name: record.get('dataNameId'), value: qualityId }]);
            if(elements){
                elements.forEach(function(element){
                    if(checked && !element.classList.contains(fpc)){
                        element.classList.add(fpc);
                    } else if(!checked && element.classList.contains(fpc)){
                        element.classList.remove(fpc);
                    }
                });
                return true;
            }
        }
        return false;
    }
});
