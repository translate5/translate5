var SpellCheck;
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "../../../javascripts/HtmlEditor/Tools/calculate-node-length.js"
/*!**********************************************************************!*\
  !*** ../../../javascripts/HtmlEditor/Tools/calculate-node-length.js ***!
  \**********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ calculateNodeLength)
/* harmony export */ });


/**
 * @param {ChildNode} node
 * @returns {number}
 */
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t.return || t.return(); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function calculateNodeLength(node) {
  var length = 0;
  if (node.nodeType === Node.TEXT_NODE) {
    return node.length;
  }
  if (node.tagName === 'IMG') {
    return 1;
  }
  var _iterator = _createForOfIteratorHelper(node.childNodes),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var child = _step.value;
      // Simplified check if node is an internal tag
      if (child.tagName === 'IMG') {
        length += 1;
        continue;
      }
      if (child.nodeType === Node.ELEMENT_NODE) {
        length += calculateNodeLength(child);
        continue;
      }
      length += child.length;
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  return length;
}

/***/ },

/***/ "../../../javascripts/HtmlEditor/Tools/calculate-node-offsets.js"
/*!***********************************************************************!*\
  !*** ../../../javascripts/HtmlEditor/Tools/calculate-node-offsets.js ***!
  \***********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ calculateNodeOffsets)
/* harmony export */ });
/* harmony import */ var _calculate_node_length_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./calculate-node-length.js */ "../../../javascripts/HtmlEditor/Tools/calculate-node-length.js");
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t.return || t.return(); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }

'use strict';

/**
 * @param {HTMLElement} root
 * @param {HTMLElement} target
 *
 * @returns {{start: number, end: number}}
 */
function calculateNodeOffsets(root, target) {
  if (!target) {
    return {
      start: 0,
      end: 0
    };
  }
  var currentOffset = 0;
  var result = null;
  function traverse(nodeList) {
    var _iterator = _createForOfIteratorHelper(nodeList),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var node = _step.value;
        if (result) {
          break;
        }
        if (node === target) {
          var start = currentOffset;
          var length = (0,_calculate_node_length_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node);
          result = {
            start: start,
            end: start + length
          };
          break;
        }
        if (node.nodeType === Node.TEXT_NODE) {
          currentOffset += node.nodeValue.length;
        }

        // Simplifid check if node is an internal tag
        if (node.tagName === 'IMG') {
          currentOffset += 1;
          continue;
        }
        if (node.nodeType === Node.ELEMENT_NODE) {
          traverse(node.childNodes);
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }
  traverse(root.childNodes);
  return result || {
    start: 0,
    end: 0
  };
}

/***/ },

/***/ "../../../javascripts/HtmlEditor/Tools/string-to-dom.js"
/*!**************************************************************!*\
  !*** ../../../javascripts/HtmlEditor/Tools/string-to-dom.js ***!
  \**************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ stringToDom)
/* harmony export */ });


var support = function () {
  if (!window.DOMParser) {
    return false;
  }
  var parser = new DOMParser();
  try {
    parser.parseFromString('x', 'text/html');
  } catch (error) {
    return false;
  }
  return true;
}();

/**
 * Convert a template string into HTML DOM nodes
 *
 * @param  {String} str The template string
 * @return {HTMLElement} The HTML div element containing the converted DOM nodes
 */
function stringToDom(str) {
  // If DOMParser is supported, use it
  if (support) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(str, 'text/html');
    return doc.body;
  }

  // Otherwise, fallback to old-school method
  var dom = document.createElement('div');
  dom.innerHTML = str;
  return dom;
}
;

/***/ },

/***/ "../../../javascripts/HtmlEditor/Tools/unescape-html.js"
/*!**************************************************************!*\
  !*** ../../../javascripts/HtmlEditor/Tools/unescape-html.js ***!
  \**************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ unescapeHtml)
/* harmony export */ });


/**
 * @param {String} text
 * @returns {String}
 */
