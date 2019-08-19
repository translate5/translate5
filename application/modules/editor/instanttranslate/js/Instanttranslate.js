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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

var editIdleTimer = null,
    DEFAULT_FILE_EXT='txt',
    NOT_AVAILABLE_CLS = 'notavailable', // css if a (source-/target-)locale is not available in combination with the other (target-/source-)locale that is set
    uploadedFiles,//Variable to store uploaded files
    translateTextResponse = '',
    latestTranslationInProgressID = false,
    latestTextToTranslate = '',
    instantTranslationIsActive = true,
    chosenSourceIsText = true,
    fileTypesAllowedAndAvailable = [],
    additionalTranslationsHtmlContainer='';

/***
 * Store allowed file-types for all available languageResources.
 */
function setFileTypesAllowedAndAvailable() {
    for (languageResourceId in Editor.data.apps.instanttranslate.allLanguageResources) {
        if(Editor.data.apps.instanttranslate.allLanguageResources.hasOwnProperty(languageResourceId)){
            addFileTypesAllowedAndAvailable(Editor.data.apps.instanttranslate.allLanguageResources[languageResourceId]);
        }
    }
}
/***
 * Adds the allowed file-types for the given languageResource.
 * - If two languageResources for the same language-combination are available and allow fileUploads,
 *   the one with a domainCode is prioritized.
 * - Other cases are not defined so far (e.g. what if we have THREE languageResources for the same 
 *   language-combination and TWO of them have a domainCode / what if they belong to different 
 *   services / ...).
 */
function addFileTypesAllowedAndAvailable(languageResource) {
    var extensionsFileTranslation,
        serviceFileExtensions,
        languageCombination,
        filesTypesForLanguageCombination;
    if(languageResource.fileUpload == false){
        return;
    }
    extensionsFileTranslation = Editor.data.languageresource.fileExtension;
    if(!extensionsFileTranslation.hasOwnProperty(languageResource.serviceName)){
        return;
    }
    serviceFileExtensions = extensionsFileTranslation[languageResource.serviceName];
    $.each(getLanguageCombinations(languageResource), function(index, languageCombination) {
        filesTypesForLanguageCombination = [];
        // Do we already have file-types stored for this language-combination? Then check the domainCode and prioritize accordingly.
        if(fileTypesAllowedAndAvailable.hasOwnProperty(languageCombination)) {
            var domainCodeStored = fileTypesAllowedAndAvailable[languageCombination].domainCode,
                isEmptyDomainCode = (languageResource.domainCode == '' || languageResource.domainCode == null) ? true : false;
            if (domainCodeStored != '' && isEmptyDomainCode) {
                return;
            } 
        }
        serviceFileExtensions.forEach(function(fileType) {
            // Add file-extensions (but only if they are not stored already).
            if ($.inArray(fileType, filesTypesForLanguageCombination) === -1) {
                filesTypesForLanguageCombination.push(fileType);
            }
          });
        if(filesTypesForLanguageCombination.length > 0) {
            var dataForLanguageCombination = {'domainCode': languageResource.domainCode,
                                              'sourceLocale': languageResource.source,
                                              'targetLocale': languageResource.target,
                                              'fileTypes': filesTypesForLanguageCombination};
            fileTypesAllowedAndAvailable[languageCombination] = dataForLanguageCombination;
        }
    });
}
/**
 * Returns all available combinations of source- and target-language for the given languageResource.
 * - TermCollections can have multiple languages in the source and in the target 
 *   and can translate in all combinations of their source- and target-languages,
 *   but not from the source to the SAME target-language.
 * @param object languageResource
 * @returns array languageCombination
 */
function getLanguageCombinations(languageResource) {
    var allLanguageCombinations = [];
    $.each(languageResource.source, function(index, sourceLang) {
        $.each(languageResource.target, function(index, targetLang) {
            if (sourceLang != targetLang) {
                allLanguageCombinations.push(sourceLang+","+targetLang);
            }
        });
    });
    return allLanguageCombinations;
}
/**
 * Checks if a localeToCheck is ok according to the given allLocalesAvailable.
 * Formerly used to compare strings, now we check arrays, who knows what's yet to come.
 * @param string localeToCheck
 * @param array allLocalesAvailable
 * @returns boolean
 */
function isAvailableLocale(localeToCheck, allLocalesAvailable) {
    return $.inArray(localeToCheck, allLocalesAvailable) !== -1;
}
/**
 * Which fileTypes are allowed for the current language-combination?
 * @returns array
 */
