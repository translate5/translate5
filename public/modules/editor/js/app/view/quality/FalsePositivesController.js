
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
    falsePositiveCssClass: 't5qfalpos', // as defined in editor_segment_Tag::CSS_CLASS_FALSEPOSITIVE. TODO FIXME: better add to Editor.data ?
    qualityIdDataName: 't5qid', // as defined in editor_segment_Tag::DATA_NAME_QUALITYID. TODO FIXME: better add to Editor.data ?

    /**
     * When QMs are set/unset, our store will have entries added/removed an we have to reflect this
     */
    onQualitiesChanged: function(store){
        this.getView().loadFalsifiable(store.getRange());
    },

    /**
     * Handler to sync the new state with the server (to catch false positives without tags) & add decorations in the editor
     */
    onFalsePositiveChanged: function(column, rowIndex, checked, record){
        var me = this, vm = this.getViewModel(), qualityId = record.get('id'), falsePositive = (checked) ? 1 : 0;

        // If there are tags in the editor we need to decorate them
        // as otherwise saving the editor would set the falsePositive value back to it's original state!
        if (record.get('hasTag') && !this.decorateFalsePositive(record, qualityId, checked)) {

            // This will be a rare case, mostly with transitional tasks being imported before the rollout of the AutoQA but used thereafter
            console.log('Decorating a false positive tag failes: ', qualityId, falsePositive, record);
        }

        // Set falsePositive-flag on quality record on client-side
        record.set('falsePositive', falsePositive);

        // Set falsePositive-flag on quality record on server-side
        Ext.Ajax.request({
            url: Editor.data.restpath + 'quality/falsepositive',
            method: 'GET',
            params: {
                id: qualityId,
                falsePositive: falsePositive
            },
            success: () => {

                // Set falsePositiveChanged-flag so that spread-button-widget will be enabled in the last grid column
                record.set('falsePositiveChanged', 1);

                // Commit changes
                record.commit();

                // Show toast message
                Editor.MessageBox.addSuccess(vm.get('l10n.falsePositives.msg.updated'));

                // Update data-t5qfp="true/false" attribute for the quality tag/node
                me.applyFalsePositiveStyle(record.get('id'), falsePositive);
            },
            failure: (response) => {

                // Reject changes
                record.reject();

                // Handle response
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /**
     * Update data-t5qfp="true/false" attribute for the quality tag/node
     *
     * @param qualityId
     * @param falsePositive
     */
    applyFalsePositiveStyle: function(qualityId, falsePositive) {

        // Get quality tag/node
        var qnode = document.querySelector('[data-t5qid="' + qualityId + '"]');

        // If found - update data-t5qfp="" attribute
        if (qnode) qnode.setAttribute('data-t5qfp', falsePositive ? 'true' : 'false');
    },

    /**
     * Spread falsePositive-flag's state for all other occurrences of such [quality - content] pair across the task
     *
     * @param button
     */
    onFalsePositiveSpread: function(button) {
        var me = this, vm = this.getViewModel(), record = button.getWidgetRecord();

        // Make request to spread
        Ext.Ajax.request({
            url: Editor.data.restpath + 'quality/falsepositivespread',
            method: 'GET',
            params: {
                id: record.get('id')
            },
            success: (xhr) => {
                var json;

                // Set falsePositiveChanged-flag so that spread-button-widget will be disabled
                // in the last grid column until the next time value changed in first column
                record.set('falsePositiveChanged', 0);

                // Commit changes
                record.commit();

                // Show tast message
                Editor.MessageBox.addSuccess(vm.get('l10n.falsePositives.msg.spreaded'));

                // If response is json-decodable
                if (json = Ext.JSON.decode(xhr.responseText, true)) {

                    // Update data-t5qfp="true/false" attribute for the similar qualities tags/nodes
                    json.ids.forEach((id) => me.applyFalsePositiveStyle(id, record.get('falsePositive')));
                }
            },
            failure: (response) => {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /**
     * Renderer function for falsepositives-grid's column having [dataIndex=text]
     *
     * @param text
     * @param meta
     * @param record
     * @returns {string}
     */
    falsepositivesGridTextRenderer: function(text, meta, record) {
        meta.tdCls += ' quality';

        // Build label
        var qlty = record.get('typeText'); if (qlty !== text) qlty += ' » ' + text;

        // Category index shortcut
        var cidx = record.get('categoryIndex');

        // Add the tag-icons for MQM to help to identify the MQMs in the markup
        if (record.get('type') === 'mqm' && cidx > -1) {

            // Build tag-icon src
            var src = Editor.data.segments.subSegment.tagPath + 'qmsubsegment-{0}-left.png';

            // Append img-tag to quality title
            qlty += ' <img class="x-label-symbol qmflag qmflag-{0}" src="' + src + '"> ';
        }

        return '<div>' + Ext.String.format(qlty, cidx) + '</div><div>' + record.get('content') + '</div>';
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