function unescapeHtml(text) {
  return text.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, '"').replace(/&#039;/g, "'");
}

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!************************!*\
  !*** ./spell-check.js ***!
  \************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SpellCheck)
/* harmony export */ });
/* harmony import */ var _javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../javascripts/HtmlEditor/Tools/string-to-dom.js */ "../../../javascripts/HtmlEditor/Tools/string-to-dom.js");
/* harmony import */ var _javascripts_HtmlEditor_Tools_calculate_node_offsets_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../javascripts/HtmlEditor/Tools/calculate-node-offsets.js */ "../../../javascripts/HtmlEditor/Tools/calculate-node-offsets.js");
/* harmony import */ var _javascripts_HtmlEditor_Tools_calculate_node_length_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../javascripts/HtmlEditor/Tools/calculate-node-length.js */ "../../../javascripts/HtmlEditor/Tools/calculate-node-length.js");
/* harmony import */ var _javascripts_HtmlEditor_Tools_unescape_html_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../javascripts/HtmlEditor/Tools/unescape-html.js */ "../../../javascripts/HtmlEditor/Tools/unescape-html.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
var _SpellCheck;
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t.return || t.return(); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _classPrivateMethodInitSpec(e, a) { _checkPrivateRedeclaration(e, a), a.add(e); }
function _checkPrivateRedeclaration(e, t) { if (t.has(e)) throw new TypeError("Cannot initialize the same private elements twice on an object"); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _assertClassBrand(e, t, n) { if ("function" == typeof e ? e === t : e.has(t)) return arguments.length < 3 ? t : n; throw new TypeError("Private element is not present on this object"); }




var _SpellCheck_brand = /*#__PURE__*/new WeakSet();
var SpellCheck = /*#__PURE__*/function () {
  function SpellCheck() {
    _classCallCheck(this, SpellCheck);
    _classPrivateMethodInitSpec(this, _SpellCheck_brand);
  }
  return _createClass(SpellCheck, [{
    key: "cleanSpellcheckOnTypingInside",
    value:
    //region cleanup on typing
    function cleanSpellcheckOnTypingInside(rawData, actions, previousPosition, tagsConversion) {
      if (!actions.length) {
        return [rawData, previousPosition];
      }
      var doc = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(rawData);
      var position = previousPosition;
      var _iterator = _createForOfIteratorHelper(actions),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var action = _step.value;
          if (!action.type) {
            continue;
          }
          var calculatedPosition = _assertClassBrand(_SpellCheck_brand, this, _processNodes).call(this, doc, action, tagsConversion);
          position = calculatedPosition !== null ? calculatedPosition : position;
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
      return [doc.innerHTML, position];
    }
  }, {
    key: "transformMatches",
    value:
    //endregion

    //region transform matches
    function transformMatches(matches, originalText) {
      var existingQualities = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
      var internalTagsPositions = [];
      var deletionsPositions = [];
      var doc = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(originalText);
      var tags = doc.querySelectorAll('img.newline, img.whitespace');
      var _iterator2 = _createForOfIteratorHelper(tags),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var tag = _step2.value;
          var offsets = (0,_javascripts_HtmlEditor_Tools_calculate_node_offsets_js__WEBPACK_IMPORTED_MODULE_1__["default"])(doc, tag);
          internalTagsPositions.push(offsets.start);
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
      var deletions = doc.querySelectorAll('del');
      var _iterator3 = _createForOfIteratorHelper(deletions),
        _step3;
      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var deletion = _step3.value;
          var _offsets = (0,_javascripts_HtmlEditor_Tools_calculate_node_offsets_js__WEBPACK_IMPORTED_MODULE_1__["default"])(doc, deletion);
          deletionsPositions.push(_offsets);
        }
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
      var posMap = _assertClassBrand(_SpellCheck_brand, this, _buildPositionMap).call(this, doc);
      var transformed = [];
      var _iterator4 = _createForOfIteratorHelper(matches.entries()),
        _step4;
      try {
        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
          var _step4$value = _slicedToArray(_step4.value, 2),
            index = _step4$value[0],
            match = _step4$value[1];
          var matchStart = match.offset;
          var matchEnd = matchStart + match.context.length;
          var transformedMatch = {
            matchIndex: index,
            range: _assertClassBrand(_SpellCheck_brand, this, _getRangeForMatch).call(this, matchStart, matchEnd, internalTagsPositions, deletionsPositions),
            textRange: {
              start: matchStart,
              end: matchEnd
            },
            message: match.message,
            replacements: match.replacements.map(function (replacement) {
              return replacement.value;
            }),
            infoURLs: match.rule.urls ? match.rule.urls.map(function (url) {
              return url.value;
            }) : [],
            cssClassErrorType: Editor.data.plugins.SpellCheck.cssMap[match.rule.issueType] || ''
          };
          var matchContent = this.prepareTextForSpellCheck(_assertClassBrand(_SpellCheck_brand, this, _replaceSpace).call(this, _assertClassBrand(_SpellCheck_brand, this, _sliceHtml).call(this, posMap, transformedMatch.range.start, transformedMatch.range.end)));
          var quality = _assertClassBrand(_SpellCheck_brand, this, _lookForMatchingQuality).call(this, match, matchContent, existingQualities);
          if (quality) {
            transformedMatch.id = quality.id;
            transformedMatch.falsePositive = quality.falsePositive;
          }
          transformed.push(transformedMatch);
        }
      } catch (err) {
        _iterator4.e(err);
      } finally {
        _iterator4.f();
      }
      return transformed;
    }

    /**
     * Get the range of a match in the Editor using the offsets of the text-only version.
     * @param {int} matchStart
     * @param {int} matchEnd
     * @param {int[]} internalTagsPositions
     * @param {Object<start: int, end: int>[]} deletionsPositions
     * @returns {Object}
     */
  }, {
    key: "applyMatches",
    value:
    //endregion

    //region apply matches

    function applyMatches(originalText, matches) {
      var dom = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(originalText);
      var posMap = _assertClassBrand(_SpellCheck_brand, this, _buildPositionMap).call(this, dom);
      var contentLength = posMap.filter(function (e) {
        return e.type === 'char' || e.type === 'img';
      }).length;
      var result = '';
      var previousRangeEnd = 0;
      var _iterator5 = _createForOfIteratorHelper(matches),
        _step5;
      try {
        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
          var match = _step5.value;
          if (_assertClassBrand(_SpellCheck_brand, this, _isInsideTermNode).call(this, posMap, match.range.start, match.range.end)) {
            continue;
          }
          if (previousRangeEnd !== match.range.start) {
            result += _assertClassBrand(_SpellCheck_brand, this, _replaceSpace).call(this, _assertClassBrand(_SpellCheck_brand, this, _sliceHtml).call(this, posMap, previousRangeEnd, match.range.start));
          }
          var matchContent = _assertClassBrand(_SpellCheck_brand, this, _replaceSpace).call(this, _assertClassBrand(_SpellCheck_brand, this, _sliceHtml).call(this, posMap, match.range.start, match.range.end));
          var _assertClassBrand$cal = _assertClassBrand(_SpellCheck_brand, this, _splitLeadingDeletions).call(this, matchContent),
            _assertClassBrand$cal2 = _slicedToArray(_assertClassBrand$cal, 2),
            leadingDeletions = _assertClassBrand$cal2[0],
            remainder = _assertClassBrand$cal2[1];
          result += leadingDeletions;
          if (remainder) {
            result += _assertClassBrand(_SpellCheck_brand, this, _createSpellcheckNode).call(this, remainder, match);
          }
          previousRangeEnd = match.range.end;
        }
      } catch (err) {
        _iterator5.e(err);
      } finally {
        _iterator5.f();
      }
      var contentLeft = _assertClassBrand(_SpellCheck_brand, this, _sliceHtml).call(this, posMap, previousRangeEnd, contentLength);
      if (contentLeft === '&nbsp;') {
        // If this is the latest nbsp in the whole content
        // do not replace it with space because it will be trimmed on applying it to editor
        result += contentLeft;
      } else {
        result += _assertClassBrand(_SpellCheck_brand, this, _replaceSpace).call(this, contentLeft, true, false);
      }
      return result;
    }

    /**
     * Checks whether a given match corresponds to a false-positive in the existing persisted qualities.
     * Matching is done by plain-text range (textRange) first, with content as a secondary check.
     *
     * @param {Object} match
     * @param {string} renderedContent  – the HTML slice that will be wrapped (inner HTML of the span)
     * @param {Array} existingQualities
     * @returns {Object|null}  the matched quality if it exists, or null if it doesn't
     */
  }, {
    key: "prepareTextForSpellCheck",
    value:
    //endregion

    //region prepare text for spellcheck

    /**
     * @param {String} text
     * @return {String}
     */
    function prepareTextForSpellCheck(text) {
      var editorContentAsText = _assertClassBrand(_SpellCheck_brand, this, _getContentWithWhitespaceImagesAsText).call(this, text);
      // Replace all new line tags to actual new line
      editorContentAsText = editorContentAsText.replaceAll('<br>', '\n');
      editorContentAsText = _assertClassBrand(_SpellCheck_brand, this, _cleanupForSpellchecking).call(this, (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(editorContentAsText));
      editorContentAsText = (0,_javascripts_HtmlEditor_Tools_unescape_html_js__WEBPACK_IMPORTED_MODULE_3__["default"])(editorContentAsText);
      return editorContentAsText.trim();
    }
  }, {
    key: "cleanupSpellcheckNodes",
    value: function cleanupSpellcheckNodes(text) {
      var dom = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(text);
      var result = dom.innerHTML;
      var spellcheckNodes = dom.querySelectorAll(SpellCheck.NODE_NAME_MATCH);
      var _iterator6 = _createForOfIteratorHelper(spellcheckNodes),
        _step6;
      try {
        for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
          var node = _step6.value;
          result = result.replace(node.outerHTML, node.innerHTML);
        }
      } catch (err) {
        _iterator6.e(err);
      } finally {
        _iterator6.f();
      }
      return result;
    }

    /**
     * Replace whitespace-images with whitespace-text.
     *
     * @params {String} text
     * @returns {String} html
     */

    //endregion
  }]);
}();
_SpellCheck = SpellCheck;
function _processNodes(doc, action, tagsConversion) {
  var _this = this;
  var position = action.position;
  var pointer = 0;
  function traverseNodes(node) {
    if (node.nodeType === Node.TEXT_NODE || tagsConversion.isTag(node)) {
      var nodeLength = (0,_javascripts_HtmlEditor_Tools_calculate_node_length_js__WEBPACK_IMPORTED_MODULE_2__["default"])(node);
      var isWithinNode = pointer <= position && pointer + nodeLength >= position;
      if (!isWithinNode) {
        pointer += nodeLength;
        return null;
      }
      var isInserting = action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.INSERT;
      var dom = !isInserting && action.content.length ? action.content[0].toDom() : null;
      var isDeletingSpellCheck = !isInserting && dom && (tagsConversion.isSpellcheckNode(dom) ||
      // If one of its children is a spellcheck node
      dom.nodeType === Node.ELEMENT_NODE && !!dom.querySelectorAll('.t5spellcheck').length);

      // if we are inserting or deleting inside a spellcheck node, unwrap it
      if (tagsConversion.isSpellcheckNode(node.parentNode) && (isInserting || isDeletingSpellCheck)) {
        _assertClassBrand(_SpellCheck_brand, _this, _unwrapNodesWithMatchIndex).call(_this, doc, node.parentNode.getAttribute(_SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX));
        return isInserting ? position + action.correction : position;
      }

      // if we are deleted at the beginning of the spellcheck node with the del or backspace key
      // and due to calculation of the position of a change current node can be right before the
      // spellcheck node that we need to unwrap
      var siblingToUnwrap = node.nextSibling && tagsConversion.isSpellcheckNode(node.nextSibling) ? node.nextSibling : null;

      // sometimes next sibling should be checked on a parent node
      if (!siblingToUnwrap && node.parentNode.lastChild === node) {
        siblingToUnwrap = node.parentNode.nextSibling && tagsConversion.isSpellcheckNode(node.parentNode.nextSibling) ? node.parentNode.nextSibling : null;
      }
      if (siblingToUnwrap && isDeletingSpellCheck) {
        _assertClassBrand(_SpellCheck_brand, _this, _unwrapNodesWithMatchIndex).call(_this, doc, siblingToUnwrap.getAttribute(_SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX));
        return isInserting ? position + action.correction : position;
      }
      return isInserting ? position + action.correction : position;
    }
    if (tagsConversion.isTag(node)) {
      pointer++;
      return null;
    }
    if (node.nodeType === Node.ELEMENT_NODE) {
      var _iterator7 = _createForOfIteratorHelper(node.childNodes),
        _step7;
      try {
        for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
          var child = _step7.value;
          var changed = traverseNodes(child);

          // This is done to prevent endless recursion
          if (changed) {
            return changed;
          }
        }
      } catch (err) {
        _iterator7.e(err);
      } finally {
        _iterator7.f();
      }
    }
    return null;
  }
  return traverseNodes(doc);
}
function _unwrapNodesWithMatchIndex(doc, matchIndex) {
  var nodes = doc.querySelectorAll("span[".concat(_SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX, "*=\"").concat(matchIndex, "\"]"));
  var _iterator8 = _createForOfIteratorHelper(nodes),
    _step8;
  try {
    for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
      var node = _step8.value;
      _assertClassBrand(_SpellCheck_brand, this, _unwrapSpellcheckNode).call(this, node);
    }
  } catch (err) {
    _iterator8.e(err);
  } finally {
    _iterator8.f();
  }
}
function _unwrapSpellcheckNode(spellCheckNode) {
  var spellCheckNodeParent = spellCheckNode.parentNode;
  var insertFragment = document.createRange().createContextualFragment(spellCheckNode.innerHTML);
  spellCheckNodeParent.insertBefore(insertFragment, spellCheckNode);
  spellCheckNodeParent.removeChild(spellCheckNode);
}
function _getRangeForMatch(matchStart, matchEnd, internalTagsPositions, deletionsPositions) {
  var deletionsBeforeStartLength = 0;
  var deletionsBeforeEndLength = 0;
  var _iterator9 = _createForOfIteratorHelper(deletionsPositions),
    _step9;
  try {
    for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
      var deletion = _step9.value;
      var deletionLength = deletion.end - deletion.start;
      var biasedStart = deletion.start - deletionsBeforeStartLength;
      var biasedEnd = deletion.end - deletionsBeforeEndLength - deletionLength;
      if (biasedStart < matchStart) {
        deletionsBeforeStartLength += deletionLength;
      }
      if (biasedEnd < matchEnd) {
        deletionsBeforeEndLength += deletionLength;
      }
    }
  } catch (err) {
    _iterator9.e(err);
  } finally {
    _iterator9.f();
  }
  var start = matchStart
  // Since internal tag length is 1 we can calculate amount of filtered tags
  + internalTagsPositions.filter(function (el) {
    return el < matchStart + deletionsBeforeStartLength;
  }).length + deletionsBeforeStartLength;
  var end = matchEnd + internalTagsPositions.filter(function (el) {
    return el < matchEnd + deletionsBeforeEndLength;
  }).length + deletionsBeforeEndLength;
  return {
    start: start,
    end: end
  };
}
function _lookForMatchingQuality(match, renderedContent, existingQualities) {
  var _match$textRange, _match$textRange2;
  if (!existingQualities.length) {
    return null;
  }
  var textStart = (_match$textRange = match.textRange) === null || _match$textRange === void 0 ? void 0 : _match$textRange.start;
  var textEnd = (_match$textRange2 = match.textRange) === null || _match$textRange2 === void 0 ? void 0 : _match$textRange2.end;

  // Strip HTML tags from the rendered slice to get comparable plain text
  var plainContent = renderedContent.replace(/<[^>]*>/g, '');
  var _iterator0 = _createForOfIteratorHelper(existingQualities),
    _step0;
  try {
    for (_iterator0.s(); !(_step0 = _iterator0.n()).done;) {
      var quality = _step0.value;
      if (!quality) {
        continue;
      }
      var rangeMatches = quality.range && quality.range.start === textStart && quality.range.end === textEnd;
      var contentMatches = quality.content !== undefined && quality.content === plainContent;
      if (rangeMatches || contentMatches) {
        return quality;
      }
    }
  } catch (err) {
    _iterator0.e(err);
  } finally {
    _iterator0.f();
  }
  return null;
}
/**
 * Builds a flat position-map from a DOM node.
 * Logical position rules (matching CKEditor model / calculateNodeLength):
 *   - text characters each count as 1  → type 'char'
 *   - <img> elements count as 1        → type 'img'
 *   - all other elements are transparent containers → type 'open' / 'close'
 *
 * @param {HTMLElement} root
 * @returns {Array}
 */
