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
defined in `/library/ZfExtended/Controllers/Action.php`  will trigger an event on each and every controller. The diverent controllers are named in the following list as &lt;Controllername&gt; wich are Index, Login etc. 

 - **before&lt;Controllername&gt;Action** on Zend preDispatch
 - **after&lt;Controllername&gt;Action** with parameter $this->view on Zend postDispatch

###RestController-Events
 - **before&lt;Controllername&gt;Action** with the following parameters:
   - entity: $this->entity
 - **after&lt;Controllername&gt;Action** with the following parameters:
   - entity: $this->entity
   - view: $this->view


###ZfExtended_Models_Entity_Abstract
- **beforeSave** with parameter array('model' => $this), on every save as first function. !!! be careful on overwritten methods to call parent::save() in first place or to take care of events !!!

###editor_Workflow_Abstract
####Task
- **doReopen** 
- **doEnd**

####TaskUserAssoc
- **beforeFinish**
- **doUnfinish** 
- **beforeOpen**
- **doOpen**
- **beforeView**
- **doView**
- **beforeEdit**
- **doEdit**
- **beforeFinish**
- **doFinish**
- **beforeWait**
- **doWait**

###Editor_SegmentController
- **beforePutSave** with parameter array('model' => $this->entity), used in function putAction() after normal processing before saving the entity (= the segment)


### editor_Models_Import
- **afterImport** is fired after parsing the data and storing the segments in DB. Parameter: 'task' => editor_Models_Task
- **importCompleted** is fired after all import plugins were run, defines the end of import. Parameter: 'task' => editor_Models_Task
- **beforeDirectoryParsing** is fired before directory parsing of proofread file. Parameter: 'importFolder' => string

### editor_Models_Export
- **afterExport** is fired after exporting the data to a folder on the disk, also on ZIP export. Parameter: 'task' => editor_Models_Task

##Handmade events
Handmade events are spezial events wich are defined direct in the code. No automatic definition is used while trigger.

At the moment there are no handmade events.


#Recomendend Best-Practice for using events in classes
Because Zend has a special class for static (global) events, best-pratice to use events in a class is:

##Event-Trigger
- use a protected variable $events to hold the event(-trigger)-object
- initialize $this->events in the class-constructor
- parameter should be send in an named-array 'name' => $value

Example:

    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    public function _construct()
    {
    	$this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));    }
    
    public function doSomething()
    {
    	$this->events->trigger("eventName", $this, array('model' => $this, 'moreParam' => $moreParams));    }
    


##Event-Listener
- use a protected variable $staticEvents to hold the event(-listener)-Object.
- initialize $this->staticEvents in the class-constructor
- define all event-listeners in the class-constructor
- if handler use a event-parameter which is an object make a var-definition-comment so the IDE autocomplete can work correct

Example:

    /**
     * @var Zend_EventManager_StaticEventManager
     */
    protected $staticEvents = false;
    
    public function __construct()
    {
		$this->staticEvents = Zend_EventManager_StaticEventManager::getInstance();
		$this->staticEvents->attach('classNameTriggerClass', 'eventName', array($this, 'handleEvent'));
	}
	
	public function handleEvent(Zend_EventManager_Event $event)
	{
		// do something on event "eventName" with parameters send within event-trigger
		$model = $event->getParam('model');
		/* @var $model nameOfTheModelClass */ // to trigger IDE
		$moreParams = $event->getParam('moreParams');	}
    
## Trigger and Listen in one class
If you use two different class-variables $events and $staticEvents you can combine event-triggering and event-listening in one class without problems.
