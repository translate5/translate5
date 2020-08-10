var Term={
        
        $_searchTermsHelper:null,
        $_searchErrorNoResults:null,
        $_searchWarningNewSource:null,
        $_searchTermsSelect:null,
        
        $_resultTermsHolder:null,
        $_resultTermsHolderHeader: null,
		$_termTable:null,
        $_termEntryAttributesTable:null,
        $_termCollectionSelect:null,
        
		searchTermsResponse:[],
		termGroupsCache:[],

		disableLimit:false,
		reloadTermEntry:false,//shoul the term entry be reloaded or fatched from the cache
        
		newTermAttributes: Attribute.renderNewTermAttributes(),
        newTermCollectionId: null,
		newTermGroupId: null,
        newTermLanguageId: null,
        newTermRfcLanguage: null,
        newTermName: null,
        newTermTermEntryId: null,
		
		KEY_TERM:'term',
		KEY_TERM_ATTRIBUTES:'termAttributes',
		
		init:function(){
			this.cacheDom();
			this.initEvents();
		},
		
		cacheDom:function(){
            this.$_searchTermsHelper=$('#searchTermsHelper');
            this.$_searchErrorNoResults = $('#error-no-results');
            this.$_searchWarningNewSource = $('#warning-new-source');
			this.$_searchTermsSelect=$('#searchTermsSelect');
            
            this.$_resultTermsHolder=$('#resultTermsHolder');
            this.$_resultTermsHolderHeader=$('#resultTermsHolderHeader');
            this.$_termTable=$('#termTable');
            this.$_termEntryAttributesTable = $('#termEntryAttributesTable');
            this.$_termCollectionSelect = $('#termcollectionSelectContainer');
            this.$_resultTermsHolder=$('#resultTermsHolder');
		},
		
		initEvents:function(){
			var me=this;
			
            // Search Results
			me.$_searchTermsSelect.on('selectableselected',{scope:me},me.onSelectSearchTerm);
		    // FIXME: why is this triggered twice sometimes (with attr('data-value') = 'undefined' in the second)
			
			if(!Editor.data.app.user.isTermProposalAllowed){
				return;
			}
			
			// Term-Entries
	        me.$_resultTermsHolderHeader.on('click', '.proposal-add',{scope:me},me.onAddTermEntryClick);
	        
	        me.$_searchTermsHelper.on('click', '.proposal-add',{scope:me},me.onAddTermEntryClick);

            // Terms
            me.$_termTable.on('click', '.term-data.proposable .proposal-add',{scope:me},me.onAddTermClick);
            me.$_termTable.on('click', '.term-data.proposable .proposal-delete',{scope:me},me.onDeleteTermClick);
            me.$_termTable.on('click', '.term-data.proposable .proposal-edit',{scope:me},me.onEditTermClick);
            
            me.$_termTable.on('click', 'span[data-editable][data-type][data-id="-1"]',{scope:me},me.onAddTermClick);
            
            me.$_resultTermsHolder.on('tabsactivate',{scope:me},me.onResultTabActivate);
            me.$_resultTermsHolder.on('tabsbeforeactivate',{scope:me},me.onResultTabBeforeActivate);
		},
        
        /***
         * On searchTermsSelect selectableselected handler
         */
        onSelectSearchTerm: function(event, ui) {
            var me = event.data.scope,
                $selected = $(ui.selected);
            // data for proposing a new Term
            me.resetNewTermData();
            me.newTermCollectionId = $selected.attr('data-collectionid');
            console.log('onSelectSearchTerm => newTermCollectionId: ' + me.newTermCollectionId);
            me.newTermTermEntryId = $selected.attr('data-termentryid');
            me.newTermGroupId = $selected.attr('data-value');
            // show Terms and Attributes
            me.findTermsAndAttributes($selected.attr('data-termentryid'));
		},
		
		/***
		 * On term/term entry results tab before activate event
		 */
		onResultTabBeforeActivate:function(){
            if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
                return false;
            }
		},
		
		/***
		 * On term/term entry results tab activate event
		 */
		onResultTabActivate:function(event){
			var me = event.data.scope;
            me.drawProposalButtons('attribute');
            me.drawProposalButtons('terms');
		},
		
		/***
		 * Search the term in the language and term collection
		 * @param searchString
		 * @param disableLimit: disable the search limit on the backend
		 * @param successCallback
		 * @returns
		 */
		searchTerm:function(searchString,successCallback){
			var me=this,
				lng=$('#language').val(),
				collectionIds = getFilteredCollections(),
                processStats = getFilteredProcessStats();
			
            if (searchString === '') {
                return;
            }

            console.log('searchTerm');
            clearResults();
            console.log('106 searchTerm => resetNewTermData');
            me.resetNewTermData();
            
			if(!lng){
				lng=$('input[name="language"]:checked').val();
			}
			console.log('searchTerm() for: ' + searchString);
			console.log('searchTerm() for language: ' + lng);
			me.searchTermsResponse=[];  
			$.ajax({
				url: Editor.data.termportal.restPath+'termcollection/search',
				dataType: 'json',
				type: 'POST',
				data: {
					'term':searchString,
					'language':lng,
					'collectionIds':collectionIds,
                    'processStats':processStats,
					'disableLimit':me.disableLimit
				},
				success: function(result){
					me.searchTermsResponse=result.rows[me.KEY_TERM];
					if(successCallback){
						successCallback(result.rows[me.KEY_TERM]);
						return;
					}
					me.fillSearchTermSelect(searchString);
				}
			});
		},
		
		/***
		 * Fill up the term option component with the search results
		 * @returns
		 */
		fillSearchTermSelect:function(searchString){
			var me=this,
			    i,
			    item;
			if(!me.searchTermsResponse || me.searchTermsResponse.length === 0){
                
                console.log('fillSearchTermSelect: nichts gefunden');
                
                // show/hide helper: errors, warning, skeleton...
                if (isTermProposalFromInstantTranslate) {
                    me.$_searchWarningNewSource.text(proposalTranslations['newSourceForSaving'] + ': ' + instanttranslate.textSource);
                    me.$_searchWarningNewSource.show();
                    me.$_searchTermsHelper.find('.skeleton').hide();
                } else {
                	$('#searchTermsHolder').show();
                    me.$_searchErrorNoResults.show();
                    me.$_searchTermsHelper.find('.proposal-txt').text(searchString + ': ' + proposalTranslations['addTermEntryProposal']);
                    me.$_searchTermsHelper.find('.proposal-btn').prop('title', searchString + ': ' + proposalTranslations['addTermEntryProposal']);
                    me.$_searchTermsHelper.find('.skeleton').show();
                }
				
				if(me.disableLimit){
					me.disableLimit=false;
				}
				
                
                // show form for adding proposal right away?
                if(isTermProposalFromInstantTranslate) {
                    $('#searchTermsHelper .proposal-add').click();
                } else {
                    me.$_resultTermsHolder.hide();
                }
                
				return;
			}
            
			
			console.log('fillSearchTermSelect: ' + me.searchTermsResponse.length + ' Treffer');
			
			console.log('fillSearchTermSelect: me.disableLimit'); // FIXME: do we need this here?

			if(me.searchTermsResponse.length>0){
				showFinalResultContent();
			}
			
			if(!me.$_searchTermsSelect.is(':visible')){
				return;
			}
			
			
			//fill the term component with the search results
			for(i=0;i<me.searchTermsResponse.length;i++){
				item = me.searchTermsResponse[i];
				me.$_searchTermsSelect.append( // FIXME; this takes too long
						$('<li>').attr('data-value', item.groupId)
						         .attr('data-collectionid', item.collectionId)
                                 .attr('data-termentryid', item.termEntryId)
                                 .attr('data-language', item.language)
						         .attr('class', 'ui-widget-content search-terms-result').append(
								$('<div>').attr('class', 'ui-widget').append(item.label)
						));
			}
			
			if (me.$_searchTermsSelect.hasClass('ui-selectable')) {
				me.$_searchTermsSelect.selectable('destroy');
			}
			
			me.$_searchTermsSelect.selectable();
			
			var searchTermsSelectLi=me.$_searchTermsSelect.find('li');
			
			searchTermsSelectLi.mouseenter(function() {
				$(this).addClass('ui-state-hover');
			});
			searchTermsSelectLi.mouseleave(function() {
				$(this).removeClass('ui-state-hover');
			});
			me.$_searchTermsSelect.on('selectableselecting', function( event, ui ) {
				$(ui.selecting).addClass('ui-state-active');
			});
			me.$_searchTermsSelect.on('selectableunselecting', function( event, ui ) {
				$(ui.unselecting).removeClass('ui-state-active');
			});
			me.$_searchTermsSelect.on('selectableselected', function( event, ui ) {
				$(ui.selected).addClass('ui-state-active');
			});
			
			if(me.searchTermsResponse.length === 1){
				me.$_searchTermsSelect.find('li:first-child').addClass('ui-state-active').addClass('ui-selected');
			}
			
			// "reset" search form
			$('#search').autocomplete('search', $('#search').val('') );
			
			//if only one record, find the attributes and display them
			if(me.searchTermsResponse.length===1){
	            me.newTermCollectionId = me.searchTermsResponse[0].collectionId;
	            console.log('fillSearchTermSelect => newTermCollectionId: ' + me.newTermCollectionId);
                me.newTermGroupId = me.searchTermsResponse[0].groupId;
	            me.newTermTermEntryId = me.searchTermsResponse[0].termEntryId;
				console.log('fillSearchTermSelect: only one record => find the attributes and display them');
				me.findTermsAndAttributes(me.searchTermsResponse[0].termEntryId);
				return;
			}
			
			
			if(me.searchTermsResponse.length > 1){
			    me.$_resultTermsHolder.hide();
			}
			
			//if term is proposed from the instant translate, and the source exist more than once
			if(me.searchTermsResponse.length > 1 && isTermProposalFromInstantTranslate){
				showInfoMessage(proposalTranslations['multipleSourcesFoundMessage'],proposalTranslations['multipleSourcesFoundTitle']);
			}
		},
		
		/***
		 * Find all terms and terms attributes for the given term entry id
		 * @param termEntryId
		 * @returns
		 */
		findTermsAndAttributes:function(termEntryId){
		    if (termEntryId === undefined) {
		        return; // TODO (quick & dirty - the REAL problem is: this should not happen at all!!)
		    }
			var me=this;
		    console.log('findTermsAndAttributes() for: ' + termEntryId);
		    Attribute.languageDefinitionContent=[];
            
            me.$_termCollectionSelect.hide();
            
		    //check the cache
            if (me.reloadTermEntry) {
                me.termGroupsCache = []; // FIXME: better remove only the termEntryId's items from the cache instead of setting reloadTermEntry to true!
                me.reloadTermEntry=false; //reset term entry reload flag
                
                //hide the no result error if the term is created via no found search
                me.$_searchErrorNoResults.hide();
                me.resetNewTermData();
            }
		    if(me.termGroupsCache[termEntryId]){
		    	TermEntry.drawTermEntryAttributes(me.termGroupsCache[termEntryId].rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
		        me.drawTermTable(me.termGroupsCache[termEntryId].rows[me.KEY_TERM_ATTRIBUTES]);
		        return;
		    }
		    
		    $.ajax({
		        url: Editor.data.termportal.restPath+'termcollection/searchattribute',
		        dataType: 'json',
		        type: 'POST',
		        data: {
		            'termEntryId':termEntryId,
		            'collectionId':getFilteredCollections()
		        },
		        success: function(result){
		        	
		        	if(!result.rows[me.KEY_TERM_ATTRIBUTES] || result.rows[me.KEY_TERM_ATTRIBUTES].length === 0){
		        		//there are no resulsts, and do not render nothing 
		        		me.emptyResultTermsHolder();
		        		return;
		        	}
		        	
		            //store the results to the cache
		            me.termGroupsCache[termEntryId]=result;
		            
		            TermEntry.drawTermEntryAttributes(result.rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);

		            me.drawTermTable(result.rows[me.KEY_TERM_ATTRIBUTES]);
		        }
		    });
		},
		
		/***
         * Draw the term-table:
         * - render skeleton for new proposals
         * - render data for existing terms (if exist)
         * - handle accordion
         * - add proposal-buttons
		 * @returns
		 */
		drawTermTable:function(termsData){
		    console.log('drawTermTable...');
            var me = this,
                html = '',
                currentItem,
                currentItemNr;
            
            // ------- "reset" term-table -------
            me.emptyResultTermsHolder(true);
            me.$_resultTermsHolder.show();
            
            // -------render skeleton for new terms -------
            html += me.renderNewTermSkeleton(termsData);
            
            // -------render term-data -------
		    $.each(termsData, function (i, term) {
		        html += me.renderTerm(term);
		    });
            
		    if(html.length>0){
		    	me.$_termTable.append(html);
		    }
            
            // -------handle accordion etc -------
		    if (me.$_termTable.hasClass('ui-accordion')) {
		    	me.$_termTable.accordion('refresh');
		    } else {
		    	me.$_termTable.accordion({
		            active: false,
		            collapsible: true,
		            heightStyle: 'content',
		            beforeActivate: function( event, ui ) {
                        if ($(event.toElement).hasClass('proposal-delete')) {
                            // Clicking the delete-icon itself does not need to open the Term.
                            event.preventDefault();
                            return;
                        }
                        
		                if (ui.newHeader.length === 0 && ui.oldHeader.has('textarea').length > 0) {
                            // Term in header is opened for editing; don't close the panel.
                            event.preventDefault();
                            return;
                        }
		                
		            	//var accordion = $(this);
		            	if($(event.toElement).is("textarea")){
		            		event.preventDefault();
		            		return;
		            	}
		            	if($(event.toElement).hasClass("proposal-btn") && ui.newHeader.length === 0){
		            		event.preventDefault();
		            		return;
		                }
		                //if the cancel panding changes return false, do not expand/collapse the current header
		                if(!me.cancelPendingChanges(ui.oldHeader)){
		                	event.preventDefault();
                            return;
		                }
		            },
                    activate: function( event, ui ) {
                        if (ui.newHeader.length === 0 && ui.oldHeader.has('textarea').length > 0) {
                            // Panel is already opened, don't close it after click on Term in header for editing.
                            currentItem = ui.oldHeader[0];
                            currentItemNr = me.$_termTable.children('h3').index(currentItem);
                            me.$_termTable.accordion('option', 'active', currentItemNr);
                        }
                        if(!$.isEmptyObject(ui.newHeader.offset())) {
                            $('html:not(:animated), body:not(:animated)').animate({ scrollTop: ui.newHeader.offset().top }, 'slow');
                        }
                    }
		        });
		    }
		    
		    //FIXME: workaround, check me later
		    $.ui.accordion.prototype._keydown = function( event ) {
		        var keyCode = $.ui.keyCode;

		        if (event.keyCode === keyCode.SPACE) {
		            return;
		        }
		    };
		    
		    //find the selected item form the search result and expand it
		    $.each($('#searchTermsSelect li'), function (i, item) {
		        if($(item).hasClass('ui-state-active')){
		        	me.$_termTable.accordion({
		                active:false
		            });
		            
		            $.each($('#termTable h3'), function (i, termitem) {
		            	if(isTermProposalFromInstantTranslate) {
		            		return true; // continue; we check isTermProposalFromInstantTranslate later
		            	}
		            	//expand the selected term (check for language, since there can be terms with same name in same term entry)
		                if((termitem.dataset.termValue === item.textContent || termitem.dataset.proposal === item.textContent) && (termitem.dataset.language === item.dataset.language)){
		                	me.$_termTable.accordion({
		                        active:i
		                    });
		                	return false;
		                }
		            });
		            
		        }
		    });
		    // We must avoid that the first item is activated (= this is the skeleton and must never be active until clicked);
		    // better collapse all if nothing is (selected) in the left column.
            if ($('#searchTermsSelect li').length === 0 || $('#searchTermsSelect li.ui-state-active').length === 0) {
                me.$_termTable.accordion({ active: false, collapsible: true });
            }
            
		    setSizesInFinalResultContent();

	    	me.initInstantTranslateSelect();
            
            // -------proposal-buttons -------
            me.drawProposalButtons('attribute');
            me.drawProposalButtons('terms');
            
            // trigger the "new translated" term editor if request is from instanttranslate for translated term
            if(isTermProposalFromInstantTranslate){
                me.$_termTable.find('.is-new').find('.proposal-add').click();
            }
        },
        
        /***
         * Render html for adding new terms (= visible as "skeleton").
         * @param {Object} termsData
         * @returns
         */
        renderNewTermSkeleton:function(termsData){
        	
			if(!Editor.data.app.user.isTermProposalAllowed){
				return '';
			}
			
            var me = this,
                html = '',
                newTermData = me.getNewTermData(termsData);
            html += me.renderTerm(newTermData[0]);
            return html;
        },
        
        /**
         * Render html for term by given term.
         * 
         * @param {Object} term
         * @returns {String}
		 */
		renderTerm: function (term) {
            var me = this,
                termAttributesHtmlContainer = [],
                termRflLang = (term.attributes[0] !== undefined) ? term.attributes[0].language : '',
                rfcLanguage = getLanguageFlag(termRflLang),
                statusIcon=me.checkTermStatusIcon(term), //check if the term contains attribute with status icon
                infosForSelection = '',
                filteredCientsNames = [],
                clientsForCollection,
                isProposal,
                proposable = (term.proposable !== false) ? ' proposable' : '', // = does the user have the rights to handle proposals for this term?,
                termHeader=[];
            
            // "is-proposal" can be ... 
            // ... a proposal for a term that already existed (term.proposal = "xyz")
            // ... or a proposal for a new term (term.proposal = null, but processStatus is "unprocessed")
            isProposal = ' is-finalized'; 
            if (term.proposal !== null || term.processStatus === 'unprocessed') {
                isProposal = ' is-proposal';
            }
            
            // for new-term-skeleton
            if (term.termId === null) {
                isProposal = ' is-new';
                statusIcon = '';
                term.termId = -1;
            }
            
            //draw term header
            termHeader.push('<h3');
            termHeader.push('class="term-data'+proposable + isProposal+'"');
            termHeader.push('data-term-value="'+term.term+'"');
            termHeader.push('data-term-id="'+term.termId+'"');
            termHeader.push('data-groupid="'+term.groupId+'"');
            termHeader.push('data-termEntryId="'+term.termEntryId+'"');
            termHeader.push('data-language="'+term.languageId+'"');
            if (term.proposal && term.proposal !== undefined) {
            	termHeader.push('data-proposal="'+term.proposal.term+'"');
            }
            termHeader.push('>');
            
            termAttributesHtmlContainer.push(termHeader.join(' '));
            
            
            //add empty space between
            termAttributesHtmlContainer.push(' ');
            
            //add language flag
            termAttributesHtmlContainer.push(rfcLanguage);
            
            //add empty space between
            termAttributesHtmlContainer.push(' ');
            
            //get term render data
            termAttributesHtmlContainer.push(me.renderTermData(term));

            if(statusIcon){
                termAttributesHtmlContainer.push(statusIcon);
            }
            
            //add empty space between
            termAttributesHtmlContainer.push(' ');
            
            //add client- and termCollection-names like this: [CUSTOMERNAME; termCollectionNAME]
            infosForSelection = [];
            clientsForCollection = collectionsClients[term.collectionId];
            if(typeof clientsForCollection !== 'undefined' && clientsForCollection.length>1){
            	for (var i = 0; i < clientsForCollection.length; i++) {
            		if(clientsNames[clientsForCollection[i]] !== undefined){
            			filteredCientsNames.push(clientsNames[clientsForCollection[i]]);
            		}
                }
            }
            if (filteredCientsNames.length >1) {
                infosForSelection.push(filteredCientsNames.join(', '));
            }
            
            if(Object.keys(collectionsNames).length>1){
            	infosForSelection.push(collectionsNames[term.collectionId]);
            }
            
            if(infosForSelection.length>0){
            	termAttributesHtmlContainer.push('<span class="selection-infos">['+infosForSelection.join('; ')+']</span>');
            }
            
            termAttributesHtmlContainer.push('</h3>');
            
            //draw term attrbute contaner with the attributes
            termAttributesHtmlContainer.push(Attribute.getTermAttributeContainerRenderData(term,true));
            
            return termAttributesHtmlContainer.join('');
		},
        
        /***
         * Return the jquery component of the term header(h3)
         */
        getTermHeader:function(termId){
            return this.$_termTable.find('h3[data-term-id="'+termId+'"]');
        },
        
        
        /***
         * Render html for the term data. If the user has proposal tights, the term proposal render data will be set.
         */
        renderTermData:function(termData){
            var me=this,
                htmlCollection=[];
            
            // DB: new term-proposals are stored as unprocessed term, not as proposal... *sigh*
            if (termData.processStatus === 'unprocessed' && !termData.proposal) {
                htmlCollection.push('<ins class="proposal-value-content">'+termData.term+'</ins>');
                return htmlCollection.join(' ');
            }
            
            //the proposal is not set, init the component editor
            if(!termData.proposal){
                //the user has proposal rights -> init term proposal span
                return Attribute.getProposalDefaultHtml('term',termData.termId,termData.term,termData);
            }
            
            //the proposal is allready defined, render the proposal
            htmlCollection.push('<del class="proposal-value-content">'+termData.term+'</del>');
            htmlCollection.push('<ins class="proposal-value-content">'+termData.proposal.term+'</ins>');
            return htmlCollection.join(' ');
        },

        /**
         * Returns the HTML for a language-select with flags.
         * @param {String} languagesFor
         * @param {String} source
         * @returns {String}
         */
        renderLanguageSelect: function (languagesFor, source) {
            var languageSelect = '',
                languageSelectDescription = '<option value="none">'+proposalTranslations['selectLanguage']+'</option>',
                languageSelectOptions = '',
                flag,
                lang,
                targetsForSources,
                targets,
                availableLanguages,
                languageName='',
                i;
            
            
            switch(languagesFor) {
                case 'instanttranslate':
                    // offer target-Languages as available in InstantTranslate for the term's source
                    source = checkSubLanguage(source);
                    targetsForSources = Editor.data.instanttranslate.targetsForSources;
                    if (source in targetsForSources){
                        targets = targetsForSources[source];
                        for (i=0; i < targets.length; i++) {
                            target = targets[i];
                            languageName=Editor.data.apps.termportal.rfcToLanguageNameMap[target] ? Editor.data.apps.termportal.rfcToLanguageNameMap[target] : target;
                            flag = getLanguageFlag(target);
                            languageSelectOptions += '<option value="'+target+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+languageName+'</option>';
                        }
                    }
                    break;
                case 'term':
                	
                	//display the language description only if all available languages for term config is enabled
                    if(Editor.data.apps.termportal.newTermAllLanguagesAvailable){
                    	languageSelectOptions += '<option value="none" disabled>-- '+translations['TermPortalLanguages']+': --</option>';
                    }
                    
                    // list the languages of the TermPortal first...
                    $('#language option').each(function() {
                        if ($(this).val() !== 'none') {
                            flag = getLanguageFlag($(this).text());
                            languageName=Editor.data.apps.termportal.rfcToLanguageNameMap[$(this).text()] ? Editor.data.apps.termportal.rfcToLanguageNameMap[$(this).text()] : $(this).text();
                            languageSelectOptions += '<option value="'+$(this).val()+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+languageName+'</option>';
                        }
                    });
                    
                    //if all languages for new term is disabled, do not add them to the dropdown
                    if(!Editor.data.apps.termportal.newTermAllLanguagesAvailable){
                    	break;
                    }
                    
                    // ... and then list ALL languages that are available in translate5
                    languageSelectOptions += '<option value="none" disabled>-- '+translations['AllLanguagesAvailable']+': --</option>';
                    availableLanguages = Editor.data.availableLanguages;
                    for (i=0; i < availableLanguages.length; i++) {
                        lang = availableLanguages[i];
                        flag = getLanguageFlag(lang.value);
                        languageName=Editor.data.apps.termportal.rfcToLanguageNameMap[lang.text] ? Editor.data.apps.termportal.rfcToLanguageNameMap[lang.text] : lang.text;
                        languageSelectOptions += '<option value="'+lang.id+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+lang.text+'</option>';
                    }
                    break;
            }
            
            if (languageSelectOptions !== '') {
                languageSelect = '<select class="chooseLanguage">'+languageSelectDescription+languageSelectOptions+'</select>';
            }
            return languageSelect;
        },

        /**
         * Return HTML for "InstantTranslate into"-LanguageDropDown.
         * @returns {String}
         * 
         */
        renderInstantTranslateIntegrationForTerm: function(source) {
            console.log('renderInstantTranslateIntegrationForTerm');
            var me = this,
                languageSelect,
                html = '';
            if (!Editor.data.app.user.isInstantTranslateAllowed) {
                console.log('do NOT renderInstantTranslateIntegrationForTerm');
                return '';
            }
            //if the value is number, find the rfc value of it
            if($.isNumeric(source)){
            	source=Editor.data.apps.termportal.idToRfcLanguageMap[source];
            }
            languageSelect = me.renderLanguageSelect('instanttranslate',source);
            if (languageSelect === '') {
                return '';
            }
            html += '<div class="instanttranslate-integration">';
            html += '<span>'+translations['instantTranslateInto']+' </span>';
            html += languageSelect;
            html += '</div>';
            return html;
        },
        
        /**
         * Opens InstantTranslate for the term and languages.
         */
        openInstantTranslate: function($_elSelect) {
            var me = this,
                $_termAttributes = $_elSelect.closest('.term-attributes'),
                $_termData = $_termAttributes.prev('.term-data'),
                text = $_termData.attr('data-term-value'),
                source = $_termData.children('img').attr('title'),
                target = $_elSelect.find('option:selected').val(),
                url = Editor.data.restpath+'instanttranslate',
                params;
            
            // use proposal if exists
            if ($_termData.children('ins.proposal-value-content').length === 1) {
                text = $_termData.children('ins.proposal-value-content')[0].innerText;
            }
            
            source = checkSubLanguage(source);
            target = checkSubLanguage(target);
            params = 'text=' + text + '&source=' + source + '&target=' + target;
            
            console.log('(openInstantTranslate:) url: ' + url +'; params: ' + params);
            window.parent.loadIframe('instanttranslate',url,params);
        },
        
		/**
		 * Append or remove buttons for proposals in the DOM.
		 * Address elements as specific as possible (= avoid long jQuery-selects).
         * @param elements
         * @param id
		 */
        drawProposalButtons: function (elements,id){
        	if(!Editor.data.app.user.isTermProposalAllowed){
				return;
			}
            console.log('drawProposalButtons: ' + elements);
            var me = this,
                $_selectorRemove = false,
                htmlProposalAddIcon     = '<span class="proposal-btn proposal-add ui-icon ui-icon-plus"></span>',
                htmlProposalDeleteIcon  = '<span class="proposal-btn proposal-delete ui-icon ui-icon-trash-b"></span>',
                htmlProposalEditIcon    = '<span class="proposal-btn proposal-edit ui-icon ui-icon-pencil"></span>',
                htmlProposalSaveIcon    = '<span class="proposal-btn proposal-save ui-icon ui-icon-check"></span>',
                htmlProposalCancelIcon  = '<span class="proposal-btn proposal-cancel ui-icon ui-icon-close"></span>',
                $_selectorAdd = false, $_selectorDelete = false, $_selectorEdit =false, $_selectorSave = false, $_selectorCancel = false,
                titleAdd, titleDelete, titleEdit, titleSave, titleCancel,
        		selectedArea='#'+$('#resultTermsHolder ul>.ui-tabs-active').attr('aria-controls');
            
            switch(elements) {
                case 'commentAttributeEditorClosed':
                    $_selectorRemove = $(selectedArea+' .proposal-save').closest('h4');
                    // only editable items can be edited; we can simply switch back to edit-icon
                    $_selectorEdit = $_selectorRemove;
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                case 'attributeEditingOpened':
                    $_selectorRemove = $(selectedArea+' textarea').closest('p').prev('h4');
                    $_selectorSave = $_selectorRemove;
                    $_selectorCancel = $_selectorRemove;
                    titleSave = proposalTranslations['saveProposal'];
                    titleCancel = proposalTranslations['cancelProposal'];
                    break;
                case 'componentEditorClosed':
                    $_selectorRemove = $(selectedArea+' .proposal-save').parent();
                    // only editable items can be edited; we can simply switch back to edit-icon
                    $_selectorEdit = $_selectorRemove;
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                case 'componentEditorOpened':
                    $_selectorRemove = $(selectedArea+' textarea').closest('h3');
                    $_selectorSave = $_selectorRemove;
                    $_selectorCancel = $_selectorRemove;
                    titleSave = proposalTranslations['saveProposal'];
                    titleCancel = proposalTranslations['cancelProposal'];
                    break;
                case 'terms':
                    $_selectorAdd = $(selectedArea+' .term-data.is-new');
                    $_selectorDelete = $(selectedArea+' .term-data.proposable.is-proposal');
                    $_selectorEdit = $(selectedArea+' .term-data.proposable.is-finalized');
                    console.dir($_selectorAdd);
                    console.dir($_selectorDelete);
                    console.dir($_selectorEdit);
                    titleAdd = proposalTranslations['addTermProposal'];
                    titleDelete = proposalTranslations['deleteTermProposal'];
                    titleEdit = proposalTranslations['editTermProposal'];
                    break;
                case 'attribute':
                    //$_selectorAdd = $(selectedArea+' .term-data').next('div');
                    $_selectorDelete = $(selectedArea+' .attribute-data.proposable.is-proposal');
                    $_selectorEdit = $(selectedArea+' .attribute-data.proposable.is-finalized');
                    titleAdd = proposalTranslations['addTermAttributeProposal'];
                    titleDelete = proposalTranslations['deleteTermAttributeProposal'];
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                case 'singleterm':
                	var termHolder=me.$_termTable.find('div[data-term-id="'+id+'"]');
                	$_selectorDelete = termHolder.find('.is-proposal');
                    $_selectorEdit = termHolder.find('.proposable.is-finalized');
                    termHolder.children('.proposal-btn').remove();
                    titleDelete = proposalTranslations['deleteProposal'];
                    titleEdit = proposalTranslations['editProposal'];
                    break;
                default:
                    // e.g. after updateComponent(): show ProposalButtons according to the new state
                    $_selectorDelete = elements.filter('.is-proposal');
                    $_selectorEdit = elements.filter('.proposable.is-finalized');
                    elements.children('.proposal-btn').remove();
                    titleDelete = proposalTranslations['deleteProposal'];
                    titleEdit = proposalTranslations['editProposal'];
                    break;
            }
            if($_selectorRemove) {
                $_selectorRemove.removeClass('in-editing');
                $_selectorRemove.children('.proposal-btn').remove();
            }
            if($_selectorAdd){
            	$_selectorAdd.each(function() {
            		if ($( this ).children('.proposal-btn.proposal-add').length === 0) {
            			$( this ).append(htmlProposalAddIcon);
            			$( this ).find('.proposal-add').prop('title', titleAdd);
            		}
            	});
            }
            if($_selectorEdit){
            	$_selectorEdit.each(function() {
            		if ($( this ).children('.proposal-btn.proposal-edit').length === 0) {
            			$( this ).append(htmlProposalEditIcon);
            			$( this ).children('.proposal-edit').prop('title', titleEdit);
            		}
            	});
            }
            if($_selectorDelete){
            	$_selectorDelete.each(function() {
            		if ($( this ).children('.proposal-btn.proposal-delete').length === 0) {
            			$( this ).append(htmlProposalDeleteIcon);
            			$( this ).children('.proposal-delete').prop('title', titleDelete);
            		}
            	});
            }
            if ($_selectorSave) {
                $_selectorSave.addClass('in-editing');
                $_selectorSave.append(htmlProposalSaveIcon);
                $_selectorSave.children('.proposal-save').prop('title', titleSave + ' [CTRL+S]');
            }
            if ($_selectorCancel) {
                $_selectorSave.addClass('in-editing');
                $_selectorCancel.append(htmlProposalCancelIcon);
                $_selectorCancel.children('.proposal-cancel').prop('title', titleCancel + ' [ESC]');
            }
        },
		
		/***
		 * Check the term status icon in the term attributes.
		 * Return the image html if such an attribute is found
		 * @param term
		 * @returns
		 */
		checkTermStatusIcon:function(term){
		    var retVal = '', 
		    	attributes=term.attributes,
		        status = 'unknown', 
		        map = Editor.data.termStatusMap,
		        labels = Editor.data.termStatusLabel,
		        label;
		    $.each(attributes, function (i, attr) {
		        var statusIcon=Attribute.getAttributeValue(attr),
		        	cmpStr='<img src="';
		        if(statusIcon && statusIcon.slice(0, cmpStr.length) === cmpStr){
		            retVal+=statusIcon;
		        }
		    });
		    if(map[term.termStatus]) {
		        status = map[term.termStatus];
		    }
		    //FIXME encoding of the string!
		    label = labels[status]+' ('+term.termStatus+')';
		    retVal += ' <img src="' + moduleFolder + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'">';
		    return retVal;
		},
        
        /**
         * "reset" data for proposing a new Term
         */
        resetNewTermData: function() {
            var me = this;
            // "default":
            me.newTermAttributes = Attribute.renderNewTermAttributes();         // currently just an empty Array
            me.newTermCollectionId = null;                                      // will be selected by user (if not already filtered)
            console.log('resetNewTermData => newTermCollectionId: ' + me.newTermCollectionId);
            me.newTermGroupId = null;                                           // will be set from result's select-list
            me.newTermLanguageId = null;                                        // will be selected by user (or set according to search without result)
            me.newTermRfcLanguage = null;                                       // (set always with newTermLanguageId!) 
            me.newTermName = proposalTranslations['addTermProposal'] + '...';   // (or set according to search without result)
            me.newTermTermEntryId = null;                                       // will be set by selecting a search-result (if not given => new TermEntry will be created)
            // if a search has no result:
            if (!isTermProposalFromInstantTranslate && me.$_searchErrorNoResults.is(':visible')) {
                me.newTermLanguageId = $('#language').val();
                me.newTermRfcLanguage = $('#language option:selected').text();
                me.newTermName = $('#search').val();
            }
            // if proposal from InstantTranslate:
            if (isTermProposalFromInstantTranslate) {
                me.newTermLanguageId = instanttranslate.langProposal;
                me.newTermRfcLanguage = instanttranslate.langProposal; // TODO: langProposal is not the same as languageId; check input from TermPortal!!
                me.newTermName = instanttranslate.textProposal;
            }
            
            console.log('After reset data for proposing a new Term:');
            console.log('- attributes: ' + JSON.stringify(me.newTermAttributes));
            console.log('- collectionId: ' + me.newTermCollectionId);
            console.log('- groupId: ' + me.newTermGroupId);
            console.log('- languageId: ' + me.newTermLanguageId);
            console.log('- rfcLanguage: ' + me.newTermRfcLanguage);
            console.log('- termName: ' + me.newTermName);
            console.log('- termTermEntryId: ' + me.newTermTermEntryId);
        },
        
        /**
         * Returns term data for creating a new term.
         * @param {Object} termsData
         */
        getNewTermData: function(termsData) {
            var me = this,
                newTermData = {};
            //if the collection id is not set, set it from the terms data
            if((me.newTermCollectionId === undefined || me.newTermCollectionId==null) && (termsData && termsData.length>0)) {
                me.newTermCollectionId = termsData[0].collectionId;
                me.newTermTermEntryId = termsData[0].termEntryId;
                me.newTermGroupId=termsData[0].groupId;
            }
            console.log('getNewTermData; currently known: ');
            console.log('- attributes: ' + JSON.stringify(me.newTermAttributes));
            console.log('- collectionId: ' + me.newTermCollectionId);
            console.log('- groupId: ' + me.newTermGroupId);
            console.log('- languageId: ' + me.newTermLanguageId);
            console.log('- rfcLanguage: ' + me.newTermRfcLanguage);
            console.log('- termName: ' + me.newTermName);
            console.log('- termTermEntryId: ' + me.newTermTermEntryId);
            newTermData = {0: {
                'attributes': me.newTermAttributes,
                'collectionId': me.newTermCollectionId,
                'definition': '',
                'desc': '',
                'groupId': me.newTermGroupId,
                'termEntryId': me.newTermTermEntryId,
                'label': '',
                'languageId': me.newTermLanguageId,
                'proposal': null,
                'term': me.newTermName,
                'termId': null,
                'termStatus': null,
                'value': null
            }};
            return newTermData;
        },
        
        /**
         * Draw a select for choosing a Language for a new Term.
         * When a Language is selected, the corresponding flag is added to the skeleton
         * for the editable new term and it is shown again.
         */
        drawLanguageSelectForNewTerm: function() {
        	if(!Editor.data.app.user.isTermProposalAllowed){
				return;
			}
            console.log('drawLanguageSelectForNewTerm');
            var me = this,
                languageSelectContainer = '<div id="languageSelectContainer" class="skeleton"></div>',
                languageSelectHeader,
                $_termSkeleton = me.$_termTable.find('.is-new');
            if( me.$_termTable.find('#languageSelectContainer').length > 0 ) {
                return;
            }
            languageSelectHeader = '<p>'+proposalTranslations['chooseLanguageForTermEntry']+':</p>';
            me.$_termTable.prepend(languageSelectContainer);
            $('#languageSelectContainer').prepend(languageSelectForNewTerm).prepend(languageSelectHeader);
            $_termSkeleton.next().hide();
            $_termSkeleton.hide();
            // TODO: first mouseover causes "jquery.js:6718 GET http://translate5.local/editor/undefined 404 (Not Found)"
            $('#languageSelectContainer .chooseLanguage').iconselectmenu({
                select: function() {
                    if ($(this).val() === 'none') {
                        return false;
                    }
                    me.newTermLanguageId = $(this).val();
                    me.newTermRfcLanguage = $('#languageSelectContainer .chooseLanguage option:selected').val();
                    $('#languageSelectContainer').remove();
                    $_termSkeleton.next().show();
                    $_termSkeleton.show();
                    me.drawLanguageFlagForNewTerm();
                    $_termSkeleton.find('.proposal-add').click();
                    $_termSkeleton.find('textarea').focus();
                }
            }).iconselectmenu('menuWidget').addClass('ui-menu-icons flag');
        },
        
        /**
         * 
         */
        drawLanguageFlagForNewTerm: function () {
        	if(!Editor.data.app.user.isTermProposalAllowed){
				return;
			}
            var me = this,
                flag = getLanguageFlag(me.newTermRfcLanguage),
                $_termSkeleton = me.$_termTable.find('.is-new'); // TODO: use DOM-cache
            console.log('drawLanguageFlagForNewTerm ('+me.newTermLanguageId+' / '+me.newTermRfcLanguage+')');
            $_termSkeleton.find('img').remove();
            $_termSkeleton.find('span.noFlagLanguage').remove();
            $_termSkeleton.children('span').first().after(flag);
        },
        
        /**
         * Draw a select for choosing a TermCollection. The list only offers
         * collections that are currently filtered (or all, if none is filtered).
         * When a collection is selected, the form for the editable new term is drawn.
         */
        drawFilteredTermCollectionSelect: function() {
            console.log('drawFilteredTermCollectionSelect');
            var me = this,
                filteredCollections = getFilteredCollections(),
                filteredCollectionId,
                collectionSelectHeader,
                collectionSelect,
                collectionSelectOptions = '';
            
            //filtered collection is empty it can means that there is only one collection available and the select is not visible
            if(filteredCollections.length === 0){
            	//collectionIds is global variable for all available collections to the current user
            	filteredCollections=Editor.data.apps.termportal.collectionIds;
            }
            
            if (filteredCollections.length === 1) {
                me.newTermCollectionId = filteredCollections[0];
                console.log('drawFilteredTermCollectionSelect => newTermCollectionId: ' + me.newTermCollectionId);
                me.drawTermTable();
                me.$_termTable.find('.proposal-add')[0].click();
                return;
            }
            console.log('choose collection (filteredCollections: ' + filteredCollections.length + ')');
            me.emptyResultTermsHolder(false);
            me.$_resultTermsHolder.show();
            collectionSelectHeader = '<h3>'+proposalTranslations['chooseTermcollectionForTermEntry']+':</h3>';
            if (filteredCollections.length === 0) {
                $('#collection option').each(function() {
                    if ($(this).val() !== 'none') {
                        filteredCollections.push($(this).val());
                    }
                });
            }
            for (var i = 0; i < filteredCollections.length; i++) {
                filteredCollectionId = filteredCollections[i];
                collectionSelectOptions += '<option value="'+filteredCollectionId+'">'+collectionsNames[filteredCollectionId]+'</option>';
            }
            collectionSelect = '<select name="chooseCollection" id="chooseCollection">'+collectionSelectOptions+'</select>';
            me.$_termCollectionSelect.append(collectionSelectHeader).append(collectionSelect).show();
            $('#chooseCollection').selectmenu({
                select: function() {
                    me.newTermCollectionId = $(this).val();
                    console.log('chooseCollection selectmenu => newTermCollectionId: ' + me.newTermCollectionId);
                    me.$_termCollectionSelect.empty().hide();
                    me.drawTermTable();
                    var proposalAdd=me.$_termTable.find('.proposal-add');
                    if(proposalAdd.length>0){
                    	proposalAdd[0].click();
                    }
                }
            });
        },
        
        /**
         * Empty the resultTermsHolder.
         * If keepAttributes is set and set to true, the attributes-Tab will not be emptied.
         * @params {Boolean} keepAttributes
         */
        emptyResultTermsHolder: function (keepAttributes) {
            var me = this;
            me.$_termTable.empty();
            me.$_termCollectionSelect.empty();
            if(typeof keepAttributes !== 'undefined' && keepAttributes === true) {
                return;
            }
            me.$_termEntryAttributesTable.empty();
        },
        
        /***
         * Init the instant translate select html to jquery component
         * This will also register the onselect handler
         */
        initInstantTranslateSelect:function(){
        	var me=this;
            $('.instanttranslate-integration .chooseLanguage')
            .iconselectmenu({
                select: function() {
                    if ($(this).val() === 'none') {
                        return false;
                    }
                    me.openInstantTranslate($(this));
                }
            }).iconselectmenu('menuWidget').addClass('ui-menu-icons flag');
        },

        /***
		 * On remove proposal click handler
		 */
		onDeleteTermClick:function(event){
			var me = event.data.scope,
                $element=$(this),
				$parent=$element.parents('h3[data-term-id]');
			
			if(parent.length === 0){
				return;
			}

            if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
                return false;
            }
			
			var yesCallback=function(){
				//ajax call to the remove proposal action
				var me=event.data.scope,
					url=Editor.data.termportal.restPath+'term/{ID}/removeproposal/operation'.replace('{ID}',$parent.attr("data-term-id")),
					termEntryId=$parent.data('termEntryId') || me.newTermTermEntryId;

				$.ajax({
			        url: url,
			        dataType: 'json',	
			        type: 'POST',
			        success: function(result){
			        	me.reloadTermEntry=true;
			        	//reload the termEntry when the term is removed
			        	if(!result.rows || result.rows.length === 0){
			        		me.findTermsAndAttributes(termEntryId);
			        		return;
			        	}
			        	me.onTermProposalRemove($parent,result.rows);
			        }
			    });
			};
			
			var yesText=proposalTranslations['Ja'],
				noText=proposalTranslations['Nein'],
				buttons={
				};
			
			buttons[yesText]=function(){
	            $(this).dialog('close');
	            yesCallback();
			};
			buttons[noText]=function(){
				$(this).dialog('close');
			};
			// Define the Dialog and its properties.
		    $('<div></div>').dialog({
		        resizable: false,
		        modal: true,
		        title: proposalTranslations['deleteTermProposal'],
		        height: 250,
		        width: 400,
		        buttons:buttons
		    }).text(proposalTranslations['deleteTermProposalMessage']);
		},
        
        /***
         * On add term-entry icon click handler.
         * (Adding a term-entry is done via creating a new term-proposal.)
         */
        onAddTermEntryClick: function(event){
            console.log('onAddTermEntryClick');

            if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
                return false;
            }
        	
            var me = event.data.scope,
                filteredCollections = getFilteredCollections();
            
            //filtered collection is empty it can means that there is only one collection available and the select is not visible
            if(filteredCollections.length === 0){
            	//collectionIds is global variable for all available collections to the current user
            	filteredCollections=Editor.data.apps.termportal.collectionIds;
            }
            
            console.log('1034 onAddTermEntryClick => resetNewTermData');
            me.resetNewTermData();
            me.$_searchTermsSelect.find('.ui-state-active').removeClass('ui-state-active');
            
            
            //focus on the term tab
            $('#resultTermsHolder').tabs({active:0});
            
            // If the collection is known, we can start right away...
            if (filteredCollections.length === 1) {
                me.newTermCollectionId = filteredCollections[0];
                console.log('onAddTermEntryClick => newTermCollectionId: ' + me.newTermCollectionId);
                me.drawTermTable();
                me.$_termTable.find('.proposal-add')[0].click();
                return;
            }
            // ... otherwise the user must select a collection first:
            me.drawFilteredTermCollectionSelect();
        },
        
        /***
         * On add term click handler
         */
        onAddTermClick:function(event){
            // TODO: is called twice after coming from InstantTranslate-Term-Proposal (goes to Component Editor and comes back again)
            console.log('onAddTermClick');
            var me = event.data.scope,
                $_termSkeleton = me.$_termTable.find('.is-new'), // TODO: use DOM-cache
                $termEditorSpan = $_termSkeleton.find('[data-editable]'),
                $termEditorHolder = me.$_termTable.find('div[data-term-id="-1"]');

            if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
                return false;
            }
        	
            // if language is not set yet, draw language-select first...
            if (me.newTermLanguageId === null) {
                me.drawLanguageSelectForNewTerm();
                return;
            }
            
            // ... otherwise draw language-flag and open form for editing:
            me.drawLanguageFlagForNewTerm();
            ComponentEditor.addTermComponentEditor($termEditorSpan,$termEditorHolder);
        },
        
        /***
         * On edit term click handler
         */
        onEditTermClick:function(event){
            var me = event.data.scope,
                $element=$(this),
                search = $element.parent().find('span[data-editable]'),
                $termAttributeHolder = me.$_termTable.find('div[data-term-id="' + search.data('id') + '"]');
            console.log('onEditTermClick');
            event.stopPropagation();
            ComponentEditor.addTermComponentEditor(search,$termAttributeHolder);
        },
        
        /***
         * Find the all opened editors and close them.
         * TODO: if requested add dialog box
         */
        cancelPendingChanges:function(termHeader){
        	if(termHeader.length<1){
        		return true;
        	}
        	//is in the header valid editor
        	if(termHeader.has('textarea').length > 0){
        		//close the opened term editor
        		termHeader.find('span.proposal-cancel').mouseup();
        	}

            if (!ComponentEditor.isCommmentAttributeRequirementMet()) {
                return false;
            }
        	
        	//render the cancel icons for the attributes
        	this.drawProposalButtons('attributeEditingOpened')
        	//find all attributes with cancel button
        	var $editors=this.$_termTable.find('span.proposal-cancel');
        	//trigger the cancel button, and with this cancel the editor
	    	$editors.each(function(index,editor) {
	    		$(editor).mouseup();
	        });
	    	return true;
        },
        
        /***
         * After the term is removed handler.
         * This will update the new term content with the new data
         * @param $termParent : the acordion h3 term header
         * @param term: the term data with attributes
         */ 
        onTermProposalRemove:function($termParent,term){
        	//the term proposal is removed, render the initial term proposable content
        	var me=this,
        		renderData=me.renderTermData(term),
        		ins=$termParent.find('ins'),
        		$termAttributeHolder = me.$_termTable.find('div[data-term-id="' + term.id + '"]'),
        		termAttributeContainerData=Attribute.getTermAttributeContainerRenderData(term);
        		
        	ins.replaceWith(renderData);
        	$termParent.find('del').empty();

        	$termParent.switchClass('is-proposal','is-finalized');
        	
        	$termAttributeHolder.empty();
        	//replace the term attribute container with the fresh data
        	$termAttributeHolder.append(termAttributeContainerData);
        	
    		me.$_termTable.accordion('refresh');
			me.initInstantTranslateSelect();
			
			me.drawProposalButtons($termParent);
        	me.drawProposalButtons('singleterm',term.id);
        },
        
    	/***
         * Get term data from the cache
         */
        getTermDataFromCache:function(termEntryId,termId){
        	var me=this,
        		data=[];
        	
        	if(!me.termGroupsCache[termEntryId] || !me.termGroupsCache[termEntryId].rows || !me.termGroupsCache[termEntryId].rows[me.KEY_TERM_ATTRIBUTES]){
        		return data;
        	}
        	data=me.termGroupsCache[termEntryId].rows[me.KEY_TERM_ATTRIBUTES];
    		for(var i=0;i<data.length;i++){
        		var term=data[i];
        		//the field value in cache is the termid
        		if(term.value==termId){
        			return term;
        		}
        	}
    		return [];
        },
        
        /***
         * Rerfresh the term attribute container with the fresh data from the database
         */
        refreshTermAttributeContent:function(term){
			//for the new term, term attribute render data is required
			var termRflLang=(term.attributes && term.attributes[0].language!=undefined) ? term.attributes[0].language : '',
				attributeRenderData=Attribute.renderTermAttributes(term,termRflLang),
				$termHeader=Term.getTermHeader(term.termId),
				instantTranslateInto=Term.renderInstantTranslateIntegrationForTerm(term.language),
				$termAttributeHolder=$termHeader.next('div[data-term-id]');

			$termAttributeHolder.attr('data-term-id', term.termId);
			$termAttributeHolder.attr("data-groupid",term.groupId);
			$termAttributeHolder.attr("data-termEntryId",term.termEntryId);
			$termAttributeHolder.empty();
			$termAttributeHolder.append(attributeRenderData);
			
			//render the instant translate into select
			if(instantTranslateInto){
				$termAttributeHolder.prepend(instantTranslateInto);
				Term.initInstantTranslateSelect();
			}
        },
        
        /***
         * Find the current active term in the accordion
         */
        findActiveTermHeader:function(){
        	var me=this,
        		activeTermHeader= me.$_termTable.find('h3.ui-accordion-header-active');
        	return activeTermHeader.length>0 ? activeTermHeader : null;
        }
};

Term.init();