function _buildPositionMap(root) {
  var entries = [];
  function traverse(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      var _iterator1 = _createForOfIteratorHelper(node.nodeValue),
        _step1;
      try {
        for (_iterator1.s(); !(_step1 = _iterator1.n()).done;) {
          var char = _step1.value;
          entries.push({
            type: 'char',
            value: char
          });
        }
      } catch (err) {
        _iterator1.e(err);
      } finally {
        _iterator1.f();
      }
      return;
    }
    if (node.nodeName === 'IMG') {
      entries.push({
        type: 'img',
        node: node
      });
      return;
    }
    if (node.nodeType === Node.ELEMENT_NODE) {
      entries.push({
        type: 'open',
        node: node
      });
      var _iterator10 = _createForOfIteratorHelper(node.childNodes),
        _step10;
      try {
        for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
          var child = _step10.value;
          traverse(child);
        }
      } catch (err) {
        _iterator10.e(err);
      } finally {
        _iterator10.f();
      }
      entries.push({
        type: 'close',
        node: node
      });
    }
  }
  var _iterator11 = _createForOfIteratorHelper(root.childNodes),
    _step11;
  try {
    for (_iterator11.s(); !(_step11 = _iterator11.n()).done;) {
      var child = _step11.value;
      traverse(child);
    }
  } catch (err) {
    _iterator11.e(err);
  } finally {
    _iterator11.f();
  }
  return entries;
}
/**
 * Reconstructs the HTML for the logical range [from, to) from a position map.
 * Transparent container tags are included when their content falls within the range.
 *
 * @param {Array}  posMap
 * @param {number} from
 * @param {number} to
 * @returns {string}
 */
