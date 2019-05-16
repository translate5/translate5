const TermEntry={
		
	$_termAttributeTable:null,
	$_resultTermsHolder:null,
	KEY_TERM_ENTRY_ATTRIBUTES:"termEntryAttributes",
	
	init:function(){
		this.cacheDom();
	},
	
	cacheDom:function(){
		this.$_termAttributeTable=$('#termAttributeTable');
		this.$_resultTermsHolder=$('#resultTermsHolder');
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
	    
	    me.$_termAttributeTable.empty();
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
	    
	    me.$_termAttributeTable.append(drawDataContainer.join(''));

        Term.drawProposalButtons('term-entry');
        Term.drawProposalButtons('term-entry-attributes');
	}
};

TermEntry.init();
