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

if (window.parent.location.hash == '#itranslate') $('#containerHeader').hide();
var editIdleTimer = null,
    NOT_AVAILABLE_CLS = 'notavailable', // css if a (source-/target-)locale is not available in combination with the other (target-/source-)locale that is set
    uploadedFiles,//Variable to store uploaded files
    translateTextResponse = '',
    latestTranslationInProgressID = false,
    latestTextToTranslate = '',
    instantTranslationIsActive = true,
    chosenSourceIsText = true,
    fileTypesAllowed = [],
    fileUploadLanguageCombinationsAvailable = [],
    additionalTranslationsHtmlContainer='';

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
 * Which fileTypes are allowed?
 * @returns array
 */
function setAllowedFileTypes() {
    fileTypesAllowed = Editor.data.languageresource.fileExtension;
}
/**
 * Is the given fileType allowed?
 * @param string fileTypeToCheck
 * @returns array
 */
function isAllowedFileType(fileTypeToCheck) {
    return $.inArray(fileTypeToCheck, fileTypesAllowed) !== -1;
}

/**
 * For which language-combinations is file-upload available?
 * (Current implementation: mt-resources)
 */
function setfileUploadLanguageCombinationsAvailable() {
    var languageResourceId,
        languageResourceToCheck,
        languageResourceToCheckAllSources,
        languageResourceToCheckAllTargets,
        langComb;
    for (languageResourceId in Editor.data.apps.instanttranslate.allLanguageResources) {
        if (Editor.data.apps.instanttranslate.allLanguageResources.hasOwnProperty(languageResourceId)) {
            languageResourceToCheck = Editor.data.apps.instanttranslate.allLanguageResources[languageResourceId];
            // If the LanguageResources is an MT resource, we store all the language-combinations it can handle.
            if (languageResourceToCheck.fileUpload) {
                languageResourceToCheckAllSources = languageResourceToCheck.source;
                $.each(languageResourceToCheckAllSources, function(indexS) {
                    languageResourceToCheckAllTargets = languageResourceToCheck.target;
                    $.each(languageResourceToCheckAllTargets, function(indexT) {
                        langComb = languageResourceToCheckAllSources[indexS] + '|' + languageResourceToCheckAllTargets[indexT];
                        if ( languageResourceToCheckAllSources[indexS] !== languageResourceToCheckAllTargets[indexT]
                            && $.inArray(langComb, fileUploadLanguageCombinationsAvailable) === -1) {
                            fileUploadLanguageCombinationsAvailable.push(langComb); 
                                
                        }
                    });
                });
            }
        }
    }
}
/**
 * Is fileUpload available for the current language-combination?
 * @returns boolean
 */
function isFileUploadAvailable() {
    var langComb = $('#sourceLocale').val() + '|' + $('#targetLocale').val();
    return $.inArray(langComb, fileUploadLanguageCombinationsAvailable) !== -1 && Editor.data.apps.instanttranslate.fileTranslation;
}
/***
 * If files are allowed for the current language-combination, show text accordingly.
 */
function setTextForSource() {
    var textForSourceIsText = Editor.data.languageresource.translatedStrings['enterText'],
        textForSourceIsFile = '';
    if (!isFileUploadAvailable()) {
        // No file-upload is possible
        chosenSourceIsText = true;
        showSource();
    } else {
        // When source is chosen to text
        textForSourceIsText += ' <span class="change-source-type">';
        textForSourceIsText += Editor.data.languageresource.translatedStrings['orTranslateFile'];
        textForSourceIsText += '</span>';
        // When source is chosen to file
        textForSourceIsFile = Editor.data.languageresource.translatedStrings['uploadFile'];
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
    });
}

/**
 * Every change in the language-selection starts a check if any lnguageResources
 * are available. If yes and text is already entered, the translation starts.
 */
function checkInstantTranslation() {
    // When the language-combination changes, former translations are not valid any longer (= the text hasn't been translated already):
    latestTextToTranslate = '';
    $('#translations').html('');
    // Neither are former error-messages valid any longer:
    clearAllErrorMessages();
    // If fileUpload is possible for currently chosen languages, show text accordingly:
    setTextForSource();
    // Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings['noLanguageResource']);
        return;
    }
    if(getInputTextValueTrim().length > 0 && getInputTextValueTrim() === latestTextToTranslate) {
        return;
    }
    // Translations can be submitted:
    showTranslations();
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
	var returnValue=isSourceTargetAvailable($("#sourceLocale").val(),$("#targetLocale").val()),
		isDisableButton=!isSourceTargetAvailable($("#targetLocale").val(),$("#sourceLocale").val());//switch source and target so the other way arround is checked
	//the button is disabled when for the target as source there is no source as target
	$("#switchSourceTarget").prop("disabled", isDisableButton);
	isDisableButton ?$("#switchSourceTarget").addClass( "switchSourceTargetDisabled" ) :  $("#switchSourceTarget").removeClass( "switchSourceTargetDisabled" );
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

