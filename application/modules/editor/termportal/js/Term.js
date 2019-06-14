const Term={
        
        $_searchTermsHelper:null,
        $_searchErrorNoResults:null,
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
			this.$_searchTermsSelect=$('#searchTermsSelect');
            
            this.$_resultTermsHolder=$('#resultTermsHolder');
            this.$_resultTermsHolderHeader=$('#resultTermsHolderHeader');
            this.$_termTable=$('#termTable');
            this.$_termEntryAttributesTable = $('#termEntryAttributeTable');
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
            me.$_termTable.on('click', '.term-data.proposable.is-new [data-editable][data-type="term"]',{scope:me, reference:'content'},me.onAddTermClick);
            me.$_termTable.on('click', '.term-data.proposable [data-editable][data-type="term"]',{scope:me, reference:'content'},me.onEditTermClick);
            
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
					if(successCallback){
						successCallback(result.rows[me.KEY_TERM]);
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
			if(!me.searchTermsResponse || me.searchTermsResponse.length === 0){

			    me.$_searchTermsHelper.find('.proposal-txt').text(searchString + ': ' + proposalTranslations['addTermEntryProposal']);
                me.$_searchTermsHelper.find('.proposal-btn').prop('title', searchString + ': ' + proposalTranslations['addTermEntryProposal']);
			    me.$_searchErrorNoResults.show();
				
				console.log("fillSearchTermSelect: nichts gefunden");
				
				if(me.disableLimit){
					me.disableLimit=false;
				}
				
				me.$_resultTermsHolder.hide();
				return;
			}
			
			me.$_searchErrorNoResults.hide();
			me.$_searchTermsSelect.empty();
			
			console.log("fillSearchTermSelect: " + me.searchTermsResponse.length + " Treffer");
			
			console.log("fillSearchTermSelect: me.disableLimit"); // FIXME: do we need this here?
				
			//if only one record, find the attributes and display them
			if(me.searchTermsResponse.length===1){
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
			var me=this;
		    console.log("findTermsAndAttributes() for: " + termGroupid);
		    Attribute.languageDefinitionContent=[];
            
            me.$_termCollectionSelect.hide();
            
		    //check the cache
		    if(!me.reloadTermEntry && me.termGroupsCache[termGroupid]){
		    	TermEntry.drawTermEntryAttributes(me.termGroupsCache[termGroupid].rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
		        me.drawTermGroups(me.termGroupsCache[termGroupid].rows[me.KEY_TERM_ATTRIBUTES]);
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

		            me.drawTermGroups(result.rows[me.KEY_TERM_ATTRIBUTES]);
		            //reset term entry reload flag
		            me.reloadTermEntry=false;
		        }
		    })
		},
        
        /**
         * Draw (only) the form for proposing a Term.
         * By this way we also create new Term-Entries (backend).
         */
        drawTermProposal: function() {
            var me = this;
            console.log('drawTermProposal...');
            me.emptyResultTermsHolder(true);
            me.drawTermGroups();
            me.$_termTable.find('.proposal-add')[0].click();
        },
        
        /***
         * Draw the skeleton for adding new terms.
         * @returns
         */
        drawNewTermSkeleton:function(){
            var me = this,
                html = '',
                $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                $_filteredClients = $('#searchFilterTags input.filter.client'),
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1); // TODO: show collection also when adding a TermEntry?,
                newTermData = me.renderNewTermData();
            html += me.renderTerm(newTermData[0],showInfosForSelection);
            if(html.length>0){
                me.$_termTable.append(html);
            }
        },
		
		/***
		 * Draw the term groups
		 * @returns
		 */
		drawTermGroups:function(termsData){
            var me = this,
                html = '',
                $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                $_filteredClients = $('#searchFilterTags input.filter.client'),
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1), // TODO: show collection also when adding a TermEntry?
                currentItem,
                currentItemNr;
		    
            me.emptyResultTermsHolder(true);
            me.$_resultTermsHolder.show();
            
            me.drawNewTermSkeleton();
		    
		    $.each(termsData, function (i, term) {
		        html += me.renderTerm(term,showInfosForSelection);
		    });
		    if(html.length>0){
		    	me.$_termTable.append(html);
		    }
		    
		    if (me.$_termTable.hasClass('ui-accordion')) {
		    	me.$_termTable.accordion('refresh');
		    } else {
		    	me.$_termTable.accordion({
		            active: false,
		            collapsible: true,
		            heightStyle: "content",
		            beforeActivate: function( event, ui ) {
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

            me.drawProposalButtons('terms-attribute');
            me.drawProposalButtons('terms');
		    
		    setSizesInFinalResultContent();

            $( ".instanttranslate-integration .chooseLanguage" )
                .iconselectmenu({
                    select: function() {
                        me.openInstantTranslate($(this));
                    }
                })
                .iconselectmenu( "menuWidget")
                .addClass( "ui-menu-icons flag" );
        },
        
        /**
         * Opens InstantTranslate for the term and languages.
         */
        openInstantTranslate: function($_elSelect) {
            var $_termAttributes = $_elSelect.closest('.term-attributes'),
                $_termData = $_termAttributes.prev('.term-data'),
                $_form = $_termAttributes.children('form'),
                text = $_termData.attr('data-term-value'),
                source = $_termData.children('img').attr('title'),
                target = $_elSelect.find("option:selected").text(),
                instanttranslateUrl = Editor.data.restpath+'apps';
            // use proposal if exists
            if ($_termData.children('ins.proposal-value-content').length === 1) {
                text = $_termData.children('ins.proposal-value-content')[0].innerText;
            }
            $_form.attr("target","instanttranslate");
            $_form.attr("action",instanttranslateUrl);
            console.log('openInstantTranslate with text="'+text+'", source="'+source+'", target="'+target+'", instanttranslateUrl: ' + instanttranslateUrl);
            // TODO: use instanttranslate with params (text, source, target)
            //$_form.submit();
		},
		
		/**
         * Render html for term data by given term.
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
                proposable = (term.proposable !== false) ? ' proposable' : ''; // = does the user have the rights to handle proposals for this term?
            
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
            termAttributesHtmlContainer.push('<h3 class="term-data'+proposable + isProposal+'" data-term-value="'+term.term+'" data-term-id="'+term.termId+'">');
            
            
            //add empty space between
            termAttributesHtmlContainer.push(' ');
            
            //add language flag
            termAttributesHtmlContainer.push(rfcLanguage);
            
            //add empty space between
            termAttributesHtmlContainer.push(' ');
            
            //get term render data
            termAttributesHtmlContainer.push(me.getTermRenderData(term));

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
            if (term.termId != -1 && instantTranslateIntegrationForTerm != '') {
                termAttributesHtmlContainer.push(instantTranslateIntegrationForTerm);
            }
            termAttributesHtmlContainer.push(Attribute.renderTermAttributes(term.attributes,termRflLang));
            termAttributesHtmlContainer.push('</div>');
            
            return termAttributesHtmlContainer.join('');
		},
        
		/**
		 * Append the buttons for proposals in the DOM.
		 * Address elements as specific as possible (= avoid long jQuery-selects).
         * @param elements
		 */
        drawProposalButtons: function (elements){
            var me = this,
                htmlProposalAddIcon,
                htmlProposalEditIcon,
                htmlProposalDeleteIcon,
                $_selectorAdd,$_selectorDelete,$_selectorEdit,
                $titleAdd, $titleDelete, $titleEdit;
            htmlProposalAddIcon = '<span class="proposal-btn proposal-add ui-icon ui-icon-plus"></span>';
            htmlProposalDeleteIcon = '<span class="proposal-btn proposal-delete ui-icon ui-icon-trash-b"></span>';
            htmlProposalEditIcon = '<span class="proposal-btn proposal-edit ui-icon ui-icon-pencil"></span>';
            switch(elements) {
                case "term-entry":
                    $_selectorAdd = me.$_resultTermsHolderHeader;
                    $_selectorDelete = false;
                    $_selectorEdit = false;
                    $titleAdd = proposalTranslations['addTermEntryProposal'];
                    break;
                case "term-entry-attributes":
                    $_selectorAdd = false; // me.$_termEntryAttributesTable;
                    $_selectorDelete = $('#termEntryAttributesTable .attribute-data.proposable.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termEntryAttributesTable .attribute-data.proposable.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = proposalTranslations['addTermEntryAttributeProposal'];
                    $titleDelete = proposalTranslations['deleteTermEntryAttributeProposal'];
                    $titleEdit = proposalTranslations['editTermEntryAttributeProposal'];
                    break;
                case "terms":
                    $_selectorAdd = $('#termTable .term-data.is-new'); // cannot use cacheCom(), rendered too late
                    $_selectorDelete = $('#termTable .term-data.proposable.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termTable .term-data.proposable.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = proposalTranslations['addTermProposal'];
                    $titleDelete = proposalTranslations['deleteTermProposal'];
                    $titleEdit = proposalTranslations['editTermProposal'];
                    break;
                case "terms-attribute":
                    $_selectorAdd = false; // $('#termTable .term-data').next('div'); // cannot use cacheCom(), rendered too late
                    $_selectorDelete = $('#termTable .attribute-data.proposable.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termTable .attribute-data.proposable.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = proposalTranslations['addTermAttributeProposal'];
                    $titleDelete = proposalTranslations['deleteTermAttributeProposal'];
                    $titleEdit = proposalTranslations['editTermAttributeProposal'];
                    break;
                default:
                    // e.g. after updateComponent(): show ProposalButtons according to the new state
                    $_this = elements;
                    $_selectorAdd = false;
                    $_selectorDelete = $_this.filter('.is-proposal');
                    $_selectorEdit = $_this.filter('.is-finalized');
                    $_this.children('.proposal-btn').remove();
                    $titleDelete = proposalTranslations['deleteProposal'];
                    $titleEdit = proposalTranslations['addProposal'];
                    break;
            }
            if ($_selectorAdd && $_selectorAdd.children('.proposal-btn').length === 0) {
                $_selectorAdd.append(htmlProposalAddIcon);
                $_selectorAdd.find('.proposal-add').prop('title', $titleAdd);
            }
            if ($_selectorEdit && $_selectorEdit.children('.proposal-btn').length === 0) {
                $_selectorEdit.append(htmlProposalEditIcon);
                $_selectorEdit.children('.proposal-edit').prop('title', $titleEdit);
            }
            if ($_selectorDelete && $_selectorDelete.children('.proposal-btn').length === 0) {
                $_selectorDelete.append(htmlProposalDeleteIcon);
                $_selectorDelete.children('.proposal-delete').prop('title', $titleDelete);
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
		
		
		/***
		 * Get the term render data. If the user has proposal tights, the term proposal render data will be set.
		 */
		getTermRenderData:function(termData){
			var me=this,
				htmlCollection=[];
			
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
         * "reset" data for proposing a new Term
         */
        resetNewTermData: function() {
            var me = this;
            console.log("reset data for proposing a new Term");
            // "default":
            me.newTermAttributes = Attribute.renderNewTermAttributes();         // currently just an empty Array
            me.newTermCollectionId = null;                                      // will be selected by user (if not already filtered)
            me.newTermGroupId = null;                                           // will be set from result's select-list
            me.newTermLanguageId = null;                                        // will be selected by user (or set according to search without result)
            me.newTermName = proposalTranslations['addTermProposal'] + '...';   // (or set according to search without result)
            me.newTermTermEntryId = null;                                       // will be set by selecting a search-result (if not given => new TermEntry will be created)
            // if a search has no result:
            if (me.$_searchErrorNoResults.is(":visible")) {
                me.newTermLanguageId = $('#language').val();
                me.newTermName = $('#search').val();
            }
        },
        
        /**
         * Returns term data for creating a new term.
         */
        renderNewTermData: function() {
            console.log('renderNewTermData...');
            var me = this,
                newTermData = {};
            console.log('renderNewTermData; currently known: ');
            console.log('- attributes: ' + JSON.stringify(me.newTermAttributes));
            console.log('- collectionId: ' + me.newTermCollectionId);
            console.log('- groupId: ' + me.newTermGroupId);
            console.log('- languageId: ' + me.newTermLanguageId);
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
        drawLanguageSelectForTerm: function() {
            console.log('drawLanguageSelectForTerm');
            var me = this,
                languageSelectContainer = '<div id="languageSelectContainer" class="skeleton"></div>',
                languageSelectHeader,
                rfcLanguage,
                $_termSkeleton = me.$_termTable.find('.is-new');
            languageSelectHeader = '<p>'+proposalTranslations['chooseLanguageForTermEntry']+':</p>';
            me.$_termTable.prepend(languageSelectContainer);
            $('#languageSelectContainer').prepend(languageSelectForNewTerm).prepend(languageSelectHeader);
            $_termSkeleton.next().hide();
            $_termSkeleton.hide();
            // TODO: first mouseover causes "jquery.js:6718 GET http://translate5.local/editor/undefined 404 (Not Found)"
            $( "#languageSelectContainer .chooseLanguage" )
                .iconselectmenu({
                    select: function() {
                        me.newTermLanguageId = $(this).val();
                        rfcLanguage = getLanguageFlag($( "#languageSelectContainer .chooseLanguage option:selected" ).text());
                        $('#languageSelectContainer').remove();
                        $_termSkeleton.next().show();
                        $_termSkeleton.show();
                        me.drawLanguageFlagForNewTerm(rfcLanguage);
                        $_termSkeleton.find('[data-editable]').click();
                        $_termSkeleton.find('.proposal-add').click();
                    }
                })
                .iconselectmenu( "menuWidget")
                .addClass( "ui-menu-icons flag" );
        },
        
        /**
         * 
         */
        drawLanguageFlagForNewTerm: function (rfcLanguage) {
            console.log('drawLanguageFlagForNewTerm');
            var me = this,
                rfcLanguage = getLanguageFlag(rfcLanguage),
                $_termSkeleton = me.$_termTable.find('.is-new'); // TODO: use DOM-cache
            $_termSkeleton.find('img').remove();
            $_termSkeleton.children('span').first().after(rfcLanguage);
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
                me.drawTermProposal();
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
                    me.drawTermProposal();
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
					url=Editor.data.termportal.restPath+'term/{ID}/removeproposal/operation'.replace("{ID}",$parent.data('term-id'));
				$.ajax({
			        url: url,
			        dataType: "json",	
			        type: "POST",
			        success: function(result){
			        	//the term proposal is removed, render the initial term proposable content
			        	var renderData=me.getTermRenderData(result.rows),
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
            if (filteredCollections.length == 1) {
                me.newTermCollectionId = filteredCollections[0];
                me.drawTermProposal();
                return;
            }
            me.drawFilteredTermCollectionSelect();
        },
        
        /***
         * On add term click handler
         */
        onAddTermClick:function(event){
            console.log('onAddTermClick');
            var me = event.data.scope,
                $_termSkeleton;
            
            if (me.$_searchErrorNoResults.is(":visible")) {
                me.newTermLanguageId = $("#language").val();
                me.drawLanguageFlagForNewTerm($("#language option:selected").text());
                $_termSkeleton = me.$_termTable.find('.is-new'); // TODO: use DOM-cache
                var $termEditorSpan=$_termSkeleton.find('[data-editable]'),
                	$termEditorHolder=me.$_termTable.find('div[data-term-id="-1"]');
                ComponentEditor.addTermComponentEditor($termEditorSpan,$termEditorHolder);
                return;
            }
            
            if (me.newTermLanguageId == null) {
                me.drawLanguageSelectForTerm();
                return;
            }
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
        },
        
        /***
    	 * Return the jquery component of the term header(h3)
    	 */
    	getTermHeader:function(termId){
			return this.$_termTable.find('h3[data-term-id="'+termId+'"]');
    	},
};

Term.init();