function _sliceHtml(posMap, from, to) {
  var pos = 0;
  var result = '';
  // Each frame: { node, emitted }
  // "emitted" is true when the open-tag has been written to `result`.
  var openStack = [];
  var VOID = new Set(['BR', 'HR', 'INPUT', 'LINK', 'META']);

  // Emit opening tags for any ancestor frames not yet emitted.
  // Called whenever we are about to emit content inside the range.
  var ensureAncestorsOpen = function ensureAncestorsOpen() {
    for (var _i = 0, _openStack = openStack; _i < _openStack.length; _i++) {
      var frame = _openStack[_i];
      if (!frame.emitted) {
        var el = frame.node;
        result += el.outerHTML.slice(0, el.outerHTML.indexOf('>') + 1);
        frame.emitted = true;
      }
    }
  };
  var _iterator12 = _createForOfIteratorHelper(posMap),
    _step12;
  try {
    for (_iterator12.s(); !(_step12 = _iterator12.n()).done;) {
      var entry = _step12.value;
      if (entry.type === 'open') {
        var inRange = pos >= from && pos < to;
        var frame = {
          node: entry.node,
          emitted: false
        };
        if (inRange) {
          // Tag opens inside the range — ensure ancestors are written first, then open this tag.
          ensureAncestorsOpen();
          result += entry.node.outerHTML.slice(0, entry.node.outerHTML.indexOf('>') + 1);
          frame.emitted = true;
        }
        // If not yet in range, push with emitted=false; it will be lazily opened
        // by ensureAncestorsOpen() if the range starts inside this element.
        openStack.push(frame);
        continue;
      }
      if (entry.type === 'close') {
        var _frame = openStack.pop();
        if (_frame && _frame.emitted && !VOID.has(entry.node.nodeName)) {
          result += "</".concat(entry.node.nodeName.toLowerCase(), ">");
        }
        continue;
      }

      // 'char' or 'img' — advance logical position
      if (pos >= from && pos < to) {
        ensureAncestorsOpen();
        if (entry.type === 'char') {
          result += entry.value === "\xA0" ? '&nbsp;' : entry.value;
        } else {
          result += entry.node.outerHTML;
        }
      }
      pos++;
    }
  } catch (err) {
    _iterator12.e(err);
  } finally {
    _iterator12.f();
  }
  return result;
}
/**
 * Returns true when any content in the logical range [from, to) is nested
 * inside a <span class="term"> element, meaning the match should not be
 * wrapped in a spellcheck node.
 *
 * @param {Array}  posMap
 * @param {number} from
 * @param {number} to
 * @returns {boolean}
 */