/***
 * Set the first first available target locale for the given source locale
 * @returns
 */
function setTargetFirstAvailable(sourceRfc){
	var targetLocalesAvailable = getLocalesAccordingToReference ('accordingToSourceLocale', sourceRfc);
	if(targetLocalesAvailable.length<1){
		return;
	}
	$("#targetLocale").val(targetLocalesAvailable[0]);
	$("#targetLocale").selectmenu("refresh");
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
        toCheck,
        langSet,
        useSub = Editor.data.instanttranslate.showSublanguages,
        allToAdd;

    for (languageResourceId in Editor.data.apps.instanttranslate.allLanguageResources) {
        if (! Editor.data.apps.instanttranslate.allLanguageResources.hasOwnProperty(languageResourceId)) {
            continue;
        }
        toCheck = Editor.data.apps.instanttranslate.allLanguageResources[languageResourceId];
        langSet = (accordingTo === 'accordingToSourceLocale') ? toCheck.source : toCheck.target;
        if (!isAvailableLocale(selectedLocale, langSet)) {
            continue;
        }
        allToAdd = (accordingTo === 'accordingToSourceLocale') ? toCheck.target : toCheck.source;
        $.each(allToAdd, function(index, toAdd) {
            // TermCollections can translate in all combinations of their source- and target-languages,
            // but not from the source to the SAME target-language.
            var isSame = toAdd == selectedLocale,
                //prevent duplicates
                notAdded = ($.inArray(toAdd, localesAvailable) === -1);
            
            //respect showSublanguages config too
            if (!isSame && notAdded && (useSub || !/-/.test(toAdd))) {
                localesAvailable.push(toAdd);
            }
        });
    }
    localesAvailable.sort();
    return localesAvailable;
}

/* --------------- start translation instantly or manually: events  --------- */