function getAllowedFileTypes() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        filesTypesForLanguageCombination,
        addFileTypes,
        allowedFileTypes = [];
    for (var key in fileTypesAllowedAndAvailable) {
        if (fileTypesAllowedAndAvailable.hasOwnProperty(key)) {
            filesTypesForLanguageCombination = fileTypesAllowedAndAvailable[key];
            addFileTypes = false;
            if (isAvailableLocale(sourceLocale, filesTypesForLanguageCombination.sourceLocale) && isAvailableLocale(targetLocale, filesTypesForLanguageCombination.targetLocale)) {
                addFileTypes = true;
            }
            if (addFileTypes) {
                $.each(filesTypesForLanguageCombination.fileTypes, function(index, fileType) {
                    if ($.inArray(fileType, allowedFileTypes) === -1) {
                        allowedFileTypes.push(fileType);
                    }
                });
            }
        }
    }
    return allowedFileTypes;
}
/***
 * If files are allowed for the current language-combination, show text accordingly.
 */
function setTextForSource() {
    var textForSourceIsText = Editor.data.languageresource.translatedStrings['enterText'],
        textForSourceIsFile = '',
        allowedFileTypes = getAllowedFileTypes();
    if (allowedFileTypes.length === 0) {
        // No file-upload is possible
        chosenSourceIsText = true;
        showSource();
    } else {
        // When source is chosen to text
        textForSourceIsText += ' <span class="change-source-type">';
        textForSourceIsText += Editor.data.languageresource.translatedStrings['orTranslateFile'];
        textForSourceIsText += ' (' + allowedFileTypes.join(', ') + ')';
        textForSourceIsText += '</span>';
        // When source is chosen to file
        textForSourceIsFile = Editor.data.languageresource.translatedStrings['uploadFile'];
        textForSourceIsFile += ' (' + allowedFileTypes.join(', ') + ')';
        textForSourceIsFile += ' <span class="change-source-type">';
        textForSourceIsFile += Editor.data.languageresource.translatedStrings['orTranslateText'];
        textForSourceIsFile += '</span>';
    }
    $("#sourceIsText").html(textForSourceIsText);
    $("#sourceIsFile").html(textForSourceIsFile);
}

/* --------------- locales-list (source, target) ---------------------------- */

/**
 * When one of the select-lists is opened, it will show the
 * availability of it's options according to the value in the 
 * other select-list that is currently NOT opened.
 * @param el
 */
function updateLocalesSelectLists(el) {
    var elId = el.attr('id'),
        referenceList,
        accordingToReference,
        selectedLocaleInReference,
        localesAvailable;
    // the list that the user IS NOT editing is now the reference;
    // in case the selected item was not set as available, it now is!
    referenceList = (elId === 'sourceLocale') ? 'targetLocale' : 'sourceLocale';
    $('#'+referenceList+' .ui-selected').removeClass(NOT_AVAILABLE_CLS);
    // the list that the user IS editing:
    // what items are available according to the reference now?
    accordingToReference = (referenceList === 'sourceLocale') ? 'accordingToSourceLocale' : 'accordingToTargetLocale';
    selectedLocaleInReference = $("#"+referenceList).val();
    localesAvailable = getLocalesAccordingToReference(accordingToReference,selectedLocaleInReference);
    $('#'+elId+'-menu li.ui-menu-item').each(function(i){
        if (localesAvailable.indexOf($(this).text()) === -1) {
            $(this).addClass(NOT_AVAILABLE_CLS);
        } else {
            $(this).removeClass(NOT_AVAILABLE_CLS);
        }
    })
}

/**
 * Every change in the language-selection starts a check if any lnguageResources
 * are available. If yes and text is already entered, the translation starts.
 */
function handleAfterLocalesChange() {
    // When the language-combination changes, former translations are not valid any longer (= the text hasn't been translated already):
    latestTextToTranslate = '';
    $('#translations').html('');
    // Neither are former error-messages valid any longer:
    clearAllErrorMessages();
    // Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings['noLanguageResource']);
        return;
    };
    // Translations can be submitted:
    showTranslations();
    // If fileUpload is possible for currently chosen languages, show text accordingly:
    setTextForSource();
    // When instantTranslation is not active; hide former translations and wait.
    if (!instantTranslationIsActive) {
        hideTranslations();
        return;
    }
    // Start translation:
    startTimerForInstantTranslation();
}

