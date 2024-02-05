
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

    statics: {

        /**
         * Update data-t5qfp="true/false" attribute for the quality tag/node
         *
         * @param {int} qualityId
         * @param {boolean} falsePositive
         */
        applyFalsePositiveStyle: function(qualityId, falsePositive) {
            // Get quality tags/nodes
            var tags = document.querySelectorAll('[data-t5qid="' + qualityId + '"]'),
                tip = Editor.data.l10n.falsePositives.hover, cell, row, rid, rec;

            // If found - update data-t5qfp="" attribute
            tags.forEach(tag => {

                // Update data-t5qfp attr
                tag.setAttribute('data-t5qfp', falsePositive ? 'true' : 'false');

                // Set/remove data-qtip attr
                if(falsePositive){
                    tag.removeAttribute('data-qtip');
                } else {
                    tag.setAttribute('data-qtip', tip);
                }

                // If tag is inside source-column
                cell = tag.closest('td[data-columnid="sourceColumn"]');
                if (cell) {

                    // Get record
                    row = cell.closest('table.x-grid-item');
                    rid = row.getAttribute('data-recordid');
                    rec = Ext.getCmp(row.getAttribute('data-boundview')).getStore().getByInternalId(rid);

                    // Update source, so that updated value will be picked by segmenteditor once opened
                    tag.removeAttribute('id');
                    rec.set('source', cell.querySelector('.x-grid-cell-inner').innerHTML + '');

                    // Set up sourceUpdated-flag to prevent endless loop
                    rec.set('sourceUpdated', true);
                    rec.commit();
                }
            });
        }
    },

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
        var qualityId = record.get('id'), falsePositive = (checked) ? 1 : 0,
            other, otherRec;

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

                // Commit changes
                record.commit();

                // Update data-t5qfp="true/false" attribute for the quality tag/node
                Editor.view.quality.FalsePositivesController.applyFalsePositiveStyle(record.get('id'), falsePositive);

                // Prepare component query selector for other instance of falsePositive-panel
                other = 'falsePositives[floating=' + (!column.up('fieldset').floating).toString() + ']';

                // If other instance of falsePositive-panel exists
                if (other = Ext.ComponentQuery.query(other).pop()) {

                    // Replicate change of falsePositive-prop to the corresponding quality-record
                    if (otherRec = other.down('grid').getStore().getById(record.get('id'))) {
                        otherRec.set('falsePositive', falsePositive);
                        otherRec.commit();
                    }
                }

                // Get quality filter panel
                var qfp = Ext.ComponentQuery.query('qualityFilterPanel').pop();

                // Reload qualities tree
                if (qfp) {
                    qfp.getController().reloadKeepingFilterVal();
                }

                // Hide floating panel if click came from there
                column.up('fieldset[floating]')?.hide();
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
     * Spread falsePositive-flag's state for all other occurrences of such [quality - content] pair across the task
     *
     * @param button
     */
    onFalsePositiveSpread: function(button) {
        var vm = this.getViewModel(), record = button.getWidgetRecord(), other;

        // Change the value in the checkbox-column
        record.set('falsePositive', record.get('falsePositive') ? 0 : 1);

        // Make request to spread
        Ext.Ajax.request({
            url: Editor.data.restpath + 'quality/falsepositivespread',
            method: 'GET',
            params: {
                id: record.get('id')
            },
            success: (xhr) => {
                var json;

                // Commit changes
                record.commit();

                // Prepare component query selector for other instance of falsePositive-panel
                other = 'falsePositives[floating=' + (!button.up('fieldset')?.floating).toString() + ']';

                // Show tast message
                Editor.MessageBox.addSuccess(vm.get('l10n.falsePositives.spreaded'));

                // If response is json-decodable
                if (json = Ext.JSON.decode(xhr.responseText, true)) {

                    // Update data-t5qfp="true/false" attribute for the similar qualities tags/nodes
                    json.ids.forEach((id) => Editor.view.quality.FalsePositivesController.applyFalsePositiveStyle(id, record.get('falsePositive')));
                }

                // Hide floating panel if click came from there
                button.up('fieldset[floating]')?.hide();
            },
            failure: response => {

                // Reject changes
                record.reject();

                // Handle exception
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
     * @param rowIndex
     * @param colIndex
     * @param store
     * @param view
     * @returns {string}
     */
    falsepositivesGridTextRenderer: function(text, meta, record, rowIndex, colIndex, store, view) {
        meta.tdCls += ' quality';

        // Build label
        var qlty = record.get('typeText');
        if (qlty !== text) {
            qlty += ' » ' + text;
        }

        // Category index shortcut
        var cidx = record.get('categoryIndex');

        // Add the tag-icons for MQM to help to identify the MQMs in the markup
        if (record.get('type') === 'mqm' && cidx > -1) {

            // Build tag-icon src
            var src = Editor.data.segments.subSegment.tagPath + 'qmsubsegment-{0}-left.png';

            // Append img-tag to quality title
            qlty += ' <img class="x-label-symbol qmflag qmflag-{0}" src="' + src + '"> ';
        }

        // Apply qtip for record if we're NOT inside floating false-positives panel
        if (!view.up('falsePositives[floating]')) {
            if (rowIndex < 10) {
                meta.tdAttr = 'data-qtip="Ctrl + Alt + ' + (rowIndex === 9 ? 0 : rowIndex + 1) + '"';
            }
        }

        // Return
        return '<div>' + Ext.String.format(qlty, cidx) + '</div><div>' + (record.get('content') || 'no content') + '</div>';
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
            var elements = htmlEditor.getEl().dom.querySelectorAll('.segment-tag-container ' + selector);
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
