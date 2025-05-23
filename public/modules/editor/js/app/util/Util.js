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
 * Useful extjs functions
 * @class Editor.util.Util
 */
Ext.define('Editor.util.Util', {
    errorLevel: {
        '1': 'fatal',
        '2': 'error',
        '4': 'warn',
        '8': 'info',
        '16': 'debug',
        '32': 'trace'
    },
    statics: {

        /***
         *
         * @param {Date} date The date to modify
         * @param {Number} days The amount to add to the current date. If decimal provided, it will be converted to hours
         * @return {Date} The new Date instance.
         */
        addBusinessDays: function(date, days){
            // if it is float number, calculate the hours from the floating point number.
            var hours = days - parseInt(days);
            if(hours > 0){
                hours = 24 * hours;
                date = Ext.Date.add(date, Ext.Date.HOUR, hours);
            }
            for(var i = 1; i <= days;){
                date = Ext.Date.add(date, Ext.Date.DAY, 1);
                if(!Ext.Date.isWeekend(date)){
                    i++;
                }
            }
            return date;
        },
        /**
         * Creates an CSS-Selector from props
         * @param nodeName {String} the relevant node-name of the serched elements
         * @param classNames {Array,String} like [class1, ..., classN] OR String the relevant class/classes of the searched elements
         * @param dataProps {Array} like [{ name:'name1', value:'val1' }, ..., { name:'nameN', value:'valN' }] the relevant data-properties of the searched elements
         * @return String
         */
        createSelectorFromProps(nodeName, classNames, dataProps){
            var selector = (nodeName) ? nodeName : '';
            if(classNames){
                selector += (Array.isArray(classNames)) ? ('.' + classNames.join('.')) : ('.' + classNames.split(' ').join('.'));
            }
            if(dataProps && Array.isArray(dataProps)){
                dataProps.forEach(function(prop){
                    if(prop.name && prop.value){
                        selector += ('[data-' + prop.name + '=\'' + prop.value + '\']');
                    }
                });
            }
            return selector;
        },

        /**
         * renders the value of the language columns
         * @param {String} val
         * @returns {String}
         */
        gridColumnLanguageRenderer: function(val, md){
            var lang = Ext.StoreMgr.get('admin.Languages').getById(val),
                label;
            if(lang){
                label = lang.get('label');
                md.tdAttr = 'data-qtip="' + label + '"';
                return label;
            }
            return '';
        },

        /***
         * Return the translated workflowStep name
         */
        getWorkflowStepNameTranslated: function(stepName,workflowId){
            if(!stepName || !workflowId || !Editor.data.app.workflows[workflowId])
            {
                return '';
            }

            const steps = Editor.data.app.workflows[workflowId].steps;
            return steps[stepName] ? steps[stepName] : stepName;
        },

        /***
         * Get the file extension from the file name
         * @returns {any|string}
         */
        getFileExtension: function(name){
            return name ? name.split('.').pop().toLowerCase() : '';
        },

        /***
         * Check if the given file name has archive type extension
         * @param fileName
         * @returns {*}
         */
        isZipFile: function(fileName){
            //TODO: the backend only supports zip
            return this.getFileExtension(fileName) === 'zip';
        },

        /***
         * Return only the file name and ignoring the file extension
         * @param filename
         * @returns {string}
         */
        getFileNameNoExtension: function(filename){
            return filename.substring(0, filename.lastIndexOf('.')) || filename;
        },

        /***
         * Compare file names in import style. The files are equal when the names are matching until the first ".".
         * This is used when comparing if the pivot/workfile are matching.
         *
         * ex: my-test-project.de-en.xlf will match my-test-project.de-it.xlf
         *
         * @param string workfile
         * @param string pivotfile
         * @return boolean
         */
        compareImportStyleFileName: function(workfile, pivotfile){
            return workfile.split('.')[0] === pivotfile.split('.')[0];
        },

        /***
         * Covert the unicode code to real character ready to be displayed in the browser
         * ECMAScript 6 Unicode code point escapes sequence https://262.ecma-international.org/6.0/#sec-literals-string-literals.
         * Ex. if the input code is U+1F98A the output will be 🦊
         * @param code
         */
        toUnicodeCodePointEscape: function(code){
            var regex = /U\+[a-zA-Z0-9]+/g;
            if(regex.test(code) === false){
                return code;
            }
            var hex = code.replace('U+', '');
            return String.fromCodePoint('0x' + hex);
        },

        /***
         * Find all rfx fuzzy languages for a given langauge code.
         *
         * de     will match de, de-DE, de-CH, de-AT etc..
         * de-DE  will match de and de-DE
         *
         * @param rfc
         * @returns {*[]}
         */
        getFuzzyLanguagesForCode: function(rfc){
            var isMajor = rfc.includes('-') === false,
                collected = [],
                checkLowerRfc = rfc.toLowerCase();

            Ext.getStore('admin.Languages').each(function(r){
                var lowerRfc = r.get('rfc5646').toLowerCase();

                if(lowerRfc === checkLowerRfc){
                    // direct match
                    collected.push(r.get('rfc5646'));
                } else if(isMajor && lowerRfc.startsWith(checkLowerRfc + '-')){
                    // de will match de-DE, de-AT, de-CH
                    collected.push(r.get('rfc5646'));
                } else if(!isMajor && (checkLowerRfc.includes('-') && checkLowerRfc.split('-')[0] === lowerRfc)){
                    // de-DE will match de and de-DE
                    collected.push(r.get('rfc5646'));
                }
            });

            return collected;
        },
        getErrorLevelName: function(level){
            if(!level){
                level = this.prototype.get('level');
            }
            if(this.prototype.errorLevel[level]){
                return this.prototype.errorLevel[level];
            }
            return '';
        },
        /**
         * Shows a 'Choose file' dialogue
         * @param accept - extensions or mimetypes to choose (comma separated)
         * @param multiple - whether multiple files can be chosen
         * @returns {Promise<file[]>} - an array of the chosen files
         */
        chooseFile: function(accept = '*', multiple = false){
            return new Promise(function(resolve, reject){
                var fileInput = Ext.DomHelper.createDom({tag: 'input', type: 'file', accept, multiple});
                fileInput.addEventListener('change', () => resolve(fileInput.files));
                setTimeout(() => fileInput.click(), 1); // Must be async bc file dialogue blocks JS
            });
        },

        /**
         * @deprecated
         * TODO FIXME: Get rid of this and use "normal" download-links instead.
         * @param {string} url
         * @param {object} params
         */
        download: function(url, params = null){
            var baseUrl = undefined;
            if(!url.startsWith('http')){
                url = Editor.data.restpath + url;
                baseUrl = location.origin;
            }
            if(params){
                var urlObj = new URL(url, baseUrl);
                var searchParams = new URLSearchParams(urlObj.search);
                for(const [param, value] of Object.entries(params)){
                    searchParams.set(param, value);
                }
                urlObj.search = searchParams;
                url = urlObj.toString();
            }
            Ext.DomHelper.createDom({
                tag: 'a',
                download: '',
                href: url
            }).click();
        },

        /**
         * @deprecated
         * A wrapper around fetch to return Ext.Ajax-like response objects to use with existing APIs
         * @param {string} url
         * @param {RequestInit} options - the fetch API options to use
         * @returns {Promise<response>} - Ext.Ajax-like response object
         */
        fetchXHRLike: async function(url, options = {}){
            return new Promise(function(resolve, reject){
                let responseHandler = async function(response){
                    let ret;
                    if(!response){
                        ret = {status: 0, statusText: '', responseText: 'No response received'};
                    } else if(response instanceof Error){
                        ret = {status: 0, statusText: response.toString(), responseText: `{errorMessage: "${response.toString()}" }`};
                    } else {
                        ret = response;
                        let contentLength = parseInt(response.headers.get('Content-Length'));
                        let contentType = (response.headers.get('Content-Type') || '').split('/').pop();
                        switch(contentType){
                            case 'json':
                                response.responseJson = contentLength ? await response.json() : {};
                                if(response.status !== 200){
                                    response.responseText = JSON.stringify(response.responseJson);
                                }
                                break;
                            case 'xml':
                                response.responseText = contentLength ? await response.text() : '';
                                response.responseXML = new window.DOMParser().parseFromString(response.responseText, 'text/xml');
                                if(response.status !== 200){
                                    delete response.responseText; // QUIRK: match ServerException.handleFailedRequest
                                }
                                break;
                            case 'text':
                            default:
                                response.responseText = contentLength ? await response.text() : '';
                        }
                    }
                    options.url = url;
                    ret.options = options;
                    resolve(ret);
                };

                var headers = options.headers || (options.headers = new Headers());
                if(!headers.has('Accept')){
                    headers.append('Accept', 'application/json');
                }
                if(!headers.has('CsrfToken')){
                    headers.append('CsrfToken', Editor.data.csrfToken);
                }
                if(options.formData){
                    var body = options.body = new FormData();
                    Object.entries(options.formData).forEach(([name, value]) => body.append(name, value));
                }
                fetch(url, options)
                    .then(responseHandler)
                    .catch(responseHandler);
            });
        },

        /** @see https://stackoverflow.com/a/3561711 */
        escapeRegex: function(string){
            return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
        },

        /***
         * Get the base route from the current route.
         * ex: from project/123/124/focus the returned value will be project
         * @returns {*|string}
         */
        getCurrentBaseRoute: function (){
            var base = Ext.util.History.getToken().split('/');
            return base.length > 0 ? base[0] : '';
        },

        /**
         * Checks changed properties from an edited object to an original object
         * Supports primitives and objects with/iterables of primitive property values
         * Does not suppert complex iterables or objects as values
         * By default, the order of array does not matter
         * @param {Object} before
         * @param {Object} after
         * @param {Array} keys
         * @param {boolean} orderIsIrrelevant
         * @returns {boolean}
         */
        objectWasChanged: function(before, after, keys, orderIsIrrelevant=true){
            var bval, aval, changed = false;
            keys.forEach(key => {
                bval = before.hasOwnProperty(key) ? before[key] : undefined;
                aval = after.hasOwnProperty(key) ? after[key] : undefined;
                if(Array.isArray(aval) && Array.isArray(bval) && !this.arraysAreEqual(aval, bval, orderIsIrrelevant)){
                    changed = true;
                } else if(aval !== bval){
                    changed = true;
                }
            });
            return changed;
        },
        /**
         * Checks if to arrays are equal. By default, the order of items is irrelevant
         * @param {Array} a
         * @param {Array} b
         * @param {boolean} orderIsIrrelevant
         * @returns {boolean}
         */
        arraysAreEqual: function(a, b, orderIsIrrelevant=true) {
            if(a === b) { return true; }
            if(!a || !b) { return false; }
            if(a.length !== b.length) { return false; }
            for (var i=0; i < a.length; i++) {
                if((orderIsIrrelevant && b.indexOf(a[i]) === -1) || (!orderIsIrrelevant && a[i] !== b[i])){
                    return false;
                }
            }
            return true;
        },
        isIterable: function(value, includeString = false){
            return typeof value[Symbol.iterator] === 'function' && (typeof value !== 'string' || includeString);
        },
        /***
         * Check if the given language id/string is empty.
         * 0 / "0" is treated as empty
         * @param e
         * @returns {boolean}
         */
        isLanguageEmpty:function (e) {
            switch (e) {
                case "":
                case 0:
                case "0":
                case null:
                case false:
                case undefined:
                    return true;
                default:
                    return false;
            }
        },

        trimLastSlash: function(str){
            return str.substring(0, str.lastIndexOf('/'));
        },
        parentRoute: async function(route){
            route = route || Ext.util.History.getToken();
            var parentRoute = Editor.util.Util.trimLastSlash(route);
            Editor.app.redirectTo(parentRoute);
        },
        awaitStore: async function(store){
            return store.isLoaded() || await new Promise(function(resolve){
                store.on('load', function(){
                        resolve();
                }, this, { single: true });
            });
        },
        awaitSelection: async function(grid, recId){
            return (!recId || grid.selection?.id === recId) && grid.selection || await new Promise(function(resolve){
                grid.on('selectionchange', function(){
                    resolve(grid.selection);
                }, this, { single: true });
            });
        },
        closeWindows: function(){
            var win;
            while(win = Ext.WindowManager.getActive()) {
                win.close && win.close() || win.hide && win.hide();
            }
        },
        /** @link https://stackoverflow.com/a/69200017 */
        getXmlError: function(xmlStr){
            const parser = new DOMParser();
            const dom = parser.parseFromString(xmlStr, 'application/xml');
            const error = dom.querySelector('parsererror');
            return !error || error.innerHTML || error.textContent;
        },
        /** @link https://stackoverflow.com/questions/1026069 */
        ucfirst: function(s){
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        /***
         * Is the import/reimport translator package available for given task
         * @param task
         * @returns {*|Boolean|boolean}
         */
        isTranslatorPackageAvailable: function (task){
            if(!task){
                return false;
            }
            // if the task is not reimportable, the export/import translator package is not available
            if (!task.get('reimportable') || !task.isNotErrorImportPendingCustom()){
                return false;
            }
            return Editor.app.authenticatedUser.isAllowed('editorPackageExport',task);
        },

        /**
         * Helper to guarantee an array of id's are all integers
         * @param {string[]} values
         * @returns {int[]}
         */
        integerizeArray: function(values){
            var ints = [];
            Ext.Array.each(values, function(id){
                ints.push(parseInt(id));
            });
            return ints;
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        removeLeadingTrailingCommas: function(value){
            while(value.substring(0, 1) === ','){
                value = value.substring(1);
            }
            while(value.substring(value.length - 1, value.length) === ','){
                value = value.substring(0, value.length - 1);
            }
            return value;
        }
    }
});