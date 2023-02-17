
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
 * @class HtmlCleanup: Cleans Markup used by the Segment-Editor from invisible characters and injected special tags to retrieve markup for use outside of the editor
 * TODO: this Code has overlappings with Editor.util.SearchReplaceUtils
 */
Ext.define('Editor.util.HtmlCleanup', {
	
	/**
	 * entfernt vom Editor / TrackChanges automatisch hinzugefügte unsichtbare Zeichen und alle internen Tags
	 */
	cleanForLiveEditing: function(html){
	    return this.cleanAllEditingTags(this.cleanInvisibleCharacters(html));
	},
	/**
	 * entfernt vom Editor / TrackChanges automatisch hinzugefügte unsichtbare Zeichen
	 */
	cleanInvisibleCharacters: function(html){
		return html.replace(/\u200B|\uFEFF/g, '');
	},
	/**
	 * Cleans all editing tags added by the frontend: <ins, <del, <mark, invisible chars band the duplicatesave-images
	 * Cleans also term-tagger, spellchecker and qm-tags
	 * The cleaning of the latter expects the contents of those tags not to be complex HTML-structures, if this is the case, the code must be rewritten from a (fast) regex type to a complex markup parser
	 */
	cleanAllEditingTags: function(html){
	    html = this.cleanProtectInternalTags(html); // UGLY: stripping the tags with regex is prone to corrupt the structure of interleaving tags so we protect them
		html = this.cleanDuplicateSaveImgTags(html);
		html = this.cleanDeleteTags(html);
		html = this.cleanInsertTags(html);
		html = this.cleanMarkerTags(html);
		html = this.cleanQmTags(html);
		html = this.cleanSpellcheckTags(html);
		html = this.cleanTermTags(html);
		html = this.cleanUnprotectInternalTags(html); // undo internal-tag protection
		return html;
	},
	/**
	 * TODO: used by RowEditorColumnParts. needed there or replacable with cleanAllEditingTags ??
	 */
	cleanForSaveEditorContent: function(html){
		html = this.cleanDuplicateSaveImgTags(html);
		html = this.cleanDeleteTags(html);
		html = this.cleanInsertTags(html);
		return html;
	},
	/**
	 * Removes internal tags and replaces them with a split-value.
	 * Internal whitespace tags will be turned to appropriate markup and thus reflected in the Live Editing
	 * In this process open-single-close combinations will lead to a single split and close followed by such a construct or open predeceeded by such a construct will be reduced to one split.
	 * open-close combinations will be preserved as it can be assumed they once surrounded some text which was removed by the author
	 * @param string splitKey: defaults to "<t5split>"
	 * @return string: the cleaned text with split-values
	 */
	cleanAndSplitInternalTagsForLiveEditing: function(html, splitKey){
		if(!splitKey){
			splitKey = '<t5split>';
		}
		// replace whitespace-tags with rendered whitespace
		html = this.cleanInternalTags(html, "&nbsp;<t5split>", ['single','nbsp']);
		html = this.cleanInternalTags(html, "<br/><t5split>", ['single','newline']);
		html = this.cleanInternalTags(html, " &emsp;<t5split>", ['single','tab']);
		
		html = this.cleanInternalTags(html, "<t5open>", ['open']);
        html = this.cleanInternalTags(html, "<t5close>", ['close']);
		html = this.cleanInternalTags(html, "<t5single>", ['single']);
		// crucial: open/close sequences may contain just internal single tags and will be replaced as a whole
		html = html.replace(/<t5open>(<t5single>)*<t5close>/ig, '<t5split>');
		// neighbouring open-split or split-close construct are also replaced, only open/close combinations with real content in between (or no = empty real content) shall be kept
		html = html.replace(/<t5close>(<t5split>)+/ig, '<t5close>');
		html = html.replace(/(<t5split>)+<t5open>/ig, '<t5open>');
		// replace remaining open / close (and fore safety single as well)
		html = html.replace(/<t5open>/ig, splitKey);
		html = html.replace(/<t5close>/ig, splitKey);
		return html.replace(/<t5single>/ig, splitKey);
	},
	/**
     * Removes the "internal tags", div's with the classname "internal" and their contents. The replacement can be given, the default is the empty string
     * Multiple internal tags in a sequence are condensed to one replacement
     * @param string html: the markup to clean
     * @param string replacement: the replacement for the tag, defaults to ""
     * @param string itClassName: if set, can specify the classname of the internal tag to replace (can be "open", "close" or "single")
     * @return string: the cleaned text
     */
    cleanInternalTags: function(html, replacement, classNames){
        if(!replacement){
            replacement = '';
        }
        if(!classNames || classNames.length == 0){
            return html.replace(/<div[^>]+internal-tag[^>]+>.+?<\/div>/ig, replacement);
        }
        classNames = (classNames.length == 1) ? classNames[0] : classNames.join('[^>]+');
        var regex = new RegExp('<div[^>]+internal-tag[^>]+'+classNames+'[^>]+>.+?</div>', 'ig');
        html = html.replace(regex, replacement);
        var regex = new RegExp('<div[^>]+'+classNames+'[^>]+internal-tag[^>]+>.+?</div>', 'ig');
        return html.replace(regex, replacement);
    },
    /**
     * Protects internal tags for further regex-processing by replacing the tag-name
     * @param string html
     * @return string
     */
    cleanProtectInternalTags: function(html){
        return html.replace(/<div[^>]+internal-tag[^>]+>.+?<\/div>/ig, function(match){ return ("<t5intag" + match.substring(4, match.length - 6) + "</t5intag>"); });
    },
    /**
     * Reverses internal tags protected with ::cleanProtectInternalTags back to their proper form
     * @param string html
     * @return string
     */
    cleanUnprotectInternalTags: function(html){
        return html.split("<t5intag").join("<div").split("</t5intag>").join("</div>");
    },
	
	cleanDuplicateSaveImgTags: function(html){
		// (1) remove DEL-Tags and their content
		return html.replace(/<img[^>]* class="duplicatesavecheck"[^>]*>/,'');
	},
	
	cleanDeleteTags: function(html){
		// remove DEL-Tags and their content
		return html.replace(/<del[^>]*>.*?<\/del>/ig,'');
	},
	
	cleanInsertTags: function(html){
		 // remove INS-Tags and keep their content:
		return html.replace(/<ins[^>]*>/ig, '').replace(/<\/ins>/ig, '');
	},
	
	cleanMarkerTags: function(html){
		 // remove INS-Tags and keep their content:
		return html.replace(/<mark[^>]*>+|<\/mark>/ig, '');
	},
	
	cleanQmTags: function(html){
		// remove quality-management tags
		return this.cleanByTagAndClassWithContent(html, '', 'img', 'qmflag')
	},
	
	cleanSpellcheckTags: function(html){
		// removes any spellchecker tags
		return this.cleanByTagAndClassKeepContent(html, 'span', Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK);
	},

	cleanTermTags: function(html){
		// removes any  term-tagger tags
		html = this.cleanByTagAndClassKeepContent(html, 'span', 'term');
		return this.cleanByTagAndClassKeepContent(html, 'div', 'term');
	},
	/**
	 * Remove tags of a certain type with a certain class. Warning: removes also the tags content !
	 * For now, only images will be correctly cleaned as tags without end-tags
	 */
	cleanByTagAndClassWithContent: function(html, replacement, tagName, className){
		var regex = (tagName == 'img') ?
			new RegExp('<'+tagName+'[^>]* class="'+this.cleanCreateClassSelector(className)+'"[^>]*>', 'ig') 
			: new RegExp('<'+tagName+'[^>]* class="'+this.cleanCreateClassSelector(className)+'"[^>]*>.*?<\/'+tagName+'>', 'ig');
		return html.replace(regex, replacement);
	},
	/**
	 * Remove tags of a certain type with a certain class. Keeps the tags content
	 * Can only be used for tags with opening/closing tag obviously
	 * The Code is not suitable, if the inner HTML of the tag is further Markup and may fails in this case !!!
	 */
	cleanByTagAndClassKeepContent: function(html, tagName, className){
		var regex = new RegExp('<'+tagName+'[^>]* class="'+this.cleanCreateClassSelector(className)+'"[^>]*>(.*?)<\/'+tagName+'>', 'ig');
		return html.replace(regex, '$2');
	},
	/**
	 * Creates a selector that catches a certain classname within a class-attribute.
	 * Catches e.g. "term" if "term" was passed but not "someterm" or "atermxy"
	 */
	cleanCreateClassSelector: function(className){
		return '([^">]* '+className+' [^">]*|[^">]* '+className+'|'+className+' [^">]*|'+className+')';
	},
	/**
	 * Works just like PHPs strip_tags, much safer than just html.replace(/(<([^>]+)>)/ig,'')
	 */
	cleanHtmlTags: function(html, allowed){
		// making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
		allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
		var tags = /<\/?([a-z0-9]*)\b[^>]*>?/gi, comments = /<!--[\s\S]*?-->/gi, before;
		// removes tha '<' char at the end of the string to replicate PHP's behaviour
		html = (html.substring(html.length - 1) === '<') ? html.substring(0, html.length - 1) : html;
		// recursively remove tags to ensure that the returned string doesn't contain forbidden tags html previous passes (e.g. '<<bait/>switch/>')
		while (true) {
			before = html
			html = before.replace(comments, '').replace(tags, function (p0, p1){
				return allowed.indexOf('<' + p1.toLowerCase() + '>') > -1 ? p0 : '';
			});
			// return once no more tags are removed
			if (before === html){
				return html;
			}
		}
	}
});