Ext.define('Erp.Utils',{
    
    /**
     * All static fields and function can be called whitout object instance
     */
    statics:{

    	/***
    	 * Grid filter reset values by type
    	 */
    	resetFilterTypes:{
    		'=':null,
    		'like':null,
    		'notInList':[],
    		'in':[],
    		'eq':null,
    		'gt':null,
    		'gteq':null,
    		'lt':null,
    		'lteq':null,
    	},
    	
        /***
         * Returns number with leading currency symbol.
         * If the number is negative, the color of the value will be red.(the html tags will be included in the return value)
         */
        currency: function(value, currency) {
            currency = currency || Erp.data.app.baseCurrency;
            var res = Ext.util.Format.currency(value, currency+' ');
            if(value < 0) {
                return '<span class="negativeNumber">'+res+'</span>';
            }
            return res;
        },

        /**
         * Round number for given rlength(decimal places)
         * Ex: 
         *    1,265 -> 1,27
         *    1,264 -> 1,26
         */
        roundNumber:function(rnum, rlength){
            return Math.round(rnum * Math.pow(10, rlength)) / Math.pow(10, rlength);
        },

        //get screan width in pixels reduced by 10%
        calculateWidth:function(){
            var sw=Ext.getBody().getViewSize().width,
                calc=sw*0.9;
            return calc;
        },
        
        //set the width of the component whit the value returned from calculateWidth function
        setCalculatedWidth:function(component){
            component.setWidth(this.calculateWidth());
        },
        
        //set the pointer to the first empty field in form
        focusOnFirstEmptyField:function(form){
            //the defer method is used because sometimes the binding of the form is not finished when the element is focused
            Ext.defer(function(){
                form.getFields().each(function(field){
                    var filterFieldTypes = field.xtype !="displayfield" && field.xtype !="multiselectfield" && field.xtype !="checkboxfield";
                    if(filterFieldTypes && (!field.value || field.value==-1) && !field.readOnly && !field.disabled){
                        field.up('panel').getEl().scrollIntoView(field.getEl(), true, false);
                        field.focus(true,200);
                        return false;
                    }
                });
            },300);
        },

        /***
         * 
         *  Sorts alphabeticly the input array based on field value
         *  arraytosort = input array that need to be sorted
         *  fieldName =   field name in arraytosort wich value will be used as sort parametar
         *  
         *  Example :
         *  array (
         *      0 => array (
         *           id:"1" 
         *          value:"de-De" -> fieldId will point to this element as as 
         *          text:"Deutsch (de-De)" 
         *      ), 
         *      1 => array ( 
         *          id:"1" 
         *          value:"mk-Mk" -> fieldId will point to this element as as 
         *          text:"Mazedonish (mk-Mk)" 
         *      ), 
         *  .... 
         *  )
         * 
         */
        arrayAlphabeticalSort:function(arraytosort,fieldName){
            var sortedArray = Ext.clone(arraytosort);
            if(!fieldName){
                fieldName = 'value';
            }
            sortedArray.sort(function(a, b){
                var lngA=a[fieldName].toLowerCase(), lngB=b[fieldName].toLowerCase();
                    if (lngA < lngB){
                        return -1;
                    } //sort string ascending
                    if (lngA > lngB){
                        return 1;
                    }
                return 0;
            });
            return sortedArray;
        },

        //if the number is lower than 0 , the function will return HTML span with 
        //a red number in it
        negativeNumber:function(value){
            if(value < 0) {
                return '<span class="negativeNumber">'+Ext.util.Format.number(value)+'</span>';
            }
            return Ext.util.Format.number(value);
        },
        //if the field value is < 0, then add the negative number class
        checkNegativeNumber:function(element,value){
            element.removeCls('negativeValueNumberfield');
            if(value && parseFloat(value) <0){
                element.addCls('negativeValueNumberfield');
            }
        },

        /**
         * validate given field in his own fieldset, and validation against the others fieldset
         */
        customValidation:function(fieldName,form){
            var fieldsets=[
                    ['wordsCount','wordsDescription','perWordPrice'],
                    ['hoursCount','hoursDescription','perHourPrice'],
                    ['additionalCount','additionalDescription','additionalUnit','additionalPrice','perAdditionalUnitPrice']
                ],
                index,
                me=this,
                isValid=false,
                rec=form.getRecord();
            
            //disable the validation if it is about old po. see TMUE-188
            if(rec && this.isDisableValidation(rec.get('creationDate'))){
            	return true;
            }
            
            //find the fieldset index
            fieldsets.forEach(function(element) {
                element.forEach(function(member){
                    if(member == fieldName){
                        index = fieldsets.indexOf(element);
                        return false;
                    }
                });
            });
            
            //check if the field is valid
            isValid =me.isFieldValid(fieldName,form);
            
            if(isValid){
                return isValid;
            }
            
            //get the fieldset
            var fieldset = fieldsets[index];
            
            isValid = true;

            var validFieldsInFieldsetCount = 0;
            //hown many valid fields in fieldset
            fieldset.forEach(function(element) {
                if(me.isFieldValid(element,form)){
                    validFieldsInFieldsetCount++;
                }
            });

            var fieldCount = fieldset.length;
            var hitCondition= (fieldCount > 3) ? 0 : 1;

            isValid = (validFieldsInFieldsetCount > hitCondition);
            //invalid in fieldset
            if(isValid){
                return "Dieses Feld darf nicht leer sein";
            }
            
            var isValidGlobal = false;
            
            var validFieldsInGlobal = 0;
            //if the field is valid for all other fieldsets
            fieldsets.forEach(function(element) {
                element.forEach(function(member){
                    if(index!=fieldsets.indexOf(element)){
                        if(me.isFieldValid(member,form)){
                            validFieldsInGlobal++;
                        }
                        if(validFieldsInGlobal>1){
                            isValidGlobal = true;
                            return false;
                        }
                    }
                });
                if(validFieldsInGlobal>1){
                    isValidGlobal = true;
                    return false;
                }
            });
            
            return (isValidGlobal) || "'Gewichtete Wörter','Stunden' oder 'Zusatzposition' Feldset  muss einen Wert enthalten";
        },
        
        isFieldValid:function(fieldname,form){
            var field=form.findField(fieldname),
                value = field.getRawValue(),
                isValid = false;
                
            isValid = value!=null && value!='';
            if(field.getXType()=='numberfieldcustom'){
                isValid = isValid && parseFloat(value.replace(/,/, '.'))>0;
            }
            return isValid;
        },
        
        /***
         * Disable validation for older pos. see TMUE-188
         * @param rec
         */
        isDisableValidation:function(creationDate){
        	var borderDate = Ext.Date.parse("2018-07-11", "Y-m-d").getTime();
            
        	creationDate=creationDate.getTime();//Ext.Date.parse(rec.get('creationDate'), "Y-m-d");
	
	        if(borderDate && creationDate && Ext.Date.diff(borderDate,creationDate,'d') <0){
	        	return true;
	        }
	        return false;
        },
        
        /***
         * Get the grid filter reset value
         * 
         *  return type value:| boolean | string | list      | list| numeric| numeric | numeric | numeric | numeric
         *  filterType:       |    =    | like   | notInList |  in |   eq   |   gt    |   gteq  |    lt   |  lteq  
         */
        getGridFilterResetValue:function(filterType){
        	return this.resetFilterTypes[filterType]!=undefined ? this.resetFilterTypes[filterType] : null; 
        }
    }
});