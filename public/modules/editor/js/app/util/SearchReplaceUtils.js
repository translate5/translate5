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

// Move methods to SearchReplace controller and delete this file
Ext.define('Editor.util.SearchReplaceUtils', {
    mixins: ['Editor.util.SegmentEditor'],

    NODE_NAME_MARK: 'mark',
    NODE_NAME_DEL: 'del',
    NODE_NAME_INS: 'ins',

    CSS_CLASSNAME_REPLACED_INS: 'searchreplace-replaced-ins',
    CSS_CLASSNAME_HIDE_ELEMENT: 'searchreplace-hide-element',
    CSS_CLASSNAME_ACTIVE_MATCH: 'searchreplace-active-match',

    //this regex will remove all mark html tags
    CLEAN_MARK_TAG_REGEX: /<mark[^>]*>+|<\/mark>/g,

    /**
     * Remove mark tags from the editor. The mark tags are used by search and replace
     */
    cleanMarkTags: function () {
        if (!this.editor) {
            return;
        }

        this.editor.editor.unmarkAll();
    },

    /**
     * Check if the given range contains node with replaced ins class
     */
    hasClassInRange: function (cls, range) {
        const me = this;
        const nodes = range.getNodes([1, 3], function (node) {
            if (node.parentNode && me.hasClass(node.parentNode, cls)) {
                return node;
            }

            return false;
        });

        return (nodes.length > 0);
    },

    /**
     * Check if the given range contains node with replaced ins class
     */
    isDeletion: function (range) {
        const me = this;
        const nodes = range.getNodes([1, 3], function (node) {
            if (node.parentNode && node.parentNode.nodeName.toLowerCase() === me.NODE_NAME_DEL) {
                return node;
            }

            return false;
        });

        return (nodes.length > 0);
    },

    /**
     * Method that checks whether cls is present in element object.
     * @param  {Object} node DOM element which needs to be checked
     * @param  {Object} cls Classname is tested
     * @return {Boolean} True if cls is present, false otherwise.
     */
    hasClass: function (node, cls) {
        if (!node.getAttribute('class')) {
            return false;
        }

        return node.getAttribute('class').indexOf(cls) > -1;
    },

    /**
     * Method that adds a class to given element.
     * @param  {Object} node DOM element where class needs to be added
     * @param  {Object} cls Classname which is to be added
     * @return {null} nothing is returned.
     */
    addClass: function (node, cls) {
        if (node.classList) {
            node.classList.add(cls);
        } else if (!this.hasClass(node, cls)) {
            node.setAttribute('class', node.getAttribute('class') + ' ' + cls);
        }
    },

    /**
     * Method that does a check to ensure that class is removed from element.
     * @param  {Object} node DOM element where class needs to be removed
     * @param  {Object} cls Classname which is to be removed
     * @return {null} Null nothing is returned.
     */
    removeClass: function (node, cls) {
        if (node.classList) {
            node.classList.remove(cls);
        } else if (this.hasClass(node, cls)) {
            node.setAttribute('class', node.getAttribute('class').replace(cls, ' '));
        }
    }
});
