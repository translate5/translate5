
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

//if we are in a frame, then disable the logout button
if (window.parent.location !== window.location) {
    $('body').addClass('noheader');
    $('#containerHeader').hide();
}

var editIdleTimer = null,
    NOT_AVAILABLE_CLS = 'notavailable', // css if a (source-/target-)locale is not available in combination with the other (target-/source-)locale that is set
    uploadedFiles = [],//Variable to store uploaded files
    translateTextResponse = '',
    latestTranslationInProgressID = false,
    latestTextToTranslate = '',
    instantTranslationIsActive = Editor.data.apps.instanttranslate.instantTranslationIsActive,
    chosenSourceIsText = true,
    fileTypesAllowed = [],
    fileUploadLanguageCombinationsAvailable = [],
    langugaeInformationData = [],
    characterLimit = 0,
    currentRequest = null,
    isRequestingTranslation = false;


/**
 * Initializes the application when the DOM is ready
 * @param {string} characterLimit
 * @param {string[]} pretranslatedFiles
 * @param {string} dateAsOf
 * @param {Boolean} disableInstantTranslate
 */
function initGui(characterLimit, pretranslatedFiles, dateAsOf, disableInstantTranslate){

    characterLimit = characterLimit;

    if(disableInstantTranslate){
        // dev-option may disables instant translation
        instantTranslationIsActive = false;
    }

    $('#logout a').mouseover(function(){
        $(this).removeClass("ui-state-active");
    });
    $('#logout a').mouseout(function(){
        $(this).addClass("ui-state-active");
    });
    $('#logout').on("click",function(){
        var loginUrl = Editor.data.loginUrl || Editor.data.apps.loginUrl;
        //check if it is used from iframe
        if(window.parent !== undefined){
            window.parent.location = loginUrl;
        } else {
            window.location =loginUrl;
        }
    });
    $('#locale').selectmenu({
        change: function() {
            var action = $(this).val();
            Editor.data.logoutOnWindowClose = false;
            $("#languageSelector").attr("action", "?locale=" + action);
            $("#languageSelector").submit();
        }
    });

    $('#sourceLocale').selectmenu({
        appendTo: "#source",
        open: function() {
            updateLocalesSelectLists($(this));
        },
        change: function() {
            changeLanguage();
        }
    }).selectmenu("menuWidget").addClass("overflow localesSelectList");
    $('#targetLocale').selectmenu({
        appendTo: "#target",
        open: function() {
            updateLocalesSelectLists($(this));
        },
        change: function() {
            changeLanguage();
        }
    }).selectmenu("menuWidget").addClass("overflow localesSelectList");
    // cause the select-menuWidget will not re-size and re-postition itself on windows resize, we just close it.
    $(window).resize(function() {
        $('.selectSourceTarget').selectmenu('close');
    });

    $('#sourceFile').button();
    $('#translationSubmit').button();

    // activate jQuery-UI tooltip to show title in styled box
    $( document ).tooltip({
        position: {
            my: "center top",
            at: "center bottom+14",
            using: function( position, feedback ) {
                $( this ).css( position );
                $( "<div>" )
                    .addClass( "arrow" )
                    .addClass( "top" )
                    .addClass( feedback.vertical )
                    .addClass( feedback.horizontal )
                    .appendTo( this );
            }
        }
    });

    //check if the source and the target are the same
    if($('#sourceLocale').val() === $('#targetLocale').val()){
        setTargetFirstAvailable($('#sourceLocale').val());
    }
    $("#sourceText").attr('maxlength', characterLimit);

    // press the button to swap source-/target-language
    $('.switchSourceTarget').on("click", function() {
        swapLanguages();
    });

    //$('#dropSourceFile').droppable();
    // $("input[type='file']").prop("files", e.dataTransfer.files);

    // prefent funny behaviour of some browsers
    $('#dropSourceFile').on('dragover', function(e) { e.preventDefault(); e.stopPropagation(); });
    $('#dropSourceFile').on('dragenter', function(e) { e.preventDefault(); e.stopPropagation(); });
    // add a special class "dargover" and remove it on dragleave
    $('#dropSourceFile').on('dragover', function() { $(this).addClass('dragover'); });
    $('#dropSourceFile').on('dragleave', function() { $(this).removeClass('dragover'); });

    // do actual "file-dropping"
    $('#dropSourceFile').on(
        'drop',
        function(e) {
            $(this).removeClass('dragover');
            if(e.originalEvent.dataTransfer){
                if(e.originalEvent.dataTransfer.files.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    /*UPLOAD FILES HERE*/
                    $('#sourceFile').prop('files', e.originalEvent.dataTransfer.files);
                    $('#sourceFile').trigger('change');
                }
            }
        }
    );
    // route click event to the input #sourceFile for normal file-select
    $('#dropSourceFile').on('click', function(e) { e.preventDefault(); e.stopPropagation(); $('#sourceFile').click(); });
    $(document).on('click', '.sourceSelector__text' , function() { toggleSource('text'); });
    $(document).on('click', '.sourceSelector__file' , function() { toggleSource('file'); });

    $(document).on('click', '.term-proposal' , function() {
        var text = getInputTextValueTrim(),
            lang = $("#sourceLocale").val(),
            textProposal = $(this).attr('data-term'),
            langProposal = $("#targetLocale").val(),
            isMT = $(this).parents('.copyable').find('.translation-result').data('languageresource-type') === 'mt';

        var q = top.window.Ext.ComponentQuery.query,
            vm = q('main').pop().getViewModel(),
            b = q('[reference=termportalBtn]').pop(),
            itranslate = { target: {lang: langProposal, term: textProposal, isMT: isMT} };

        // If termId-param is not given, it means that source termEntry is not known,
        // so we append data for trying to find it
        if (!location.search.match(/termId/)) {
            itranslate.source = {lang: lang, term: text};
        }

        // Set main viewModel's itranslate-prop
        vm.set('itranslate', itranslate);

        // Click on TermPortal-button
        b.el.dom.click();
    });

    $('#translations').on('touchstart click','.term-info',function(){
        var term = $(this).attr('id'),
            lang = $("#targetLocale").val(),
            collectionId = $(this).parent().find('[data-languageresource-type=termcollection]').attr('id'),
            q = top.window.Ext.ComponentQuery.query,
            vm = q('main').pop().getViewModel(),
            b = q('[reference=termportalBtn]').pop(),
            itranslate = { search: {lang: lang, term: term, collectionId: collectionId} };

        // Set main viewModel's itranslate-prop
        vm.set('itranslate', itranslate);

        // Click on TermPortal-button
        b.el.dom.click();
    });

    $('#termPortalButton').on('touchstart click',function(){
        openTermPortal();
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
        // we can not use text() because we need to turn <br/> into blanks
        var textToCopy = markupToText($(this).closest('.copyable').find('.translation-result').html(), ' ');
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

    /* --------------- start translation instantly or manually: events  --------- */

    // start instant translation automatically
    $('#sourceText').bind('keyup', function() {
        checkInstantTranslation();
    });

    //instantly after uploading a file: grab the files and set them to uploadedFiles
    //(if instantTranslation is on, the translation will start, too)
    $('#sourceFile').on('change', grabUploadedFiles);

    //start translation manually via button
    $('.click-starts-translation').click(function(){
        if (chosenSourceIsText) {
            startTranslation();
        } else {
            startFileTranslation();
        }
        return false;
    });

    $(document).on('click', '.getdownloads' , function(e) {
        e.stopPropagation();
        getDownloads();
        return false;
    });
    // initially, we appear as text translation
    showSourceIsText();

    clearAllErrorMessages();
    setAllowedFileTypes();
    setfileUploadLanguageCombinationsAvailable();
    setTextForSource();

    // start with checking according to the locales as stored for user
    checkInstantTranslation();

    // we show any downloads that may are pretranslated
    showDownloads(pretranslatedFiles, dateAsOf);
}

/**
 * Removes all markup from a text and converts break-tags to the given string
 * @param {string} text
 * @param {string} breakTagReplacement
 * @returns {string}
 */
function markupToText(text, breakTagReplacement){
    text = text.replace(/<br\s*\/{0,1}>/ig, breakTagReplacement);
    return text.replace(/<\/{0,1}[a-zA-Z][^>]*\/{0,1}>/ig, '');
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
    $.each(languageResource.source, function(indexSource, sourceLang) {
        $.each(languageResource.target, function(indexTarget, targetLang) {
            if (sourceLang !== targetLang) {
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
    var useSub = Editor.data.instanttranslate.showSublanguages,
        hasLang = false;

    if(useSub){
        return $.inArray(localeToCheck, allLocalesAvailable) !== -1;
    }

    // when no sublangauges are used, use fuzzy matching on localeToCheck, since in the backend fuzzy latching will be used as well
    $.each(allLocalesAvailable, function(index) {
        var locale = allLocalesAvailable[index];
        hasLang = localeToCheck === locale.split('-')[0];
        if(hasLang){
            return false;
        }
    });
    return hasLang;
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
        useSub = Editor.data.instanttranslate.showSublanguages,
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

                        // when using sub langauges, direct matching is required. Without sub-languages, fuzzy matching will be applied on
                        // the backend side and decided which resource will be used for file-translations
                        if(useSub){
                            langComb = languageResourceToCheckAllSources[indexS] + '|' + languageResourceToCheckAllTargets[indexT];
                        }else{
                            // use the major languages when sub-languages are disabled
                            langComb = languageResourceToCheckAllSources[indexS].split('-')[0] + '|' + languageResourceToCheckAllTargets[indexT].split('-')[0];
                        }

                        if ( languageResourceToCheckAllSources[indexS] !== languageResourceToCheckAllTargets[indexT] && $.inArray(langComb, fileUploadLanguageCombinationsAvailable) === -1) {
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
    var textForSourceIsText = Editor.data.languageresource.translatedStrings.enterText,
        textForSourceIsFile = '';

    // When source is chosen to text
    textForSourceIsText += ' <span class="change-source-type">';
    textForSourceIsText += Editor.data.languageresource.translatedStrings.orTranslateFile;
    textForSourceIsText += '</span>';
    // When source is chosen to file
    textForSourceIsFile = Editor.data.languageresource.translatedStrings.uploadFile;
    textForSourceIsFile += ' <span class="change-source-type">';
    textForSourceIsFile += Editor.data.languageresource.translatedStrings.orTranslateText;
    textForSourceIsFile += '</span>';

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
    $('#'+elId+'-menu li.ui-menu-item').each(function(){
        if (localesAvailable.indexOf($(this).text()) === -1) {
            $(this).addClass(NOT_AVAILABLE_CLS);
        } else {
            $(this).removeClass(NOT_AVAILABLE_CLS);
        }
    });
}
/**
 * Check if any engines are available for the current language-combination.
 * @returns {Boolean}
 */
function hasEnginesForLanguageCombination() {
	var returnValue = isSourceTargetAvailable($("#sourceLocale").val(), $("#targetLocale").val()),
		isDisableButton = !isSourceTargetAvailable($("#targetLocale").val(), $("#sourceLocale").val());//switch source and target so the other way arround is checked
	//the button is disabled when for the target as source there is no source as target
	$("#switchSourceTarget").prop("disabled", isDisableButton);
    if(isDisableButton){
        $("#switchSourceTarget").addClass( "switchSourceTargetDisabled" );
    } else {
        $("#switchSourceTarget").removeClass( "switchSourceTargetDisabled" );
    }
    return returnValue;
}

/***
 * Check if for given source target combo there is available language resource
 * @param sourceRfc
 * @param targetRfc
 * @returns {Boolean}
 */
function isSourceTargetAvailable(sourceRfc,targetRfc){
    var targetLocalesAvailable = getLocalesAccordingToReference ('accordingToSourceLocale', sourceRfc),
        useSub = Editor.data.instanttranslate.showSublanguages,
        hasLang = false;
    if(useSub){
        return targetLocalesAvailable.indexOf(targetRfc) !== -1;
    }
    // when no sublangauges are used, use fuzzy matching on localeToCheck, since in the backend fuzzy latching will be used as well
    $.each(targetLocalesAvailable, function(index) {
        var locale = targetLocalesAvailable[index];
        hasLang = targetRfc === locale.split('-')[0];
        if(hasLang){
            return false;
        }
    });
    return hasLang;
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
        allToAdd;

    for (languageResourceId in Editor.data.apps.instanttranslate.allLanguageResources) {
        if (! Editor.data.apps.instanttranslate.allLanguageResources.hasOwnProperty(languageResourceId)) {
            continue;
        }
        toCheck = Editor.data.apps.instanttranslate.allLanguageResources[languageResourceId];
        langSet = (accordingTo === 'accordingToSourceLocale') ? toCheck.source : toCheck.target;
        if (!isAvailableLocale(selectedLocale, langSet)){
            continue;
        }
        allToAdd = (accordingTo === 'accordingToSourceLocale') ? toCheck.target : toCheck.source;
        $.each(allToAdd, function(index, toAdd) {
            // TermCollections can translate in all combinations of their source- and target-languages,
            // but not from the source to the SAME target-language.
            var isSame = toAdd === selectedLocale,
                //prevent duplicates
                notAdded = ($.inArray(toAdd, localesAvailable) === -1);
            
            if (!isSame && notAdded) {
                localesAvailable.push(toAdd);
            }
        });
    }
    localesAvailable.sort();
    return localesAvailable;
}

/* --------------- prepare file-translations  ------------------------------- */

function grabUploadedFiles(event){
    uploadedFiles = event.target.files;
    startFileTranslation();
}

function startFileTranslation() {
    var fileName,
        fileType,
        fileTypesErrorList = [];
    if ($('#sourceFile').val() === '') {
        showSourceError(Editor.data.languageresource.translatedStrings.uploadFileNotFound);
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
        showSourceError(Editor.data.languageresource.translatedStrings.notAllowed + ': ' + fileTypesErrorList.join());
        return;
    }
    startTranslation();
}

/* --------------- prepare, start and terminate translations  --------------- */
function startTimerForInstantTranslation() {
    terminateTranslation();
    editIdleTimer = setTimeout(function() {
        startTranslation(); // TODO: this can start a filetranslation without calling startFileTranslation()
    }, Editor.data.apps.instanttranslate.translateDelay);
}
function startTranslation() {
    var textToTranslate,
        translationInProgressID;
    // Check if any engines are available for that language-combination.

    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings.noLanguageResource);
        return;
    }
    // translate a file?
    if (!chosenSourceIsText) {
        // TODO: we can get here via startTimerForInstantTranslation
        // (= without calling startFileTranslation() first),
        // so we have to run the same validation. Bad!
        if ($('#sourceFile').val() === '') {
            showSourceError(Editor.data.languageresource.translatedStrings.uploadFileNotFound);
            return;
        }
        if(uploadedFiles && uploadedFiles.length > 0){
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
function translateText(textToTranslate, translationInProgressID){
    startLoadingSign();
    if(currentRequest){
        currentRequest.abort();
    }
    currentRequest = $.ajax({
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
            'source': $("#sourceLocale").val(),
            'target': $("#targetLocale").val(),
            'text': textToTranslate,
            'escape': 1,
            'orderer': 'gui'
        },
        success: function(result){
            if (translationInProgressID !== latestTranslationInProgressID) {
                return;
            }
            if (result.errors !== undefined && result.errors !== '') {
                showTargetError(result.errors);
            } else {
                clearAllErrorMessages();
                translateTextResponse = result.rows;
                fillTranslation();
            }
            stopLoadingSign();
            currentRequest = null;
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(textStatus !== 'abort' && errorThrown !== 'abort'){
                showSourceError(createJqXhrError(jqXHR, textStatus, errorThrown));
                stopLoadingSign();
            }
            currentRequest = null;
        },
        fail: function(){
            currentRequest = null;
            debugger;
        }
    });
}

function abortTranslateText(){
    if(currentRequest){
        currentRequest.abort();
        stopLoadingSign();
    }
}

function fillTranslation() {
    var translationHtml = '';
    if (translateTextResponse.hasOwnProperty('translationForSegmentedText')) {
        translationHtml = renderSingleMatchAndResources(translateTextResponse);
    } else {
        translationHtml = renderMatchesByResource(translateTextResponse);
    }
    
    if (translationHtml === '') {
        showTargetError(Editor.data.languageresource.translatedStrings.noResultsFound);
    }
    $('#translations').html(translationHtml);
    showTranslations();

    // open TermPortal for proposing new term?
    checkTermPortalIntegration();
}

function renderSingleMatchAndResources (translateTextResponse) {
    var resultHtml = '',
        translationText = translateTextResponse.translationForSegmentedText,
        usedResources = translateTextResponse.usedResources,
        resultData = {
            'languageResourceHeadline':           Editor.data.languageresource.translatedStrings.translationBasedOn + ' ' + usedResources,
            'languageResourceId':                 '',
            'languageResourceType':               '',
            'fuzzyMatch':                         '',
            'infoText':                           '',
            'term':                               '',
            'termStatus':                         '',
            'translationText':                    translationText,
            'processStatusAttribute':             '',
            'processStatusAttributeValue':        '',
            'languageRfc':                        '',
            'alternativeTranslations':            '',
            'singleResultBestMatchrateTooltip':   Editor.data.languageresource.translatedStrings.singleResultBestMatchrateTooltip
        };
    resultHtml += renderTranslationContainer(resultData);
    return resultHtml;
}

function renderMatchesByResource (translateTextResponse) {
    var resultHtml = '',
        fuzzyMatch,
        infoText,
        term,
        termStatus,
        metaData,
        resultData,
        languageRfc,
        alternativeTranslations;

    // first: order the response-result, that fuzzyMatches are at the end
    // separate the fuzzyMatches from the results
    var $orderedResults = [];
    var $fuzzyResults = [];
    $.each(translateTextResponse, function(serviceName, resource) {
        $.each(resource, function(resourceName, allResults) {
            $.each(allResults, function(key, result) {
                result.serviceName = serviceName;
                result.resourceName = resourceName;
                // check if result is a fuzzyMatch
                if (result.sourceDiff !== undefined) {
                    $fuzzyResults.push(result);
                }
                else {
                    $orderedResults.push(result);
                }
            });
        });
    });
    // and then add the fuzzyMatches at the end of he other results
    $orderedResults = $orderedResults.concat($fuzzyResults);

    resultHtml = '';
    $.each($orderedResults, function(key, result){
        if (result.target !== '') {
            fuzzyMatch = {};
            if (result.sourceDiff !== undefined) {
                fuzzyMatch = {'matchRate': result.matchrate,
                              'sourceDiff': result.sourceDiff};
            }
            infoText = [];
            term = '';
            languageRfc=null;
            termStatus = '';
            processStatusAttribute = '';
            processStatusAttributeValue = '';
            alternativeTranslations=[];
            if (result.metaData) {
                metaData = result.metaData;
                if(metaData.definitions && metaData.definitions.length > 0) {
                    //add all available definitions as separate row
                    for(var i = 0; i < metaData.definitions.length; i++){
                        infoText.push(metaData.definitions[i]);
                    }
                }
                if(metaData.term !== undefined) {
                    term = metaData.term;
                }
                if(metaData.languageRfc !== undefined) {
                    languageRfc = metaData.languageRfc;
                }
                if(metaData.status !== undefined) {
                    termStatus = metaData.status;
                }
                if(metaData.processStatusAttribute !== undefined) {
                    processStatusAttribute = metaData.processStatusAttribute;
                }
                if(metaData.processStatusAttributeValue !== undefined) {
                    processStatusAttributeValue = metaData.processStatusAttributeValue;
                }
                if(metaData.alternativeTranslations !== undefined) {
                    alternativeTranslations = metaData.alternativeTranslations;
                }
            }
            resultData = {'languageResourceHeadline' : result.resourceName + ' [' + getLanguageresourceName(result.serviceName) + '] ',
                          'languageResourceId': result.languageResourceid,
                          'languageResourceType': result.languageResourceType,
                          'fuzzyMatch': fuzzyMatch,
                          'infoText': infoText.join('<br/>'),
                          'term': term,
                          'termStatus': termStatus,
                          'translationText': result.target,
                          'processStatusAttribute':processStatusAttribute,
                          'processStatusAttributeValue':processStatusAttributeValue,
                          'languageRfc':languageRfc,
                          'alternativeTranslations':alternativeTranslations
                          };
            resultHtml += renderTranslationContainer(resultData);
        }
    });

    if (resultHtml !== '') {
        return resultHtml;
    }

    return '';
}

/**
 * get the translate name of the languageservice.
 * if no translation is defined, the requested $service will be returned
 * Sample:
 * 'TermCollection' => 'Terminologiedatenbank (translate5)'
 * '<unknown>' => '<unknown'
 *
 * @param $service
 * @returns {string|*}
 */
function getLanguageresourceName($service) {
    if (Editor.data.languageresource.translatedStrings.languageresourceNames[$service] !== undefined) {
        return Editor.data.languageresource.translatedStrings.languageresourceNames[$service];
    }
    return $service;
}

function renderTranslationContainer(resultData) {
    var translationsContainer = '';
    var $additionalHeaderClass = 'b';
    var $isFuzzyMatch = false;
    var $fuzzyContainer = '';
    var $alternativeTranslations = false;

    // detect color of the header-bottom-border
    // results of TermCollection are green
    if (resultData.languageResourceType === 'termcollection') {
        $additionalHeaderClass = 'box__result__header__green';
    }
    // and 100% matches of TranslationMemory are green
    if (resultData.languageResourceType === 'tm' && resultData.fuzzyMatch.sourceDiff === undefined) {
        $additionalHeaderClass = 'box__result__header__green';
    }

    if (resultData.fuzzyMatch.sourceDiff !== undefined) {
        $isFuzzyMatch = true;
        $additionalHeaderClass = 'box__result__header__red';
        var fuzzyMatchTranslatedString = Editor.data.languageresource.translatedStrings.attentionFuzzyMatch.replace("{0}", resultData.fuzzyMatch.matchRate);
        $fuzzyContainer += '<div class="translation-sourcediff" title="'+Editor.data.languageresource.translatedStrings.differenceIsHighlighted+'">';
        $fuzzyContainer += '<svg class="icon icon-t5_attention" /><span class="error">'+fuzzyMatchTranslatedString+'</span><br>';
        $fuzzyContainer += '    <span class="translation-sourcediff-content">' + resultData.fuzzyMatch.sourceDiff + '</span>';
        $fuzzyContainer += '</div>';
    }

    translationsContainer += '<div class="copyable marginTop">';

    translationsContainer += '<div class="box box__result__header '+$additionalHeaderClass+' font-size-big">';
        translationsContainer += '<h2>';
            translationsContainer += '<div class="floatRight">'+renderTranslationContainerResultIcons(resultData)+'</div>';
            translationsContainer += '<div class="translation-result" id="'+resultData.languageResourceId+'" data-languageresource-type="'+resultData.languageResourceType+'">'+sanitizeTranslatedText(resultData.translationText)+'</div>';
        translationsContainer += '</h2>';
    translationsContainer += '</div>'; // end of <div className="box box__result__header ...

    translationsContainer += '<div class="box box__result__content">';
        translationsContainer += '<p class="font-size-medium">';
            //if this tooltip is set, there is only one result -> result with best matchrate
            if(resultData.singleResultBestMatchrateTooltip){
                translationsContainer += '<span class="singleResultBestMatchrateInfoIcon" title="'+resultData.singleResultBestMatchrateTooltip+'"></span>';
            }
            translationsContainer += '<div class="overflowEllipses">'+resultData.languageResourceHeadline+'</div>';
            translationsContainer += '<span class="loadingSpinnerIndicator"><img src="'+Editor.data.publicModulePath+'images/loading-spinner.gif"/></span>';
        translationsContainer += '</p>';
        if (resultData.infoText !== '') {
            translationsContainer += '<div class="translation-infotext marginTop">'+resultData.infoText+'</div>';
        }
        translationsContainer += $fuzzyContainer;

    translationsContainer += '</div>'; // end of <div className="box box__result__content">

    if (($alternativeTranslations = renderTranslationContainerResultAlternativeTranslations(resultData))) {
        translationsContainer += '<div class="box box__result__content bg-grey_01">'+$alternativeTranslations+'</div>';
    }

    translationsContainer += '</div>'; // end of <div class="copyable">

    //renderTranslationContainerResultAlternativeTranslations(resultData);

    // SBE: as far as I can see, this container is never used:
    // translationsContainer += '<div id="translationError'+resultData.languageResourceId+'" class="instant-translation-error ui-state-error ui-corner-all"></div>';
    return translationsContainer;
}

/**
 * render all icons like "copy" etc. for the tranlsations result text
 * @param resultData
 * @returns {string}
 */
function renderTranslationContainerResultIcons (resultData) {
    var tempHtml = '';

    // render process-status icon
    if(resultData.processStatusAttributeValue && resultData.processStatusAttributeValue === 'finalized') {
        //tempHtml += '<span class="process-status-attribute"><img src="' + Editor.data.publicModulePath + 'images/tick.png" alt="finalized" title="finalized"></span>';
        tempHtml += '<span class="process-status-attribute" title="finalized"><svg class="icon icon-t5_check_green" /></span>';
    }

    // render usage-status icon
    if (resultData.termStatus !== '') {
        tempHtml += renderTermUsageStatusIcon(resultData.termStatus);
    }

    // render info-icon
    if (resultData.term !== '' && Editor.data.isUserTermportalAllowed) {
        //check if for the current term the rfc language value is set, if yes set data property so the language is used in the term portal
        var languageRfc=resultData.languageRfc ? ('data-languageRfc="'+resultData.languageRfc+'"') : '';
        tempHtml += '<span class="term-info" id="'+resultData.term+'" '+languageRfc+' title="'+Editor.data.languageresource.translatedStrings.openInTermPortal+'"><svg class="icon icon-t5_info" /></span>';
    }

    // render "copy to clipboard"
    tempHtml += '<span class="copyable-copy" title="'+Editor.data.languageresource.translatedStrings.copy+'"><svg class="icon icon-t5_copy" /></span>';


    return tempHtml;
}

/**
 * render the additional results of alternative translations
 * which are submitted by Microsoft TM
 * @param resultData
 * @returns {string}
 */
function renderTranslationContainerResultAlternativeTranslations (resultData) {
    if (resultData.alternativeTranslations !== undefined) {
        var at = resultData.alternativeTranslations,
            highestConfidenceTranslation='',
            atHtmlTableResultPosTag = '',
            atHtmlTableStart = '',
            atHtmlTableEnd = '',
            atHtmlTable='',
            atHtmlBt=[];

        if (at.length < 1) {
            return false;
        }
        atHtmlTableStart = '<table class="translationsForLabel bg-grey_01 marginTop">';
        atHtmlTableEnd = '</table>';

        $.each(at, function(key, result){
            if (atHtmlTableResultPosTag === '') {
                // This assumes that result['posTag'] is the same for all results!
                atHtmlTableResultPosTag = '<tr><td colspan="2"><strong>'+result.posTag+'</strong></td></tr>';
            }
            atHtmlTable += '<tr>';
            //atHtmlTable += '<td><progress value="'+result['confidence']+'" max="1"></progress></td>';
            // we need th &nbsp; to get the correct vertical position of the progress bar
            atHtmlTable += '<td><div class="progress bg-grey_02" style="width: 50px;"><div class="progressBar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: '+ (100 * result.confidence) + '%"></div></div>&nbsp;&nbsp;&nbsp;</td>';
            atHtmlTable += '<td><b>'+result.displayTarget+':</b><br>';
            atHtmlBt=[];
            $.each(result.backTranslations, function(keyBt, resultBt){
                if(highestConfidenceTranslation === ''){
                    highestConfidenceTranslation = Editor.data.languageresource.translatedStrings.translationsForLabel+'<strong class="displayTarget"> '+result.displayTarget+'</strong>';
                }
                atHtmlBt.push(resultBt.displayText);
            });
            atHtmlTable += atHtmlBt.join(', ')+'</td>';
            atHtmlTable += '</tr>';
        });
        $return = '';
        $return += highestConfidenceTranslation;
        $return += atHtmlTableStart;
        $return += atHtmlTableResultPosTag;
        $return += atHtmlTable;
        $return += atHtmlTableEnd;
        return $return;
    }
    return '';
}

/***
 * Render the image-html for TermStatus.
 * @param string termStatus
 * @returns string
 */
function renderTermUsageStatusIcon(termStatus){
    var termStatusHtml = '', 
        status = 'unknown', 
        map = Editor.data.termStatusMap,
        labels = Editor.data.termStatusLabel,
        label;
    if(map[termStatus]) {
        status = map[termStatus];
        label = labels[status]+' ('+termStatus+')';
        //termStatusHtml = '<img src="' + Editor.data.publicModulePath + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'"> ';
        termStatusHtml = '<span class="term-status" title="'+label+'"><svg class="icon icon-t5_termstate-'+status+'" /></span>';
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
    
    if(uploadedFiles && uploadedFiles.length > 0){
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
            } else {
                // Handle errors here
                var error = (result.taskId === '') ? Editor.data.languageresource.translatedStrings.error : result.error;
                showSourceError('ERRORS: ' + error);
                $('#sourceFile').val('');
                stopLoadingState();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            showSourceError(createJqXhrError(jqXHR, textStatus, errorThrown));
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
        error: function(jqXHR, textStatus, errorThrown){
            showSourceError(createJqXhrError(jqXHR, textStatus, errorThrown));
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
function showDownloads(allPretranslatedFiles, dateAsOf){
    var pretranslatedFiles = [],
        html = '',
        importProgressUpdate = false;
    $.each(allPretranslatedFiles, function(index, taskData) {
        var $htmlFile = '';
        var $headerContent = '<h2>'+taskData.taskName+'</h2>';
        var $headerClassAddition = '';
        var $progressBar = '';
        var $innerContent = getLanguageName(taskData.sourceLang)+' &ndash; '+getLanguageName(taskData.targetLang)+'<br>';

        switch(taskData.downloadUrl) {
            case 'isImporting':
                $headerClassAddition = '';
                $headerContent = '<h2 class="color-grey_06">'+taskData.taskName+'<span id="importProgressLabel_'+taskData.taskId+'" class="floatRight"></span></h2>';
                importProgressUpdate = true;
                $innerContent += Editor.data.languageresource.translatedStrings.noDownloadWhileImport;
                //add import progress html. For each task separate progress component and progress label.
                if(taskData.importProgress){
                    $progressBar += '<div className="progress" style="margin-top: -3px;">';
                    $progressBar += '    <div id="progressBar_'+taskData.taskId+'" class="progressBar progressBarThin" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>';
                    $progressBar += '</div>';
                }
                break;
            case 'isErroneous':
                $headerClassAddition = 'color-grey_06 box__result__header__red';
                $innerContent += '<span class="error">'+Editor.data.languageresource.translatedStrings.noDownloadAfterError+'</span>';
                break;
            case 'isNotPretranslated':
                $headerClassAddition = 'color-grey_06 box__result__header__red';
                $innerContent += '<span class="error">'+Editor.data.languageresource.translatedStrings.noDownloadNotTranslated+'</span>';
                break;
            default:
                $headerClassAddition = 'box__result__header__green';
                $headerContent =
                    '<a href="' + taskData.downloadUrl + '" class="color-grey_09" target="_blank" title="Download">'
                    + '<h2>'+taskData.taskName+' <small class="color-grey_06">('+taskData.orderDate+')</small><svg class="icon icon-t5_download floatRight" /></h2>'
                    + '</a>';
                $innerContent += '(' + Editor.data.languageresource.translatedStrings.availableUntil+' '+taskData.removeDate+')';
                break;
        }
        $htmlFile += '<div class="box box__result__header '+$headerClassAddition+' font-size-big marginTop">';
            $htmlFile += $headerContent;
        $htmlFile += '</div>';
        $htmlFile += $progressBar;
        $htmlFile += '<div class="box box__result__content">';
            $htmlFile += '<p>'+$innerContent+'</p>';
        $htmlFile += '</div>';

        pretranslatedFiles.push($htmlFile);
    });

    if (pretranslatedFiles.length > 0) {
        html += pretranslatedFiles.join(' ');
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

function getLanguageName($shortCode) {
    if (Editor.data.allDbLanguages[$shortCode]) {
        return Editor.data.allDbLanguages[$shortCode][1];
    }
    // fallback: return original posted short language Code
    return $shortCode;
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

    $.each(taskData, function() {
        var taskProgressData = taskData.importProgress;
        if(!taskProgressData || taskProgressData.length < 1){
            return true;
        }
        //console.log(taskProgressData);
        setProgressBar('progressBar_'+taskData.taskId, taskProgressData.progress);
        var label = $('#importProgressLabel_'+taskData.taskId);
        label.text(Math.round(taskProgressData.progress) + "%" );
    });
}

function setProgressBar($id, $value) {
    $('#'+$id)
        .css("width", $value + "%")
        .attr("aria-valuenow", $value);
}

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
    if(searchTerms.length === 0) {
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
    var html = '<span class="term-proposal" title="'+Editor.data.languageresource.translatedStrings.termProposalIconTooltip+'" data-id="'+termToPropose.id+'" data-term="'+termToPropose.text+'"><svg class="icon icon-t5_circle_plus_grey" /></span>';
    $('#'+termToPropose.id+'.translation-result').prev('.floatRight').children('.copyable-copy').prepend(html);
}

/***
 * Prepare the transalted text before it is rendered to the page
 * @param translatedText
 * @returns {*}
 */
function sanitizeTranslatedText(translatedText){
    translatedText = translatedText.split("\r\n").join('<br/>').split("\n").join('<br/>');
    return translatedText;
}



/**
 * Open TermPortal with given params (optional).
 * @param {String} params
 */
function openTermPortal (params) {
    //console.log('openTermPortal ' + params);
    var url = Editor.data.restpath+"termportal";
    if (params) {
        window.parent.loadIframe('termportal',url,params);
    } else {
        window.parent.loadIframe('termportal',url);
    }
}

/* --------------- show/hide: helpers --------------------------------------- */

function showTranslations() {
    if (getInputTextValueTrim().length === 0) {
        $('#translations').html('');
    }
    $('#translations').show();
}
function hideTranslations() {
    $('#translations').hide();
}
/* --------------- show/hide: errors --------------------------------------- */
function showLanguageResourceSelectorError(errorMode) {
    $('#languageResourceSelectorError').html(Editor.data.languageresource.translatedStrings[errorMode]).show();
}
/***
 * Show the given error in the targetError container.
 * @param string error
 */
function showTargetError(errorText) {
    $('#targetError').html(errorText).show();
}
function hideTargetError() {
    $('#targetError').html('').hide();
}
/***
 * Show the given error in the sourceError container.
 * @param string error
 */
function showSourceError(errorText) {
    $('#sourceError').html(errorText).show();
}
function hideSourceError() {
    $('#sourceError').html('').hide();
}
/***
 * Creates an error out of an jqXHR error Object defaulting to the other texts passed to an ajax error-handler
 * @param Object jqXHR
 */
function createJqXhrError(jqXHR, textStatus, errorThrown) {
    if(jqXHR.responseJSON && jqXHR.responseJSON.errorCode && jqXHR.responseJSON.errorMessage){
        // QUIRK: if the Error-Code is "E1383" this is just a slight problem with the user-input like invalid markup and we will not like to see a error-code
        if(jqXHR.responseJSON.errorCode === 'E1383'){
            return jqXHR.responseJSON.errorMessage;
        }
        return '<strong>Error ' + jqXHR.responseJSON.errorCode + '</strong><br/>' + jqXHR.responseJSON.errorMessage;
    }
    if(errorThrown){
        return '<strong>Error:</strong> ' + textStatus;
    }
    return '<strong>Error:</strong> ' + textStatus;
}

function clearAllErrorMessages() {
    $('.instant-translation-error').html('').hide();
    $("#sourceIsText").removeClass('source-text-error');
    $('#sourceError').html('').hide();
    $("#targetError").html('').hide();
    // ALWAYS: Check if any engines are available for that language-combination.
    if (!hasEnginesForLanguageCombination()) {
        hideTranslations();
        showTargetError(Editor.data.languageresource.translatedStrings.noLanguageResource);
        return;
    }
}
/* --------------- show/hide: loading spinner ------------------------------- */
// 'sign' = show indicator in addition to content (currently used for text-translations)
// 'state' = shown layer upon content (currently used for file-translations)
function startLoadingSign() {
	if ($('#translations').is(":visible") && $('#translations').html() !== '') {
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
/**
 * Toggles the main GUI between text / file mode
 * @param $source
 */
function toggleSource($source) {
    // do nothing if the chosen source is already selected
    if ($source === 'text' && chosenSourceIsText || $source === 'file' && !chosenSourceIsText) {
        return;
    }
    chosenSourceIsText = !chosenSourceIsText;
    showGui();
}

function changeLanguage(){
    if(chosenSourceIsText){
        checkInstantTranslation();
        startTranslation();
    } else {
        checkFileTranslation();
    }
}

function swapLanguages(){
    // detect old languages
    var $oldSourceLang = $('#sourceLocale').val(),
        $oldTargetLang = $('#targetLocale').val(),
        allSourceLangs = $.map($("#sourceLocale option[value]"), function(option) {
            return $(option).attr("value");
        }),
        allTargetLangs = $.map($("#targetLocale option[value]"), function(option) {
            return $(option).attr("value");
        });
        
    //check if oldLangs are missing in other language, if yes try fuzzying, 
    // if still nothing found keep logic as it was (use the last one of the select)
    
    if($.inArray($oldSourceLang, allTargetLangs) < 0) {
        if(/[-_]/.test($oldSourceLang)) {
            //if old source is sublanguage, use main language of it
            $oldSourceLang = $oldSourceLang.split(/[-_]/)[0];
        }        
        else {
            //if it is a main language, use the first matchin sub language
            $.each(allTargetLangs, function(idx,target){
                if((new RegExp($oldSourceLang+'[-_]')).test(target)) {
                    $oldSourceLang = target;
                    return false;
                }
            });
        }
    }
    if($.inArray($oldTargetLang, allSourceLangs) < 0) {
        if(/[-_]/.test($oldTargetLang)) {
            //if old source is sublanguage, use main language of it
            $oldTargetLang = $oldTargetLang.split(/[-_]/)[0];
        }
        else {
            //if it is a main language, use the first matchin sub language
            $.each(allSourceLangs, function(idx,source){
                if((new RegExp($oldTargetLang+'[-_]')).test(source)) {
                    $oldTargetLang = source;
                    return false;
                }
            });
        }
    }

    // if after all checks, still no source or target language is found, show the error about "no language resources" found
    if($.inArray($oldTargetLang, allSourceLangs) < 0 || $.inArray($oldSourceLang, allTargetLangs) < 0) {
        showTargetError(Editor.data.languageresource.translatedStrings.noLanguageResource);
        return;
    }

    // now swap the language selections
    $("#sourceLocale").val($oldTargetLang);
    $("#targetLocale").val($oldSourceLang);
    
    $("#sourceLocale").selectmenu("refresh");
    $("#targetLocale").selectmenu("refresh");

    // renew the results, if there are any
    var results = $('div.translation-result');
    if(results.length > 0) {
        // set the source textarea text, therfore markup must be removed and breaktags restored
        var text = unescapeHtml(results.first().html());
        $('#sourceText').val(text);
    }
    $('#translations').hide();
    changeLanguage();
}
/**
 * Just a simple htmlspecialchars_decode in JS
 * @param string text
 * @returns string
 */
function unescapeHtml(text){
    return text
        .replace(/&amp;/g, "&")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&apos;/g, "'");
}
/**
 * Shows the GUI as neccessary for the current app-state
 */
function showGui(){
    abortTranslateText();
    clearAllErrorMessages();
    document.getElementById("sourceFile").value = "";
    if(chosenSourceIsText) {
        showSourceIsText();
    } else {
        showSourceIsFile();
        checkFileTranslation();
    }
}
/**
 * Shows the text-translation GUI
 */
function showSourceIsText(){
    $('.show-if-source-is-text').show();
    $('.show-if-source-is-file').hide();
    $('#translations').show();
    $("#sourceIsText").removeClass('source-text-error');
    $('#sourceText').focus();
    $('body').removeClass('sourceIsFile');
    $('body').addClass('sourceIsText');
    // show "translate" button?
    if (instantTranslationIsActive) {
        $('#translationSubmit').hide();
    } else {
        $('#translationSubmit').show();
    }
}
/**
 * Shows the file-translation GUI
 */
function showSourceIsFile(){
    hideSourceError();
    $('.show-if-source-is-text').hide();
    $('.show-if-source-is-file').show();
    $('#translations').hide();
    $('body').removeClass('sourceIsText');
    $('body').addClass('sourceIsFile');
    $('#translationSubmit').hide();
}
/**
 * Checks, if file-translation is available and adjusts the GUI accordingly
 */
function checkFileTranslation(){
    hideTargetError();
    if(!isFileUploadAvailable()){
        showSourceError(Editor.data.languageresource.translatedStrings.noLanguageResource);
        hideTranslations();
        $('#dropSourceFile').hide();
    } else {
        hideSourceError();
        showTranslations();
        $('#dropSourceFile').show();
    }
}
/**
 * Every change in the language-selection starts a check if any languageResources
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
        showTargetError(Editor.data.languageresource.translatedStrings.noLanguageResource);
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
 * Setup a counter for browser tabs having opened t5 app, and return current value of that counter
 * If diff arg is expected to be -1, +1, and if so counter will be incremented/decremented and updated value is returned
 *
 * @param diff
 * @returns {number}
 */
function tabQty(diff) {
    var key = 'translate5-tabQty', ls = localStorage, qty = parseInt(ls.getItem(key) || 0);

    // Update qty if need
    if (diff) {

        // Update value in localStorage
        ls.setItem(key, qty + diff);
    }

    // Return original or updated qty
    return parseInt(ls.getItem(key));
}

/**
 * If configured the user is logged out on window close
 */
function logoutOnWindowClose() {
    var me = this;

    // If logoutOnWindowClose-config is turned Off - do nothing
    if (!Editor.data.logoutOnWindowClose) {
        return;
    }

    // Increment t5 app tabs qty
    me.tabQty(+1);

    // Bind handler on window beforeunload-event
    onbeforeunload = () => {

        // Decrement t5 app tabs qty, and if this was the last tab - do logout
        if (me.tabQty(-1)) {
            return;
        }

        // If logoutOnWindowClose-config is temporarily turned Off - do nothing
        if (!Editor.data.logoutOnWindowClose) {
            return;
        }

        // Get regexp to pick zfExtended-cookie
        var rex = /(?:^|; )zfExtended=([^;]*)(?:; |$)/, m = document.cookie.match(rex), zfExtended = m ? m[1] : false;

        // Prepare FormData object to be submitted via sendBeacon()
        var fd = new FormData();
        if (zfExtended) fd.append('zfExtended', zfExtended);
        fd.append('noredirect', 1);

        // Destroy the user session and prevent redirect
        navigator.sendBeacon(Editor.data.pathToRunDir + '/login/logout', fd);

        // Remove now invalid session cookie
        document.cookie = document.cookie.replace(rex, '; ').replace(/^; |; $/, '');
    }
}

// If we're not within an iframe
if (window.parent.location === window.location) {

    // Put a handler on window close, if need
    logoutOnWindowClose();
}