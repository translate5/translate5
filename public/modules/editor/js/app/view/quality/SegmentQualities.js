
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
    title: "#UT#QA: Falsch Positive",
    requires: [ 'Editor.store.quality.Segment' ], 
    defaultType: 'checkbox',
    hidden: true,
    initConfig: function(instanceConfig) {
        var config = {
            store: Ext.create('Editor.store.quality.Segment')
        };
        if (instanceConfig) {
            this.self.getConfigurator().merge(this, config, instanceConfig);
        }
        return this.callParent([config]);
    },
    changeFalsePositive: function(qualityId, value){
        var me = this;
        Ext.Ajax.request({
            url: Editor.data.restpath+'quality/falsepositive',
            method: 'GET',
            params: { id: qualityId, falsePositive: value },
            success: function(response){
                me.falsePositiveChanged(qualityId, value);             
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });        
    },
    falsePositiveChanged: function(qualityId, value){
        this.store.updateRecordProp(qualityId, 'falsePositive', value);
    },
    qualitiesLoaded: function(qualities){
        var me = this;
        if(this.store.getCount() == 0){
            this.endEditing();
        } else {
            this.store.each(function(record, idx){
                me.add({
                    xtype: 'checkbox',
                    anchor: '100%',
                    name: 'segq' + record.get('id'),
                    inputValue: record.get('id'),
                    value: (record.get('falsePositive') == 1),
                    boxLabel: record.get('typeText') + ' > ' + record.get('text'),
                    disabled: !record.get('falsifiable'),
                    // boxLabelAlign: 'before',
                    handler: function(checkbox, checked){
                        me.changeFalsePositive(checkbox.inputValue, (checked ? '1' : '0'));
                    }
                });
            });
            this.show();
        }
    },
    /**
     * Starts editing. Loads the Segment's qualities and shows the GUI if qualities found
     * @param {Integer} segmentId for which the qualities should be loaded 
     */
    startEditing: function(segmentId){
        var me = this;
        this.store.load({
            scope: this,
            params: { segmentId: segmentId },
            callback: function(){ this.qualitiesLoaded(); }
        });
    },
    /**
     * Hides the GUI if present
     */
    endEditing: function(){
        this.removeAll();
        this.hide();            
    }
});