/**
 * Check if any engines are available for the current language-combination.
 * @returns {Boolean}
 */
function hasEnginesForLanguageCombination() {
	var returnValue=isSourceTargetAvailable($("#sourceLocale").val(),$("#targetLocale").val());
	returnValue ? $("#switchSourceTarget").removeAttr("disabled") : $("#switchSourceTarget").attr("disabled", true);
	returnValue ? $("#switchSourceTarget").removeClass( "switchSourceTargetDisabled" ) : $("#switchSourceTarget").addClass( "switchSourceTargetDisabled" );
    return returnValue;
}

/***
 * Check if for given source target combo there is available language resource
 * @param sourceRfc
 * @param targetRfc
 * @returns {Boolean}
 */
function isSourceTargetAvailable(sourceRfc,targetRfc){
    var targetLocalesAvailable = getLocalesAccordingToReference ('accordingToSourceLocale', sourceRfc);
    return targetLocalesAvailable.indexOf(targetRfc) !== -1;
}

/**
 * What locales are available for translation for the given locale?
 * @param {String} accordingTo ('accordingToSourceLocale'|'accordingToTargetLocale')
 * @param {String} selectedLocale
 * @returns {Array} 
 */
function getLocalesAccordingToReference (accordingTo, selectedLocale) {
    var localesAvailable = [],
        languageResourceId,
        languageResourceToCheck,
        languageResourceToCheckAllSources,
        languageResourceToCheckAllTargets,
        languageResourceLocaleSet,
        languageResourceAllLocalesToAdd;
    for (languageResourceId in Editor.data.apps.instanttranslate.allLanguageResources) {
        if (Editor.data.apps.instanttranslate.allLanguageResources.hasOwnProperty(languageResourceId)) {
            languageResourceToCheck = Editor.data.apps.instanttranslate.allLanguageResources[languageResourceId];
            languageResourceToCheckAllSources = languageResourceToCheck.source;
            languageResourceToCheckAllTargets = languageResourceToCheck.target;
            languageResourceLocaleSet = (accordingTo === 'accordingToSourceLocale') ? languageResourceToCheckAllSources : languageResourceToCheckAllTargets;
            if (isAvailableLocale(selectedLocale, languageResourceLocaleSet)) {
                languageResourceAllLocalesToAdd = (accordingTo === 'accordingToSourceLocale') ? languageResourceToCheckAllTargets : languageResourceToCheckAllSources;
                $.each(languageResourceAllLocalesToAdd, function(index, languageResourceLocaleToAdd) {
                    // TermCollections can translate in all combinations of their source- and target-languages,
                    // but not from the source to the SAME target-language.
                    if (languageResourceLocaleToAdd != selectedLocale
                        && $.inArray(languageResourceLocaleToAdd, localesAvailable) === -1) {
                            localesAvailable.push(languageResourceLocaleToAdd); 
                    }
                });
            }
        }
    }
    localesAvailable.sort();
    return localesAvailable;
}

/* --------------- start translation instantly or manually: events  --------- */

//start instant (sic!) translation automatically
$('#sourceText').bind('keyup', function() {
    // Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings['noLanguageResource']);
        return;
    };
    if($('#sourceText').val().length > 0 && $('#sourceText').val() === latestTextToTranslate) {
        return;
    }
    startTimerForInstantTranslation();
});

//instantly after uploading a file: grab the files and set them to uploadedFiles
//(if instantTranslation is on, the translation will start, too)
$('#sourceFile').on('change', grabUploadedFiles);

//start translation manually via button
$('.click-starts-translation').click(function(event){
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible")) {
        startFileTranslation();
    } else {
        startTranslation();
    }
    return false;
});

/* --------------- prepare file-translations  ------------------------------- */

function grabUploadedFiles(event){
    uploadedFiles = event.target.files; 
    if (instantTranslationIsActive) {
        startFileTranslation();
    }
}

function startFileTranslation(){
    var fileName,
        fileType,
        fileTypesAllowed = getAllowedFileTypes(),
        fileTypesErrorList = [];
    if ($('#sourceFile').val() == "") {
        showSourceError(Editor.data.languageresource.translatedStrings['uploadFileNotFound']);
        return;
    }
    clearAllErrorMessages();
    $.each(uploadedFiles, function(key, value){
        fileName = value.name;
        fileType = fileName.substr(fileName.lastIndexOf('.')+1,fileName.length);
        if ($.inArray(fileType, fileTypesAllowed) === -1) {
            fileTypesErrorList.push(fileType);
        }
    });
    if (fileTypesErrorList.length > 0) {
        showSourceError(Editor.data.languageresource.translatedStrings['notAllowed'] + ': ' + fileTypesErrorList.join());
        return;
    }
    startTranslation();
}

