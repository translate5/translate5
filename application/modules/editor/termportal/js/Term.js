const Term={
        
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
		
		KEY_TERM:"term",
		KEY_TERM_ATTRIBUTES:"termAttributes",
		
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
		},
		
		initEvents:function(){
			var me=this;
			
            // Search Results
			me.$_searchTermsSelect.on( "selectableselected",{scope:me},me.onSelectSearchTerm);
		    // FIXME: why is this triggered twice sometimes (with attr('data-value') = "undefined" in the second)
			
			// Term-Entries
	        me.$_resultTermsHolderHeader.on('click', ".proposal-add",{scope:me},me.onAddTermEntryClick);
	        me.$_searchTermsHelper.on('click', ".proposal-add",{scope:me},me.onAddTermEntryClick);

            // Terms
            // - Icons
            me.$_termTable.on('click', ".term-data.proposable .proposal-add",{scope:me, reference:'icon'},me.onAddTermClick);
            me.$_termTable.on('click', ".term-data.proposable .proposal-delete",{scope:me, reference:'icon'},me.onDeleteTermClick);
            me.$_termTable.on('click', ".term-data.proposable .proposal-edit",{scope:me, reference:'icon'},me.onEditTermClick);
            // - Content
            me.$_termTable.on('click', '.term-data.proposable.is-new',{scope:me, reference:'content'},me.onAddTermClick);
            me.$_termTable.on('click', '.term-data.proposable.is-new [data-editable][data-type="term"]',{scope:me, reference:'content'},me.onAddTermClick);
            me.$_termTable.on('click', '.term-data.proposable.is-finalized [data-editable][data-type="term"]',{scope:me, reference:'content'},me.onEditTermClick);
            
            // Terms-Attributes: see Attribute.js
		},
        
        /***
         * On searchTermsSelect selectableselected handler
         */
        onSelectSearchTerm: function(event, ui) {
            var me = event.data.scope,
                $_selected = $(ui.selected);
            // data for proposing a new Term
            me.resetNewTermData();
            me.newTermCollectionId = $_selected.attr('data-collectionid');
            me.newTermTermEntryId = $_selected.attr('data-termentryid');
            // show Terms and Attributes
            me.findTermsAndAttributes($_selected.attr('data-value'));
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
			
            if (searchString == '') {
                return;
            }
            
            clearResults();
			me.resetNewTermData();
            
			if(!lng){
				lng=$("input[name='language']:checked").val();
			}
			console.log("searchTerm() for: " + searchString);
			console.log("searchTerm() for language: " + lng);
			me.searchTermsResponse=[];  
			$.ajax({
				url: Editor.data.termportal.restPath+"termcollection/search",
				dataType: "json",
				type: "POST",
				data: {
					'term':searchString,
					'language':lng,
					'collectionIds':collectionIds,
                    'processStats':processStats,
					'disableLimit':me.disableLimit
				},
				success: function(result){
					me.searchTermsResponse=result.rows[me.KEY_TERM];
					if(successCallback && me.searchTermsResponse.length>1){
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
			var me=this;
            clearResults();
			if(!me.searchTermsResponse || me.searchTermsResponse.length === 0){
                
                console.log("fillSearchTermSelect: nichts gefunden");
                
                // show/hide helper: errors, warning, skeleton...
                if (isTermProposalFromInstantTranslate) {
                    me.$_searchWarningNewSource.text(proposalTranslations['newSourceForSaving'] + ': ' + instanttranslate.textSource);
                    me.$_searchWarningNewSource.show();
                    me.$_searchTermsHelper.find('.skeleton').hide();
                } else {
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
                    $("#searchTermsHelper .proposal-add").click();
                } else {
                    me.$_resultTermsHolder.hide();
                }
                
                // "reset", is valid only once (= when coming from TermPortal)
                if(isTermProposalFromInstantTranslate) {
                    isTermProposalFromInstantTranslate = false;
                }
                
				return;
			}
            
			
			console.log("fillSearchTermSelect: " + me.searchTermsResponse.length + " Treffer");
			
			console.log("fillSearchTermSelect: me.disableLimit"); // FIXME: do we need this here?
				
			//if only one record, find the attributes and display them
			if(me.searchTermsResponse.length===1){
	            me.newTermCollectionId = me.searchTermsResponse[0].collectionId;
                me.newTermGroupId = me.searchTermsResponse[0].groupId;
	            me.newTermTermEntryId = me.searchTermsResponse[0].termEntryId;
				console.log("fillSearchTermSelect: only one record => find the attributes and display them");
				me.findTermsAndAttributes(me.searchTermsResponse[0].groupId);
			}
			
			if(me.searchTermsResponse.length>0){
				showFinalResultContent();
			}
			
			if(!me.$_searchTermsSelect.is(":visible")){
				return;
			}
			
			if(me.searchTermsResponse.length > 1){
			    me.$_resultTermsHolder.hide();
			}
			
			//fill the term component with the search results
			for(var i=0;i<me.searchTermsResponse.length;i++){
				var item=me.searchTermsResponse[i];
				me.$_searchTermsSelect.append( // FIXME; this takes too long
						$('<li>').attr('data-value', item.groupId)
						         .attr('data-collectionid', item.collectionId)
                                 .attr('data-termentryid', item.termEntryId)
						         .attr('class', 'ui-widget-content search-terms-result').append(
								$('<div>').attr('class', 'ui-widget').append(item.label)
						));
			}
			
			if (me.$_searchTermsSelect.hasClass('ui-selectable')) {
				me.$_searchTermsSelect.selectable("destroy");
			}
			
			me.$_searchTermsSelect.selectable();
			
			var searchTermsSelectLi=me.$_searchTermsSelect.find('li');
			
			searchTermsSelectLi.mouseenter(function() {
				$(this).addClass('ui-state-hover');
			});
			searchTermsSelectLi.mouseleave(function() {
				$(this).removeClass('ui-state-hover');
			});
			me.$_searchTermsSelect.on( "selectableselecting", function( event, ui ) {
				$(ui.selecting).addClass('ui-state-active');
			});
			me.$_searchTermsSelect.on( "selectableunselecting", function( event, ui ) {
				$(ui.unselecting).removeClass('ui-state-active');
			});
			me.$_searchTermsSelect.on( "selectableselected", function( event, ui ) {
				$(ui.selected).addClass('ui-state-active');
			});
			
			if(me.searchTermsResponse.length==1){
				me.$_searchTermsSelect.find("li:first-child").addClass('ui-state-active').addClass('ui-selected');
			}
			
			// "reset" search form
			$("#search").autocomplete( "search", $("#search").val('') );
		},
		
		/***
		 * Find all terms and terms attributes for the given term entry id (groupId)
		 * @param termGroupid
		 * @returns
		 */
		findTermsAndAttributes:function(termGroupid){
		    if (termGroupid === undefined) {
		        return; // TODO (quick & dirty - the REAL problem is: this should not happen at all!!)
		    }
			var me=this;
		    console.log("findTermsAndAttributes() for: " + termGroupid);
		    Attribute.languageDefinitionContent=[];
            
            me.$_termCollectionSelect.hide();
            
		    //check the cache
            if (me.reloadTermEntry) {
                me.termGroupsCache = []; // FIXME: better remove only the groupId's items from the cache instead of setting reloadTermEntry to true!
                me.reloadTermEntry=false; //reset term entry reload flag
            }
		    if(me.termGroupsCache[termGroupid]){
		    	TermEntry.drawTermEntryAttributes(me.termGroupsCache[termGroupid].rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
		        me.drawTermTable(me.termGroupsCache[termGroupid].rows[me.KEY_TERM_ATTRIBUTES]);
		        return;
		    }
		    
		    $.ajax({
		        url: Editor.data.termportal.restPath+"termcollection/searchattribute",
		        dataType: "json",
		        type: "POST",
		        data: {
		            'groupId':termGroupid
		        },
		        success: function(result){
		            //store the results to the cache
		            me.termGroupsCache[termGroupid]=result;
		            
		            TermEntry.drawTermEntryAttributes(result.rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);

		            me.drawTermTable(result.rows[me.KEY_TERM_ATTRIBUTES]);
		        }
		    })
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
                $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                $_filteredClients = $('#searchFilterTags input.filter.client'),
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1), // TODO: show collection also when adding a TermEntry?
                currentItem,
                currentItemNr;
            
            // ------- "reset" term-table -------
            me.emptyResultTermsHolder(true);
            me.$_resultTermsHolder.show();
            
            // -------render skeleton for new terms -------
            html += me.renderNewTermSkeleton();
            
            // -------render term-data -------
		    $.each(termsData, function (i, term) {
		        html += me.renderTerm(term,showInfosForSelection);
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
		            heightStyle: "content",
		            beforeActivate: function( event, ui ) {
                        if ($(event.toElement).hasClass('proposal-delete')) {
                            // Clicking the delete-icon itself does not need to open the Term.
                            event.preventDefault();
                        }
		                if (ui.newHeader.length === 0 && ui.oldHeader.has("textarea").length > 0) {
                            // Term in header is opened for editing; don't close the panel.
                            event.preventDefault();
                        }
		            },
                    activate: function( event, ui ) {
                        if (ui.newHeader.length === 0 && ui.oldHeader.has("textarea").length > 0) {
                            // Panel is already opened, don't close it after click on Term in header for editing.
                            currentItem = ui.oldHeader[0];
                            currentItemNr = me.$_termTable.children('h3').index(currentItem);
                            me.$_termTable.accordion('option', 'active', currentItemNr);
                        }
                    }
		        });
		    }
		    
		    //FIXME: workaround, check me later
		    $.ui.accordion.prototype._keydown = function( event ) {
		        var keyCode = $.ui.keyCode;

		        if (event.keyCode == keyCode.SPACE) {
		            return;
		        }
		    };
		    
		    //find the selected item form the search result and expand it
		    $.each($("#searchTermsSelect li"), function (i, item) {
		        if($(item).hasClass('ui-state-active')){
		        	me.$_termTable.accordion({
		                active:false
		            });
		            
		            $.each($("#termTable h3"), function (i, termitem) {
		                if(termitem.dataset.termValue === item.textContent){
		                	me.$_termTable.accordion({
		                        active:i
		                    });
		                }
		            });
		            
		        }
		    });
            
		    setSizesInFinalResultContent();

            $( ".instanttranslate-integration .chooseLanguage" )
                .iconselectmenu({
                    select: function() {
                        if ($(this).val() == 'none') {
                            return false;
                        }
                        me.openInstantTranslate($(this));
                    }
                })
                .iconselectmenu( "menuWidget")
                .addClass( "ui-menu-icons flag" );
            
            // -------proposal-buttons -------
            me.drawProposalButtons('terms-attribute');
            me.drawProposalButtons('terms');
        },
        
        /***
         * Render html for adding new terms (= visible as "skeleton").
         * @returns
         */
        renderNewTermSkeleton:function(){
            var me = this,
                html = '',
                $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                $_filteredClients = $('#searchFilterTags input.filter.client'),
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1); // TODO: show collection also when adding a TermEntry?,
                newTermData = me.getNewTermData();
            html += me.renderTerm(newTermData[0],showInfosForSelection);
            return html;
        },
        
        /**
         * Render html for term by given term.
         * 
         * @param {Object} term
         * @param {Boolean} showInfosForSelection
         * @returns {String}
		 */
		renderTerm: function (term, showInfosForSelection) {
            var me = this,
                termAttributesHtmlContainer = [],
                termRflLang=term.attributes[0]!=undefined ? term.attributes[0].language : '',
                rfcLanguage = getLanguageFlag(termRflLang),
                statusIcon=me.checkTermStatusIcon(term), //check if the term contains attribute with status icon
                infosForSelection = '',
                filteredCientsNames = [],
                filteredCollectionsNames = [],
                clientId,
                clientName,
                isProposal,
                proposable = (term.proposable !== false) ? ' proposable' : '', // = does the user have the rights to handle proposals for this term?,
                instantTranslateIntegrationForTerm;
            
            // "is-proposal" can be ... 
            // ... a proposal for a term that already existed (term.proposal = "xyz")
            // ... or a proposal for a new term (term.proposal = null, but processStatus is "unprocessed")
            isProposal = ' is-finalized'; 
            if (term.proposal !== null || term.processStatus === "unprocessed") {
                isProposal = ' is-proposal';
            }
            
            // for new-term-skeleton
            if (term.termId === null) {
                isProposal = ' is-new';
                statusIcon = '';
                term.termId = -1;
            }
            
            //draw term header
            termAttributesHtmlContainer.push('<h3 class="term-data'+proposable + isProposal+'" data-term-value="'+term.term+'" data-term-id="'+term.termId+'" data-groupid="'+term.groupId+'">');
            
            
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
            //(display only those that are selected for filtering)
            if (showInfosForSelection) {
                infosForSelection = [];
                clientsForCollection = collectionsClients[term.collectionId];
                for (i = 0; i < clientsForCollection.length; i++) {
                    clientId = clientsForCollection[i];
                    clientName = clientsNames[clientId];
                    if ($("#searchFilterTags").tagit("assignedTags").indexOf(clientName) != -1) {
                        filteredCientsNames.push(clientName);
                    }
                }
                if (filteredCientsNames.length > 0) {
                    infosForSelection.push(filteredCientsNames.join(', '));
                }
                collectionName = collectionsNames[term.collectionId];
                if ($("#searchFilterTags").tagit("assignedTags").indexOf(collectionName) != -1) {
                    infosForSelection.push(collectionName);
                }
                termAttributesHtmlContainer.push('<span class="selection-infos">['+infosForSelection.join('; ')+']</span>');
            }
            
            termAttributesHtmlContainer.push('</h3>');
            
            //draw term attributes
            termAttributesHtmlContainer.push('<div data-term-id="'+term.termId+'" data-collection-id="'+term.collectionId+'" class="term-attributes">');
            if (term.termId != -1) {
                instantTranslateIntegrationForTerm = me.renderInstantTranslateIntegrationForTerm(termRflLang);
                termAttributesHtmlContainer.push(instantTranslateIntegrationForTerm);
            }
            termAttributesHtmlContainer.push(Attribute.renderTermAttributes(term.attributes,termRflLang));
            termAttributesHtmlContainer.push('</div>');
            
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
            if (termData.processStatus === "unprocessed" && !termData.proposal) {
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
        renderLanguageSelect: function (languagesFor, source=null) {
            var languageSelect = '',
                languageSelectDescription = '<option value="none">'+proposalTranslations['selectLanguage']+'</option>',
                languageSelectOptions = '',
                flag,
                lang,
                targetsForSources,
                targets,
                availableLanguages;
            
            switch(languagesFor) {
                case "instanttranslate":
                    // offer target-Languages as available in InstantTranslate for the term's source
                    source = checkSubLanguage(source);
                    targetsForSources = Editor.data.instanttranslate.targetsForSources;
                    if (source in targetsForSources){
                        targets = targetsForSources[source];
                        for (var i=0; i < targets.length; i++) {
                            target = targets[i];
                            flag = getLanguageFlag(target);
                            languageSelectOptions += '<option value="'+target+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+target+'</option>';
                        }
                    }
                    break;
                case "term":
                    // list the languages of the TermPortal first...
                    languageSelectOptions += '<option value="none" disabled>-- '+translations['TermPortalLanguages']+': --</option>';
                    $("#language option").each(function() {
                        if ($(this).val() != 'none') {
                            flag = getLanguageFlag($(this).text());
                            languageSelectOptions += '<option value="'+$(this).val()+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+$(this).text()+'</option>';
                        }
                    });
                    // ... and then list ALL languages that are available in translate5
                    languageSelectOptions += '<option value="none" disabled>-- '+translations['AllLanguagesAvailable']+': --</option>';
                    availableLanguages = Editor.data.availableLanguages;
                    for (var i=0; i < availableLanguages.length; i++) {
                        lang = availableLanguages[i];
                        flag = getLanguageFlag(lang.value);
                        languageSelectOptions += '<option value="'+lang.id+'" data-class="flag" data-style="background-image: url(\''+$(flag).attr('src')+'\') !important;">'+lang.text+'</option>';
                    }
                    break;
            }
            
            if (languageSelectOptions != '') {
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
            languageSelect = me.renderLanguageSelect('instanttranslate',source);
            if (languageSelect == '') {
                return '';
            }
            html += '<div class="instanttranslate-integration">';
            html += '<span>'+translations['instantTranslateInto']+' </span>';
            html += me.renderLanguageSelect('instanttranslate',source);
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
                target = $_elSelect.find("option:selected").text(),
                url = Editor.data.restpath+'instanttranslate',
                params;
            
            // use proposal if exists
            if ($_termData.children('ins.proposal-value-content').length === 1) {
                text = $_termData.children('ins.proposal-value-content')[0].innerText;
            }
            
            source = checkSubLanguage(source);
            target = checkSubLanguage(target);
            params = "text="+text+"&source="+source+"&target="+target;
            
            console.log('(openInstantTranslate:) url: ' + url +'; params: ' + params);
            window.parent.loadIframe('instanttranslate',url,params);
        },
        
		/**
		 * Append or remove buttons for proposals in the DOM.
		 * Address elements as specific as possible (= avoid long jQuery-selects).
         * @param elements
		 */
        drawProposalButtons: function (elements){
            var me = this,
                $_selectorRemove = false,
                htmlProposalAddIcon     = '<span class="proposal-btn proposal-add ui-icon ui-icon-plus"></span>',
                htmlProposalDeleteIcon  = '<span class="proposal-btn proposal-delete ui-icon ui-icon-trash-b"></span>',
                htmlProposalEditIcon    = '<span class="proposal-btn proposal-edit ui-icon ui-icon-pencil"></span>',
                htmlProposalSaveIcon    = '<span class="proposal-btn proposal-save ui-icon ui-icon-check"></span>',
                $_selectorAdd = false, $_selectorDelete = false, $_selectorEdit = false, $_selectorSave = false,
                titleAdd, titleDelete, titleEdit, titleSave;
            switch(elements) {
                case "commentAttributeEditorClosed":
                    $_selectorRemove = $('#termTable .proposal-save').closest('h4');
                    // only editable items can be edited; we can simply switch back to edit-icon
                    $_selectorEdit = $_selectorRemove;
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                case "attributeComponentEditorOpened":
                case "commentAttributeEditorOpened":
                    $_selectorRemove = $('#termTable textarea').closest('p').prev('h4');
                    $_selectorSave = $_selectorRemove;
                    titleSave = proposalTranslations['saveProposal'];
                    break;
                case "componentEditorClosed":
                // case "attributeComponentEditorClosed" (= is handled via saveComponentChange(), too)
                    $_selectorRemove = $('#termTable .proposal-save').parent();
                    // only editable items can be edited; we can simply switch back to edit-icon
                    $_selectorEdit = $_selectorRemove;
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                case "componentEditorOpened":
                    $_selectorRemove = $('#termTable textarea').closest('h3');
                    $_selectorSave = $_selectorRemove;
                    titleSave = proposalTranslations['saveProposal'];
                    break;
                case "terms":
                    $_selectorAdd = $('#termTable .term-data.is-new');
                    $_selectorDelete = $('#termTable .term-data.proposable.is-proposal');
                    $_selectorEdit = $('#termTable .term-data.proposable.is-finalized');
                    titleAdd = proposalTranslations['addTermProposal'];
                    titleDelete = proposalTranslations['deleteTermProposal'];
                    titleEdit = proposalTranslations['editTermProposal'];
                    break;
                case "terms-attribute":
                    //$_selectorAdd = $('#termTable .term-data').next('div');
                    $_selectorDelete = $('#termTable .attribute-data.proposable.is-proposal');
                    $_selectorEdit = $('#termTable .attribute-data.proposable.is-finalized');
                    titleAdd = proposalTranslations['addTermAttributeProposal'];
                    titleDelete = proposalTranslations['deleteTermAttributeProposal'];
                    titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                default:
                    // e.g. after updateComponent(): show ProposalButtons according to the new state
                    $_this = elements;
                    $_selectorDelete = $_this.filter('.is-proposal');
                    $_selectorEdit = $_this.filter('.proposable');
                    $_this.children('.proposal-btn').remove();
                    titleDelete = proposalTranslations['deleteProposal'];
                    titleEdit = proposalTranslations['editProposal'];
                    break;
            }
            if($_selectorRemove) {
                $_selectorRemove.removeClass('in-editing');
                $_selectorRemove.children('.proposal-btn').remove();
            }
            if ($_selectorAdd && $_selectorAdd.children('.proposal-btn.proposal-add').length === 0) {
                $_selectorAdd.append(htmlProposalAddIcon);
                $_selectorAdd.find('.proposal-add').prop('title', titleAdd);
            }
            if ($_selectorEdit && $_selectorEdit.children('.proposal-btn.proposal-edit').length === 0) {
                $_selectorEdit.append(htmlProposalEditIcon);
                $_selectorEdit.children('.proposal-edit').prop('title', titleEdit);
            }
            if ($_selectorDelete && $_selectorDelete.children('.proposal-btn.proposal-delete').length === 0) {
                $_selectorDelete.append(htmlProposalDeleteIcon);
                $_selectorDelete.children('.proposal-delete').prop('title', titleDelete);
            }
            if ($_selectorSave) {
                $_selectorSave.addClass('in-editing');
                $_selectorSave.append(htmlProposalSaveIcon);
                $_selectorSave.children('.proposal-save').prop('title', titleSave);
            }
        },
		
		/***
		 * Check the term status icon in the term attributes.
		 * Return the image html if such an attribute is found
		 * @param term
		 * @returns
		 */
		checkTermStatusIcon:function(term){
		    var retVal="", 
		    	attributes=term.attributes,
		        status = 'unknown', 
		        map = Editor.data.termStatusMap,
		        labels = Editor.data.termStatusLabel,
		        label;
		    $.each(attributes, function (i, attr) {
		        var statusIcon=Attribute.getAttributeValue(attr),
		        	cmpStr='<img src="';
		        if(statusIcon && statusIcon.slice(0, cmpStr.length) == cmpStr){
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
            me.newTermGroupId = null;                                           // will be set from result's select-list
            me.newTermLanguageId = null;                                        // will be selected by user (or set according to search without result)
            me.newTermRfcLanguage = null;                                       // (set always with newTermLanguageId!) 
            me.newTermName = proposalTranslations['addTermProposal'] + '...';   // (or set according to search without result)
            me.newTermTermEntryId = null;                                       // will be set by selecting a search-result (if not given => new TermEntry will be created)
            // if a search has no result:
            if (!isTermProposalFromInstantTranslate && me.$_searchErrorNoResults.is(":visible")) {
                me.newTermLanguageId = $('#language').val();
                me.newTermRfcLanguage = $("#language option:selected").text();
                me.newTermName = $('#search').val();
            }
            // if proposal from InstantTranslate:
            if (isTermProposalFromInstantTranslate) {
                me.newTermLanguageId = instanttranslate.langProposal;
                me.newTermRfcLanguage = instanttranslate.langProposal; // TODO: langProposal is not the same as languageId; check input from TermPortal!!
                me.newTermName = instanttranslate.textProposal;
            }
            
            console.log("After reset data for proposing a new Term:");
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
         */
        getNewTermData: function() {
            var me = this,
                newTermData = {};
            if(me.newTermCollectionId == undefined) {
                debugger;
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
                'definition': "",
                'desc': "",
                'groupId': me.newTermGroupId,
                'label': "",
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
            console.log('drawLanguageSelectForNewTerm');
            var me = this,
                languageSelectContainer = '<div id="languageSelectContainer" class="skeleton"></div>',
                languageSelectHeader,
                rfcLanguage,
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
            $( "#languageSelectContainer .chooseLanguage" )
                .iconselectmenu({
                    select: function() {
                        if ($(this).val() == 'none') {
                            return false;
                        }
                        me.newTermLanguageId = $(this).val();
                        me.newTermRfcLanguage = $( "#languageSelectContainer .chooseLanguage option:selected" ).text();
                        $('#languageSelectContainer').remove();
                        $_termSkeleton.next().show();
                        $_termSkeleton.show();
                        me.drawLanguageFlagForNewTerm();
                        $_termSkeleton.find('.proposal-add').click();
                        $_termSkeleton.find('textarea').focus();
                    }
                })
                .iconselectmenu( "menuWidget")
                .addClass( "ui-menu-icons flag" );
        },
        
        /**
         * 
         */
        drawLanguageFlagForNewTerm: function () {
            var me = this,
                flag = getLanguageFlag(me.newTermRfcLanguage),
                $_termSkeleton = me.$_termTable.find('.is-new'); // TODO: use DOM-cache
            console.log('drawLanguageFlagForNewTerm ('+me.newTermLanguageId+' / '+me.newTermRfcLanguage+')');
            $_termSkeleton.find('img').remove();
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
            if (filteredCollections.length == 1) {
                me.newTermCollectionId = filteredCollections[0];
                me.drawTermTable();
                me.$_termTable.find('.proposal-add')[0].click();
                return;
            }
            console.log('choose collection (filteredCollections: ' + filteredCollections.length + ')');
            me.emptyResultTermsHolder(false);
            me.$_resultTermsHolder.show();
            collectionSelectHeader = '<h3>'+proposalTranslations['chooseTermcollectionForTermEntry']+':</h3>';
            if (filteredCollections.length == 0) {
                $("#collection option").each(function() {
                    if ($(this).val() != 'none') {
                        filteredCollections.push($(this).val());
                    }
                });
            }
            for (i = 0; i < filteredCollections.length; i++) {
                filteredCollectionId = filteredCollections[i];
                collectionSelectOptions += '<option value="'+filteredCollectionId+'">'+collectionsNames[filteredCollectionId]+'</option>';
            }
            collectionSelect = '<select name="chooseCollection" id="chooseCollection">'+collectionSelectOptions+'</select>';
            me.$_termCollectionSelect.append(collectionSelectHeader).append(collectionSelect).show();
            $('#chooseCollection').selectmenu({
                select: function() {
                    me.newTermCollectionId = $(this).val();
                    me.$_termCollectionSelect.empty().hide();
                    me.drawTermTable();
                    me.$_termTable.find('.proposal-add')[0].click();
                }
            });
        },
        
        /**
         * Empty the resultTermsHolder.
         * If keepAttributes is set and set to true, the attributes-Tab will not be emptied.
         * @params {Boolean} keepAttributes
         */
        emptyResultTermsHolder: function (keepAttributes) {
            var me = this,
                cssDisplayHeader;
            me.$_termTable.empty();
            me.$_termCollectionSelect.empty();
            if(typeof keepAttributes !== "undefined" && keepAttributes === true) {
                return;
            }
            me.$_termEntryAttributesTable.empty();
        },

        /***
		 * On remove proposal click handler
		 */
		onDeleteTermClick:function(event){
			var me = event.data.scope,
                $element=$(this),
				$parent=$element.parents('h3[data-term-id]');
			
			if(parent.length==0){
				return;
			}
			
			var yesCallback=function(){
				//ajax call to the remove proposal action
				var me=event.data.scope,
					url=Editor.data.termportal.restPath+'term/{ID}/removeproposal/operation'.replace("{ID}",$parent.data('term-id')),
					groupId=$parent.data('groupid');

				$.ajax({
			        url: url,
			        dataType: "json",	
			        type: "POST",
			        success: function(result){
			        	
			        	//reload the termEntry when the term is removed
			        	if(!result.rows || result.rows.length==0){
			        		me.reloadTermEntry=true;
			        		me.findTermsAndAttributes(groupId);
			        		return;
			        	}
			        	
			        	//the term proposal is removed, render the initial term proposable content
			        	var renderData=me.renderTermData(result.rows),
			        		ins=$parent.find('ins');
			        		
			        	ins.replaceWith(renderData);
			        	$parent.find('del').empty();

			        	$parent.switchClass('is-proposal','is-finalized');
			        	me.drawProposalButtons($parent);
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
		    $("<div></div>").dialog({
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
            var me = event.data.scope,
                filteredCollections = getFilteredCollections();
            me.resetNewTermData();
            me.$_searchTermsSelect.find('.ui-state-active').removeClass('ui-state-active');
            // If the collection is known, we can start right away...
            if (filteredCollections.length == 1) {
                me.newTermCollectionId = filteredCollections[0];
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
            
            // if language is not set yet, draw language-select first...
            if (me.newTermLanguageId == null) {
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
                reference = event.data.reference,
                $element=$(this),
                search,
                $termAttributeHolder;
            console.log('onEditTermClick ('+reference+')');
            
            event.stopPropagation();
            
            switch(reference) {
                case "content":
                    search = $element;
                    break;
                case "icon":
                    search=$element.parent().find("span[data-editable]");
                    break;
            }
            
            $termAttributeHolder = me.$_termTable.find('div[data-term-id="' + search.data('id') + '"]');
            ComponentEditor.addTermComponentEditor(search,$termAttributeHolder);
        }
};

Term.init();
