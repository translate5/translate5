
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
 * Shows the Qualities for a Segment and enables to set those as false positive or not
 */
Ext.define('Editor.view.quality.SegmentQualities', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.segmentQualities',
    title: "#UT#QA: Falsch-Positive",
    requires: [
        'Editor.view.quality.SegmentQualitiesController'        
    ],
    controller: 'segmentQualities',
    cls: 'segmentQualities',
    defaultType: 'checkbox',
    hidden: true,
    isActive: false,
    initConfig: function(instanceConfig) {
        var config = {
            title: this.title
        };
        if (instanceConfig) {
            this.self.getConfigurator().merge(this, config, instanceConfig);
        }
        return this.callParent([config]);
    },
    /**
     * Creates the checkbox components after a store load & evaluates the visibility of our view
     * @param {Editor.model.quality.Segment[]} records
     * @param {Integer} segmentId for which the qualities should be loaded
     * @param {boolean} isActive: if the component is active
     */
    startEditing: function(records, segmentId, isActive){
        this.isActive = isActive;
        if(isActive && records.length > 0){
            this.createCheckboxes(records);
        }
    },
    /**
     * Updates our view after a store change
     * For simplicity we simply recreate it
     */
    rebuildByRecords: function(records){
        if(this.isActive){
            this.createCheckboxes(records);
        }
    },
    /**
     * Creates our GUI
     */
    createCheckboxes: function(records){
        var added = false;
        this.removeAll();
        Ext.each(records, function(record){
            if(record.get('falsifiable')){
                this.addCheckbox(record);
                added = true;
            }
        }, this);
        if(added){
            this.show();
        } else {
            this.endEditing(true, false);
        }
    },
    /**
     * Adds a checkbox after the store was loaded
     */
    addCheckbox: function(record){
        // add the tag-icons for MQM to help to identify the MQMs in the markup
        var label = record.get('typeText') + ' > ' + record.get('text');
        if(record.get('type') == 'mqm' && record.get('categoryIndex') > -1){
            label += ' <img class="x-label-symbol qmflag qmflag-' + record.get('categoryIndex') + '" src="' 
                + Editor.data.segments.subSegment.tagPath + 'qmsubsegment-' + record.get('categoryIndex') + '-left.png"> ';
        }
        this.add({
            xtype: 'checkbox',
            anchor: '100%',
            name: 'segq' + record.get('id'),
            inputValue: record.get('id'),
            value: (record.get('falsePositive') == 1),
            qrecord: record,
            boxLabel: label,
            listeners:{
                change: 'onFalsePositiveChanged'
            }
        });
    },
    /**
     * Hides the GUI if present
     */
    endEditing: function(isActive, isSaving){
        if(isActive){
            this.removeAll();
            this.hide();
        }
    }
});

