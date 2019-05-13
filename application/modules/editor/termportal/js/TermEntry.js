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
	    
	    entryAttribute = groupChildData(entryAttribute);
	    
	    var drawDataContainer=[];
	    entryAttribute.forEach(function(attribute) {
	    	drawDataContainer.push(Attribute.handleAttributeDrawData(attribute));
	    });
	    
	    me.$_termAttributeTable.append(drawDataContainer.join(''));

        Term.drawProposalButtons('term-entry');
        Term.drawProposalButtons('term-entry-attributes');
	}
};

TermEntry.init();
