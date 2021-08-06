
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
 * Shows the possible QMs for a Segment and enables to set them
 */
Ext.define('Editor.view.quality.SegmentQm', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.segmentQm',
    title: "#UT#Manuelle QS (ganzes Segment)",
    requires: [
        'Editor.view.quality.SegmentQmController'
    ],
    controller: 'segmentQm',
    cls: 'segmentQM',
    defaultType: 'checkbox',
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
     * Starts editing with the loaded records
     * @param {Editor.model.quality.Segment[]} records
     * @param {Integer} segmentId for which the qualities should be loaded
     * @param {boolean} isActive: if the component is active
     */
    startEditing: function(records, segmentId, isActive){
        if(isActive){
            this.labels = [];
            this.removeAll();
            var selectedIds = [];
            Ext.each(records, function(record){
                if(record.get('type') == 'qm'){
                    selectedIds.push(record.get('categoryIndex'));
                }
            });
            Ext.each(Editor.data.segments.qualityFlags, function(item){
                this.add({
                    xtype: 'checkbox',
                    name: 'segmentQm', 
                    anchor: '100%',
                    checked: Ext.Array.contains(selectedIds, item.id),
                    inputValue: item.id,
                    boxLabel: item.label,
                    segmentId: segmentId,
                    listeners:{
                        change: 'onQmChanged'
                    }
                });
            }, this);
        }
    },
    startTaskEditing: function(){
        this.createInactiveCheckBoxes();
    },
    /**
     * Ends editing, invalidates our checkboxes (by adding new checkboxes without listeners. It was not possible, to remove a listener that was autoconnected to our ViewControler...
     */
    endEditing: function(isActive, isSaving){
        if(isActive){
            this.removeAll();
            this.createInactiveCheckBoxes();
        }
    },
    /**
     * Creates the boxes that represent the QMs in the inactive state
     */
    createInactiveCheckBoxes: function(){
        Ext.each(Editor.data.segments.qualityFlags, function(item){
            this.add({
                xtype: 'checkbox',
                anchor: '100%',
                checked: false,
                boxLabel: item.label,
                disabled: true
            });
        }, this);
    }
});