function _isInsideTermNode(posMap, from, to) {
  var pos = 0;
  var openStack = [];
  var _iterator13 = _createForOfIteratorHelper(posMap),
    _step13;
  try {
    for (_iterator13.s(); !(_step13 = _iterator13.n()).done;) {
      var entry = _step13.value;
      if (entry.type === 'open') {
        openStack.push(entry.node);
        continue;
      }
      if (entry.type === 'close') {
        openStack.pop();
        continue;
      }

      // 'char' or 'img' — check logical position against the range
      if (pos >= from && pos < to) {
        if (openStack.some(function (node) {
          return node.nodeName === 'SPAN' && node.classList.contains('term');
        })) {
          return true;
        }
      }
      pos++;
    }
  } catch (err) {
    _iterator13.e(err);
  } finally {
    _iterator13.f();
  }
  return false;
}
/**
 * Splits the leading <del> nodes from the beginning of an HTML string.
 * Returns [leadingDeletionsHtml, remainderHtml].
 * If there are no leading deletions, leadingDeletionsHtml is an empty string.
 *
 * @param {string} html
 * @returns {[string, string]}
 */
function _splitLeadingDeletions(html) {
  var dom = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(html);
  var leadingDeletions = '';
  var remainder = '';
  var foundNonDeletion = false;
  var _iterator14 = _createForOfIteratorHelper(dom.childNodes),
    _step14;
  try {
    for (_iterator14.s(); !(_step14 = _iterator14.n()).done;) {
      var node = _step14.value;
      if (!foundNonDeletion && node.nodeName === 'DEL') {
        leadingDeletions += node.outerHTML;
      } else {
        foundNonDeletion = true;
        remainder += node.nodeType === Node.TEXT_NODE ? node.nodeValue : node.outerHTML;
      }
    }
  } catch (err) {
    _iterator14.e(err);
  } finally {
    _iterator14.f();
  }
  return [leadingDeletions, remainder];
}
/**
 * Create and return a new node for SpellCheck-Match of the given index.
 * For match-specific data, get the data from the tool.
 *
 * @param {string}  text
 * @param {Object}  match
 *
 * @returns {string}
 */