/* --------------- prepare, start and terminate translations  --------------- */
function startTimerForInstantTranslation() {
    terminateTranslation();
    editIdleTimer = setTimeout(function() {
        startTranslation(); // TODO: this can start a filetranslation without calling startFileTranslation()
    }, 200);
}
function startTranslation() {
    var textToTranslate,
        translationInProgressID;
    // Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings['noLanguageResource']);
        return;
    };
    // translate a file?
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible")) {
        // TODO: we can get here via startTimerForInstantTranslation 
        // (= without calling startFileTranslation() first),
        // so we have to run the same validation. Bad!
        if ($('#sourceFile').val() == "") {
            showSourceError(Editor.data.languageresource.translatedStrings['uploadFileNotFound']);
            return;
        }
        if (uploadedFiles != undefined) {
            startLoadingState();
            requestFileTranslate();
        }
        return;
    }
    // otherwise: translate Text
    if ($('#sourceText').val().length === 0) {
        // no text given
        $("#sourceIsText").addClass('source-text-error');
        $('#translations').hide();
        return;
    }
    if ($('#sourceText').val() === latestTextToTranslate) {
        return;
    }
    terminateTranslation();
    textToTranslate = $('#sourceText').val();
    translationInProgressID = new Date().getTime();
    // store both for comparison on other places
    latestTextToTranslate = textToTranslate;
    latestTranslationInProgressID = translationInProgressID;
    // start translation
    translateText(textToTranslate,translationInProgressID);
}

function terminateTranslation() {
    clearTimeout(editIdleTimer);
    editIdleTimer = null;
    latestTranslationInProgressID = false;
}

/* --------------- translation ---------------------------------------------- */
/***
 * Send a request for translation, this will return translation result for each associated language resource
 * INFO: in the current implementation only result from sdlcloud language will be returned
 * @returns
 */
function translateText(textToTranslate,translationInProgressID){
    startLoadingSign();
    var translateRequest = $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showLanguageResourceSelectorError('serverErrorMsg500');
            }
        },
        url: Editor.data.restpath+"instanttranslateapi/translate",
        dataType: "json",
        type: "POST",
        data: {
            'source':$("#sourceLocale").val(),
            'target':$("#targetLocale").val(),
            'text':textToTranslate
        },
        success: function(result){
            if (translationInProgressID != latestTranslationInProgressID) {
                return;
            }
            if (result.errors !== undefined && result.errors != '') {
                showTargetError(result.errors);
            } else {
                clearAllErrorMessages();
                translateTextResponse = result.rows;
                fillTranslation();
            }
            stopLoadingSign();
        },
        fail: function(xhr, textStatus, errorThrown){
            debugger;
        }
    });
}

