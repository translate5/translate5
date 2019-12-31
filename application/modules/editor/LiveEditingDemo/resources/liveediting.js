"use strict";

(function(w, d, $) {

	// quick way of adding rotation-getter to jQuery ... a plugin seems overkill for now
	// see https://stackoverflow.com/questions/8270612/get-element-moz-transformrotate-value-in-jquery
	$.fn.rotation = function(){
		var el = $(this), tr = el.css("transform") || el.css("-webkit-transform") || el.css("-moz-transform") || el.css("-ms-transform") || el.css("-o-transform") || '';
		if (tr = tr.match('matrix\\((.*)\\)')) {
			tr = tr[1].split(',');
			if(typeof tr[0] != 'undefined' && typeof tr[1] != 'undefined') {
				var rad = Math.atan2(tr[1], tr[0]);
				return parseFloat((rad * 180 / Math.PI).toFixed(1));
			}
		}
		return 0;
	};
	// quick way of getting the parsed transformation in jQuery
	$.fn.transformationScale = function(){
		var el = $(this), tr = el.css("transform") || el.css("-webkit-transform") || el.css("-moz-transform") || el.css("-ms-transform") || el.css("-o-transform") || '';
		if (tr = tr.match('matrix\\((.*)\\)')) {
			tr = tr[1].split(',');
			if(typeof tr[0] != 'undefined' && typeof tr[3] != 'undefined') {
				return {"x":parseFloat(tr[0]),"y":parseFloat(tr[3])};
			}
		}
		return {"x":1,"y":1};
	};
	// Small extension of JQuery to get an API the retrieves the merged direct text-nodes of an element
	// and to have an api to check if an element has no absolute children
	function elementText(el, separator) {
		var textContents = [];
		for(var chld = el.firstChild; chld; chld = chld.nextSibling) {
			if (chld.nodeType == 3) {
				textContents.push(chld.nodeValue);
			}
		}
		return textContents.join(separator);
	}
	$.fn.directText = function(elementSeparator, nodeSeparator) {
		if (arguments.length<2){
			nodeSeparator = "";
		}
		if (arguments.length<1){
			elementSeparator = "";
		}
		return $.map(this, function(el){
			return elementText(el, nodeSeparator);
		}).join(elementSeparator);
	};
	// Shim for the outerHTML API that is missing in some browsers
	$.fn.outerHTML = function(){
		return (!this.length) ? this : (this[0].outerHTML || (
		function(el){
			var div = document.createElement('div');
			div.appendChild(el.cloneNode(true));
			var contents = div.innerHTML;
			div = null;
			return contents;
		})(this[0]));
	};


	/* *************************************************************************************************** */
	/* ********************************** Live Editing Parser ******************************************** */
	/*
		This parser is tailored to convert the output of pdf2htmlEX to a simpler structure that
		tries to keep the text-flow. There are lots of assumptions on how he output of pdf2htmlEX
		is structured. This parser is not meant to parse any non-pdf2htmlEX generated HTML-structure in any way ...
		Prequesites of the parsed HTML:
		
	
	*/
	
	/* *************************************************************************************************** */

	var LeParserSort = {
		byPos: function(a, b){
			return LeParserSort._byPropAsc(a, b, "pos");
		},
		byBottom: function(a, b){
			return LeParserSort._byPropAsc(a, b, "bottom");
		},
		byLeft: function(a, b){
			return LeParserSort._byPropAsc(a, b, "left");
		},
		byRight: function(a, b){
			return LeParserSort._byPropAsc(a, b, "right");
		},
		bySize: function(a, b){
			return LeParserSort._byPropAsc(a, b, "size");
		},
		byWeight: function(a, b){
			return LeParserSort._byPropDesc(a, b, "weight");
		},
		_byPropAsc: function(a, b, prop){
			if(a[prop] < b[prop]){
				return -1;
			}
			if (a[prop] > b[prop]){
				return 1;
			}
			return 0;
		},
		_byPropDesc: function(a, b, prop){
			if(a[prop] > b[prop]){
				return -1;
			}
			if (a[prop] < b[prop]){
				return 1;
			}
			return 0;
		}
	};
	
	/* *************************************************************************************************** */

	var LeParserElement = function(top, left, width, height, rotation){
		this.top = top;
		this.right = left + width;
		this.bottom = top + height;
		this.left = left;
		this.width = width;
		this.height = height;
		this.rotation = rotation;
		this.rendered = false;
		this.seperated = false;
		this.fontSize = 0;
		this.lineHeight = 0;
		this.multiline = false;
		this.virtual = false;
		this.fixed = false;
		this.joinable = false;
		this.align = "left";
		this.fontFamily = "sans-serif";
		this.fontWeight = "400";
		this.fontStyle = "normal";
		this.html = "";
		this._subs = [];
	};
	LeParserElement.prototype.getRelevantTab = function(){
		if(this.align == "left"){
			return this._ltab;
		} else {
			return this._rtab;
		}
	};
	LeParserElement.prototype.cloneForSub = function(left, width){
		var ele = new LeParserElement(this.top, left, width, this.height, this.rotation);
		ele.lineHeight = this.lineHeight;
		ele. multiline= this.multiline;
		ele.align = this.align;
		ele.fontSize = this.fontSize;
		ele.fontFamily = this.fontFamily;
		ele.fontWeight = this.fontWeight;
		ele.fontStyle = this.fontStyle;
		ele.virtual = true;
		return ele;
	};
	LeParserElement.prototype.isInLine = function(element){
		return(this.bottom < (element.bottom + leParser.config.linePosThresh) && this.bottom > (element.bottom - leParser.config.linePosThresh) && this.top < (element.top + leParser.config.linePosThresh) && this.top > (element.top - leParser.config.linePosThresh));
	};
	LeParserElement.prototype.isFontEqual = function(element){
		return(this.fontSize == element.fontSize && this.fontFamily == element.fontFamily);
	};
	LeParserElement.prototype.isDirectlyBefore = function(element){
		return (this.right > element.left - leParser.config.subSuperJoinThresh && this.left < element.left);
	};
	LeParserElement.prototype.isDirectlyAfter = function(element){
		return (this.left < element.right + leParser.config.subSuperJoinThresh && this.right > element.right);
	};
	LeParserElement.prototype.appendHorizontally = function(element){
		if(this.html.length > 0 && this.html.charAt(this.html.length - 1) != " " && element.html.length > 0 && element.html.charAt(0) != " "){
			this.html += " ";
		}
		if(element.fontSize != this.fontSize || element.fontFamily != this.fontFamily || element.fontWeight != this.fontWeight || element.fontStyle != this.fontStyle){
			this.html += ('<span style="'+this._renderFontStyle()+'">' + element.html + '</span>');
		} else {
			this.html += element.html;
		}
		this.top = Math.min(this.top, element.top);
		this.bottom = Math.max(this.bottom, element.bottom);
		this.right = element.right;
		this.width = this.right - this.left;
		this.height = this.bottom - this.top;
		element.rendered = false;
	};
	// Checks, if an Element is a sup-or superscript relative to the passed element
	LeParserElement.prototype.isSubOrSuper = function(element){
		// font too big
		if(this.fontSize == 0 || this.fontSize > (element.fontSize * leParser.config.subSuperFontSizeMax)){
			return false;
		}
		// no vertical overlap
		if(this.top > element.bottom || this.bottom < element.top){
			return false;
		}
		return true;
	};
	// The element is added to the passed one as sub- or superscript
	LeParserElement.prototype.addAsSubOrSuper = function(element){
		if(this.isDirectlyAfter(element)){
			element.html += this._renderAsSubOrSuperTag(element);
			element.right = this.right;
			element.width = element.right - element.left;
			this.rendered = false;
		} else if(this.isDirectlyBefore(element)){
			element.html = this._renderAsSubOrSuperTag(element) + element.html;
			element.left = this.left;
			element.width = element.right - element.left;
			this.rendered = false;
		}
	};
	LeParserElement.prototype.turnSubOrSuperToNormal = function(element){
		this.html = this._renderAsSubOrSuperTag(element);
		this.top = element.top;
		this.bottom = element.bottom;
		this.height = element.height;
		this.fontSize = element.fontSize;
		this.fontFamily = element.fontFamily;
		this.fontWeight = element.fontWeight;
		this.fontStyle = element.fontStyle;
	};
	LeParserElement.prototype._renderAsSubOrSuperTag = function(element){
		var span = '<span style="position:relative; ';
		if(this.top >= element.top){
			// superscript:
			span += ' top:' + String(this.bottom - element.bottom) + 'px;';
		} else {
			// subscript
			span += ' bottom:' + String(element.bottom - this.bottom) + 'px;';
		}
		span += (' '+this._renderFontStyle()+'">');
		return span + this.html + '</span>';
	};
	LeParserElement.prototype._renderFontStyle = function(){
		return 'font-family:'+this.fontFamily+'; font-size:'+this.fontSize+'px; font-style:'+this.fontStyle+'; font-weight:'+this.fontWeight+';';
	};

	/* *************************************************************************************************** */

	var LeParserFrame = function(page, index){
		this.index = index;
		this.pageId = page.id;
		this.left = -1;
		this.top = -1;
		this.right = 0;
		this.bottom = 0;
		this.width = -1;
		this.height = -1;
		this.fontSize = 0;
		this.scaleX = 1;
		this.scaleY = 1;
		this.transform = "";
		this.fixed = false;
		this.rotated = false;
		this.rotation = 0;
		this.elements = [];
		this.rows = [];		
		this.tabs = [];
		this.jDomNode = null;
		// temporarily stores used in the build-phase to avoid excessive memory use
		this._nodes = {};
		this._lines = {};
		this._ltabs = {};
		this._rtabs = {};
		this._tabs = {};
		this._fsizes = {};
	};

	LeParserFrame.prototype.addElement = function(jNode, element){
		// add first element, inherit it's properties
		if(this.elements.length == 0){
			this.top = element.top;
			this.right = element.right;
			this.bottom = element.bottom;
			this.left = element.left;
			this.width = element.width;
			this.height = element.height;
			// DIRTY: the first Element sets the frames transform. this is obviously dependant on pdf2html logic
			this.transform = jNode.css("transform"); // retrieves smth. like matrix(0.25, 0, 0, 0.25, 0, 0)
			var scale = jNode.transformationScale();
			if(scale.x > 0 && scale.y > 0){
				this.scaleX = scale.x;
				this.scaleY = scale.y;
			}
		} else {
			if(this.top > element.top){
				this.top = element.top;
			}
			if(this.right < element.right){
				this.right = element.right;
			}
			if(this.bottom < element.bottom){
				this.bottom = element.bottom;
			}
			if(this.left > element.left){
				this.left = element.left;
			}
			this.width = this.right - this.left;
			this.height = this.bottom - this.top;
		}
		var lastElement = this.getLastRenderedElement();
		if(lastElement != null){
			// detection of sub-or-superscript, re-joining the parts
			// this depends on pdf2htmlEx rendering the sub/superscript elements directly after the text they belong to
			if(lastElement.isSubOrSuper(element) && !lastElement.joinable){
				// the element before is a sub/superscript
				this.removeElementProps(lastElement);
				if(lastElement.isDirectlyBefore(element)){
					lastElement.addAsSubOrSuper(element);
					element.joinable = true;
				} else {
					lastElement.turnSubOrSuperToNormal(element);
					this.addElementProps(lastElement);
				}
			} else if(element.isSubOrSuper(lastElement)){
				// the new element is a sub/superscript
				if(element.isDirectlyAfter(lastElement)){
					this.removeElementProps(lastElement);
					element.addAsSubOrSuper(lastElement);
					lastElement.joinable = true;
					this.addElementProps(lastElement);
				} else {
					element.turnSubOrSuperToNormal(lastElement);
				}
			} else if(lastElement.joinable && element.isInLine(lastElement) && element.isFontEqual(lastElement) && element.isDirectlyAfter(lastElement)) {
				// the last element was a joined element and we now might can append the rest of the string
				this.removeElementProps(lastElement);
				lastElement.appendHorizontally(element);
				this.addElementProps(lastElement);
			}
		}
		this.elements.push(element);
		if(!element.virtual){
			this._nodes[element.id] = jNode;
		}
		this.addElementProps(element);
	};
	LeParserFrame.prototype.addElementProps = function(element){
		// add rounded element position to lines / tabs
		if(element.rendered){
			var roundedPos = Math.round(element.bottom * leParser.config.linePrecision) / leParser.config.linePrecision;
			element._line = this._addTo(roundedPos, "_lines", leParser.config.linePosThresh);
			roundedPos = Math.round(element.left * leParser.config.tabPrecision) / leParser.config.tabPrecision;
			element._ltab = this._addTo(roundedPos, "_ltabs", leParser.config.tabPosThresh);
			roundedPos = Math.round(element.right * leParser.config.tabPrecision) / leParser.config.tabPrecision;
			element._rtab = this._addTo(roundedPos, "_rtabs", leParser.config.tabPosThresh);
		}
	};
	// can only be used to remove an element that already has been added !
	LeParserFrame.prototype.removeElementProps = function(element){
		if(element.hasOwnProperty("_line")){
			this._removeFrom(element._line, "_lines");
		}
		if(element.hasOwnProperty("_ltab")){
			this._removeFrom(element._ltab, "_ltabs");
		}
		if(element.hasOwnProperty("_rtab")){
			this._removeFrom(element._rtab, "_rtabs");
		}
	};
	LeParserFrame.prototype.getLastRenderedElement = function(){
		if(this.elements.length == 0){
			return null;
		}
		for(var i = (this.elements.length - 1); i >= 0; i--){
			if(this.elements[i].rendered){
				return this.elements[i];
			}
		}
		return null;
	};
	// retrieves the number of times an element was on the provided line
	LeParserFrame.prototype.isInLines = function(bottom){
		return this._isIn(bottom, "_lines", leParser.config.linePosThresh);
	};
	// retrieves the number of times an element was on the provided left tab
	LeParserFrame.prototype.isInLeftTabs = function(left){
		return this._isIn(left, "_ltabs", leParser.config.tabPosThresh);
	};
	// retrieves the number of times an element was on the provided right tab
	LeParserFrame.prototype.isInRightTabs = function(right){
		return this._isIn(right, "_rtabs", leParser.config.tabPosThresh);
	};
	LeParserFrame.prototype.build = function(jPage){
		if(this.jDomNode != null){
			return;
		}
		var e, i, j, l, s, t;
		// create our ID
		this.id = leParser.createFrameId();
		
		// evaluate font-size, which is the most used (sorted by weight)
		for(i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered){
				var key = "fs"+String(e.fontSize);
				if(key in this._fsizes){
					this._fsizes[key].weight += leParser.stripTags(e.html).length;
				} else {
					this._fsizes[key] = {"size":e.fontSize, "weight":leParser.stripTags(e.html).length };
				}
			}
		}
		s = [];
		for(i in this._fsizes){
			if(this._fsizes[i].weight > 0){
				s.push(this._fsizes[i]);
			}
		}
		s.sort(LeParserSort.bySize);
		s.sort(LeParserSort.byWeight);
		this.fontSize = s[0].size;
		
		// these values match better what was evaluated for elements
		var _left = Math.round(this.left * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		var _right = Math.round(this.right * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		this._tabs["p"+_left] = _left;
		this._tabs["p"+_right] = _right;
		// round our values to match the general precision
		this.top = Math.floor(this.top * leParser.config.linePrecision) / leParser.config.linePrecision;
		this.bottom = Math.ceil(this.bottom * leParser.config.linePrecision) / leParser.config.linePrecision;
		this.left = Math.floor(this.left * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		this.right = Math.ceil(this.right * leParser.config.tabPrecision) / leParser.config.tabPrecision;
		this.width = this.right - this.left;
		this.height = this.bottom - this.top;
		// sort our elements by horizontal line
		this.elements.sort(LeParserSort.byBottom);
		// evaluate the relevant tab for each element

		
		for(i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered){
				// evaluating the align by comparing the frequency of the tabs of all our elements
				e.align = (this._rtabs["p"+e._rtab].num > this._ltabs["p"+e._ltab].num ) ?  "right" : "left";
				t = e.getRelevantTab();
				if(!this._tabs.hasOwnProperty("p"+t)){
					this._tabs["p"+t] = t;
				}
			}
		}
		for(i in this._tabs){
			this.tabs.push(this._tabs[i]);
		}
		this.tabs.sort();
		// find tab-boundries for all elements
		for(i=0; i < this.elements.length; i++){
			e = this.elements[i];
			if(e.rendered){
				if(e.align == "left"){
					e._rtab = this._findNearestTab((e._ltab + e.width), true);
				} else {
					e._ltab = this._findNearestTab((e._rtab - e.width), false);
				}
			}
		}
		// create rows with cols ...
		
		
		
		
		

		this.jDomNode = $(
			'<div id="'+this.id+'" class="'+leParser.createFrameClasses()+'" style="'+leParser.createFrameStyles(this)+'"></div>');

		jPage.append(this.jDomNode);
		

		// TEMPORARY: just to make found elements visible
		if(leParser.config.devMode){
			for(i=0; i < this.elements.length; i++){
				e = this.elements[i];
				if(e.rendered){
					jPage.append('<div id="'+e.id+'tmp" class="t5lep-ele t5lep-colorized" style="top:'+e.top+'px; left:'+e.left+'px; width:'+e.width+'px; height:'+e.height+'px"></div>');
				}
			}
		}










		delete  this._nodes;
		delete  this._lines;
		delete  this._ltabs;
		delete  this._rtabs;
		delete  this._tabs;
		delete  this._fsizes;
	};
	LeParserFrame.prototype.remove = function(jPage){
		
		
		// TEMPORARY: just to make found elements invisible
		if(leParser.config.devMode){
			for(var i=0; i < this.elements.length; i++){
				var e = this.elements[i];
				if(e.rendered){
					$("#"+e.id+"tmp").remove();
				}
			}
		}
		
		
		if(this.jDomNode != null){
			this.jDomNode.remove();
		}
		this.jDomNode  = null;
		this.rows = [];
		this.elements = [];
		this.cols = [];
		this.tabs = [];
	};

	// internal helper

	LeParserFrame.prototype._isIn = function(pos, varName, thresh){
		for(var i in this[varName]){
			if(Math.abs(this[varName][i].pos - pos) < thresh){
				return this[varName][i].num;
			}
		}
		return 0;
	};
	LeParserFrame.prototype._addTo = function(pos, varName, thresh){
		for(var i in this[varName]){
			if(Math.abs(this[varName][i].pos - pos) < thresh){
				this[varName][i].num++;
				return this[varName][i].pos;
			}
		}
		this[varName]["p"+String(pos)] = {"pos":pos, "num":1};
		return pos;
	};
	LeParserFrame.prototype._removeFrom = function(pos, varName){
		if(this[varName].hasOwnProperty("p"+String(pos))){
			this[varName]["p"+String(pos)].num--;
			if(this[varName]["p"+String(pos)].num <= 0){
				delete this[varName]["p"+String(pos)];
			}
		}
	};
	LeParserFrame.prototype._findNearestTab = function(pos, toTheRight){
		if(toTheRight){
			for(var i=0; i < this.tabs.length; i++){
				if(this.tabs[i] >= pos){
					return this.tabs[i];
				}
			}
			return this.tabs[this.tabs.length - 1];
		} else {
			for(var i = this.tabs.length - 1; i >= 0; i--){
				if(this.tabs[i] <= pos){
					return this.tabs[i];
				}
			}
			return this.tabs[0];
		}
	};

	/* *************************************************************************************************** */

	var LeParserRotatedFrame = function(page, index, element, jNode){
		this.index = index;
		this.pageId = page.id;
		this.left = element.left;
		this.top = element.top;
		this.right = element.right;
		this.bottom = element.bottom;
		this.width = element.width;
		this.height = element.height;
		this.fixed = true;
		this.rotated = true;
		this.rotation = element.rotation;
		this.jDomNode = jNode;
		this.jDomLayer = null;
		// we do not generate contents and thus do not apply scaling but scale our fontSize to keep consistency
		this.transform = "";
		this.scaleX = 1;
		this.scaleY = 1;
		var scale = jNode.transformationScale();
		this.fontSize = element.fontSize * scale.y;
	};
	LeParserRotatedFrame.prototype.build = function(jPage){
		if(this.jDomNode == null || this.jDomLayer != null)
			return;
		this.id = leParser.createFrameId();
		// we add the coloration as a seperate layer to check the positions in dev-mode. therefore we do not scale & do not apply aour transformation-matrix
		if(leParser.config.devMode && leParser.config.colorizeFrames){
			this.jDomLayer = $('<div id="'+this.id+'" class="'+leParser.createRotatedFrameClasses()+'" style="'+leParser.createFrameStyles(this)+'"></div>');
			jPage.append(this.jDomLayer);
		}
	};
	LeParserRotatedFrame.prototype.remove = function(jPage){
		if(this.jDomLayer != null)
			this.jDomLayer.remove();
		this.jDomNode  = null;
	};

	/* *************************************************************************************************** */

	var LeParserPage = function(index, id, width, height, left, top){
		this.index = index;
		this.id = id;
		this.width = width;
		this.height = height;
		// take care: these coords contain the scroll-positions and are only useful in the build-phase
		this.left = left;
		this.top = top;

		this.frames = [];

		this.numFrames = function(){
			return this.frames.length;
		};
		this.addFrame = function(frame){
			this.frames.push(frame);
		};
		this.build = function(jPage){
			if(!jPage || jPage == null){
				jPage = $("+"+this.id);
			}
			for(var i=0; i < this.frames.length; i++){
				this.frames[i].build(jPage);
			}
		};
		this.remove = function(jPage){
			for(var i=0; i < this.frames.length; i++){
				this.frames[i].remove();
			}
			this.frames = [];
		};
	};
	LeParserPage.prototype.numFrames = function(){
		return this.frames.length;
	};
	LeParserPage.prototype.addFrame = function(frame){
		this.frames.push(frame);
	};
	LeParserPage.prototype.build = function(jPage){
		if(!jPage || jPage == null){
			jPage = $("+"+this.id);
		}
		for(var i=0; i < this.frames.length; i++){
			this.frames[i].build(jPage);
		}
	};
	LeParserPage.prototype.remove = function(jPage){
		for(var i=0; i < this.frames.length; i++){
			this.frames[i].remove();
		}
		this.frames = [];
	};

	/* *************************** OPTIONAL: Configurator / Editor ************************************ */

	var LeParserConfigurator = function(){
		// has to be in sync with leParser config
		this.config = {
			"frameHorizTrashOnLine": { "type":"int", "min":1, "max":100, "step":1, "unit":"px", "txt":"Horizontal threshhold detecting Elements belong to a frame that are horizontally aligned (bottomline)" },
			"frameHorizTrash": { "type":"int", "min":10, "max":500, "step":5, "unit":"px", "txt":"Horizontal threshhold detecting Elements belong to a frame" },
			"frameVertiThreshForTab": { "type":"int", "min":1, "max":100, "step":1, "unit":"px", "txt":"Vertical threshhold detecting Elements belong to a frame that are vertically aligned (left or right)" },
			"frameVertiThresh": { "type":"int", "min":10, "max":200, "step":1, "unit":"px", "txt":"Vertical threshhold detecting Elements belong to a frame" },
			"rotationThresh": { "type":"float", "min":0.1, "max":10, "step":0.1, "unit":"degree", "txt":"Threshhold detecting rotated Elements" },
			"linePrecision": { "type":"int", "min":1, "max":10, "step":1, "unit":"1/X", "txt":"Reciprocal precision of horizontal line calculations" },
			"tabPrecision": { "type":"int", "min":1, "max":10, "step":1, "unit":"1/X", "txt":"Reciprocal precision of vertical line calculations" },
			"linePosThresh": { "type":"float", "min":0.05, "max":10, "step":0.05, "unit":"px", "txt":"Threshhold to identify vertical positions to be on the same line" },
			"tabPosThresh": { "type":"float", "min":0.1, "max":10, "step":0.1, "unit":"px", "txt":"Threshhold to identify horizontal positions to be on the same line" },
			"maxMultiLineHeight": { "type":"int", "min":100, "max":250, "step":1, "unit":"percent", "txt":"Maximum line-height two following textfields will be regarded as being one multiline-field" },
			"subSuperFontSizeMax": { "type":"float", "min":0.1, "max":1, "step":0.05, "unit":"percent", "txt":"Maximum percentage a sub- or superscript item can have in relation to the text it is part of" },
			"subSuperJoinThresh": { "type":"int", "min":0, "max":25, "step":1, "unit":"px", "txt":"Max distance between a sub/superscript item and a text to be rendered as belonging together" },
			"splitOnSeperatorSpans": { "type":"bool", "unit":"", "txt":"If set the empty spans used by pdf2html to generate horizontal whitespace are used to split the text into columns" },
			"colorizeFrames": { "type":"bool", "unit":"", "txt":"Colorize all frames" },
			"colorizeRows": { "type":"bool", "unit":"", "txt":"Colorize all rows in frames" },
			"colorizeCols": { "type":"bool", "unit":"", "txt":"Colorize all columns in rows in frames" }
		};
		this.build = function(){
			var lc = leParser.config, lp = leParser.config.namespace, item;
			var html =
				'<div id="'+lp+'-configurator">'
				+'<form onsubmit="return leParser.configurator.render(this);">'
				+'<table>';
			for(var prop in this.config){
				item = this.config[prop];
				html += '<tr><td><label title="'+item.txt+'">'+prop+':</label></td><td>';
				switch(item.type){
					case "bool":
						var checked = (lc[prop]) ? ' checked' : '';
						html += '<input type="checkbox" name="'+prop+'" value="1" data-type="bool"'+checked+'/>';
						break;
					case "int":
					case "float":
						html += '<input type="number" name="'+prop+'" value="'+lc[prop]+'" min="'+item.min+'" max="'+item.max+'" step="'+item.step+'" data-type="'+item.type+'"/>';
						break;
				}
				if(item.unit != ""){
					html += '<span>'+item.unit+'</span>';
				}
				html += '</td></tr>';
			}
			html +=
				'<tr><td colspan="2" class="'+lp+'-submit">'
				+'<input type="submit" value="RENDER" title="Render the adjusted configuration" /> '
				+'<input type="reset" value="Reset" title="Revert to the initial values" /> '
				+'<input type="button" onclick="return leParser.configurator.showConfig();" value="Config" title="Show the currently rendered config" /> '
				+'</td></tr>'
				+'</table></form></div>';
			$("body").append($(html));
		};
		this.showConfig = function(){
			var html =
				'<div id="'+leParser.config.namespace+'-configurator-config">'
				+'<a href="javascript:leParser.configurator.closeConfig();">x</a>'
				+'<pre>'+JSON.stringify(leParser.config, null, 5)+'</pre>'
				+'</div>';
			$("body").append($(html));
			return false;
		};
		this.closeConfig = function(){
			$("#"+leParser.config.namespace+"-configurator-config").remove();
		};
		this.render = function(form){
			var ele, name;
			$(form).find("input").each(function(){
				if(this.type != "submit" && this.type != "reset"){
					ele = $(this);
					switch(ele.data("type")){
						case "bool":
							leParser.config[this.name] = ele.prop("checked");
							break;
						case "float":
							leParser.config[this.name] = parseFloat(ele.val());
							break;
						case "int":
							leParser.config[this.name] = parseInt(ele.val());
							break;
					}
				}
			});
			leParser.removeAllPages();
			window.setTimeout(function(){ leParser.buildAllPages(); }, 500);
			return false;
		};
	};

	/* *************************************************************************************************** */

	w.leParser = {
		eleCounter: -1,
		frmCounter: -1,
		/* Explanation of config: see LeParserConfigurator.config */
		config: {
			layoutTags: ["div"],
			frameHorizTrashOnLine: 25,
			frameHorizTrash: 150,
			frameVertiThreshForTab: 11,
			frameVertiThresh: 25,
			rotationThresh: 0.9,
			linePrecision: 2,
			tabPrecision: 2,
			linePosThresh: 0.25,
			tabPosThresh: 1.5,
			maxMultiLineHeight: 175,
			subSuperFontSizeMax: 0.75,
			subSuperJoinThresh: 2,
			splitOnSeperatorSpans: true,
			namespace: 't5lep',
			devMode: false,
			colorizeFrames: false,
			colorizeRows: false,
			colorizeCols: false,
			defaultFontSize: 11,
			defaultLineHeight: 1.20
		},
		pages: {},
		/* a config-object can be set to manipulate the defaults */
		init: function(props){
			if(props){
				for(var prop in props){
					if(this.config.hasOwnProperty(prop)){
						this.config[prop] = props[prop];
					}
				}
			}
			if(this.config.devMode && LeParserConfigurator){
				this.configurator = new LeParserConfigurator();
				this.configurator.build();
			}
		},
		buildAllPages: function(){
			leParser.pages = {};
			$("#page-container").children().each(function(index){
				leParser.parsePage(this, index);
			});
		},
		removeAllPages: function(){
			for(var id in this.pages){
				this.pages[id].remove();
			}
			this.pages = {};
		},
		parsePage: function(node, index){
			if(node.id && node.id != undefined){
				// did we already parse this page ?
				if(!this.pages.hasOwnProperty(node.id)){
					// retrieves the dimensions with transform matrix applied !!
					var pos = node.getBoundingClientRect();
					var page = new LeParserPage(index, node.id, pos.width, pos.height, pos.left, pos.top);
					var jPage = $(node);
					this.findFrames(page, jPage);
					page.build(jPage);
					this.pages[node.id]  = page;
				}
			} else {
				this.logError('1', 'Page without ID attribute found');
			}
		},
		findFrames: function(page, jPage){
			var positions = {}, fCount = 0, frame = null, i = 0, nn;
			jPage.find("*").each(function(index){
				// QUIRK: this is so crazy: the node-name is actally uppercase while leParser.config.layoutTags are lowercase. Still, this comparision works. If this.nodeName.toLowerCase() is used, it DOS NOT WORK anymore.
				// If this.nodeName.toUpperCase() is used, it also works. WHAT ???
				if($.inArray(this.nodeName, leParser.config.layoutTags)){
					var jNode = $(this);
					var element = leParser.createRenderedElement(jNode, this, page);
					if(element.rendered){
						// generate ID for the element if it does not have one
						element.id = leParser.getOrAddId(jNode);
						// Elements with a rotation will not be handled currently
						if(element.rotation != 0){
							page.addFrame(new LeParserRotatedFrame(page, fCount++, element, jNode));
						} else {
							// Logic is dependant on html2pdfEX not generating layouts with padding & margin ...
							// TODO: neccessary ???
							if(element.height >= (element.lineHeight * 2)){
								element.multiline = true;
							}
							if(leParser.doCreateNewFrame(frame, element)){
								frame = new LeParserFrame(page, fCount++);
								page.addFrame(frame);
							}
							frame.addElement(jNode, element);
							if(element._subs.length > 0){
								for(i=0; i < element._subs.length; i++){
									// TODO: neccessary ???
									element._subs[i].id = leParser.createId();
									element._subs[i].multiline = element.multiline;
									frame.addElement(jNode, element._subs[i]);
								}
							}
							delete element._subs;
						}
					}
				}
			});
		},
		// The logic to identify the need to create a new frame
		doCreateNewFrame: function(frame, pos){
			// we create a frame in any case if there is no frame already
			if(frame == null || pos == null){
				return true;
			}
			// if the element is too far away vertically (even for tab-detection) we crate a new frame in any case
			if(pos.top - frame.bottom > this.config.frameVertiThresh || frame.top - pos.bottom > this.config.frameVertiThresh){
				return true;
			}
			// if the element is in the distance to check for tab-detection we do just that
			if(pos.top - frame.bottom > this.config.frameVertiThreshForTab || frame.top - pos.bottom > this.config.frameVertiThreshForTab){
				return (frame.isInLeftTabs(pos.left) == 0 && frame.isInRightTabs(pos.right) == 0);
			}
			// if the element is too far away horizontally we crate a new frame
			if(pos.left - frame.right > this.config.frameHorizTrash || frame.left - pos.right > this.config.frameHorizTrash){
				return true;
			}
			// if the element is in the limit for line detection we do just that
			if(pos.left - frame.right > this.config.frameHorizTrashOnLine || frame.left - pos.right > this.config.frameHorizTrashOnLine){
				return (frame.isInLines(pos.bottom) == 0);
			}
			return false;
		},
		findRows: function(){

		},
		findCols: function(){

		},
		getOrAddId: function(jNode){
			if(jNode.get(0).hasAttribute("id")){
				return jNode.attr("id");
			}
			this.eleCounter++;
			jNode.attr("id", (this.config.namespace + this.eleCounter));
			return (this.config.namespace + this.eleCounter);
		},
		createId: function(){
			this.eleCounter++;
			return (this.config.namespace + this.eleCounter);
		},
		createFrameId: function(){
			this.frmCounter++;
			return this.config.namespace + "-frm" + this.frmCounter;
		},
		createFrameClasses: function(){
			return this.createClassesForType("frm", (this.config.devMode && this.config.colorizeFrames));
		},
		createRotatedFrameClasses: function(){
			return this.createClassesForType("rotfrm", (this.config.devMode && this.config.colorizeFrames));
		},
		createFrameStyles: function(frame){
			// re-applying the matrix, this is needed to apply a proper font-size in pixels (fractions do not work ...)
			if(frame.scaleX != 1 && frame.scaleY != 1 && frame.transform != ""){
				var width = frame.width / frame.scaleX;
				var height = frame.height / frame.scaleY;
				return 'top:'+String(frame.top - ((height - frame.height) / 2))+'px; left:'+String(frame.left - ((width - frame.width) / 2))+'px; width:'+String(width)+'px; height:'+String(height)+'px; transform:'+frame.transform+'; font-size:'+String(frame.fontSize)+'px;';
			}
			return 'top:'+String(frame.top)+'px; left:'+String(frame.left)+'px; width:'+String(frame.width)+'px; height:'+String(frame.height)+'px; font-size:'+String(frame.fontSize)+'px;';
		},
		createRowClasses: function(){
			return this.createClassesForType("row", (this.config.devMode && this.config.colorizeRows));
		},
		createColumnClasses: function(){
			return this.createClassesForType("col", (this.config.devMode && this.config.colorizeCols));
		},
		createClassesForType: function(type, addColorization){
			if(addColorization){
				return this.config.namespace + "-" + type + " "+this.config.namespace + "-colorized";
			}
			return this.config.namespace + "-" + type;
		},
		// jQuerys implementation does not work in all cases for unknown reasons
		getOffsetParent: function(jNode){
			var parent = jNode.parent();
			if(parent.length == 0){
				return parent;
			}
			var position = parent.css("position");
			if(position == "relative" || position == "absolute" || position == "fixed")
				return parent;
			return leParser.getOffsetParent(parent);
		},
		createRenderedElement: function(jNode, node, page){
			var clientRect = node.getBoundingClientRect();
			var element = new LeParserElement((clientRect.top - page.top), (clientRect.left - page.left), clientRect.width, clientRect.height, jNode.rotation());
			if(jNode.css("position") != "absolute")
				return element;
			var children = jNode.contents();
			if(children.length == 0){
				return element;
			}
			// if under a thresh, the rotation will be reset to 0 (= horizontal)
			if(Math.abs(element.rotation) < leParser.config.rotationThresh){
				element.rotation = 0;
			}
			// fontSize. QUIRK: expects font-size to be set in pixels !
			var val = jNode.css('font-size');
			element.fontSize = (val.indexOf("px") > 0) ? parseFloat(val.split("px").join("")) : leParser.config.defaultFontSize;
			// lineHeight, multiline. QUIRK: expects all line-heights to be set in px, % or unitless
			val = jNode.css('line-height');
			if(val.indexOf("px") > 0) {
				element.lineHeight = parseFloat(val.split("px").join(""))
			} else if(val.indexOf("%") > 0) {
				element.lineHeight = parseFloat(val.split("%").join("")) / 100 * element.fontSize;
			} else if(/^\d*\.?\d+$/.test(val)){
				element.lineHeight = parseFloat(val) *  element.fontSize;
			} else {
				element.lineHeight = leParser.config.defaultLineHeight *  element.fontSize;
			}
			// we limit the min lineHeight to 100%. It seems html2pdfEX uses lineheights < fontSize for unknown reason
			if(element.lineHeight < element.fontSize){
				element.lineHeight = element.fontSize;
			}
			element.fontFamily = jNode.css('font-family');
			element.fontWeight = jNode.css('font-weight');
			element.fontStyle = jNode.css('font-style');
			// adding the html-nodes as strings, detect the ugly "seperator-spans"
			var html = "", sub = null, left, right;
			for(var i=0; i < children.length; i++){
				if(children[i].nodeType === 3){
					element.rendered = true;
				}
				if(children[i].nodeType === 1 && $(children[i]).css("position") != "absolute"){
					element.rendered = true;
				}
				// identify seperating spans only for horizontal elements
				if(leParser.config.splitOnSeperatorSpans && element.rotation == 0 && children[i].nodeType == 1 && leParser.isSeperatorSpan(children[i])){
					// if we have a seperating span, we split the 
					clientRect = children[i].getBoundingClientRect();
					left = clientRect.left - page.left;
					if(sub == null){
						sub = element.cloneForSub((left + clientRect.width), (element.right - left - clientRect.width));
						element.right = left;
						element.width = element.right - element.left;
						element.html = html;
					} else {
						right = sub.right;
						sub.right = left;
						sub.width = sub.right - sub.left;
						sub.html = html;
						sub.rendered = true;
						element._subs.push(sub);
						sub = element.cloneForSub((left + clientRect.width), (right - left - clientRect.width));
					}
					html = "";
				} else {
					html += $(children[i]).outerHTML();
				}
			}
			if(sub == null){
				element.html = html;
			} else {
				sub.html = html;
				sub.rendered = true;
				element._subs.push(sub);
			}
			return element;
		},
		isSeperatorSpan: function(node){
			return (node.nodeName.toLowerCase() === "span" && $(node).hasClass("_") && leParser.isOnlyWhitespace(String(node.textContent)));
		},
		isOnlyWhitespace: function(text){
			return (/^\s+$/.test(text) == true);
		},
		stripTags: function(html){
			return html.toString().replace(/(<([^>]+)>)/ig,"");
		},
		logError: function(code, msg){
			console.log('LiveEditingParser ERROR ('+code+'): '+msg);
		}
	};



})(window, document, jQuery);

$(document).ready(function(){
	window.leParser.init({ "devMode":true, "colorizeFrames":true, "colorizeRows":true, "colorizeCols":true });
	window.leParser.buildAllPages();
});
