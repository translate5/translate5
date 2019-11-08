var TermEntry={

	$_resultTermsHolder:null,
    $_termEntryAttributesTable:null,
    
	KEY_TERM_ENTRY_ATTRIBUTES:"termEntryAttributes",
	
	init:function(){
		this.cacheDom();
	},
	
	cacheDom:function(){
		this.$_resultTermsHolder=$('#resultTermsHolder');
        this.$_termEntryAttributesTable=$('#termEntryAttributesTable');
	},
    
    initEvents:function(){
        // TermEntries:
        // - adding, deleting and editing term-entries is done by editing it's term(s)
        
        // TermEntries-Attributes: 
        // - see Attribute.js
    },
	
	/***
	 * Draw the term entry groups
	 * @param entryAttribute
	 * @returns
	 */
	drawTermEntryAttributes:function(entryAttribute){
	    if(!entryAttribute || entryAttribute.length<1){
	        return;
	    }
	    var me=this;
	    
	    me.$_termEntryAttributesTable.empty();
	    me.$_resultTermsHolder.show();
	    
	    var drawDataContainer=[],
	    	commentAttribute=[];
	    
	    entryAttribute.forEach(function(attribute) {
	    	//comment attribute should always appear as first
	    	if(attribute.name=='note'){
	    		commentAttribute.push(Attribute.handleAttributeDrawData(attribute));
	    	}else{
	    		drawDataContainer.push(Attribute.handleAttributeDrawData(attribute));
	    	}
	    });
	    //merge the comments and the other attributes
	    drawDataContainer=commentAttribute.concat(drawDataContainer);
	    
	    me.$_termEntryAttributesTable.append(drawDataContainer.join(''));
	    Term.drawProposalButtons('attribute');
	},

    
    /***
     * Update front-end for given termEntryId.
     */
    reloadTermEntry: function(termEntryId) {
        console.log('reloadTermEntry');
        Term.reloadTermEntry = true;
        Term.findTermsAndAttributes(termEntryId);
    }
};

TermEntry.init();