function _createSpellcheckNode(text, match) {
  var node = document.createElement(_SpellCheck.NODE_NAME_MATCH);
  node.className = "".concat(Editor.util.HtmlClasses.CSS_CLASSNAME_SPELLCHECK, " ").concat(match.cssClassErrorType, " ownttip");
  node.setAttribute(_SpellCheck.ATTRIBUTE_ACTIVEMATCHINDEX, match.matchIndex);
  node.setAttribute(_SpellCheck.ATTRIBUTE_QTIP, Editor.data.l10n.SpellCheck.nodeTitle);
  var falsePositive = !!(match !== null && match !== void 0 && match.falsePositive);
  node.setAttribute(_SpellCheck.ATTRIBUTE_FALSE_POSITIVE, falsePositive ? 'true' : 'false');
  if (match.id) {
    node.setAttribute(_SpellCheck.ATTRIBUTE_QUALITY_ID, match.id);
  }
  node.innerHTML = text;
  return node.outerHTML;
}
function _replaceSpace(text) {
  var start = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  var end = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
  var result = text;
  if (start) {
    result = result.replace(/^&nbsp;/, ' ');
  }
  if (end) {
    result = result.replace(/&nbsp;$/, ' ');
  }
  return result;
}
function _cleanupForSpellchecking(dom) {
  var result = '';
  var _iterator15 = _createForOfIteratorHelper(dom.childNodes),
    _step15;
  try {
    for (_iterator15.s(); !(_step15 = _iterator15.n()).done;) {
      var node = _step15.value;
      if (node.nodeName === '#text') {
        result += node.data;
        continue;
      }
      if (node.nodeName === 'SPAN') {
        result += _assertClassBrand(_SpellCheck_brand, this, _cleanupForSpellchecking).call(this, node);
        continue;
      }
      if (node.nodeName === 'IMG') {
        continue;
      }
      if (node.nodeName === 'DEL') {
        continue;
      }
      if (node.childNodes.length > 0) {
        result += _assertClassBrand(_SpellCheck_brand, this, _cleanupForSpellchecking).call(this, node);
        continue;
      }
      result += node.outerHTML;
    }
  } catch (err) {
    _iterator15.e(err);
  } finally {
    _iterator15.f();
  }
  return result;
}
function _getContentWithWhitespaceImagesAsText(text) {
  var html = text;
  var dom = (0,_javascripts_HtmlEditor_Tools_string_to_dom_js__WEBPACK_IMPORTED_MODULE_0__["default"])(html);
  var _iterator16 = _createForOfIteratorHelper(dom.childNodes),
    _step16;
  try {
    for (_iterator16.s(); !(_step16 = _iterator16.n()).done;) {
      var node = _step16.value;
      if (node.nodeName === 'IMG' && node.classList.contains('whitespace') && !node.classList.contains('newline')) {
        html = html.replace(node.outerHTML, ' ');
      }
    }
  } catch (err) {
    _iterator16.e(err);
  } finally {
    _iterator16.f();
  }
  return html;
}
_defineProperty(SpellCheck, "NODE_NAME_MATCH", 'span');
_defineProperty(SpellCheck, "ATTRIBUTE_ACTIVEMATCHINDEX", 'data-spellCheck-activeMatchIndex');
_defineProperty(SpellCheck, "ATTRIBUTE_QTIP", 'data-qtip');
_defineProperty(SpellCheck, "ATTRIBUTE_FALSE_POSITIVE", 'data-t5qfp');
_defineProperty(SpellCheck, "ATTRIBUTE_QUALITY_ID", 'data-t5qid');

})();

SpellCheck = __webpack_exports__["default"];
/******/ })()
;
//# sourceMappingURL=spell-check.js.map