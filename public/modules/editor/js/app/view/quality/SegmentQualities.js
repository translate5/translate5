
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
    /**
     * Changes a false positive value, via request and via GUI
     */
    changeFalsePositive: function(qualityId, checked, record){
        if(record.get('hasTag')){
            this.decoratefalsePositive(qualityId, checked, record);
        }
        Ext.Ajax.request({
            url: Editor.data.restpath+'quality/falsepositive',
            method: 'GET',
            params: { id: qualityId, falsePositive: (checked ? '1' : '0') },
            success: function(response){
                // TODO AUTOQA: remove
                console.log('CHANGED FALSE POSITIVE!!', qualityId, checked, record.get('type'));
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });        
    },
    /**
     * Changes the decoration-class in the editor of the tag
     */
    decoratefalsePositive: function(qualityId, checked, record){
     // TODO AUTOQA: implement
        console.log('decoratefalsePositive: ', qualityId, checked, record);
        // CSS_CLASS_FALSEPOSITIVE = 't5qfalpos'
        // DATA_NAME_QUALITYID = 't5qid'; with MQM: seq
    },
    /**
     * Creates the view from the loaded data. Note, that QM qualities are not falsifyable
     */
    qualitiesLoaded: function(qualities){
        var me = this, added = false;
        if(this.store.getCount() > 0){
            this.store.each(function(record, idx){
                if(record.get('falsifiable')){
                    me.add({
                        xtype: 'checkbox',
                        anchor: '100%',
                        name: 'segq' + record.get('id'),
                        inputValue: record.get('id'),
                        value: (record.get('falsePositive') == 1),
                        qrecord: record,
                        boxLabel: record.get('typeText') + ' > ' + record.get('text'),
                        handler: function(checkbox, checked){
                            me.changeFalsePositive(checkbox.inputValue, checked, checkbox.qrecord);
                        }
                    });
                    added = true;
                }
            });
        }
        if(added){
            this.show();
        } else {
            this.removeAll();
            this.hide();
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

