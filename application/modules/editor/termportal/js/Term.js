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
            this.$_termEntryAttributesTable = $('#termAttributeTable');
            this.$_termCollectionSelect = $('#termcollectionSelectContainer');
		},
		
		initEvents:function(){
			var me=this;
			me.$_searchTermsSelect.on( "selectableselected", function( event, ui ) { // FIXME: why is this triggered twice sometimes (with attr('data-value') = "undefined" in the second)
				me.findTermsAndAttributes($(ui.selected).attr('data-value'));
		    });
			
            // TermEntries
			me.$_resultTermsHolderHeader.on('click', ".proposal-add",{scope:me},me.onAddTermEntryClick);
            me.$_searchTermsHelper.on('click', ".proposal-add",{scope:me},me.onAddTermEntryClick);
            me.$_termEntryAttributesTable.on('click', ".proposal-add",{scope:me},me.onAddTermEntryAttributeClick);

            // Terms
            me.$_termTable.on('click', ".term-data .proposal-add",{scope:me},me.onEditTermClick); // = the same procedure as editing
            me.$_termTable.on('click', ".term-data .proposal-delete",{scope:me},me.onDeleteTermClick);
            me.$_termTable.on('click', ".term-data .proposal-edit",{scope:me},me.onEditTermClick);
            me.$_termTable.on('click', ".term-attributes .proposal-add",{scope:me},me.onAddTermAttributeClick);
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
				collectionIds = me.getFilteredCollections(),
                processStats = me.getFilteredProcessStats();
			
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
        
        /**
         * Return all the Term-Collections that are set in the tag field.
         * @returns {Array}
         */
        getFilteredCollections: function() {
            var $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                filteredCollections = [];
            if ($_filteredCollections.length === 0 && this.getFilteredClients().length > 0) {
                // user has not selected any collections, but client(s) => use only those collections that belong to the client(s)
                $_filteredCollections = $("#collection option:enabled");
            }
            $_filteredCollections.each(function( index, el ) {
                if (el.value != 'none') {
                    filteredCollections.push(el.value);
                }
            });
            return filteredCollections;
        },
        
        /**
         * Return all the clients that are set in the tag field.
         * @returns {Array}
         */
        getFilteredClients: function() {
            var filteredClients = [];
            $( '#searchFilterTags input.filter.client' ).each(function( index, el ) {
                filteredClients.push(el.value);
            });
            return filteredClients;
        },
        
        /**
         * Return all the prcoessStats that are set in the tag field.
         * @returns {Array}
         */
        getFilteredProcessStats: function() {
            var filteredProcessStats = [];
            $( '#searchFilterTags input.filter.processStatus' ).each(function( index, el ) {
                filteredProcessStats.push(el.value);
            });
            return filteredProcessStats;
        },
		
		/***
		 * Fill up the term option component with the search results
		 * @returns
		 */
		fillSearchTermSelect:function(searchString){
			var me=this;
			if(!me.searchTermsResponse){

			    me.$_searchTermsHelper.find('.proposal-txt').text(searchString + ': ' + translations['addTermEntry']);
                me.$_searchTermsHelper.find('.proposal-btn').prop('title', searchString + ': ' + translations['addTermEntry']);
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
			
			if(me.disableLimit){
				console.log("fillSearchTermSelect: me.disableLimit");
				
				me.disableLimit=false;
				
				//if only one record, find the attributes and display them
				if(me.searchTermsResponse.length===1){
					console.log("fillSearchTermSelect: only one record => find the attributes and display them");
					me.findTermsAndAttributes(me.searchTermsResponse[0].groupId);
				}
				
				if(me.searchTermsResponse.length>0){
					showFinalResultContent();
				}
				
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
						$('<li>').attr('data-value', item.groupId).attr('class', 'ui-widget-content search-terms-result').append(
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
            
            // data for proposing a new Term
            me.resetNewTermData();
            me.newTermGroupId = termGroupid;
            
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
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1); // TODO: show collection also when adding a TermEntry?
		    
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
		            heightStyle: "content"
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
                isProposal = (term.proposal == null) ? ' is-finalized' : ' is-proposal';
                
            if (term.termId === null) {
                isProposal = ' is-new';
                statusIcon = '';
                term.termId = -1;
            }
            
            //draw term header
            termAttributesHtmlContainer.push('<h3 class="term-data'+isProposal+'" data-term-value="'+term.term+'" data-term-id="'+term.termId+'">');
            
            
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
            
            //draw term attriubtes
            termAttributesHtmlContainer.push('<div data-term-id="'+term.termId+'" data-collection-id="'+term.collectionId+'" class="term-attributes">');
            termAttributesHtmlContainer.push(me.renderTermAttributes(term.attributes,termRflLang));
            termAttributesHtmlContainer.push('</div>');
            
            return termAttributesHtmlContainer.join('');
		},
		
		/***
		 * Render term attributes by given term
		 * 
		 * @param attributes
		 * @param termLang
		 * @returns {String}
		 */
		renderTermAttributes:function(attributes,termLang){
		    var me=this,
		        html = [],
		        commentAttribute=[];
		    
		    if(Attribute.languageDefinitionContent[termLang]){
		    	html.push(Attribute.languageDefinitionContent[termLang]);
		    }
		    
		    for(var i=0;i<attributes.length;i++){
		    	//comment attribute should always appear as first
		    	if(attributes[i].name=='note'){
		    		commentAttribute.push(Attribute.handleAttributeDrawData(attributes[i]));
		    		continue;
		    	}
		    	html.push(Attribute.handleAttributeDrawData(attributes[i]));
		    }
		    html=commentAttribute.concat(html);
		    
		    return html.join('');
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
                    $titleAdd = translations['addTermEntry'];
                    break;
                case "term-entry-attributes":
                    $_selectorAdd = false; // me.$_termEntryAttributesTable;
                    $_selectorDelete = $('#termAttributeTable .attribute-data.proposable.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termAttributeTable .attribute-data.proposable.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = translations['addTermEntryAttribute'];
                    $titleDelete = translations['deleteTermEntryAttribute'];
                    $titleEdit = translations['editTermEntryAttribute'];
                    break;
                case "terms":
                    $_selectorAdd = $('#termTable .term-data.is-new'); // cannot use cacheCom(), rendered too late
                    $_selectorDelete = $('#termTable .term-data.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termTable .term-data.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = translations['addTerm'];
                    $titleDelete = translations['deleteTerm'];
                    $titleEdit = translations['editTerm'];
                    break;
                case "terms-attribute":
                    $_selectorAdd = false; // $('#termTable .term-data').next('div'); // cannot use cacheCom(), rendered too late
                    $_selectorDelete = $('#termTable .attribute-data.proposable.is-proposal'); // cannot use cacheCom(), rendered too late
                    $_selectorEdit = $('#termTable .attribute-data.proposable.is-finalized'); // cannot use cacheCom(), rendered too late
                    $titleAdd = translations['addTermAttribute'];
                    $titleDelete = translations['deleteTermAttribute'];
                    $titleEdit = translations['editTermAttribute'];
                    break;
                default:
                    // e.g. after updateComponent(): show ProposalButtons according to the new state
                    $_this = elements;
                    $_selectorAdd = false;
                    $_selectorDelete = $_this.filter('.is-proposal');
                    $_selectorEdit = $_this.filter('.is-finalized');
                    $_this.children('.proposal-btn').remove();
                    $titleDelete = "Delete Proposal"; // TODO: use translations
                    $titleEdit = "Propose changes"; // TODO: use translations
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
            me.newTermAttributes = Attribute.renderNewTermAttributes(); // currently just an empty Array
            me.newTermCollectionId = null;                              // will be selected by user (if not already filtered)
            me.newTermGroupId = null;                                   // will be set from result's select-list
            me.newTermLanguageId = null;                                // will be selected by user (or set according to search without result)
            me.newTermName = "Add new term-proposal";                   // (or set according to search without result) // TODO: use translation 
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
            console.log('renderNewTermData with: ');
            console.log('- attributes: ' + JSON.stringify(me.newTermAttributes));
            console.log('- collectionId: ' + me.newTermCollectionId);
            console.log('- groupId: ' + me.newTermGroupId);
            console.log('- languageId: ' + me.newTermLanguageId);
            console.log('- termName: ' + me.newTermName);
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
        
        /***
         * On add term-entry icon click handler
         */
        onAddTermEntryClick: function(eventData){
            var me = eventData.data.scope,
                filteredCollections = me.getFilteredCollections();
            me.resetNewTermData();
            me.$_searchTermsSelect.find('.ui-state-active').removeClass('ui-state-active');
            if (filteredCollections.length == 1) {
                me.newTermCollectionId = filteredCollections[0];
                me.drawTermProposal();
                return;
            }
            me.drawFilteredTermCollectionSelect();
        },
        
        /**
         * Draw a select for choosing a Language for a new Term.
         * When a Language is selected, the corresponding flag is added to the skeleton
         * for the editable new term and it is shown again.
         */
        drawLanguageSelect: function() {
            var me = this,
                languageSelectContainer = '<div id="languageSelectContainer" class="skeleton"></div>',
                languageSelectHeader,
                languageSelect,
                languageSelectOptions,
                rfcLanguage,
                $_termSkeleton = me.$_termTable.find('.is-new');
            languageSelectHeader = '<p>Choose a language for the new TermEntry:</p>'; // TODO: use translation
            $("#language option").each(function() {
                if ($(this).val() != 'none') {
                    languageSelectOptions += '<option value="'+$(this).val()+'">'+$(this).text()+'</option>'; // TODO: add flags
                }
            });
            languageSelect = '<select name="chooseLanguage" id="chooseLanguage">'+languageSelectOptions+'</select>';
            me.$_termTable.prepend(languageSelectContainer);
            $('#languageSelectContainer').prepend(languageSelect).prepend(languageSelectHeader);
            $_termSkeleton.next().hide();
            $_termSkeleton.hide();
            $('#chooseLanguage').selectmenu({
                select: function() {
                    me.newTermLanguageId = $(this).val();
                    rfcLanguage = getLanguageFlag($( "#chooseLanguage option:selected" ).text());
                    $('#languageSelectContainer').remove();
                    $_termSkeleton.next().show();
                    $_termSkeleton.show();
                    me.drawLanguageFlagForNewTerm(rfcLanguage);
                }
            });
        },
        
        /**
         * 
         */
        drawLanguageFlagForNewTerm: function (rfcLanguage) {
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
            var me = this,
                filteredCollections = me.getFilteredCollections(),
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
            collectionSelectHeader = '<h3>Choose a collection for the new TermEntry:</h3>'; // TODO: use translation
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
         * @params {Boolean} displayHeader
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
         * On add term-entry-attribute icon click handler
         */
        onAddTermEntryAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onAddTermEntryAttributeClick');
            // TODO
        },
        
        /***
		 * On remove proposal click handler
		 */
		onDeleteTermClick:function(eventData){
			var me = eventData.data.scope,
                $element=$(this),
				$parent=$element.parents('h3[data-term-id]');
			
			if(parent.length==0){
				return;
			}
			
			var yesCallback=function(){
				//ajax call to the remove proposal action
				var me=eventData.data.scope,
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
			
			var yesText=Editor.data.apps.termportal.proposal.translations['Ja'],
				noText=Editor.data.apps.termportal.proposal.translations['Nein'],
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
		        title: Editor.data.apps.termportal.proposal.translations['deleteTermProposalTitle'],
		        height: 250,
		        width: 400,
		        buttons:buttons
		    }).text(Editor.data.apps.termportal.proposal.translations['deleteTermProposalMessage']);
		},
        
        /***
         * On edit term icon click handler
         */
        onEditTermClick:function(eventData){
            var me = eventData.data.scope,
                element=$(this),
                parent=element.parent(),
                search=parent.find("span[data-editable]"),
                $termAttributeHolder=me.$_termTable.find('div[data-term-id="' + search.data('id') + '"]');
            console.log('onEditTermClick');
            if ($(eventData.target).hasClass('proposal-add')) {
                if (me.$_searchErrorNoResults.is(":visible")) {
                    me.drawLanguageFlagForNewTerm($( "#language option:selected" ).text());
                } else if (me.newTermLanguageId == null) {
                    me.drawLanguageSelect();
                }
            }
            ComponentEditor.addTermComponentEditor(search,$termAttributeHolder);
        },

        /***
         * On add term-attribute icon click handler
         */
        onAddTermAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onAddTermAttributeClick');
            // TODO
        },
        
        /***
    	 * Return the jquery component of the term header(h3)
    	 */
    	getTermHeader:function(termId){
			return this.$_termTable.find('h3[data-term-id="'+termId+'"]');
    	},
};

Term.init();