function fillTranslation() {
    var translationHtml = '',
        resultHtml = '',
        fuzzyMatch,
        infoText,
        term,
        termStatus,
        metaData,
        resultData,
        languageRfc,
        alternativeTranslations;
    
    //reset the additional translations
    additionalTranslationsHtmlContainer='';
    $.each(translateTextResponse, function(serviceName, resource){
        resultHtml = '';
        $.each(resource, function(resourceName, allResults){
            $.each(allResults, function(key, result){
                if (result['target'] != '') {
                    fuzzyMatch = {};
                    if (result['sourceDiff'] != undefined) {
                        fuzzyMatch = {'matchRate': result['matchrate'], 
                                      'sourceDiff': result['sourceDiff']}
                    }
                    infoText = [];
                    term = '';
                    languageRfc=null;
                    termStatus = '';
                    processStatusAttribute = '';
                    processStatusAttributeValue = '';
                    alternativeTranslations=[];
                    if (result['metaData'] != undefined) {
                        metaData = result['metaData'];
                        if(metaData['definitions'] != undefined && metaData['definitions'].length>0) {
                        	//add all available definitions as separate row
                        	for(var i=0;i<metaData['definitions'].length;i++){
                        		infoText.push(metaData['definitions'][i]);
                        	}
                        }
                        if(metaData['term'] != undefined) {
                            term = metaData['term'];
                        }
                        if(metaData['languageRfc'] != undefined) {
                        	languageRfc = metaData['languageRfc'];
                        }
                        if(metaData['status'] != undefined) {
                            termStatus = metaData['status'];
                        }
                        if(metaData['processStatusAttribute'] != undefined) {
                        	processStatusAttribute = metaData['processStatusAttribute'];
                        }
                        if(metaData['processStatusAttributeValue'] != undefined) {
                        	processStatusAttributeValue = metaData['processStatusAttributeValue'];
                        }
                        if(metaData['alternativeTranslations'] != undefined) {
                        	alternativeTranslations = metaData['alternativeTranslations'];
                        }
                    }
                    resultData = {'languageResourceId': result['languageResourceid'],
                                  'languageResourceType': result['languageResourceType'],
                                  'fuzzyMatch': fuzzyMatch,
                                  'infoText': infoText.join('<br/>'),
                                  'resourceName': resourceName,
                                  'serviceName': serviceName,
                                  'term': term,
                                  'termStatus': termStatus,
                                  'translationText': result['target'],
                    			  'processStatusAttribute':processStatusAttribute,
                    			  'processStatusAttributeValue':processStatusAttributeValue,
                    			  'languageRfc':languageRfc,
                    			  'alternativeTranslations':alternativeTranslations
                                  };
                    resultHtml += renderTranslationContainer(resultData);
                }
            });
        });
        if (resultHtml != '') {
            translationHtml += resultHtml;
        }
    });
    if (translationHtml == '') {
        showTargetError(Editor.data.languageresource.translatedStrings['noResultsFound']);
    }
    //when there is aditional translations, display them at the end
    if(additionalTranslationsHtmlContainer!=''){
    	translationHtml+=additionalTranslationsHtmlContainer;
    }
    $('#translations').html(translationHtml);
    showTranslations();

    // open TermPortal for proposing new term?
    checkTermPortalIntegration();
}

function renderTranslationContainer(resultData) {
    var translationsContainer = '';
    
    translationsContainer += '<h4>';
    translationsContainer += resultData.resourceName + ' (' + resultData.serviceName + ')';
    translationsContainer += '<span class="loadingSpinnerIndicator"><img src="'+Editor.data.publicModulePath+'images/loading-spinner.gif"/></span>';
    translationsContainer += '</h4>';
    
    if (resultData.fuzzyMatch.sourceDiff != undefined) {
        var fuzzyMatchTranslatedString = Editor.data.languageresource.translatedStrings['attentionFuzzyMatch'].replace("{0}", resultData.fuzzyMatch.matchRate);
        translationsContainer += '<div class="translation-sourcediff" title="'+Editor.data.languageresource.translatedStrings['differenceIsHighlighted']+'">'+fuzzyMatchTranslatedString+': ';
        translationsContainer += '<span class="translation-sourcediff-content">' + resultData.fuzzyMatch.sourceDiff + '</span>';
        translationsContainer += '</div>';
    }
    
    translationsContainer += '<div class="copyable">';
    translationsContainer += '<div class="translation-result" id="'+resultData.languageResourceId+'" data-languageresource-type="'+resultData.languageResourceType+'">'+resultData.translationText+'</div>';
    translationsContainer += '<span class="copyable-copy" title="'+Editor.data.languageresource.translatedStrings['copy']+'"><span class="ui-icon ui-icon-copy"></span></span>';
    
    if(resultData.processStatusAttributeValue && resultData.processStatusAttributeValue === 'finalized') {
        translationsContainer += '<span class="process-status-attribute"><img src="' + Editor.data.publicModulePath + 'images/tick.png" alt="finalized" title="finalized"></span>';
    }
    
    if (resultData.term != '' && Editor.data.isUserTermportalAllowed) {
    	//check if for the current term the rfc language value is set, if yes set data property so the language is used in the term portal
    	var languageRfc=resultData.languageRfc ? ('data-languageRfc="'+resultData.languageRfc+'"') : '';
        translationsContainer += '<span class="term-info" id="'+resultData.term+'" '+languageRfc+' title="'+Editor.data.languageresource.translatedStrings['openInTermPortal']+'"><span class="ui-icon ui-icon-info"></span></span>';
    }
    
    if (resultData.termStatus != '') {
        translationsContainer += '<span class="term-status">'+renderTermStatusIcon(resultData.termStatus)+'</span>';
    }
    
    if (resultData.alternativeTranslations != undefined) {
    	var at=resultData.alternativeTranslations,
    		highestConfidenceTranslation='',
    		atHtmlTableResultPosTag = '',
    		atHtmlTableStart = '',
    		atHtmlTableEnd = '',
    		atHtmlTable='',
    		atHtmlBt=[];
    	if (at.length > 0) {
    		atHtmlTableStart = '<table class="translationsForLabel">';
    		atHtmlTableEnd = '</table>';
    	}
    	$.each(at, function(key, result){
    		if (atHtmlTableResultPosTag == '') {
    			// This assumes that result['posTag'] is the same for all results!
    			atHtmlTableResultPosTag = '<tr><td colspan="3"><b>'+result['posTag']+'</b></td></tr>';
    		}
    		atHtmlTable += '<tr>';
    		atHtmlTable += '<td><progress value="'+result['confidence']+'" max="1"></progress></td>';
    		atHtmlTable += '<td><b>'+result['displayTarget']+':</b></td>';
    		atHtmlBt=[];
        	$.each(result.backTranslations, function(keyBt, resultBt){
        		if(highestConfidenceTranslation==''){
        			highestConfidenceTranslation='<h5 class="translationsForLabel">'+Editor.data.languageresource.translatedStrings['translationsForLabel']+'<span class="displayTarget"> '+result['displayTarget']+'</span></h5>';
        		}
        		atHtmlBt.push(resultBt.displayText);
        	});
        	atHtmlTable += '<td>'+atHtmlBt.join(', ')+'</td>';
        	atHtmlTable += '</tr>';
    	});
    }
    
    translationsContainer += '</div>';
    
    //collect the additional translations, thay are rendered at the end of the result list
    additionalTranslationsHtmlContainer +=highestConfidenceTranslation;
    additionalTranslationsHtmlContainer +=atHtmlTableStart;
    additionalTranslationsHtmlContainer +=atHtmlTableResultPosTag;
    additionalTranslationsHtmlContainer +=atHtmlTable;
    additionalTranslationsHtmlContainer +=atHtmlTableEnd;
	
    if (resultData.infoText != '') {
        translationsContainer += '<div class="translation-infotext">'+resultData.infoText+'</div>';
    }
    
    translationsContainer += '<div id="translationError'+resultData.languageResourceId+'" class="instant-translation-error ui-state-error ui-corner-all"></div>';
    return translationsContainer;
}