//start instant (sic!) translation automatically
$('#sourceText').bind('keyup', function() {
    checkInstantTranslation();
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
        fileTypesErrorList = [];
    if ($('#sourceFile').val() == "") {
        showSourceError(Editor.data.languageresource.translatedStrings['uploadFileNotFound']);
        return;
    }
    clearAllErrorMessages();
    $.each(uploadedFiles, function(key, value){
        fileName = value.name;
        fileType = fileName.substr(fileName.lastIndexOf('.')+1,fileName.length);
        if (!isAllowedFileType(fileType)) {
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
    }
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
    if (getInputTextValueTrim().length === 0) {
        // no text given
        $('#translations').hide();
        return;
    }
    if (getInputTextValueTrim() === latestTextToTranslate) {
        return;
    }
    terminateTranslation();
    textToTranslate = getInputTextValueTrim();
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
        error: function(jqXHR, textStatus) {
            showSourceError('ERRORS: ' + textStatus);
            stopLoadingSign();
        },
        fail: function(xhr, textStatus, errorThrown){
            debugger;
        }
    });
}

function fillTranslation() {
    var translationHtml = '';
    
    //reset the additional translations
    additionalTranslationsHtmlContainer='';
    
    // 
    if (translateTextResponse.hasOwnProperty('translationForSegmentedText')) {
        translationHtml = renderSingleMatchAndResources(translateTextResponse);
    } else {
        translationHtml = renderMatchesByResource(translateTextResponse);
    }
    
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

function renderSingleMatchAndResources (translateTextResponse) {
    var translationHtml = '',
        resultHtml = '',
        resultData,
        translationText = translateTextResponse.translationForSegmentedText,
        usedResources = translateTextResponse.usedResources;
    resultData = {'languageResourceHeadline': Editor.data.languageresource.translatedStrings['translationBasedOn'] + ' ' + usedResources,
                  'languageResourceId': '',
                  'languageResourceType': '',
                  'fuzzyMatch': '',
                  'infoText': '',
                  'term': '',
                  'termStatus': '',
                  'translationText': translationText,
                  'processStatusAttribute':'',
                  'processStatusAttributeValue':'',
                  'languageRfc':'',
                  'alternativeTranslations':'',
                  'singleResultBestMatchrateTooltip':Editor.data.languageresource.translatedStrings['singleResultBestMatchrateTooltip']
                  };
    resultHtml += renderTranslationContainer(resultData);
    return resultHtml;
}

function renderMatchesByResource (translateTextResponse) {
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
                    resultData = {'languageResourceHeadline' : resourceName + ' (' + serviceName + ')',
                                  'languageResourceId': result['languageResourceid'],
                                  'languageResourceType': result['languageResourceType'],
                                  'fuzzyMatch': fuzzyMatch,
                                  'infoText': infoText.join('<br/>'),
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
    return translationHtml;
}

function renderTranslationContainer(resultData) {
    var translationsContainer = '';
    
    translationsContainer += '<h4>';
    //if this tooltip is set, there is onyl one result -> result with best matchrate 
    if(resultData.singleResultBestMatchrateTooltip){
        translationsContainer += '<span class="singleResultBestMatchrateInfoIcon" title="'+resultData.singleResultBestMatchrateTooltip+'"></span>';
    }
    translationsContainer += resultData.languageResourceHeadline;
    translationsContainer += '<span class="loadingSpinnerIndicator"><img src="'+Editor.data.publicModulePath+'images/loading-spinner.gif"/></span>';
    translationsContainer += '</h4>';
    
    if (resultData.fuzzyMatch.sourceDiff != undefined) {
        var fuzzyMatchTranslatedString = Editor.data.languageresource.translatedStrings['attentionFuzzyMatch'].replace("{0}", resultData.fuzzyMatch.matchRate);
        translationsContainer += '<div class="translation-sourcediff" title="'+Editor.data.languageresource.translatedStrings['differenceIsHighlighted']+'">'+fuzzyMatchTranslatedString+': ';
        translationsContainer += '<span class="translation-sourcediff-content">' + resultData.fuzzyMatch.sourceDiff + '</span>';
        translationsContainer += '</div>';
    }
    
    translationsContainer += '<div class="copyable">';
    translationsContainer += '<div class="translation-result" id="'+resultData.languageResourceId+'" data-languageresource-type="'+resultData.languageResourceType+'">'+sanitizeTranslatedText(resultData.translationText)+'</div>';
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

/* --------------- file translation ----------------------------------------- */

/***
 * file translation
 * Create a task and start the import with the pretranslation.
 */
function requestFileTranslate(){
    // Create a formdata object and add the files
    var data = new FormData();
    
    if(uploadedFiles && uploadedFiles.length>0){
        //only single file can be selected
        data.append('file', uploadedFiles[0]);
    }
    
    data.append('source', $('#sourceLocale').val());
    data.append('target', $('#targetLocale').val());

    $.ajax({
        url:Editor.data.restpath+'instanttranslateapi/filepretranslate',
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false, // Don't process the files
        contentType: false, // Set content type to false as jQuery will tell the server its a query string request
        success: function(result){
            if(typeof result.error === 'undefined' && result.taskId !== ''){
                getDownloads();
            }else{
                // Handle errors here
                var error = (result.taskId === '') ? Editor.data.languageresource.translatedStrings['error'] : result.error;
                showSourceError('ERRORS: ' + error);
                $('#sourceFile').val('');
                stopLoadingState();
            }
        },
        error: function(jqXHR, textStatus)
        {
            // Handle errors here
            showSourceError('ERRORS: ' + textStatus);
            $('#sourceFile').val('');
            stopLoadingState();
        }
    });
}

/***
 * file translation
 * Step 3: Show a list of all pretranslations that are currently available
 * @param int taskGuid
 * @returns
 */
function getDownloads(){
    $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showLanguageResourceSelectorError('serverErrorMsg500');
                }
        },
        url: Editor.data.restpath+'instanttranslateapi/filelist',
        dataType: 'json',
        success: function(result){
            clearAllErrorMessages();
            $('#sourceFile').val('');
            showDownloads(result.allPretranslatedFiles, result.dateAsOf);
            stopLoadingState();
        },
        error: function(jqXHR, textStatus)
        {
            // Handle errors here
            showSourceError('ERRORS: ' + textStatus);
            $('#sourceFile').val('');
            $('#pretranslatedfiles').html('');
            stopLoadingState();
        }
    });
}

/***
 * Offer to download the pretranslated files (= currently: export the task).
 * @param array allPretranslatedFiles
 * @param string dateAsOf
 */
