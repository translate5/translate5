
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
 * The model for a quality filter entry
 * This involves specialized checked propagation methods
 */
Ext.define('Editor.model.quality.Filter', {
    extend: 'Ext.data.TreeModel',
    fields: [
        { name:'text', type:'string' },
        { name:'qid', type:'int' },
        { name:'qtype', type:'string' },
        { name:'qcount', type:'int', defaultValue:0 },
        { name:'qtotal', type:'int', defaultValue:0 },
        { name:'qcategory', type:'string', defaultValue:'---' },
        { name:'qcatidx', type:'int', defaultValue:-1 },
        { name:'qcomplete', type:'boolean', defaultValue:true },
        { name:'qfaulty', type:'boolean', defaultValue:false }
    ],
    /**
     * Note that this is a virtual root (= the first child of the store root !)
     */
    isQualityRoot: function(){
        return (this.get('qtype') == 'root');
    },
    isRubric: function(){
        return (this.get('qcategory') == '' && this.get('qtype') != 'root');
    },
    isCategory: function(){
        return (this.get('qcategory') != '' && this.get('qtype') != 'root');
    },
    isEmptyQuality: function(){
        return (this.get('qcategory') != '' && this.get('qtype') != 'root' && this.get('qcount') == 0);
    },
    isEmptyRubric: function(){
        return (this.isRubric() && this.get('qtotal') == 0);
    },
    hasNoCategories: function() {
        return this.get('qtype') != 'root' && this.get('qcategory') == '' && !this.get('children');
    },
    isEmpty: function(){
        if(this.get('qtype') == 'root' || this.get('qcategory') == ''){
            return (this.get('qtotal') == 0);
        }
        return (this.get('qcount') == 0);
    },
    isIncomplete: function(){
        return (this.get('qcomplete') == false);
    },
    isFaulty: function(){
        return (this.get('qfaulty') == true);
    },
    propagateChecked: function(checked){
        var isQRoot = this.isQualityRoot(), isRubric = this.isRubric();
        if(isQRoot || isRubric){
            this.propagateCheckedDown(checked);
        }
        if(!isQRoot && this.parentNode){
            this.parentNode.propagateCheckedUp(checked, (isRubric ? this.internalId : null));
        }
    },
    /**
     * Propagates the checked state down
     */
    propagateCheckedDown: function(checked){
        for(var i=0; i < this.childNodes.length; i++){
            if(!this.childNodes[i].isEmptyRubric()){
                if(!this.childNodes[i].isEmptyQuality()){
                    this.childNodes[i].set('checked', checked);
                }
                this.childNodes[i].propagateCheckedDown(checked);
            }
        }
    },
    /**
     * Propagate the checked state up
     */
    propagateCheckedUp: function(checked, rubricId){
        var isQRoot = this.isQualityRoot(), isRubric = this.isRubric();
        if(checked){
            if((isQRoot || isRubric) && this.allChildrenChecked(isRubric, rubricId)){
                this.set('checked', true);
            }
        } else {
            if((isQRoot || isRubric) && this.get('checked')){
                this.set('checked', false);
            } 
        }
        if(!isQRoot && this.parentNode){
            this.parentNode.propagateCheckedUp(checked);
        }
    },
    /**
     * Check, if our children are all checked (originating rubric (=id) will not be checked)
     */
    allChildrenChecked: function(deep, rubricId){
        if(rubricId == this.internalId){
            return this.get('checked');
        }
        for(var i=0; i < this.childNodes.length; i++){
            if(!this.childNodes[i].isEmptyRubric()){
                if((!this.childNodes[i].get('checked') && !this.childNodes[i].isEmptyQuality()) || (deep && !this.childNodes[i].allChildrenChecked(deep, rubricId))){
                    return false;
                }
            }
        }
        return true;
    },
    /**
     * Retrieves all children that are collapsed recursively
     * Collapsed nodes will not be investigated recursively
     */
    getCollapsedChildren: function(){
        var list = [];
        this.addCollapsedChildren(list);
        return list;
    },
    /**
     * Adds all children that are collapsed recursively
     */
    addCollapsedChildren: function(list){
        for(var i=0; i < this.childNodes.length; i++){
            // only expanded nodes need to be investigated recursively
            if(this.childNodes[i].isExpanded()){
                this.childNodes[i].addCollapsedChildren(list);
            } else {
                list.push(this.childNodes[i]);
            }
        }
    },
    /**
     * Returns the Key that will be used to identify a quality filter with a request
     */
    getTypeCatKey(){
        if(this.get('qcategory') == ''){

            // If current record has empty 'qcategory' prop and no children it means
            // that it's a quality having no categories, so in that case to keep filters
            // server-side compatibility we append ':<qtype>' here
            return this.get('qtype') + (this.get('children') ? '' : ':' + this.get('qtype'));
        }
        return this.get('qtype') + ':' + this.get('qcategory');
    }
});