/***
 * Render the image-html for TermStatus.
 * @param string termStatus
 * @returns string
 */
function renderTermStatusIcon(termStatus){
    var termStatusHtml = '', 
        status = 'unknown', 
        map = Editor.data.termStatusMap,
        labels = Editor.data.termStatusLabel,
        label;
    if(map[termStatus]) {
        status = map[termStatus];
        label = labels[status]+' ('+termStatus+')';
        termStatusHtml += '<img src="' + Editor.data.publicModulePath + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'"> ';
    }
    return termStatusHtml;
}

/***
 * Request a file translate for the curent uploaded file
 * @returns
 */
function requestFileTranslate(){

    // Create a formdata object and add the files
    var data = new FormData(),
        ext=getFileExtension(),
        languageCombination = $("#sourceLocale").val()+','+$("#targetLocale").val(),
        dataForLanguageCombination = fileTypesAllowedAndAvailable[languageCombination];
    
    $.each(uploadedFiles, function(key, value){
        data.append(key, value);
    });

    data.append('domainCode', dataForLanguageCombination['domainCode']); // TODO
    data.append('source', $("#sourceLocale").val());
    data.append('target', $("#targetLocale").val());
    
    //when no extension in the file is found, use default file extension
    data.append('fileExtension', ext != "" ? ext : DEFAULT_FILE_EXT);
    
    $.ajax({
        url:Editor.data.restpath+"instanttranslateapi/file",
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false, // Don't process the files
        contentType: false, // Set content type to false as jQuery will tell the server its a query string request
        success: function(data, textStatus, jqXHR){
            if(typeof data.error === 'undefined'){
                getDownloadUrl(data.fileId);
            }else{
                // Handle errors here
                showSourceError('ERRORS: ' + data.error);
                stopLoadingState();
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            // Handle errors here
            showSourceError('ERRORS: ' + textStatus);
            stopLoadingState();
        }
    });
}

/***
 * Request a download url for given sdl file id
 * @param fileId
 * @returns
 */
function getDownloadUrl(fileId){
    $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showLanguageResourceSelectorError('serverErrorMsg500');
                }
        },
        url: Editor.data.restpath+"instanttranslateapi/url",
        dataType: "json",
        data: {
            'fileId':fileId
        },
        success: function(result){
            //when no url from server is provided, the file is not ready yet
            if(result.downloadUrl==""){
                setTimeout(function(){ getDownloadUrl(fileId); }, 1000);
                return;
            }
            downloadFile(result.downloadUrl);
        }
    })
}

