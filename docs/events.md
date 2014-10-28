#Events in translate5

This document is only a draft !!!

##Automatic events
Automatic events are defined in some global (basic ??) tranlate5 classes. For the events-names variables or automatisms are used. On that way a lot of internal trigger-points are generated on the fly.

Example:

        public function preDispatch()
        {
            $eventName = "before".ucfirst($this->_request->getActionName())."Action";
            $this->events->trigger($eventName, $this);
        }
        
will define an "beforeControllerAction"-event for each an every controller. For the default IndexController of the Editor-Modul this will lead to an event `Editor_IndexController#beforeIndexAction`


###Controller-Events
defined in `/library/ZfExtended/Controllers/Action.php`  will trigger an event on each and every controller. The diverentt controllers are named in the following list as &lt;Controllername&gt; wich are Index, Login etc. 

 - **before&lt;Controllername&gt;Action** on Zend preDispatch
 - **after&lt;Controllername&gt;Action** with parameter $this->view on Zend postDispatch


###ZfExtended_Model_Abstract
- **beforeSave** on every save as first function. !!! be careful on overwritten methods to call parent::save() in first place or to take care of events !!!



##Handmade events
Handmade events are spezial events wich are defined direct in the code. No automatic definition is used while trigger.

