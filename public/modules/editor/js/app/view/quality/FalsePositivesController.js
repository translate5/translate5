
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
Ext.define('Editor.view.quality.FalsePositivesController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.falsePositives',
    listen: {
        store: {
            '#SegmentQualities': {
                add: 'onQualitiesChanged',
                remove: 'onQualitiesChanged'
            }
        }
    },
    messages: {
        falsePositiveUpdated: '#UT#Der False-Positive Status wurde aktualisiert'
    },
    falsePositiveCssClass: 't5qfalpos', // as defined in editor_segment_Tag::CSS_CLASS_FALSEPOSITIVE. TODO FIXME: better add to Editor.data ?
    qualityIdDataName: 't5qid', // as defined in editor_segment_Tag::DATA_NAME_QUALITYID. TODO FIXME: better add to Editor.data ?
    /**
     * When QMs are set/unset, our store will have entries added/removed an we have to reflect this
     */
    onQualitiesChanged: function(store){
        this.getView().rebuildByRecords(store.getRange());
    },
    /**
     * Handler to sync the new state with the server (to catch false positives without tags) & add decorations in the editor
     */
    onFalsePositiveChanged: function(checkbox, checked){
        var me = this, qualityId = checkbox.inputValue, record = checkbox.qrecord, falsePositiveVal = (checked) ? 1 : 0;
        // if there are tags in the editor we need to decorate them (otherwise saving the editor would set the falsePositive value back to it's original state!)
        if(record.get('hasTag') && !this.decorateFalsePositive(record, qualityId, checked)){
            // This will be a rare case, mostly whith transitional tasks being imported before the rollout of the AutoQA but used thereafter
            console.log('Decorating a false positive tag failes: ', qualityId, falsePositiveVal, record);
        }
        Ext.Ajax.request({
            url: Editor.data.restpath+'quality/falsepositive',
            method: 'GET',
            params: { id: qualityId, falsePositive: falsePositiveVal },
            success: function(response){
                record.set('falsePositive', falsePositiveVal); // updating store is currently meaningless but that may changes later on ...
                Editor.MessageBox.addSuccess(me.messages.falsePositiveUpdated);
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },
    /**
     * Changes the decoration-class in the HtmlEditor of the tag
     */
    decorateFalsePositive: function(record, qualityId, checked){
        // reference to htmlEditor is somehow dirty, may add a global API to achieve this ? Hint: we're created too late to catch the HtmlEditors init event
        var htmlEditor = Editor.app.getController('Editor').htmlEditor,
            selector = Editor.util.Util.createSelectorFromProps(null, record.get('cssClass'), [{ name: this.qualityIdDataName, value: qualityId }]);
        if(htmlEditor){
            // quirk: we can not use the tag-name because in the html-editor-markup these may are changed
            if(this.decorateElements(htmlEditor.getElementsBySelector(selector), checked)){
                return true;
            }
        }
        // if not found in the html-editor we search in the other contents of the html-editor. This is only for optical reasons
        htmlEditor = Ext.ComponentQuery.query('#roweditor')[0];
        if(htmlEditor){
            elements = htmlEditor.getEl().dom.querySelectorAll('.segment-tag-container ' + selector);
            return this.decorateElements(elements, checked);
        }
        return false;
    },
    /**
     * Decorates a list of elements with the false-positive decorators
     */
    decorateElements: function(elements, checked){
        var fpc = this.falsePositiveCssClass, cfpc;
        if(elements && elements.length > 0){
            elements.forEach(function(element){
                cfpc = element.classList.contains(fpc);
                if(checked && !cfpc){
                    element.classList.add(fpc);
                } else if(!checked && cfpc){
                    element.classList.remove(fpc);
                }
            });
            return true;
        }
        return false;
    }
});