/***
 * Download the file from the sdl url.
 * @param url
 * @returns
 */
function downloadFile(url){
    var hasFileExt=getFileExtension()!="",
        fullFileName=hasFileExt ? getFileName():(getFileName()+"."+getFileExtension()),
        newTab = window.open("about:blank", "translatedFile"),
        newTabHref = Editor.data.restpath+"instanttranslateapi/download?fileName="+fullFileName+"&url="+url;
    if(newTab == null) { // window.open does not work in Chrome
        window.location.href = newTabHref;
    } else {
        newTab.location = newTabHref;
    }
    stopLoadingState();
}


/***
 * Get the file name of the uploaded file
 * @returns
 */
function getFileName(){
    //return without fakepath
    return $('#sourceFile').val().replace(/.*(\/|\\)/, '');;
}

/***
 * Get file extension of the uploaded file
 * @returns
 */
function getFileExtension(){
    var fileName=getFileName(),
        found = fileName.lastIndexOf('.') + 1,
        ext = (found > 0 ? fileName.substr(found) : "");
    return ext;
}

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
    instantTranslationIsActive = !instantTranslationIsActive;
    clearAllErrorMessages();
});

/* --------------- clear source --------------------------------------------- */
$(".clearable").each(function() {
    // idea from https://stackoverflow.com/a/6258628
    var elInp = $(this).find("#sourceText"),
        elCle = $(this).find(".clearable-clear");
    elInp.on("input", function(){
        elCle.toggle(!!this.value);
        latestTextToTranslate = '';
        elInp.focus();
    });
    elCle.on("touchstart click", function(e) {
        e.preventDefault();
        elInp.val("").trigger("input");
    });
});

/* --------------- count characters ----------------------------------------- */
$('#sourceText').on("input focus", function(){
    var sourceTextLength = $(this).val().length;
    $('#countedCharacters').html(sourceTextLength+'/'+characterLimit);
    if (sourceTextLength === 0) {
        $(".clearable-clear").hide();
        $('#translations').html('');
        $('#translations').hide();
    } else {
        $("#sourceIsText").removeClass('source-text-error');
    }
    if (sourceTextLength >= characterLimit) {
        $("#sourceText").addClass('source-text-error');
        $("#countedCharacters").addClass('source-text-error');
    } else {
        $("#sourceText").removeClass('source-text-error');
        $("#countedCharacters").removeClass('source-text-error');
    }
});

