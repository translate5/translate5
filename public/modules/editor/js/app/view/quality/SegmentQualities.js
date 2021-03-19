
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
    requires: [ 'Editor.model.quality.SegmentQuality' ], 
    defaultType: 'checkbox',
    qualities: [],
    hidden: true,
    loader: {
        url: Editor.data.restpath+'quality/segment',
        loadMask: true,
        renderer: function(loader, response, active) {
            var me = loader.getTarget();
            var data = Ext.decode(response.responseText);
            if(data.total > 0){
                me.qualitiesLoaded(data.rows);
            }
        }
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
        for(var i=0; i < this.qualities.length; i++){
            if(this.qualities[i].id == qualityId){
                this.qualities[i].falsePositive = value;
                this.fireEvent('segmentQualitiyChanged', Ext.create('Editor.model.quality.SegmentQuality', this.qualities[i]), this);
                return;
            }
        }
    },
    qualitiesLoaded: function(qualities){
        this.qualities = qualities;
        var me = this;
        Ext.each(qualities, function(row){
            me.add({
                xtype: 'checkbox',
                anchor: '100%',
                name: 'segq' + row.id,
                inputValue: row.id,
                value: (row.falsePositive == '1'),
                boxLabel: row.typeTitle + ' > ' + row.title,
                // boxLabelAlign: 'before',
                handler: function(checkbox, checked){
                    me.changeFalsePositive(checkbox.inputValue, (checked ? '1' : '0'));
                }
            });
        });
        this.show();
    },
    /**
     * Starts editing. Loads the Segment's qualities and shows the GUI if qualities found
     * @param {Integer} segmentId for which the qualities should be loaded 
     */
    startEditing: function(segmentId){
        this.getLoader().load({
            params: { segmentId: segmentId }
        });
    },
    /**
     * Hides the GUI if present
     */
    endEditing: function(){
        if(this.qualities.length > 0){
            this.removeAll();
            this.qualities = [];
            this.hide();            
        }
    }
});