function showDownloads(allPretranslatedFiles, dateAsOf){ // array[taskId] = array(taskName, downloadUrl, removeDate)
    var pretranslatedFiles = [],
        html = '',
        htmlFile,
        importProgressUpdate = false;
    $.each(allPretranslatedFiles, function(taskId, taskData) {
        htmlFile = '<li>';
        htmlFile += taskData['taskName'];
        htmlFile += ' ' + taskData['sourceLang'] +' &rarr; ' + taskData['targetLang'];
        htmlFile += ' (' + Editor.data.languageresource.translatedStrings['availableUntil'] + ' ' + taskData['removeDate'] +')<br>';
        switch(taskData['downloadUrl']) {
            case 'isImporting':
                importProgressUpdate = true;
                htmlFile += '<p style="font-size:80%;">' + Editor.data.languageresource.translatedStrings['noDownloadWhileImport'] + '</p>';
                //add import progres html. For each task separate progress component and progress label.
                if(taskData['importProgress']){
                    htmlFile += '<div id="importProgress'+taskId+'" style="width: 150px; position: relative">';
                    htmlFile += '<div id="importProgressLabel'+taskId+'" style="position: absolute;left: 50%;top: 4px;font-weight: bold;text-shadow: 1px 1px 0 #fff;"></div>';
                    htmlFile += '</div>';
                }
                break;
            case 'isErroneous': 
                htmlFile += '<p style="font-size:80%;" class="error">' + Editor.data.languageresource.translatedStrings['noDownloadAfterError'] + '</p>';
                break;
            case 'isNotPretranslated':
                htmlFile += '<p style="font-size:80%;" class="error">' + Editor.data.languageresource.translatedStrings['noDownloadNotTranslated'] + '</p>';
                break;
            default:
                htmlFile += '<a href="' + taskData['downloadUrl'] + '" class="ui-button ui-widget ui-corner-all" target="_blank">Download</a>';
        }
        htmlFile += '<br/>';
        
        pretranslatedFiles.push(htmlFile);
    });
    if (pretranslatedFiles.length > 0) {
        html += '<h2>' + Editor.data.languageresource.translatedStrings['pretranslatedFiles'] + '</h2>';
        html += '<p style="font-size:small;">(' + Editor.data.languageresource.translatedStrings['asOf'] + ' ' + dateAsOf + '):</p>';
        html += '<ul>';
        html += pretranslatedFiles.join(' ');
        html += '</ul>';
    }
    
    $('#pretranslatedfiles').html(html);
    
    //for each pretranslated files task, update the progress bar
    //this will be done only if the task is in status import
    updateImportProgressBar(allPretranslatedFiles);
    
    // if we are still waiting for a file to be ready: try again after 50 seconds
    if (importProgressUpdate) {
        setTimeout(function(){ 
            getDownloads(); 
        }, 5000);
    }
}

/***
 * Update the progress bar for all importing tasks
 * @param taskData
 * @returns
 */
function updateImportProgressBar(taskData){
    if(!taskData || taskData.length < 1) {
        return;
    }
    
    $.each(taskData, function(taskId, taskData) {
        var taskProgresData = taskData['importProgress'];
        
        if(!taskProgresData || taskProgresData.length < 1){
            return true;
        }
        
        //console.log(taskProgresData);
        
        var progressbar =$("#importProgress"+taskId),
            label = $("#importProgressLabel"+taskId);
        
        progressbar.progressbar({
            value:taskProgresData['progress']
        });
        label.text(progressbar.progressbar( "value" ) + "%" );
    });
}

$(document).on('click', '.getdownloads' , function(e) {
    e.stopPropagation();
    getDownloads();
    return false;
});

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
    var searchTerms = [],
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
            'targetLang':$("#targetLocale").val()
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

/***
 * Prepare the transalted text before it is rendered to the page
 * @param translatedText
 * @returns {*}
 */
function sanitizeTranslatedText(translatedText){
    translatedText = translatedText.replace(/(\r\n|\n|\r)/gm, '<br>');
    return translatedText;
}

/**
 * events
 */
$(document).on('click', '.term-proposal' , function() {
    var text = getInputTextValueTrim(),
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
    if (chosenSourceIsText || !isFileUploadAvailable()) {
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
    if (getInputTextValueTrim().length === 0) {
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
	if ($('#translations').is(":visible") && $('#translations').html()!='') {
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

/***
 * This function removes all newlines, spaces (including non-breaking spaces), and tabs from the beginning and end of the sourceText input. 
 * If these whitespace characters occur in the middle of the string, they are preserved.
 * @returns
 */
function getInputTextValueTrim(){
    return $('#sourceText').val().trim();
}