/* --------------- copy translation ----------------------------------------- */
$('#translations').on('touchstart click','.copyable-copy',function(){
    var textToCopy = $(this).closest('.copyable').find('.translation-result').text();
    // https://stackoverflow.com/a/33928558
    if (window.clipboardData && window.clipboardData.setData) {
        // IE specific code path to prevent textarea being shown while dialog is visible.
        return clipboardData.setData("Text", textToCopy); 
    } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var textarea = document.createElement("textarea");
        textarea.textContent = textToCopy;
        textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
        document.body.appendChild(textarea);
        textarea.select();
        try {
            return document.execCommand("copy");  // Security exception may be thrown by some browsers.
        } catch (ex) {
            console.warn("Copy to clipboard failed.", ex);
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
});

/* --------------- open TermPortal ------------------------------------------ */

/**
 * If the user has the right to propose terms in the TermPortal,
 * we initiate drawing the links here.
 */
function checkTermPortalIntegration() {
    var html = '',
        searchTerms = [],
        nonExistingTerms = [];
    // check user rights
    if(!Editor.data.app.user.isUserTermproposer) {
        return;
    }
    // check number of results
    if($('#translations .translation-result').length > 3) {
        //return;
    }
    // check if the terms already exist in any TermCollection
    $('#translations [data-languageresource-type="mt"]').each(function() {
        searchTerms.push({'text' : $(this).text(), 'id' : $(this).attr('id')});
    });
    if(searchTerms.length == 0) {
        return;
    }
    $.ajax({
        url: Editor.data.restpath+"termcollection/searchtermexists",
        dataType: "json",
        type: "POST",
        data: {
            'searchTerms':JSON.stringify(searchTerms),
        },
        success: function(result){
            nonExistingTerms = result.rows;
            nonExistingTerms.forEach(function(termToPropose) {
                // for all translation-results that don't exist already exists as term: draw propose-Button
                drawTermPortalIntegration(termToPropose);
              });
        }
    });
}

/**
 * Draw button for proposing term in the TermPortal.
 * @params {Object}
 */
function drawTermPortalIntegration(termToPropose) {
    var html = '<span class="term-proposal" title="'+Editor.data.languageresource.translatedStrings['termProposalIconTooltip']+'" data-id="'+termToPropose.id+'" data-term="'+termToPropose.text+'"><span class="ui-icon ui-icon-circle-plus"></span></span>';
    $('#'+termToPropose.id+'.translation-result').next('.copyable-copy').append(html);
}

/**
 * events
 */
$(document).on('click', '.term-proposal' , function() {
    var text = $('#sourceText').val(),
        lang = $("#sourceLocale").val(),
        textProposal = $(this).attr('data-term'),
        langProposal = $("#targetLocale").val(),
        isTermProposalFromInstantTranslate = 'true';
        params = "text="+text+"&lang="+lang+"&textProposal="+textProposal+"&langProposal="+langProposal+"&isTermProposalFromInstantTranslate="+isTermProposalFromInstantTranslate;
    openTermPortal(params);
});

$('#translations').on('touchstart click','.term-info',function(){
    var text = $(this).attr('id'),
        lang = $(this).attr("data-languageRfc"),
        params="text="+text+"&lang="+lang;
    openTermPortal(params);
});

$('#termPortalButton').on('touchstart click',function(){
    openTermPortal();
});

/**
 * Open TermPortal with given params (optional).
 * @param {String} params
 */
function openTermPortal (params) {
    console.log('openTermPortal ' + params);
    var url = Editor.data.restpath+"termportal";
    if (params) {
        window.parent.loadIframe('termportal',url,params);
    } else {
        window.parent.loadIframe('termportal',url);
    }
}

/* --------------- show/hide: helpers --------------------------------------- */
function showSource() {
    $('#sourceContent').show();
    showInstantTranslationOffOn();
    if (chosenSourceIsText || getAllowedFileTypes().length === 0) {
        $('.show-if-source-is-text').show();
        $('.show-if-source-is-file').hide();
        $('#translations').show();
        $("#sourceIsText").removeClass('source-text-error');
        $('#sourceText').focus();
    } else {
        $('.show-if-source-is-text').hide();
        $('.show-if-source-is-file').show();
        $('#translations').hide();
    }
}
function showTranslations() {
    if ($('#sourceText').val().length === 0) {
        $('#translations').html('');
    }
    $('#translations').show();
    showInstantTranslationOffOn();
}
function hideTranslations() {
    $('#translations').hide();
}
function showInstantTranslationOffOn() {
    if (instantTranslationIsActive) {
        $('#instantTranslationIsOn').show();
        $('#instantTranslationIsOff').hide();
    } else {
        $('#instantTranslationIsOn').hide();
        $('#instantTranslationIsOff').show();
    }
}
/* --------------- show/hide: errors --------------------------------------- */
function showLanguageResourceSelectorError(errorMode) {
    $('#languageResourceSelectorError').html(Editor.data.languageresource.translatedStrings[errorMode]).show();
}
function showTargetError(errorText) {
    $('#targetError').html(errorText).show();
}
function showSourceError(errorText) {
    $('#sourceError').html(errorText).show();
}
function clearAllErrorMessages() {
    $('.instant-translation-error').html('').hide();
    $("#sourceIsText").removeClass('source-text-error');
    $('#sourceError').html('').hide();
    $("#targetError").html('').hide();
    // ALWAYS: Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings['noLanguageResource']);
        return;
    };
}
/* --------------- show/hide: loading spinner ------------------------------- */
// 'sign' = show indicator in addition to content (currently used for text-translations)
// 'state' = shown layer upon content (currently used for file-translations)
function startLoadingSign() {
    if ($('#translations').is(":visible")) {
        $('#translations').find('.loadingSpinnerIndicator').show();
    } else {
        $('#target').children('.loadingSpinnerIndicator').show();
    }
}
function stopLoadingSign() {
    $('.loadingSpinnerIndicator').hide();
}
function startLoadingState() {
    $('.loadingSpinnerLayer').show();
}
function stopLoadingState() {
    $('.loadingSpinnerLayer').hide();
}
