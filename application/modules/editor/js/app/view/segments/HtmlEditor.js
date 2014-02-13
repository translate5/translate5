/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Erweitert den Ext Standard HTML Editor um 
 *  - Markup und Unmarkup Funktionen der Editor Html Tags
 *  - Methoden für das Umschalten zwischen den verschiedenen Tag Modi im HTMLEditor
 *  überschreibt Methoden die das gewünschte Verhalten verhindern 
 * @class Editor.view.segments.HtmlEditor
 * @extends Ext.form.field.HtmlEditor
 */
Ext.define('Editor.view.segments.HtmlEditor', {
  extend: 'Ext.form.field.HtmlEditor',
  alias: 'widget.segmentsHtmleditor',
  markupImages: null,
  //prefix der img Tag ids im HTML Editor
  idPrefix: 'tag-image-',

  //Konfiguration der parent Klasse
  enableFormat: false,
  enableFontSize : false,
  enableColors : false,
  enableAlignments : false,
  enableLists : false,
  enableSourceEdit : false, //FIXME this value should be obsolete
  enableLinks : false,
  enableFont : false,
  isTagOrderClean: true,
  
  strings: {
	  errorTitle: '#UT# Fehler bei der Segment Validierung!',
	  tagOrderErrorText: '#UT# Einige der im Segment verwendeten Tags sind in der falschen Reihenfolgen (schließender vor öffnendem Tag).',
	  tagRemovedText: '#UT# Es wurden Tags mit fehlendem Partner entfernt!'
  },

  //hilfsvariable für die "letzte Segment anzeigen" Funktionalität beim Verlassen des Browsers. 
  lastSegmentContentWithoutTags: null,
  
  initComponent: function() {
    var me = this;
    me.viewModesController = Editor.controller.ViewModes;
    me.imageTemplate = new Ext.Template([
      '<img id="'+me.idPrefix+'{key}" class="{type}" title="{text}" alt="{text}" src="{path}"/>'
    ]);
    me.imageTemplate.compile();
    me.spanTemplate = new Ext.Template([
      '<span title="{text}" class="short">&lt;{shortTag}&gt;</span>',
      '<span id="{id}" class="full">{text}</span>'
    ]);
    me.spanTemplate.compile();
    me.callParent(arguments);
  },
  initFrameDoc: function() {
	  this.callParent(arguments);
	  this.fireEvent('afterinitframedoc', this);
  },
  initEditor: function() {
	  var me = this, 
	  	body = me.getEditorBody(),
	  	id;
	  if(!body){
		  //if body does not exists, the browser (mostly IE) is not ready so call again a little more deffered as the default 10ms
		  if(!me.deferred){
                      me.deferred = 0;
                  }
                  me.deferred = me.deferred + 150; //prevent endless loops
                  if(me.deferred < 15000){
                      Ext.defer(me.initEditor, 150, me);
                      return;
                  }
	  }
          me.deferred = 0;
	  me.callParent(arguments);
	  body = Ext.get(body),
	  id = body.id;
	  //the editor body cache entry (and so all the handlers) are removed by the GarbageCollector, so disable GC for the body:
	  Ext.cache[id].skipGarbageCollection = true;
	  //track the created body id to enable GC again on editorDomCleanUp
	  me.bodyGenId = id;
	  me.fireEvent('afteriniteditor', me);
  },
  /**
   * Überschreibt die Methode um den Editor Iframe mit eigenem CSS ausstatten
   * @returns string
   */
  getDocMarkup: function() {
    var me = this,
        additionalCss = '<link type="text/css" rel="stylesheet" href="'+Editor.data.moduleFolder+'/css/htmleditor.css?v=8" />'; //disable Img resizing
        //ursprünglich wurde ein body style height gesetzt. Das führte aber zu Problemen beim wechsel zwischen den unterschiedlich großen Segmente, daher wurde die Höhe entfernt.
    return Ext.String.format('<html><head><style type="text/css">body{border:0;margin:0;padding:{0}px;}</style>{1}</head><body style="font-size:9pt;line-height:14px;"></body></html>', me.iframePad, additionalCss);
  },
  /**
   * Setzt Daten im HtmlEditor und fügt markup hinzu
   * @param value String
   */
  setValueAndMarkup: function(value){
    this.lastSegmentContentWithoutTags = [];
    this.setValue(this.markup(value));
  },
  /**
   * Holt Daten aus dem HtmlEditor und entfernt das markup
   * @return String
   */
  getValueAndUnMarkup: function(){
    var me = this,
    	body = me.getEditorBody();
    me.lastSegmentContentWithoutTags = [];
    me.checkTags(body);
    return me.unMarkup(body);
  },
  /**
   * ersetzt die div und spans durch images im string 
   * @private
   * @param value String
   */
  markup: function(value) {
    var me = this;
    me.result = [],
    me.markupImages = {},
    tempNode = document.createElement('DIV');
    //tempnode mit inhalt füllen => Browser HTML Parsing
    value = value.replace(/ </g, Editor.TRANSTILDE+'<');
    value = value.replace(/> /g, '>'+Editor.TRANSTILDE);
    Ext.fly(tempNode).update(value);
    me.replaceTagToImage(tempNode);
    if(me.viewModesController.isHideTag()){
      me.hideTags();
    }
    return me.result.join('');
  },
  replaceTagToImage: function(rootnode) {
    var me = this,
    data = {
        fullPath: Editor.data.segments.fullTagPath,
        shortPath: Editor.data.segments.shortTagPath
    },
    sp, fp, //[short|full]Path shortcuts
    shortTagContent;
    
    Ext.each(rootnode.childNodes, function(item){
      if(Ext.isTextNode(item)){
        var text = item.data.replace(new RegExp(Editor.TRANSTILDE, "g"), ' ');
        me.lastSegmentContentWithoutTags.push(text);
        me.result.push(Ext.htmlEncode(text));
        return;
      }
      if(item.tagName == 'IMG'){
    	  me.result.push(me.imgNodeToString(item, true));
    	  return;
      }
      // Span für Terminologie
      if(item.tagName == 'DIV' && /(^|[\s])term([\s]|$)/.test(item.className)){
        me.result.push(Ext.String.format('<span class="{0}" title="{1}">', item.className, item.title));
        me.replaceTagToImage(item);
        me.result.push('</span>');
        return;
      }
      if(item.tagName != 'DIV'){
        return;
      }
      //daten aus den tags holen:
      data.text = Ext.fly(item).down('span.full').dom.innerHTML.replace(/"/g, '&quot;');
      data.id = Ext.fly(item).down('span.full').getAttribute('id');
      data.md5 = data.id.split('-').pop();
      shortTagContent = Ext.fly(item).down('span.short').dom.innerHTML;
	  data.nr = shortTagContent.replace(/[^0-9]/g, '');
      if(shortTagContent.search(/locked/)!==-1){
          data.nr = 'locked'+data.nr;
      }
      //Fallunterscheidung Tag Typ
      switch(true){
        case /open/.test(item.className):
          data.type = 'open';
          data.suffix = '-left';
          data.shortTag = data.nr;
          break;
        case /close/.test(item.className):
          data.type = 'close';
          data.suffix = '-right';
          data.shortTag = '/'+data.nr;
          break;
        case /single/.test(item.className):
          data.type = 'single';
          data.suffix = '-single';
          data.shortTag = data.nr+'/';
          break;
      }
      data.key = data.type+data.nr;

      //zusammengesetzte img Pfade:
      sp = data.shortPath+data.nr+data.suffix+'.png';
      fp = data.fullPath+data.md5+data.suffix+'.png';
      //caching der Pfade und den zugehörigen divs fürs unmarkup 
      me.markupImages[data.key] = {
          shortPath: sp,
          fullPath: fp,
          html: '<div class="'+item.className+'">'+me.spanTemplate.apply(data)+'</div>'
      };

      if(me.viewModesController.isFullTag()){
        data.path = fp;
      }
      else {
        data.path = sp;
      }

      me.result.push(me.imageTemplate.apply(data));
    });
  },
  /**
   * ersetzt die images durch div und spans im string 
   * @private
   * @param node dom-node
   * @return String
   */
  unMarkup: function(node){
    var me = this,
    result = [];
    
    if(!node.hasChildNodes()){
        return "";
    }
    
    Ext.each(node.childNodes, function(item){
      var markupImage,
      text;
      if(Ext.isTextNode(item)){
        text = item.data;
        result.push(Ext.htmlEncode(text));
        me.lastSegmentContentWithoutTags.push(text);
        return;
      }
      // recursive processing of Terminologie spans, removes the term span
      //@todo die Term Spans fliegen hier richtigerweise raus (wg. Umsortierung des Textes)
      //Allerdings muss danach die Terminologie anhand der Begriffe im Text wiederhergestellt werden.
      if(item.tagName == 'SPAN' && item.hasChildNodes()){
        result.push(me.unMarkup(item));
        return;
      }
      if(item.tagName == 'IMG'){
        if(markupImage = me.getMarkupImage(item.id)){
          result.push(markupImage.html);
        }
        else if(/^qm-image-/.test(item.id)){
            result.push(me.imgNodeToString(item, false));
        }
        return;
      }
      if(item.hasChildNodes()){
    	result.push(me.unMarkup(item));
    	return;
      }
      result.push(item.textContent || item.innerText);
    });
    return result.join('');
  },
  /**
   * generates a img tag string
   * @param {Image} imgNode
   * @param {Boolean} markup flag whether markup or unmarkup process
   * @returns {String}
   */
  imgNodeToString: function(imgNode, markup) {
	  var id = '', 
	  	  src = imgNode.src.replace(/^.*\/\/[^\/]+/, ''),
	  	  img = Ext.fly(imgNode),
	  	  comment = img.getAttribute('data-comment');
	  	  seq = img.getAttribute('data-seq');
	  if(markup) { //on markup an id is needed for remove orphaned tags
		  //qm-image-open-#
		  //qm-image-close-#
		  id = (/open/.test(imgNode.className) ? 'open' : 'close');
		  id = 'id="qm-image-'+id+'-'+seq+'"';
	  }
	  return Ext.String.format('<img {0} class="{1}" data-seq="{2}" data-comment="{3}" src="{4}" />', id, imgNode.className, seq, comment ? comment : '', src);
  },
  hasAndDisplayErrors: function() {
	 if(!this.isTagOrderClean){
		 Ext.Msg.alert(this.strings.errorTitle, this.strings.tagOrderErrorText);
		 return true;
	 }
	 return false;
  },
  /**
   * check and fix tags
   * @param node
   */
  checkTags: function(node) {
	  var nodelist = node.getElementsByTagName('img');
	  this.fixDuplicateImgIds(nodelist);
	  this.removeOrphanedTags(nodelist);
	  this.checkTagOrder(nodelist);
  },
  /**
   * Tag Order Check
   * assumes that img tag contains an id with substring "-open" or "-close"
   * ids starting with "remove" are ignored, because they are marked to be removed by removeOrphanedTags   
   * @param {Array} nodelist
   */
  checkTagOrder: function(nodelist) {
	  var open = {}, clean = true;
	  Ext.each(nodelist, function(img) {
		  if(/^remove/.test(img.id) || /-single/.test(img.id)){
			  //ignore tags marked to remove
			  return;
		  }
		  if(/-open/.test(img.id)){
			  open[img.id] = true;
			  return;
		  }
		  var o = img.id.replace(/-close/, '-open');
		  if(! open[o]) {
			  clean = false;
			  return false; //break each
		  }
	  });
	  this.isTagOrderClean = clean;
  },
  /**
   * Fixes duplicate img ids in the opened editor on unmarkup
   * Works with <img> tags with the following specifications: 
   * IMG needs an id Attribute. Assuming that the id contains the strings "-open" or "-close". The rest of the id string is identical.
   * Needs also an attribute "data-seq" which is containing the plain ID of the tag pair.
   * If a duplicated img tag is found, the "123" of the id will be replaced with a generated Ext.id()
   * 
   * example, tag with needed infos:
   * <img id="foo-open-123" data-seq="123"/> open tag 
   * <img id="foo-close-123" data-seq="123"/> close tag
   * 
   * copying this tags will result in
   * <img id="foo-open-ext-456" data-seq="ext-456"/> 
   * <img id="foo-close-ext-456" data-seq="ext-456"/>
   * 
   * Warning:
   * fixing IDs means that existing ids are wandering forward: 
   * Before duplicating:
   * This is the [X 1]testtext[/X 1].
   * after duplicating, before fixing:
   * This [X 1]is[/X 1] the [X 1]testtext[/X 1].
   * after fixing:
   * This [X 1]is[/X 1] the [X 2]testtext[/X 2].
   * 
   * @param {Array} nodelist
   */
  fixDuplicateImgIds: function(nodelist) {
	  var ids = {}, 
	  	stackList = {}, 
	  	updateId = function(img, newSeq, oldSeq) {
	    	//dieses img mit der neuen seq versorgen.
		  	img.id = img.id.replace(new RegExp(oldSeq+'$'), newSeq);
	    	img.setAttribute('data-seq', newSeq);
	  	};
	    //duplicate id fix vor removeOrphanedLogik, da diese auf eindeutigkeit der IDs baut
	    //dupl id fix benötigt checkTagOrder, welcher sich aber mit removeOrphanedLogik beißt
	    Ext.each(nodelist, function(img) {
	    	var newSeq, oldSeq = img.getAttribute('data-seq'), id = img.id, pid, open;
	    	if(! id) {
	    		return;
	    	}
	    	if(! ids[id]) {
	    		//id noch nicht vorhanden, dann ist sie nicht doppelt => raus
	    		ids[id] = true;
	    		return;
	    	}

	    	//gibt es einen Stack mit inhalten für meine ID, dann hole die Seq vom Stack und verwende diese
	    	if(stackList[id] && stackList[id].length > 0) {
	    		newSeq = stackList[id].shift();
	    		updateId(img, newSeq, oldSeq);
	    		return;
	    	}
    		//wenn nein, dann:
    		//partner id erzeugen
	    	open = new RegExp("-open");
    		if(open.test(id)) {
    			pid = id.replace(open, '-close');
    		}
    		else {
    			pid = id.replace(/-close/, '-open');
    		}
    		//bei bedarf partner stack erzeugen
    		if(!stackList[pid]) {
    			stackList[pid] = [];
    		}
    		newSeq = Ext.id();
    		//die neue seq auf den Stack der PartnerId legen
    		stackList[pid].push(newSeq);
	    	updateId(img, newSeq, oldSeq);
	    });
  },
  
  /**
   * assuming same id for open and close tag. Each Tag ID contains the string "-open" or "-close"
   * prepends "remove-" to the id of an orphaned tag
   * @see fixDuplicateImgIds
   * @param {Array} nodelist
   */
  removeOrphanedTags: function(nodelist) {
	var openers = {}, closers = {}, hasRemoves = false;
    Ext.each(nodelist, function(img) {
      if(/-open/.test(img.id)){
        openers[img.id] = img;
      }
      if(/-close/.test(img.id)){
        closers[img.id] = img;
      }
    });
    Ext.iterate(openers, function(id, img){
      var closeId = img.id.replace(/-open/, '-close');
      if(closers[closeId]) {
        //closer zum opener => aus "closer entfern" liste raus
        delete closers[closeId];
      }
      else {
        //kein closer zum opener => opener zum entfernen markieren
    	hasRemoves = true;
        img.id = 'remove-'+img.id;
      }
    });
    Ext.iterate(closers, function(id, img){
	  hasRemoves = true;
      img.id = 'remove-'+img.id;
    });
    if(hasRemoves) {
    	Editor.MessageBox.addInfo(this.strings.tagRemovedText);
    }
  },
  showShortTags: function() {
    this.rendered && this.setImagePath('shortPath');
  },
  showFullTags: function() {
    this.rendered && this.setImagePath('fullPath');
  },
  setImagePath: function(target){
    var me = this;
    me.getEditorBody().className = '';
    Ext.each(Ext.query('img', me.getEditorBody()), function(item){
      var markupImage;
      if(markupImage = me.getMarkupImage(item.id)){
        item.src = markupImage[target];
      }
    });
  },
  /**
   * @param imgHtml string containing the MarkupImageId ([open|close|single][0-9]+) prefixed by this.idPrefix
   * @returns this.markupImages item
   */
  getMarkupImage: function(imgHtml) {
    var matches = imgHtml.match(new RegExp('^'+this.idPrefix+'((open|close|single)([0-9]+|locked[0-9]+))'));
    if (!matches || matches.length != 4) {
      return null;
    }
    if(this.markupImages[matches[1]]){
      return this.markupImages[matches[1]];
    }
    return null;
  },
  /**
   * überschreibt die Methode um die leere Toolbar ausblenden
   */
  createToolbar: function() {
    this.callParent(arguments);
    this.toolbar.hide();
  },
  /**
   * überschreibt die Methode um HtmlEditor Funktionen (bold etc. pp.) zu deaktivieren
   */
  execCmd: function() {
  },
  hasSelection: function() {
	  var doc = this.getDoc();
	  if(doc.selection) {
		  return doc.selection.type.toLowerCase() != 'none'; 
	  }
	  if(! doc.getSelection){
		  return false;
	  }
	  var sel = doc.getSelection();
	  return !(sel.isCollapsed || sel.rangeCount > 0 && sel.getRangeAt(0).collapsed);
  }
});
