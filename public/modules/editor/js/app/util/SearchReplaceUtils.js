
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
 * @class SearchReplaceUtils
 */
Ext.define('Editor.util.SearchReplaceUtils', {
    mixins: ['Editor.util.SegmentEditor'],

    NODE_NAME_MARK: 'mark',
    NODE_NAME_DEL: 'del',
    NODE_NAME_INS: 'ins',

    CSS_CLASSNAME_REPLACED_INS:'searchreplace-replaced-ins',
    CSS_CLASSNAME_HIDE_ELEMENT:'searchreplace-hide-element',
    CSS_CLASSNAME_ACTIVE_MATCH:'searchreplace-active-match',

    //this regex will remove all mark html tags
    CLEAN_MARK_TAG_REGEX:/<mark[^>]*>+|<\/mark>/g,

    /***
     * Remove mark tags from the editor. The mark tags are used by search and replace
     */
    cleanMarkTags:function(){
        var me=this,
            cell = me.getEditorBody();
        
        if(!cell){
            return false;
        }
        cellHTML = cell.innerHTML.replace(me.CLEAN_MARK_TAG_REGEX, "");
        cell.innerHTML = cellHTML;
    },

    /***
    * Remove the replace node css class from the ins tags.
    * TODO:"removeReplaceClass" and "prepareDelNodeForSearch" as one function
    */
    removeReplaceClass:function(){
        var me = this;
        if(!me.getEditorBodyExtDomElement()){
            return;
        }

        var insNodes=me.getEditorBodyExtDomElement().query(me.NODE_NAME_INS),
            arrLength=insNodes.length;
        
        for (i = 0; i < arrLength; i++){
            node = insNodes[i];
            me.removeClass(node,me.CSS_CLASSNAME_REPLACED_INS);
        }
 
    },

    /***
     * Add or remove the display class to the del nodes.
     * If true, the class will be add else removed.
     */
    prepareDelNodeForSearch:function(addClass){
        var me = this;
        if(!me.getEditorBodyExtDomElement()){
            return;
        }
        
        var delNodes=me.getEditorBodyExtDomElement().query(me.NODE_NAME_DEL),
            arrLength = delNodes.length; 
        
        for (i = 0; i < arrLength; i++){
            node = delNodes[i];
            //node.style.display=displayValue;
            if(addClass){
                me.addClass(node,me.CSS_CLASSNAME_HIDE_ELEMENT);
            }else{
                me.removeClass(node,me.CSS_CLASSNAME_HIDE_ELEMENT);
            }
        }
    },

    /***
    * Check if the given range contains node with replaced ins class
    */
    hasReplacedClass:function(range){
        var me = this,
            nodes;
        nodes = range.getNodes([1,3], function(node) {
            if(node.parentNode && me.hasClass(node.parentNode,me.CSS_CLASSNAME_REPLACED_INS)){
                return node;
            }
            return false;
        });
        
        return (nodes.length>0);
    },


    /***
     * Set the active match css class to all mark tagis within the active range
     */
    setActiveMatchClass:function(bookmarRange){
        if(!bookmarRange){
            return;
        }
        var me = this,
            range = rangy.createRange();
        
        range.moveToBookmark(bookmarRange);

        range.getNodes([1,3], function(node) {
            if(node.nodeName.toLowerCase()===me.NODE_NAME_MARK){
                me.addClass(node,me.CSS_CLASSNAME_ACTIVE_MATCH);
                return;
            }
            if(node.parentNode.nodeName.toLowerCase()===me.NODE_NAME_MARK){
                me.addClass(node.parentNode,me.CSS_CLASSNAME_ACTIVE_MATCH);
            }
        });

        delete range;
    },
    
    /**
	 * Method that checks whether cls is present in element object.
	 * @param  {Object} ele DOM element which needs to be checked
	 * @param  {Object} cls Classname is tested
	 * @return {Boolean} True if cls is present, false otherwise.
	 */
	hasClass:function(ele, cls) {
        if(!ele.getAttribute('class')){
            return false;
        }
	    return ele.getAttribute('class').indexOf(cls) > -1;
	},

	/**
	 * Method that adds a class to given element.
	 * @param  {Object} ele DOM element where class needs to be added
	 * @param  {Object} cls Classname which is to be added
	 * @return {null} nothing is returned.
	 */
	addClass:function(ele, cls) {
	    if (ele.classList) {
		    ele.classList.add(cls);
	    } else if (!hasClass(ele, cls)) {
		    ele.setAttribute('class', ele.getAttribute('class') + ' ' + cls);
	    }
	},

	/**
	 * Method that does a check to ensure that class is removed from element.
	 * @param  {Object} ele DOM element where class needs to be removed
	 * @param  {Object} cls Classname which is to be removed
	 * @return {null} Null nothing is returned.
	 */
	removeClass:function(ele, cls) {
	    if (ele.classList) {
		    ele.classList.remove(cls);
	    } else if (hasClass(ele, cls)) {
		    ele.setAttribute('class', ele.getAttribute('class').replace(cls, ' '));
	    }
	}
});