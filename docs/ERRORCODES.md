# EventCodes

## TODO STATUS OF THIS DOCUMENT
Work in progress of implementing the errorcodes as MD file!

## List of Error- and EventCodes
In the future each error or event in translate5 should have an own event code to improve support / make live for help desk easier.

Formerly this list was maintained in:
https://confluence.translate5.net/display/TAD/EventCodes

### General
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E0000"></a>E0000  | everywhere | Several | Code used for multi purposes: Mostly for debug messages below level warn, where no fixed message is needed.
| <a id="E9999"></a>E9999  | everywhere | Several | Default code used for old error messages, which are not converted yet to the new error code system.
| <a id="E0001"></a>E0001  | everywhere | HTTP client logging - SEND | The raw connection content of a internal used HTTP client is logged for debugging purposes.
| <a id="E0002"></a>E0002  | everywhere | HTTP client logging - RECEIVE | The raw connection content of a internal used HTTP client is logged for debugging purposes.
| <a id="E1352"></a>E1352  | everywhere | No access to requested URL | The currently authenticated user is not allowed to access the requested resource.<br />Since this is normally a misconfiguration of ACL rules or programming error in the GUI (missing isAllowed check) this error is locked as error.
| <a id="E1014"></a>E1014  | everywhere | Log HTTP Request | The HTTP request to the server and its parameters are logged. Generally for debugging only.
| <a id="E1015"></a>E1015  | entities | Duplicate Key | A database key for the entity to be saved does already exist.
| <a id="E1016"></a>E1016  | entities | Integrity Constraint Violation | An entity can not be added or updated since a referenced entity does not exist (anymore).<br />Or an entity can not be updated or deleted since it is referenced by other entities.
| <a id="E1019"></a>E1019  | everywhere | HTTP Status 404 | The requested URL / page was not found, the API endpoint in the application does not exist.
| <a id="E1025"></a>E1025  | everywhere | HTTP Status 422 | The PUT / POST request to that URL could not be processed due invalid given data. <br />The invalid fields are listed in the result from the server.
| <a id="E1026"></a>E1026  | everywhere | HTTP Status 422 | The File Upload did not succeed PUT / POST request to that URL could not be processed due invalid given data.<br />The invalid fields are listed in the result from the server.
| <a id="E1041"></a>E1041  | everywhere | HTTP Status 409 | The PUT / POST request to that URL could not be processed due the given data would produce an invalid state of the entity on the server.<br />If possible, the causing fields are listed in the result from the server, or the error message is self explaining.
| <a id="E1310"></a>E1310  | everywhere | HTTP Status 502 Bad Gateway | A requested service is not available, or answers with an error.
| <a id="E1027"></a>E1027  | everywhere | PHP Fatal Error | PHP Fatal error, see error message for details.
| <a id="E1029"></a>E1029  | everywhere | PHP Warning | PHP Warning, see error message for details.
| <a id="E1030"></a>E1030  | everywhere | PHP Info | PHP Info, see error message for details.
| <a id="E1072"></a>E1072  | Worker | Can not trigger worker URL: {host}:{port} Error: {errorName} ({errorNumber}) | The triggered worker URL is technically not available. Local firewall problems? Is the configured &quot;runtimeOptions.server.name&quot; available and callable from the translate5 instance? If not, it can help to configure &quot;runtimeOptions.worker.server&quot; with the local host name or the localhost IP, basicly the server adress which points to the local server and is available for the translate5 instance.
| <a id="E1073"></a>E1073  | Worker | Worker URL result is no HTTP answer!: {host}:{port} | This can only happen if the worker URL is not pointing to an translate5 instance, or if there is an error on the translate5 instance. In the latter case investigate the log for further errors.
| <a id="E1074"></a>E1074  | Worker | Worker HTTP response state was not 2XX but {state}. | This should happen only if there is an error on the translate5 instance. In the latter case investigate the log for further errors.
| <a id="E1107"></a>E1107  | Worker | Worker HTTP response state was 404, the worker system requests probably the wrong server! | Check the server URL configuration values.<br />Either runtimeOptions.worker.server or runtimeOptions.server.protocol and runtimeOptions.server.name are pointing to a wrong server / translate5 installation!<br />This can also happen, if the server name is resolving to multiple IPs on the server it self (multiple entries in the /etc/hosts for example).
| <a id="E1219"></a>E1219  | Worker | Worker &quot;{worker}&quot; failed on initialisation. | Check other errors , this message is just for debugging.
| <a id="E1201"></a>E1201  | DB | Still producing a DB DeadLock after {retries} retries. | A transaction was repeated X times after a deadlock and it is still producing a deadlock. <br />The original dead lock exception is contained in this exception.
| <a id="E1202"></a>E1202  | DB | A transaction could be completed after {retries} retries after a DB deadlock. | This is just a info / debug message to track if a deadlock occured, an that it could be successfully executed after X retries.
| <a id="E1203"></a>E1203  | DB | A transaction was rejected after a DB deadlock. | This is just a info / debug message to track if a deadlock occured, an that it was intentionally rejected.
| <a id="E1220"></a>E1220  | API Filter | Errors in parsing filters Filterstring: &quot;{filter}&quot; | The given JSON filter string can not parsed. Is the given JSON valid?
| <a id="E1221"></a>E1221  | API Filter | Illegal type &quot;{type}&quot; in filter | The given filter type does not exist.
| <a id="E1222"></a>E1222  | API Filter | Illegal chars in field name &quot;{field}&quot; | There were invalid characters in the field name. Only Alphanumeric characters and dash &quot;-&quot; and underscore &quot;_&quot; are allowed.
| <a id="E1223"></a>E1223  | API Filter | Illegal field &quot;{field}&quot; requested | The requested field does not exist.
| <a id="E1224"></a>E1224  | API Filter | Unkown filter operator &quot;{operator}&quot; from ExtJS 5 Grid Filter! | The given filter operator is invalid.
| <a id="E1225"></a>E1225  | API Filter Join | Given tableClass &quot;{tableClass}&quot; is not a subclass of Zend_Db_Table_Abstract! | The given tableClass in the join filter must be a sub class of Zend_Db_Table_Abstract
| <a id="E1293"></a>E1293  | Installation &amp; Update | The following file does not exist or is not readable and is therefore ignored: {path} | Check the existence and access rights of the mentioned file.
| <a id="E1294"></a>E1294  | Installation &amp; Update | Errors on calling database update - see details for more information. | This are the errors happend on calling the alter SQLs.
| <a id="E1295"></a>E1295  | Installation &amp; Update | Result of imported DbUpdater PHP File {path}: {result} | The output of a PHP update file is logged as info.
| <a id="E1307"></a>E1307  | Http Client | Request time out in {method}ing URL {url} | The requested service did not respond in a reasonable time.
| <a id="E1308"></a>E1308  | Http Client | Requested URL is DOWN: {url} | The requested service is either not available or not reachable.
| <a id="E1309"></a>E1309  | Http Client | Empty response in {method}ing URL {url} | The requested service returns an empty response, this may indicate a problem (crash) on the service side.


### Request params validation
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E2000"></a>E2000  | Validation | Param &quot;{0}&quot; - is not given | Param is not given at all, or given but is empty
| <a id="E2001"></a>E2001  | Validation | Value &quot;{0}&quot; of param &quot;{1}&quot; - is in invalid format | Param is given but has an invalid format. For example: wrong email format, wrong number format, etc
| <a id="E2002"></a>E2002  | Validation |No object of type &quot;{0}&quot; was found by key &quot;{1}&quot; | No object was found in the database by the given key
| <a id="E2003"></a>E2003  | Validation |Wrong value | Given value is not equal to the value that is stored/provided by server-side
| <a id="E2004"></a>E2004  | Validation |Value &quot;{0}&quot; of param &quot;{1}&quot; - is not in the list of allowed values | There is the list of allowed values, but given value is <strong>not in</strong> that list
| <a id="E2005"></a>E2005  | Validation |Value &quot;{0}&quot; of param &quot;{1}&quot; - is in the list of disabled values | There is the list of disabled values, but given value is <strong>in</strong> that list
| <a id="E2006"></a>E2006  | Validation |Value &quot;{0}&quot; of param &quot;{1}&quot; - is not unique. It should be unique. | The given value already exists within certain column of certain database table
| <a id="E2007"></a>E2007  | Validation | Extension &quot;{0}&quot; of file &quot;{1}&quot; - is not in the list of allowed values | The file type uploaded in the termportal is not allowed


### Authentication

| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E1156"></a>E1156  | Authentication | Tried to authenticate via hashAuthentication, but feature is disabled in the config! | Please set runtimeOptions.hashAuthentication in the configuration to one of the following values:<br />disabled: the feature is disabled and produces this error.<br />dynamic: use a dynamic auth hash.<br />static: use a static auth hash.<br />See [Single Click Authentication](https://confluence.translate5.net/display/TAD/Single+Click+Authentication).
| <a id="E1289"></a>E1289  | Authentication | Ip based authentication: Customer with number ({number}) does't exist. | The configured customer in&nbsp; runtimeOptions.authentication.ipbased.IpCustomerMap&nbsp;<br />configuration does not exist. Default customer will be used instead.
| <a id="E1290"></a>E1290  | Authentication | Ip based authentication: User with roles:({configuredRoles}) is not allowed to authenticate ip based. | There is no configured ip based roles for the ip based authentication or the configured roles are not allowed per acl
| <a id="E1332"></a>E1332  | Authentication | Fail or success info log for sessionToken based authentication. | 
| <a id="E1342"></a>E1342  | Authentication: Session impersonate | The parameter login containing the desired username is missing. | The parameter login containing the desired username is missing.

### Configuration
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E1292"></a>E1292   | Configuration | Not enough rights to modify config with level : {level} | The config can not be&nbsp;modified because the user has not have rights to do so
| <a id="E1296"></a>E1296   | Task Configuration | Unable to modify config {name}. The task is not in import state. | The config can not be&nbsp;modified because the config is with level task_import and the task state is not import.
| <a id="E1297"></a>E1297   | Task Configuration | Unable to load task config. &quot;taskGuid&quot; is not set for this entity. | taskGuid is not set for this entity.
| <a id="E1298"></a>E1298   | Customer Configuration | Unable to load customer config. &quot;id&quot; is not set for this entity. | id is not set for this entity.
| <a id="E1299"></a>E1299   | User Configuration | Not allowed to load user config for different user. | The current request to load the user config is not for the currently authenticated user.
| <a id="E1324"></a>E1324   | Configuration | Updated config with name &quot;{name}&quot; to &quot;{value}&quot; | Log info when configuration value is updated.
| <a id="E1363"></a>E1363   | Configuration | Configuration value invalid: {errorMsg} | The given configuration value is invalid, check the configuration description or default value to find out what is wrong.

### Categories
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E1179"></a>E1179  | Category Assocs | Save category assocs: categories could not be JSON-decoded with message: {msg} | The given data for the categories is wrong; check this first.

### Users &amp; Customers
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :------------------- 
| <a id="E1047"></a>E1047  | Customer | A client cannot be deleted as long as tasks are assigned to this client. | Remove all tasks first from that customer.
| <a id="E1063"></a>E1063  | Customer | The given client-number is already in use. | There exists already a customer with that client-number.
| <a id="E1048"></a>E1048  | User | The user can not be deleted, he is PM in one or more tasks. | Change the PMs in the affected tasks.
| <a id="E1094"></a>E1094  | User | User can not be saved: the chosen login does already exist. | Use a different login.
| <a id="E1095"></a>E1095  | User | User can not be saved: the chosen userGuid does already exist. | Use a different userGuid, if no userGuid was provided explicitly, just save again to generate a new one.
| <a id="E1104"></a>E1104  | Customer | The given domain is already in use. | The given domain is already defined for one of the customers.
| <a id="E1347"></a>E1347  | User | Auto user&nbsp;assignment with defining source and target language for a user is no longer possible. Please use &quot;user assoc default&quot; api endpoint. | The auto user&nbsp;assignment&nbsp; via user source and target is removed. From now on, there is new api endpoint to do this: Default Task User Associations.

### Task &amp; Workflow
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1011"></a>E1011  | Task; Workflow | Multi Purpose Code logging in the context of a task | Multi Purpose code for several info logs around a task.<br />Also important in Context of workflow.
| <a id="E1012"></a>E1012  | Job; Workflow | Multi Purpose Code logging in the context of jobs (task user association) | Multi Purpose Code logging in the context of jobs (task user association)<br />Also important in Context of workflow.
| <a id="E1013"></a>E1013  | Workflow only | Multi Purpose Code logging in the context of pure workflow processing | Multi Purpose Code logging in the context of workflow processing
| <a id="E1042"></a>E1042  | Task | The task can not be removed due it is used by a user. | One user has opened the task for reading or editing the task. Therefore this task can not be deleted.
| <a id="E1043"></a>E1043  | Task | The task can not be removed due it is locked by a user. | One user has opened the task for editing the task, or some other action has locked the task.<br />Therefore this task can not be deleted.
| <a id="E1044"></a>E1044  | Task | The task can not be locked for deletion. | The task must be locked by the deleting user, before it can be deleted. This lock could not be set.
| <a id="E1045"></a>E1045  | Task; ManualStatusCheck Plug-In | The Task can not be set to finished, since not all segments have a set status. | Each segment must have set a status in order to finish the task.
| <a id="E1046"></a>E1046  | Task | The current task status does not allow that action. | This error occurs if the current status of the task does not allow the triggering action.
| <a id="E1049"></a>E1049  | Task; ArchiveTaskBeforeDelete Plug-In | Task could not be locked for archiving, stopping therefore the delete call. | The task must be locked by the deleting user, before it can be deleted. This lock could not be set.
| <a id="E1064"></a>E1064  | Task | The referenced customer does not exist (anymore). | The user tried to add a non existence client to a task. Probably the customer was deleted in the mean time.
| <a id="E1159"></a>E1159  | Task | Task usageMode can only be modified, if no user is assigned to the task. | Remove first all assigned users from the task, change the usage mode and reassign the users again.
| <a id="E1171"></a>E1171  | Workflow only | Workflow Action: JSON Parameters for workflow action call could not be parsed with message: {msg} | Check the parameters field of the mentioned action in the additional log data, it can not be parsed as JSON.
| <a id="E1172"></a>E1172  | Workflow User Preferences | The referenced user is not associated to the task or does event not exist anymore. | Reload the task properties.
| <a id="E1205"></a>E1205  | Workflow User Preferences | Missing workflow step in given data. | Missing workflow step in given data.
| <a id="E1206"></a>E1206  | Workflow User Preferences | Missing workflow step in given data. | Missing workflow step in given data.
| <a id="E1210"></a>E1210  | Task; Workflow | The&nbsp;targetDeliveryDate for the task is deprecated. Use the LEK_taskUserAssoc deadlineDate instead. | Temporary warning for the task&nbsp;targetDeliveryDate (delivery date) api field. Deadline date should be defined on user- task-assoc level.
| <a id="E1216"></a>E1216  | Task reference files | A non existent reference file &quot;{file}&quot; was requested. | The requested file does not exist on the disk. Either this was a malicious file request, or some one deleted the files on the disk manually.
| <a id="E1217"></a>E1217  | Task | TaskType not valid. | Check the parameter given as taskType.
| <a id="E1251"></a>E1251  | Workflow Manager | Workflow to class &quot;{className}&quot; not found! | In the application a not existent worfklow class was requested. This is probably a programming error.
| <a id="E1252"></a>E1252  | Workflow Manager | Workflow with ID &quot;{workflowId}&quot; not found! | There is a task configured with a not (anymore) existent workflow.
| <a id="E1253"></a>E1253  | Workflow Manager | {className}::$labels has to much / or missing labels! | The translatable labels of the the requested workflow do not cover all or to much strings of the workflow.
| <a id="E1258"></a>E1258  | Project/Task | Missing projectId parameter in the delete project request. | In the delete project request the project id was not provided.
| <a id="E1280"></a>E1280  | Task/User | The format of the segmentrange that is assigned to the user is not valid. Example: 1-3,5,8-9 | Check the input for the editable segments; must be like: &quot;1-3,5,8-9&quot;.
| <a id="E1281"></a>E1281  | Task/User | The content of the segmentrange that is assigned to the user is not valid. | Make sure that the values are not reverse and do not overlap (neither in itself nor with other users of the same role).
| <a id="E1339"></a>E1339  | Task | Missing mandatory parameter taskGuid. | The taskGuid is not provided as request parameter.
| <a id="E1341"></a>E1341  | Task | You tried to open or edit another task, but you have already opened another one in another window. Please press F5 to open the previous one here, or close this message to stay in the Taskoverview. | Only one task can be opened for editing per user. If if the user has already opened a task for editing, he/she is not able to modify tasks in task overview with different browser tab. In short words, please use only one browser tab.
| <a id="E1348"></a>E1348  | Task materialized view | The tasks materialized view was created. | Debugging information to trace the creation of a tasks materialiazed view.
| <a id="E1349"></a>E1349  | Task materialized view | The tasks materialized view was dropped. | Debugging information to trace the deletion of a tasks materialiazed view.
| <a id="E1381"></a>E1381  | Current Task | Access to CurrentTask was requested but no task ID was given in the URL. | Development error: Some PHP code tried to load the currently opened task (identified by the taskid given in the URL) but no task ID was provided in the URL. So either the URL producing the request is wrongly created (no Editor.data.restpath prefix), or its just the wrong context where the CurrentTask was accessed.
| <a id="E1382"></a>E1382  | Current Task | Access to CurrentTask was requested but it was NOT initialized yet. | Development error: Some PHP code tried to access the currently opened task but it was not loaded yet. This can be done calling TaskContextTrait::initCurrentTask.
| <a id="E1395"></a>E1395  | Task-Operation | The Task-Operation &quot;{operation}&quot; can not be started when the task is in state &quot;{taskstate}&quot; | The Task-Operation can not be started if the task is in the given state
| <a id="E1396"></a>E1396  | Task-Operation | The Task-Operation &quot;{operation}&quot; can not be started, there is already an operation running | Another User may already started an operation for the task and therefore this operation has to be finished before another operation is started. Try again after the running operation has finished.
| <a id="E1399"></a>E1399  | Task auto delete | No task taskLifetimeDays configuration defined. | Please set runtimeOptions.taskLifetimeDays in configuration.
| <a id="E1400"></a>E1400  | Task Backup | Task could not backuped there fore it also was not deleted. | Please check the log for further information and validate your workflow action configuration for deleteOldEndedTasks action
| <a id="E1401"></a>E1401  | Task Backup | Could not zip the export of the task | Check the application and PHP log for further information.
| <a id="E1402"></a>E1402  | Task Backup | Task successfully removed. ID: {id} {name} | The given task was successfully removed (and backuped before if configured).


### Project
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1284"></a>E1284  | Project | Projects are not editable. | For task with task-type project the task put action is not allowed.

### Jobs (Association between Tasks and Users)
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1061"></a>E1061 | Job | The job can not be removed, since the user is using the task. | The user of the job has opened the associated task for reading or editing. Therefore this job can not be deleted.
| <a id="E1062"></a>E1062  | Job | The&nbsp;job can not be removed, since the task is locked by the user. | The user of the job has opened the associated task for editing. Therefore this job can not be deleted.
| <a id="E1160"></a>E1160  | Job | The competitive users can not be removed, probably some other user was faster and you are not assigned anymore to that task. | Leave the task and try to reopen it again.
| <a id="E1161"></a>E1161  | Job | The&nbsp;job can not be modified, since the user has already opened the task for editing. You are to late. | Advice the user to leave the task, so that you can edit the Job again.
| <a id="E1163"></a>E1163  | Job | Your job was removed, therefore you are not allowed to access that task anymore. | Refresh the task overview.
| <a id="E1164"></a>E1164  | Job | You tried to open the task for editing, but in the meantime you are not allowed to edit the task anymore. | Refresh the task overview.
| <a id="E1232"></a>E1232  | Job | Job creation: role &quot;lector&quot; is deprecated, use &quot;reviewer&quot; instead! | Please use the role &quot;reviewer&quot; instead!

### Import
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1083"></a>E1083  | Fileparser | The encoding of the file &quot;{fileName}&quot; is none of the encodings utf-8, iso-8859-1 and win-1252. | The named file is not encoded in one of the three supported types.
| <a id="E1084"></a>E1084  | Fileparser | Given MID was to long (max 1000 chars), MID: &quot;{mid}&quot;. | One of the MIDs in the uploaded file is too long. Maximal 1000 bytes are allowed.
| <a id="E1000"></a>E1000  | SdlXliff Fileparser | The file &quot;{filename}&quot; has contained SDL comments, but comment import is disabled: the comments were removed! | Enable comment import (see [SDLXIFF](https://confluence.translate5.net/display/BUS/SDLXLIFF), or omit that file, or remove SDL comments for a successful import.
| <a id="E1001"></a>E1001  | SdlXliff Fileparser | The opening tag &quot;{tagName}&quot; contains the tagId &quot;{tagId}&quot; which is not SDLXLIFF conform! | That &quot;{tagName}&quot; contains the tagId &quot;{tagId}&quot; is not valid SDLXLIFF according to our reverse engineering of the SDLXLIFF format. Probably the parse has to be extended here.
| <a id="E1002"></a>E1002  | SdlXliff Fileparser | Found a closing tag without an opening one. Segment MID: &quot;{mid}&quot;. | Invalid XML structure in the mentioned SDLXLIFF file.
| <a id="E1003"></a>E1003  | SdlXliff Fileparser | There are change-markers in the sdlxliff-file &quot;{filename}&quot;, but the import of&nbsp;change-markers is disabled. | Enable&nbsp;change-markers import (see [SDLXIFF](https://confluence.translate5.net/display/BUS/SDLXLIFF)), or omit that file, or remove change-markers for a successful import.
| <a id="E1004"></a>E1004  | SdlXliff Fileparser | Locked-tag-content was requested but tag does not contain a xid attribute. | Invalid SDLXIFF according to our reverse engineering.
| <a id="E1005"></a>E1005  | SdlXliff Fileparser | &lt;sdl:seg-defs was not found in the current transunit: &quot;{transunit}&quot; | Invalid SDLXIFF according to our reverse engineering.
| <a id="E1006"></a>E1006  | SdlXliff Fileparser | Loading the tag information from the SDLXLIFF header has failed! | Check if SDLXLIFF header content is valid XML.
| <a id="E1007"></a>E1007  | SdlXliff Fileparser | The tag &quot;{tagname}&quot; is not defined in the &quot;_tagDefMapping&quot; list. | Invalid SDLXIFF according to our reverse engineering. The used tags are either not contained in the tag definition list in the header, or the parser did not parse the header completely.
| <a id="E1008"></a>E1008  | SdlXliff Fileparser | The tag ID &quot;{tagId}&quot; contains a dash &quot;-&quot; which is not allowed! | Dashes are not allowed, since this may interfere with the GUI where dashes are used as delimiter in the IDs.
| <a id="E1009"></a>E1009  | SdlXliff Fileparser | The source and target segment count does not match in transunit: &quot;{transunit}&quot;. | Invalid SDLXIFF according to our reverse engineering.
| <a id="E1010"></a>E1010  | SdlXliff Fileparser | The tag &quot;{tagname}&quot; was used in the segment but is not defined in the &quot;_tagDefMapping&quot; list! | Invalid SDLXIFF according to our reverse engineering. The used tags are either not contained in the tag definition list in the header, or the parser did not parse the header completely.
| <a id="E1291"></a>E1291  | SdlXliff Fileparser | The file &quot;{filename}&quot; did not contain any translation relevant content. Either all segments are set to translate=&quot;no&quot; or the file was not segmented. | Either all segments in the SDLXLIFF are set to translate=no or no trans-unit contains segmented content.<br />Studio is segmenting the source content only if the pre-translation was used or at least one segment was opened, edited and saved there.
| <a id="E1322"></a>E1322  | SdlXliff Fileparser | A CXT tag type x-tm-length-info with a unknown prop type &quot;{propType}&quot; was found. | The provided CXT tag of type x-tm-length-info did contain an unknown value in the prop &gt; value tag with attribtute key = 'length_type'.
| <a id="E1323"></a>E1323  | SdlXliff Fileparser | The transUnit contains sdl:cxt tags, but we assume that tags only in the group tag! | According to our reverse engineering we have seen the sdl:cxt tag only in the group tag surrounding a transUnit tag but never in the transUnit tag directly. If that ever happen we have to implement that usage form.
| <a id="E1017"></a>E1017  | CSV Fileparser | The regex {regex} matches the placeholderCSV string {placeholder} that is used in the editor_Models_Import_FileParser_Csv class to manage the protection loop. | It is not allowed to use a regular expression to protect CSV content which matches the internally used placeholder.<br />Please find another solution to protect what you need to protect in your CSV via Regular Expression.
| <a id="E1018"></a>E1018  | CSV Fileparser | The string $this-&gt;placeholderCSV ({placeholder}) had been present in the segment before parsing it. This is not allowed. | The mentioned placeholder string is used for internally replacement, therefore it may not occur in the real CSV content.
| <a id="E1075"></a>E1075  | CSV Fileparser | Error on parsing a line of CSV. Current line is: &quot;{line}&quot;. Error could also be in previous line! | The mentioned line could not be parsed as CSV line. Check the CSV content.
| <a id="E1076"></a>E1076  | CSV Fileparser | In the line &quot;{line}&quot; there is no third column. | Each line must contain at least 3 columns: mid, source and target column. Check the CSV content.
| <a id="E1077"></a>E1077  | CSV Fileparser | No linebreak found in CSV: {file} | No valid line break(s) were found in the CSV, does it contain only one line?
| <a id="E1078"></a>E1078  | CSV Fileparser | No header column found in CSV: &quot;{file}&quot; | No header columns could be found, check the CSV content.
| <a id="E1079"></a>E1079  | CSV Fileparser | In application.ini configured column-header(s) &quot;{headers}&quot; not found in CSV: &quot;{file}&quot; | The header column names of the CSV do not match to the configured header column names in &quot;runtimeOptions.import.csv.fields&quot;.&nbsp; The missing fields according to the configuration are shown in the error message.
| <a id="E1080"></a>E1080  | CSV Fileparser | Source and mid given but no more data columns found in CSV: &quot;{file}&quot; | Each line must contain at least 3 columns: mid, source and target column. Check the CSV content.
| <a id="E1067"></a>E1067  | XLF 1.2 Fileparser | MRK/SUB tag of source not found in target with Mid: &quot;{mid}&quot; | In the XLF a MRK or SUB tag was referenced in the source, but the referenced segment with the given mid was not found.
| <a id="E1068"></a>E1068  | XLF 1.2 Fileparser | MRK/SUB tag of target not found in source with Mid(s): &quot;{mids}&quot; | In the XLF a MRK or SUB tag was referenced in the target, but the referenced segment with the given mid was not found.
| <a id="E1069"></a>E1069  | XLF 1.2 Fileparser | There is other content as whitespace outside of the mrk tags. Found content: {content} | Translate5 interprets the XLIFF 1.2 specification in a way that in a segmented segment there may not be any other content as whitespace outside between the &lt;mrk type=&quot;seg&quot;&gt; tags. If this is the case translate5 can not import the XLF file.
| <a id="E1070"></a>E1070  | XLF 1.2 Fileparser | SUB tag of {field} is not unique due missing ID in the parent node and is ignored as separate segment therefore. | The XML node surrounding a &lt;sub&gt; tag must contain an id in order to identfy that sub tag.
| <a id="E1071"></a>E1071  | XLF 1.2 Fileparser | MRK tag of {field} has no MID attribute. | The given MRK tag does not contain a MID attribute.
| <a id="E1194"></a>E1194  | XLF 1.2 Fileparser | The file &quot;{file}&quot; contains &quot;{tag}&quot; tags, which are currently not supported! Stop Import. | Contact the support to implement the import of the new tags.
| <a id="E1195"></a>E1195  | XLF 1.2 Fileparser | A trans-unit of file &quot;{file}&quot; contains MRK tags other than type=seg, which are currently not supported! Stop Import. | Contact the support to implement the import of the other mrk tags.
| <a id="E1196"></a>E1196  | XLF 1.2 Fileparser | Whitespace in text content of file &quot;{file}&quot; can not be cleaned by preg_replace. Error Message: &quot;{pregMsg}&quot;. Stop Import. | Check the file content for validity.
| <a id="E1232"></a>E1232  | XLF 1.2 Fileparser | XLF Parser supports only XLIFF Version 1.1 and 1.2, but the imported xliff tag does not match that criteria: {tag} | Check the file content for validity.
| <a id="E1363"></a>E1363  | XLF 1.2 Fileparser | Unknown XLF tag found: {tag} - can not import that. | The listed tag is not known in XLF, therefore it can not be imported. Get in contact with the support.
| <a id="E1273"></a>E1273  | DisplayText XML Fileparser | The XML of the DisplayText XML file &quot;{fileName} (id {fileId})&quot; is invalid! | The internal XML parser can not parse the structure of the uploaded XML file, probably it is not well formed.
| <a id="E1274"></a>E1274  | DisplayText XML Fileparser | The DisplayText XML file &quot;{fileName} (id {fileId})&quot; does not contain any translation relevant segments. | The uploaded XML could be parsed, but there were no Displaymessage tags containing any data.
| <a id="E1275"></a>E1275  | DisplayText XML Fileparser | Element &quot;Inset&quot; with ID {id} has the invalid type {type}, only type &quot;pixel&quot; is supported! | In &quot;Inset&quot; tag elements the attribute type must contain the value &quot;pixel&quot;.
| <a id="E1276"></a>E1276  | DisplayText XML Fileparser | Element &quot;Len&quot; with ID {id} has the invalid type {type}, only type &quot;pixel&quot; is supported! | In &quot;Len&quot; tag elements the attribute type must contain the value &quot;pixel&quot;.
| <a id="E1277"></a>E1277  | DisplayText XML Fileparser | Unknown XML tags &quot;{tag}&quot; discovered in file &quot;{fileName} (id {fileId})&quot;! | There were some unknown tags in the uploaded XML. This may not be a problem, but should be checked.
| <a id="E1020"></a>E1020  | Relais Import | Errors in processing relais files: The following MIDs are present in the relais file&nbsp;&quot;{fileName}&quot; but could not be found in the source file, the relais segment(s) was/were ignored. MIDs: {midList} | For the listed MIDs the source segment was not found to the MID in the relais file, the Relais segment was ignored on import. The affected file is also logged.
| <a id="E1021"></a>E1021  | Relais Import | Errors in processing relais files: Source-content of relais file&nbsp;&quot;{fileName}&quot; is not identical with source of translated file. Relais target is left empty. Segments: {segments}&nbsp; | For the listed segments the source content in the source file and in the relais file was different, therefore no relais target content was saved to the segment.
| <a id="E1022"></a>E1022  | Relais Import | Errors in adding relais segment: Source-content of relais file &quot;{fileName}&quot; is identical with source of translated file, but still original segment not found in the database: {segments} | This issue is similar to [E1021](#E1021), first a matching source segment was found to the relais segment, the source content equals, but the relais target data could not be saved. Investigate additional debug content!
| <a id="E1112"></a>E1112  | Relais Import | Task was configured with relais language, but some relais file were not found. See Details. | Some of the work files do not have a corresponding file in the relais folder. In the details of the error the work files without a relais file are listed. Compare them with the files in the imported task.
| <a id="E1023"></a>E1023  | TBX Parser | Unable to read the provided tbx file {filename} | The provided file for parsing can is not readable.
| <a id="E1024"></a>E1024  | XML Parser | Invalid XML: expected closing &quot;{closingTag}&quot; tag, but got tag &quot;{receivedTag}&quot;. Opening tag was: {openingTag} | Invalid xml chunk found while parsing xml file.
| <a id="E1028"></a>E1028  | TBX Parser | {message}. \n Term collection name: {name} | Log the term collection exception/info produced while the tbx parser is running for the term.
| <a id="E1031"></a>E1031  | Task Import - File Upload | A file &quot;{filename}&quot; with an unknown file extension &quot;{ext}&quot; was tried to be imported. | The uploaded file type is currently not supported.
| <a id="E1032"></a>E1032  | Task Import | The passed source language &quot;{language}&quot; is not valid. | The source language given for the new task is invalid or not found in the languages table of the application.
| <a id="E1033"></a>E1033  | Task Import | The passed target language &quot;{language}&quot; is not valid. | The target language given for the new task is invalid or not found in the languages table of the application.
| <a id="E1034"></a>E1034  | Task Import | The import did not contain files for the relais language &quot;{language}&quot;. | No importable data was found in the import package for the chosen relais language.
| <a id="E1035"></a>E1035  | Task Import | The given taskGuid &quot;{taskGuid}&quot; was not valid GUID. | Please provide a valid GUID.
| <a id="E1036"></a>E1036  | Task Import | The given userGuid &quot;{userGuid}&quot; was not valid GUID. | Please provide a valid GUID.
| <a id="E1037"></a>E1037  | Task Import | The given userName &quot;{userName}&quot; was not valid user name. | Please provide a valid username.
| <a id="E1038"></a>E1038  | Task Import | The import root folder does not exist. Path &quot;{folder}&quot;. | The provided ZIP package is unzipped on the server for further processing. That unzipped folder can not be found. This is probably a problem of permissions on the server.
| <a id="E1039"></a>E1039  | Task Import | The imported package did not contain a valid &quot;{workfiles}&quot; folder. | The uploaded ZIP package did not contain a &quot;workfiles&quot; folder, which contains the data to be imported. See [ZIP import package format](https://confluence.translate5.net/display/BUS/ZIP+import+package+format).
| <a id="E1040"></a>E1040  | Task Import | The imported package did not contain any files in the &quot;{workfiles}&quot; folder. | The &quot;workfiles&quot; Folder in the imported ZIP package was empty, so there is nothing to be imported.
| <a id="E1052"></a>E1052  | Task Import | TODO Some of the tasks metaData can not be imported. See previous exception. | Some of the tasks metaData can not be imported. See previous exception.
| <a id="E1325"></a>E1325  | Task Import; Task-config template | Something went wrong when loading task config template with name: {filename}. The error was:{errorMessage} | Error happened when the config template is being parsed or the parsed configs are saved as task specific configs
| <a id="E1327"></a>E1327  | Task Import; Task-config template | The config value {name} given in the task-config.ini does not exist in the main configuration and is ignored therefore. | Check the config name, change it or remove it from the task-config.ini
| <a id="E1053"></a>E1053  | Task Import; Pixel-Mapping MetaData | Pixel-Mapping: Import failed due not found customer specified by customer number in excel - client nr: {lastClientNr} |
| <a id="E1054"></a>E1054  | Task Import; Pixel-Mapping MetaData | Pixel-Mapping: missing default-values for pixel-width for font-size {fontSize}. Add the missing values to the config. | Add the missing value in your defaultPixelWidths-settings in the task template config.
| <a id="E1096"></a>E1096  | Task Import; Pixel-Mapping MetaData | Pixel-Mapping: ignored one ore more lines of the excel due one or more empty columns. | Check the imported pixel-mapping.xlsx some of the needed columns were empty, see also error details for the collected lines with empty columns.
| <a id="E1278"></a>E1278  | Task Import; Pixel-Mapping MetaData | Segment length calculation: No pixel-width set for several characters.<br />Default width is used. See affected characters in extra data. | For the listed characters no width was defined, so the configured default value as fallback is used. <br />The missing characters are listed as unicode charpoints and the real character in parathensis.
| <a id="E1060"></a>E1060  | Task Import | For the fileextension &quot;{extension}&quot; no parser is registered. For available parsers see log details. | The user tried to import a file which can not be imported by the native import converters. See the log details for the available native importable file formats.<br />Otherwise consider to enable [Okapi](https://confluence.translate5.net/display/CON/Okapi) to convert the uploaded file into a native importable XLF format.
| <a id="E1135"></a>E1135  | Task Import | There are no importable files in the Task. The following file extensions can be imported: {extensions} | There is no file in the import package which can be imported. Neither native by translate5, nor via a converter plug-in like [Okapi](https://confluence.translate5.net/display/CON/Okapi).
| <a id="E1136"></a>E1136  | Task Import | Some files could not be imported, since there is no parser available. For affected files see log details. | The user tried to import one or more files which can not be imported. Neither native by translate5, nor via a converter plug-in like [Okapi](https://confluence.translate5.net/display/CON/Okapi). See the log details for the affected files.
| <a id="E1166"></a>E1166  | Task Import | Although there were importable files in the task, no files were imported. Investigate the log for preceeding errors. | There was at least one importable file in the package which can be imported, but the import process did not import any file. Probably there was another error before, for example with a file converter plug-in like [Okapi](https://confluence.translate5.net/display/CON/Okapi).
| <a id="E1190"></a>E1190  | XLF 1.2 Fileparser | The XML of the XLF file &quot;{fileName} (id {fileId})&quot; is invalid! | The provided XLF file contains no valid XML.<br />See the task log, the concrete XML error should be logged there too.
| <a id="E1191"></a>E1191  | XLF 1.2 Fileparser | The XLF file &quot;{fileName} (id {fileId})&quot; does not contain any translation relevant segments. | Since there are no importable segments in the file, omit the file in import.
| <a id="E1193"></a>E1193  | Imported Matchrate Type | File &quot;{file}&quot; contains unknown matchrate types. See details. | In the mentioned file there are matchrate types not known to translate5.
| <a id="E1241"></a>E1241  | DataProvider Zip | DataProvider Zip: zip file could not be opened: &quot;{zip}&quot; | Check if the uploaded file is a valid ZIP file.
| <a id="E1242"></a>E1242  | DataProvider Zip | DataProvider Zip: content from zip file could not be extracted: &quot;{zip}&quot; | Check if the uploaded file is a valid ZIP file.
| <a id="E1243"></a>E1243  | DataProvider Zip | DataProvider Zip: TaskData Import Archive Zip already exists: &quot;{target}&quot; | Remove the Archive ZIP manually.
| <a id="E1244"></a>E1244  | DataProvider SingleUpload | DataProvider SingleUpload: Uploaded file &quot;{file}&quot; cannot be moved to &quot;{target}&quot; | Please contact the support.
| <a id="E1245"></a>E1245  | DataProvider | DataProvider: Could not create folder &quot;{path}&quot; | Please contact the support.
| <a id="E1246"></a>E1246  | DataProvider | DataProvider: Temporary directory does already exist - path: &quot;{path}&quot; | Remove the already existing path manually.
| <a id="E1247"></a>E1247  | DataProvider Directory | DataProvider Directory: Could not create archive-zip | Please contact the support.
| <a id="E1248"></a>E1248  | DataProvider Directory | DataProvider Directory: The importRootFolder &quot;{importRoot}&quot; does not exist! | Create the import root folder manually.
| <a id="E1249"></a>E1249  | DataProvider ZippedUrl | DataProvider ZippedUrl: fetched file can not be saved to path &quot;{path}&quot; | Check the fetched URL and the location where the file should be saved to.
| <a id="E1250"></a>E1250  | DataProvider ZippedUrl | DataProvider ZippedUrl: ZIP file could not be fetched from URL &quot;{url}&quot; | Check the fetched URL.
| <a id="E1265"></a>E1265  | DataProvider&nbsp;Factory | DataProvider Factory: The task to be cloned does not have a import archive zip! Path: {path} | The task to be cloned does not have a import archive zip.
| <a id="E1338"></a>E1338  | Task Import | IMPORTANT: The &quot;proofRead&quot; folder in the zip import package is deprecated from now on. In the future please always use the new folder &quot;workfiles&quot; instead. All files that need to be reviewed or translated will have to be placed in the new folder &quot;workfiles&quot; from now on. In some future version of translate5 the support for &quot;proofRead&quot; folder will be completely removed. Currently it still is supported, but will write a &quot;deprecated&quot; message to the php error-log. | The proofRead folder name in the task import zip package should be no longer used. Please use "workfiles" instead.
| <a id="E1369"></a>E1369  | DataProvider Project | DataProvider Project: No matching work-files where found for the task. | No matching work files were found for the currently processed task upload. This can happen when for the current task there is no matching language or filetype provided in the request parameters.
| <a id="E1372"></a>E1372  | DataProvider Zip | DataProvider Zip: Uploaded zip file &quot;{file}&quot; cannot be moved to &quot;{target} | Unable to copy the zip file to the target location.
| <a id="E1378"></a>E1378  | Task Import Callback | The task import callback HTTP status code is {code} instead 200. | See extra data for complete result.
| <a id="E1379"></a>E1379  | Task Import | The task import was cancelled after {hours} hours. | Normally this happens only if the import it self was either crashed in a very unusal way or the server was restarted while import.
| <a id="E1384"></a>E1384  | DataProvider Project | DataProvider Project: Maximum number of allowable file uploads has been exceeded. | The number of uploaded files is over the defined limit in php. Increase max_file_uploads php configuration to allow more files to be uploaded.
| <a id="E1394"></a>E1394  | All finish of a role Callback | All finish of a role callback HTTP status code is {code} instead 200. | The configured callback when all users are finishing the role does return different HTTP status then 200. See extra data for more info.


### Export
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1398"></a>E1398  | Task Export | Export folder not found or not write able: {folder} | Contact the sys admin to check the folder access rights
| <a id="E1085"></a>E1085  | Task Export | this-&gt;_classNameDifftagger must be defined in the child class. | This is a developer issue, contact the developers.
| <a id="E1086"></a>E1086  | Task Export | Error in Export-Fileparsing. instead of a id=&quot;INT&quot; and a optional field=&quot;STRING&quot; attribute the following content was extracted: &quot;{content}&quot; | The parsing of the internal &lt;lekTargetSeg/&gt; placeholders in the skeleton data failed.
| <a id="E1087"></a>E1087  | Task Export | See E1086 |
| <a id="E1088"></a>E1088  | Task Export | Error in diff tagging of export. For details see the previous exception. | The export with enabled automatic diff of the content fails. The original message of the export should be attached.
| <a id="E1089"></a>E1089  | Task Export with Diff | Tag syntax error in the segment content. No diff export is possible. The segment had been: &quot;{segment}&quot; | In this segment for one closing tag no corresponding opening tag exists - or the tagorder had been syntactically incorrect already before the import in the editor. Therefore it is not possible to create an export with sdl-change-marks in it. Try to export without change-marks.
| <a id="E1090"></a>E1090  | Task Export with Diff | The number of opening and closing g-Tags had not been the same! The Segment had been: &quot;{segment}&quot; | Similar to E1089.
| <a id="E1091"></a>E1091  | Task Export with Diff | See E1089 |
| <a id="E1092"></a>E1092  | Task Export with Diff | See E1090 |
| <a id="E1093"></a>E1093  | Task Export with Diff | See E1090 |
| <a id="E1143"></a>E1143  | Task Export | ExportedWorker: No Parameter &quot;zipFile&quot; given for worker. | This is an implementantion error.
| <a id="E1144"></a>E1144  | Task Export | ExportedWorker: No Parameter &quot;folderToBeZipped&quot; given for worker. | This is an implementantion error.
| <a id="E1145"></a>E1145  | Task Export | Could not create export-zip. |
| <a id="E1146"></a>E1146  | Task Export | The task export folder does not exist, no export ZIP file can be created. | The user has probably clicked multiple times on the export button while the first export was still running.
| <a id="E1147"></a>E1147  | Task Export | The task export folder does not exist or is not writeable, no export ZIP file can be created. | See E1146.
| <a id="E1149"></a>E1149  | Task Export | Export: Some segments contains tag errors. | See error details for affected segments and details.
| <a id="E1157"></a>E1157  | Task Export | Export: the file &quot;{file}&quot; could not be exported, since had possibly already errors on import. | See error details for affected file and details.
| <a id="E1170"></a>E1170  | Task Metadata Export | The Metadata of the currently filtered tasks can not be exported as Excel-file. |


### Language Resources
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1257"></a>E1257  | Language Resources; Service | The LanguageResource-Service &quot;{serviceType}&quot; is not configured. Please check this confluence-page for more details: &quot;{url}&quot; | When adding a LanguageResource, translate5 shows all LanguageResource-Services that translate5 can handle, no matter if they are already configured or not. If an unconfigured service is chosen, the user gets the info that more action is needed, including a link to a confluence-page with further details regarding that service.
| <a id="E1316"></a>E1316  | Language Resources; Service | The previously configured LanguageResource-Service &quot;{service}&quot; is not available anymore. | This errors happens, if a language resource was configured and associated to a task, and after that the configuration (the URL to the server) was removed again.
| <a id="E1050"></a>E1050  | Language Resources; Task | Referenced language resource not found. | This error can happen on the association of language resources to tasks, if the a chosen language resource was deleted in the meantime.
| <a id="E1051"></a>E1051  | Language Resources; Task | Cannot remove language resource from task, since task is used at the moment. | The association of a language resource to a task can not removed, since the affected task is used by a user at the moment.
| <a id="E1106"></a>E1106  | Language Resources | Given Language-Resource-Service &quot;{serviceType}.&quot; is not registered in the Language-Resource-Service-Manager! | This happens if a task has associated a language resource, which has completly deactivated (for example by deactivating the plug-in).
| <a id="E1158"></a>E1158  | Language Resources | A&nbsp;Language Resources cannot be deleted as long as tasks are assigned to this Language Resource. | Remove all tasks first from that resource.
| <a id="E1169"></a>E1169  | Language Resources | The task is in use and cannot be reimported into the associated language resources. | Check who or what process is locking the task
| <a id="E1282"></a>E1282  | Language Resources; Service; Connector | Language resource&nbsp;communication error. | General language resources error. It can happend when segment pre-translation is requested, general task pre-translation or instant-translate search. For more info check the error log,
| <a id="E1311"></a>E1311  | Language Resources | Could not connect to language resource {service}: server not reachable | The server of the language resource is not reachable. This means that the server/service is not running, there is a network problem, or the service is not configured properly.
| <a id="E1312"></a>E1312  | Language Resources | Could not connect to language resource {service}: timeout on connection to server | The server of the language resource is not reachable in a determined time span. This means that the server/service may be running but is not able to accept new connections.
| <a id="E1313"></a>E1313  | Language Resources | The queried language resource {service} returns an error. | See the error log for more details about the error.
| <a id="E1370"></a>E1370  | Language Resources | Empty response from language resource {service} | The service could be queried but returns an empty response, there is probably an error with the service.
| <a id="E1315"></a>E1315  | Language Resources | JSON decode error: {errorMsg} | The result of the language resource was invalid.
| <a id="E1288"></a>E1288  | Language Resources | The language code [{languageCode}] from resource [{resourceName}] is not valid or does not exist in the translate5 language code collection. | The language code received from the remote resource was not found(does not exist) in translate5 languages code collection.
| <a id="E1300"></a>E1300  | Language Resources | The LanguageResource answer did contain additional tags which were added to the segment, starting with Tag Nr {nr}. | The answer of the language resource did contain more tags as available in the source text.
| <a id="E1301"></a>E1301  | Language Resources | The LanguageResource answer did contain it|ph|ept|bpt tags, which are removed since they can not be handled. | The language resource returned some &lt;it&gt; &lt;ph&gt; &lt;ept&gt; or &lt;bpt&gt; tag, which can not be handled here. So the tag with its content was removed.
| <a id="E1302"></a>E1302  | Language Resources | The LanguageResource did contain invalid XML, all tags are getting removed. See also previous InvalidXMLException in Log. | The language resource returned invalid XML, all tags were removed. The user has to add the missing source tags manually on reviewing the pretranslated content.
| <a id="E1397"></a>E1397  | Language Resources: pivot pre-translation | Pivot pre-translation: task can not be locked for pivot pre-translation. | The task was locked by another process, so no pivot pre-translation is possible.
| <a id="E1303"></a>E1303  | Language&nbsp;Resource OpenTM2 | OpenTM2: could not add TMX data to TM | 
| <a id="E1304"></a>E1304  | Language&nbsp;Resource OpenTM2 | OpenTM2: could not create prefilled TM | The uploaded TM could not be used as new TM, see error details for more information.
| <a id="E1305"></a>E1305  | Language&nbsp;Resource OpenTM2 | OpenTM2: could not create TM | The TM could not be created, see error details for more information.
| <a id="E1306"></a>E1306  | Language&nbsp;Resource OpenTM2 | OpenTM2: could not save segment to TM | A segment in the TM could not be updated, see error details for more information.
| <a id="E1314"></a>E1314  | Language&nbsp;Resource OpenTM2 | The queried OpenTM2 TM &quot;{tm}&quot; is corrupt and must be reorganized before usage! | The mentioned TM must be reorganized via the OpenTM2 GUI. See the error log details for the affected server and the TM ID used on the server.
| <a id="E1333"></a>E1333  | Language&nbsp;Resource OpenTM2 | The queried OpenTM2 server has to many open TMs. | Something was going wrong with the internal garbage cleaning and TM closing of OpenTM2.<br />If this error persists, the OpenTM2 service should be restarted.
| <a id="E1377"></a>E1377  | Language&nbsp;Resource OpenTM2 | OpenTM2: Unable to use the memory because of the memory status {status}.&nbsp; | After 20 seconds of waiting, the memory status is still not &quot;available&quot; which makes this memory not usable.
| <a id="E1319"></a>E1319  | Language&nbsp;Resource Google Translate | Google Translate authorization failed. Please supply a valid API Key. | The configured API Key is not valid.
| <a id="E1320"></a>E1320  | Language&nbsp;Resource Google Translate | Google Translate&nbsp;quota exceeded. The character limit has been reached. | The usage quota of your&nbsp;Google Translate account is exceeded.
| <a id="E1321"></a>E1321  | Language&nbsp;Resource Term Collection | Term Collection Import: Errors on parsing the TBX, the file could not be imported. | See the log for additional errors on importing the TBX file.
| <a id="E1335"></a>E1335  | Language resource: resources usage export | Unable to create export zip archive {path}. | Unable to create export zip archive. For more info see the error log.
| <a id="E1336"></a>E1336  | Language resource: resources usage export | Unable to close the export zip archive {path}. | Unable to close export zip archive. For more info see the error log.
| <a id="E1344"></a>E1344  | Language&nbsp;Resource Microsoft Translator | Microsoft Translator returns an error: {errorNr} - {message} | See the error message from Microsoft Translator to find out what is wrong.
| <a id="E1345"></a>E1345  | Language&nbsp;Resource Microsoft Translator | Could not authorize to Microsoft Translator, check your configured credentials. | Validate your Microsoft Translator API configuration. Ensure that apiUrl, apiKey and apiLocation are configured to the same values as in your azure configuration.
| <a id="E1346"></a>E1346  | Language&nbsp;Resource Microsoft Translator | Microsoft Translator quota exceeded. A limit has been reached. | See the error log for details.
| <a id="E1358"></a>E1358  | Language&nbsp;Resource Term Collection | Term Collection Import: Unable to open zip file from file-path: {filePath} | See the error log for details.
| <a id="E1359"></a>E1359  | Language&nbsp;Resource Term Collection | Term Collection Import: Content from zip file could not be extracted. | See the error log for details.


### Terminology
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1152"></a>E1152  | Terminology | Missing mandatory collectionId for term creation. | 
| <a id="E1153"></a>E1153  | Terminology | Missing mandatory language (ID) for term creation. | 
| <a id="E1154"></a>E1154  | Terminology | GroupId was set explicitly, this is not allowed. Must be set implicit via a given termEntryId. | 
| <a id="E1105"></a>E1105  | Terminology | There is no proposal which can be confirmed. | The user tried to confirm a term proposal on a term which does not have any proposal to be confirmed.
| <a id="E1108"></a>E1108  | Terminology | There is no attribute proposal which can be confirmed. | The user tried to confirm a term attribute proposal on a attribute which does not have any proposal to be confirmed.
| <a id="E1109"></a>E1109  | Terminology | There is no proposal which can be deleted. | The user tried to&nbsp;deleted a term proposal on a term which does not have any proposal.
| <a id="E1110"></a>E1110  | Terminology | There is no attribute proposal which can be deleted. | The user tried to&nbsp;deleted a term attribute proposal on a attribute which does not have any proposal.
| <a id="E1111"></a>E1111  | Terminology | The made term proposal does already exist as different term in the same language in the current term collection. | Search for the proposed term in the collection.
| <a id="E1113"></a>E1113  | Terminology | No term collection assigned to task although tasks terminology flag is true. | This indicates a new programming error or manual change of the data in the DB. If associations between TermCollections and task are maintained via API the &quot;terminlogy&quot; flag of the task entity should be maintained correctly.
| <a id="E1114"></a>E1114  | Terminology | The associated collections don't contain terms in the languages of the task. | Could happen when all terms of a language are removed from a TermCollection via term import after associating that term collection to a task.
| <a id="E1115"></a>E1115  | Terminology | Collected terms could not be converted to XML. | Internal SimpleXML error.
| <a id="E1116"></a>E1116  | Terminology | Could not load TBX into TermTagger: TBX hash is empty. | There was probably an error on the TBX generation before.
| <a id="E1117"></a>E1117  | Terminology | Could not load TBX into TermTagger: TermTagger HTTP result was not successful! | Loading terminology (TBX generated by translate5) into a termtagger instance has failed!<br />Look also for a directly following E1133 or E1134 in the log.
| <a id="E1118"></a>E1118  | Terminology | Could not load TBX into TermTagger: TermTagger HTTP result could not be decoded! | Loading terminology (TBX generated by translate5) into a termtagger instance has probably failed. Since the answer can not be decoded this could mean that the request has failed, or it was successful without answering correctly.<br />Look also for a directly following E1133 or E1134 in the log.
| <a id="E1119"></a>E1119  | Terminology | TermTagger communication Error. | 
| <a id="E1130"></a>E1130  | Terminology | TermTagger communication Error, probably crashing the TermTagger instance. | See Details for the transferred segments to find out which content led to the crash. Probably other errors before or after that error could contain usable information too.
| <a id="E1120"></a>E1120  | Terminology | TermTagger returns an error on tagging segments. | 
| <a id="E1121"></a>E1121  | Terminology | TermTagger result could not be decoded. | Look also for a directly following E1133 or E1134 in the log.
| <a id="E1122"></a>E1122  | Terminology | TermTaggerImport Worker can not be initialized! | Some error happened on worker initialization, check logged parameters.
| <a id="E1123"></a>E1123  | Terminology | Some segments could not be tagged by the TermTagger. | Happens on termtagging while importing a task. See the error details to get a list of affected, non tagged segments.
| <a id="E1124"></a>E1124  | Terminology | Parameter validation failed, missing serverCommunication object. | Happens on live termtagging on segment editing only.
| <a id="E1125"></a>E1125  | Terminology | TermTagger DOWN: one or more configured TermTagger instances are not available: {serverList} | One or more TermTagger instances are not available. All TermTagger instances are listed with their status. Please check them manually and restart them if needed.
| <a id="E1126"></a>E1126  | Terminology | Plugin TermTagger URL config default, import or gui not defined (check config runtimeOptions.termTagger.url) | One of the required config-settings default, import or gui under runtimeOptions.termTagger.url is not defined in configuration.
| <a id="E1127"></a>E1127  | Terminology | Plugin TermTagger default server not configured: configuration is empty. | The required config-setting runtimeOptions.termTagger.url.default is not set in configuration. Value is empty.
| <a id="E1128"></a>E1128  | Terminology | See [E1122](#E1122). | 
| <a id="E1398"></a>E1398  | TODO    | SET ME BY USING ME! {TEST} | TODO DESCRIPTION / SOLUTION
| <a id="E1399"></a>E1399  | TODO    | SET ME BY USING ME! {TEST} | TODO DESCRIPTION / SOLUTION
| <a id="E1400"></a>E1400  | TODO    | SET ME BY USING ME! {TEST} | TODO DESCRIPTION / SOLUTION
| <a id="E1401"></a>E1401  | TODO    | SET ME BY USING ME! {TEST} | TODO DESCRIPTION / SOLUTION
| <a id="E1402"></a>E1402  | TODO    | SET ME BY USING ME! {TEST} | TODO DESCRIPTION / SOLUTION
| <a id="E1240"></a>E1240  | Terminology | TermTagger TIMEOUT: The configured TermTagger &quot;{termTaggerUrl}&quot; did not respond in an appropriate time. | Normally everything should be OK, the considered termtagger is probably just doing its work and can not respond to another request in an appropriate time frame.<br />Only if this error is logged multiple times further investigations should be done.
| <a id="E1129"></a>E1129  | Terminology | TermTagger DOWN: The configured TermTagger&nbsp;&quot;{termTaggerUrl}&quot; is not reachable and is deactivated in translate5 temporary. | The termTagger server as specified in the error message is deactivated automatically. On each periodical cron call (normally all 15 minutes) all termtaggers are checked for availability. If a previously deactivated TermTagger is available again, it is reactivated automatically.<br />To reactivate the TermTagger servers manually just call the following SQL statement in the Database:<code>DELETE FROM `Zf_memcache` WHERE `id` = 'TermTaggerDownList';</code>
| <a id="E1131"></a>E1131  | Terminology | TermTagger DOWN: No TermTagger instances are available, please enable them and reimport this task. | Start the TermTagger(s) if not already done.<br />If the TermTaggers were started and crashed then, see E1129 how to reactivate the TermTaggers marked as offline in translate5. After reactivation, reimport the task. The task clone functionality can be used to reimport the task.
| <a id="E1132"></a>E1132  | Terminology | Conflict in merging terminology and track changes: &quot;{type}&quot;. | Merging track changes and terminology was producing and error. See log details for more information.
| <a id="E1133"></a>E1133  | Terminology | TermTagger reports error &quot;{error}&quot;. | There was an error on the side of the termtagger, the error message was displayed.<br />Attaching more log data to that error is not possible, but in the log there should be another error (E1117, E1118, E1121) directly after that error.
| <a id="E1134"></a>E1134  | Terminology | TermTagger produces invalid JSON: &quot;{jsonError}&quot;. | The JSON produced by the TermTagger was invalid, see the JSON decode error message.<br />Attaching more log data to that error is not possible, but in the log there should be another error (E1117, E1118, E1121) directly after that error.
| <a id="E1326"></a>E1326  | Terminology | TermTagger can not work when source and target language are equal. | The task's source and target language are equal. This makes it impossible to use the TermTagger causing a hang otherwise.
| <a id="E1353"></a>E1353  | Terminology | TBX Import: Folder to save images does not exist or is not writable! | See details to get the folder which was tried to be used. This folder does either not exist or is not writable by the apache.
| <a id="E1354"></a>E1354  | Terminology | TBX Import: Folder to save termcollection images could not be created! | See details to get the folder which was tried to be used. This folder does either not exist or is not writable by the apache.
| <a id="E1356"></a>E1356  | Terminology | TBX Import: Import error - {msg} | Very unusual that this error happens, see the msg and extra data.
| <a id="E1357"></a>E1357  | Terminology | TBX Import: Could not import due unknown attribute level | Very unusual that this error happens, see the msg and extra data.
| <a id="E1393"></a>E1393  | Terminology | TBX Import: The XML structure of the TBX file is invalid: {message} | In this case the XML of the given TBX file is invalid and can not be parsed.
| <a id="E1360"></a>E1360  | Terminology | TBX Import: The TBX contains terms with unknown administrative / normative states. See details for a list of states. | The listed states are unknown and can not be mapped to the usual administrative status values.<br />Please configure them in the runtimeOptions.tbx.termImportMap configuration.
| <a id="E1361"></a>E1361  | Terminology | TBX Import: Unable to import terms due invalid Rfc5646 language code &quot;{code}&quot; | The listed language code is invalid / not configured in translate5, the corresponding terms could not be imported.
| <a id="E1364"></a>E1364  | Terminology | TermTagger overall run done - {segmentCounts} | Reports that the whole task was tagged with the termtagger and shows the segment status counts.


### Segment
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1065"></a>E1065  | Segment | The data of the saved segment is not valid. | The data of the saved segment contains invalid data, see error details for more information.
| <a id="E1066"></a>E1066  | Segment | The data of the saved segment is not valid. | See E1065, in addition that the validations contain at least an error where the segment is either to long or to short. This error is separate since it should produce an warning instead just a debug level entry.
| <a id="E1259"></a>E1259  | Segment | The data of the saved segment is not valid. | See E1065, in addition that the validations contain at least an error where the lines in the segment are either too many or (at least one of them is) too long. This error is separate since it should produce an warning instead just a debug level entry.
| <a id="E1155"></a>E1155  | Segment | Unable to save the segment. The segment model tried to save to the materialized view directly. | Programming error: The writeable table of the segment is set to the materialized view. This is wrong, since the way is: write to the LEK_segments table, write to the data table, then update the view with the data from there.
| <a id="E1081"></a>E1081  | Segment Pixellength | Textlength by pixel failed; most probably data about the pixelWidth is missing: fontFamily: &quot;{fontFamily} fontSize: &quot;{fontSize}&quot;. | Same as E1082 below, but no default width is available for that font and font size.
| <a id="E1082"></a>E1082  | Segment Pixellength | Segment length calculation: missing pixel width for several characters. | On of the characters in the segment has no pixel length defined, the default pixel width is used.<br />This error happens in most cases on the export of a task, where the length of the segments is finally checked. Also it happens on saving a segment.


### Segment: Search and replace
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1192"></a>E1192  | Search and replace | Replace all can not be used for task with usageMode &quot;simultaneous&quot; | The replace all is disabled for the tasks where the task usage mode is simultaneous.


### QA / AutoQA
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1343"></a>E1343  | Quality processing on Task Import or Segment editing | Setting the FieldTags tags by text led to a changed text-content presumably because the encoded tags have been improperly processed | Create a ticket for this issue with the event added.
| <a id="E1391"></a>E1391  | Quality processing on Task Import or Segment editing | Two non-splittable tags interleave each other. | Create a ticket for this issue with the event added.
| <a id="E1392"></a>E1392  | Quality processing on Task Import or Segment editing | SNC lib (which stands behind AutoQA Numbers Check) detected an error of a kind previously unknown to translate5 app | Create a ticket for this issue with the event added.


### Excel Ex-Import
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1137"></a>E1137   | Task | Task can not be exported as Excel-file. | 
| <a id="E1141"></a>E1141   | Task | Excel Reimport: upload failed. | error on writing file to /data/editorImportedTasks/{takGuid}/excelReimport
| <a id="E1138"></a>E1138   | Task | Excel Reimport: Formal check failed: task-guid differs in task compared to the excel. | 
| <a id="E1139"></a>E1139   | Task | Excel Reimport: Formal check failed: number of segments differ in task compared to the excel. | 
| <a id="E1140"></a>E1140   | Task | Excel Reimport: Formal check failed: segment #{segmentNr} is empty in excel while there was content in the the original task. | 
| <a id="E1142"></a>E1142   | Segment | Excel Reimport: at least one segment needs to be controlled. | This is actually a warning. You have to control all segments in the given list. They may have an invalid tag-structure (eg. open a tag but not closing it) or something similar. This can lead to problems on further workflow steps.
| <a id="E1148"></a>E1148   | Task | Task can not be locked for excel export, no excel export could be created. | That means the task is currently in use by another user / process.<br />This is logged as info, since this can happen if another reviewer&nbsp;is editing while another&nbsp;reviewer is finishing the task.


### Plug-Ins
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1218"></a>E1218  | Plug-In Manager | The PHP class for the activated plug-in &quot;{plugin}&quot; does not exist. | Either there is a typo in the class name of the activated plug-in class, or the class does relly not exist (anymore). Perhaps it was deleted. Or the wrong translate5 package without such a plug-in was installed.
| <a id="E1234"></a>E1234  | Plug-In Manager | Multiple Plugin Classes found to key {key} | if some one ever traps here: search key ordered by &quot;_&quot; in the plugin class list (or implement something like a search tree)
| <a id="E1235"></a>E1235  | Plug-In | No Plugin Configuration found! | No general plug-in configuration runtimeOptions&rarr;plugins exists.
| <a id="E1236"></a>E1236  | Plug-In | A Plugin is missing or not active - plugin: {plugin} | The mentioned plug-in is needed for another plug-in and must be activated therefore.
| <a id="E1237"></a>E1237  | Plug-In | The following Plugins are not allowed to be active simultaneously: {current} and {blocked} | The mentioned plug-in is needed for another plug-in and must be activated therefore.
| <a id="E1238"></a>E1238  | Plug-In | No Plugin Configuration found for plugin {plugin} | No specific plug-in configuration was found under runtimeOptions&rarr;plugins.


#### Plug-In DeepL
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1198"></a>E1198  | Plug-In DeepL | DeepL Plug-In: No config given. | Make sure that config-data for the DeepL-Plugin exists.
| <a id="E1199"></a>E1199  | Plug-In DeepL | DeepL Plug-In: API-Server is not defined. | Make sure that DeepL's API-Server is set in the config.
| <a id="E1200"></a>E1200  | Plug-In DeepL | DeepL Plug-In: Authentication key is not defined. | Make sure that the authentication-key from the DeepL-Account is set in the config.
| <a id="E1317"></a>E1317  | Plug-In DeepL | DeepL authorization failed. Please supply a valid API Key. | The configured API Key is not valid.
| <a id="E1318"></a>E1318  | Plug-In DeepL | DeepL quota exceeded. The character limit has been reached. | The usage quota of your DeepL account is exceeded.
| <a id="E1334"></a>E1334  | Plug-In DeepL | DeepL is returning an error: {message} | See the error message what is going wrong.
| <a id="E1385"></a>E1385  | Plug-In DeepL | DeepL Plug-In: Unable to create glossary | There was a problem on DeepL glossary creation. For more info check the error log.
| <a id="E1386"></a>E1386  | Plug-In DeepL | DeepL Plug-In: For one or more of the clients assigned to your current TermCollection the TermCollection {0} has already been assigned as &quot;DeepL glossary source&quot;. Should the TermCollection {0} be unassigned as glossary source for all of its assigned clients? | There is already term-collection in use as glossary source for one of the matching customers when trying to assign another term-collection to be used as glossary source.
| <a id="E1388"></a>E1388  | Plug-In DeepL | DeepL Plug-In: Unable to fetch all available language combinations for glossaries. | Api error calling the glossary-language-pairs endpoint. For more info see the error log.
| <a id="E1389"></a>E1389  | Plug-In DeepL | DeepL Plug-In: Unable to delete glossary. | Api error calling the DELETE /v2/glossaries/\[glossary_id\] endpoint. For more info see the error log.

#### Plug-In Groupshare
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1097"></a>E1097  | Plug-In GroupShare | Multi Purpose Code logging in the context of Groupshare | Multi Purpose Code logging in the context of Groupshare.
| <a id="E1098"></a>E1098  | Plug-In GroupShare | Groupshare Plug-In: Exception | General Purpose Exception
| <a id="E1099"></a>E1099  | Plug-In GroupShare | Groupshare Plug-In: No valid license token in cache for current user | There is no valid license token in the cache for the current user. The license token creation for this user was probably done before, but the token does not exist anymore.
| <a id="E1214"></a>E1214  | Plug-In GroupShare | No connection to Groupshare Server: {message} | There is no connection to the Groupshare Server, check added message which should contain the raw error message.
| <a id="E1215"></a>E1215  | Plug-In GroupShare | No connection to Groupshare Server: {message} | See E1214 above.
| <a id="E1264"></a>E1264  | Plug-In GroupShare | GroupShare Plug-In: retrieved data is missing or wrong. | Check the result from the request.


#### Plug-In InstantTranslate
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1207"></a>E1207  | Plug-In InstantTranslate | InstantTranslate: Response status&nbsp;&quot;{status}&quot; in indicates failure in communication with the API. | Check the error log for further information.
| <a id="E1208"></a>E1208  | Plug-In InstantTranslate | InstantTranslate: parse error in JSON response, the error was: &quot;{msg}&quot; | Check the error log for further information.
| <a id="E1209"></a>E1209  | Plug-In InstantTranslate | InstantTranslate: empty JSON response. | Check the error log for further information.
| <a id="E1211"></a>E1211  | Plug-In InstantTranslate | InstantTranslate: Filetranslation failed. &quot;{msg}&quot; | Check the error log for further information.
| <a id="E1212"></a>E1212  | Plug-In InstantTranslate | InstantTranslate: Parameter is not valid. &quot;{msg}&quot; | Check the error log for further information.
| <a id="E1213"></a>E1213  | Plug-In InstantTranslate | InstantTranslate: Error in config or roles. &quot;{msg}&quot; | Throws an error due to configuration-issues. Check the error log for further information.
| <a id="E1233"></a>E1233  | Plug-In InstantTranslate | InstantTranslate:&nbsp;Please check your configuration for pretranslationTaskLifetimeDays. &quot; | Adds an entry in the error-log due to configuration-issues, but doesn't stop the application. Solution: Check if pretranslationTaskLifetimeDays is set in the configuration.
| <a id="E1287"></a>E1287  | Plug-In InstantTranslate | InstantTranslate: &quot;0&quot; as upload field name is deprecated. Use &quot;file&quot; as upload field name instead. | Temporary warning for &quot;0&quot; the file upload api field name. The correct file upload field name for files pre-translations should be &quot;file&quot;.
| <a id="E1376"></a>E1376  | Plug-In InstantTranslate | InstantTranslate: Not all required parameters are provided when writing to instant-translate memory | Missing parameters where found in the write to instant translate memory request.
| <a id="E1383"></a>E1383  | Plug-In InstantTranslate | InstantTranslate: The submitted Markup is invalid | The User submitted invalid markup that therefore could not be translated


#### Plug-In MatchAnalysis
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1100"></a>E1100  | Plug-In MatchAnalysis | Multi Purpose Code logging in the context of MatchAnalysis | Multi Purpose Code logging in the context of MatchAnalysis.
| <a id="E1101"></a>E1101  | Plug-In MatchAnalysis | Disabled a Language Resource for analysing and pretranslation due too much errors. | Check the details and also the logs for more information.<br />Enable debugging for domain &quot;plugin.matchanalysis&quot; to get more info about the problems of the disabled language resources.
| <a id="E1371"></a>E1371  | Plug-In MatchAnalysis | Internal Fuzzy language resource could not be created. Check log for previous errors. | Check the details and also the logs for more information.
| <a id="E1102"></a>E1102  | Plug-In MatchAnalysis | Unable to use connector from Language Resource &quot;{name}&quot;. Error was: &quot;{msg}&quot;. | Check the details and also the logs for more information.<br />Enable debugging for domain &quot;plugin.matchanalysis&quot; to get more info about the problems of the disabled language resources.
| <a id="E1103"></a>E1103  | Plug-In MatchAnalysis | MatchAnalysis Plug-In: tried to load analysis data without providing a valid taskGuid | A valid taskGuid must be provided here as parameter, to load only the analysis data for one task.
| <a id="E1167"></a>E1167  | Plug-In MatchAnalysis | MatchAnalysis Plug-In: task can not be locked for analysis and pre-translation. | The task was locked by another process, so no analysis and pre-translation is possible.
| <a id="E1168"></a>E1168  | Plug-In MatchAnalysis | MatchAnalysis Plug-In: TermTagger worker for pre-translation can not be initialized. | <br />
| <a id="E1239"></a>E1239  | Plug-In MatchAnalysis | MatchAnalysis Plug-In: Language resource &quot;{name}&quot; has status &quot;{status}&quot; and is not available for match analysis and pre-translations. | There is a problem with the associated language resource to be used for analysis and pre-translation. Please check status and details of the problem.


#### Plug-In NecTm
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1162"></a>E1162  | Plug-In NecTm | NEC-TM Plug-In: Exception |  | 
| <a id="E1180"></a>E1180  | Plug-In NecTm | NEC-TM Plug-In: Error |  | 
| <a id="E1181"></a>E1181  | Plug-In NecTm | NEC-TM Plug-In: Synchronize of NEC-TM-Tags with our categories failed | NecTm Plug-In: Synchronize of NEC-TM-Tags with our categories failed. Check if the api-server of NEC-TM is running. | 
| <a id="E1256"></a>E1256  | Plug-In NecTm | NEC-TM Plug-In: A new NEC-TM-LanguageResource must have at least one category assigned. | NecTm Plug-In: We should always use tags in the data uploaded, if not, the data can't be searched by users (only by admin). | 
| <a id="E1182"></a>E1182  | Plug-In NecTm | NEC-TM Plug-In: The languages for the Nec-TM-LanguageResource differ from the languages of the segment. |  | 
| <a id="E1183"></a>E1183  | Plug-In NecTm | NEC-TM Plug-In: Could not save segment to TM|   | 


#### Plug-In Okapi
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1055"></a>E1055  | Plug-In Okapi | Okapi Plug-In: Bconf not given or not found: {bconfFile} | Either there was no bconf given, or the default bconf could not be found.<br />Default for import should be: ./application/modules/editor/Plugins/Okapi/data/okapi_default_import.bconf<br />Default for export should be: ./application/modules/editor/Plugins/Okapi/data/okapi_default_export.bconf
| <a id="E1056"></a>E1056  | Plug-In Okapi | Okapi Plug-In: tikal fallback can not be used, workfile does not contain the XLF suffix: {workfile} | In seldom scenarios tikal is used for export, if tikal receives an non XLIFF file this error is thrown.
| <a id="E1057"></a>E1057  | Plug-In Okapi | Okapi Plug-In: Data dir not writeable: {okapiDataDir} | Solution: change filesystem rights so that the apache user can write into <br />./application/modules/editor/Plugins/Okapi/data
| <a id="E1058"></a>E1058  | Plug-In Okapi | Okapi Plug-In: Error in converting file {file} on import. See log details for more information. | An error described in message happend on converting the file. <br />Check the message, since the error could be independent from the given file, for example if some Okapi configuration was wrong, or the Okapi server is not available.<br />A full log of the happened exception is available in the log (level debug).
| <a id="E1059"></a>E1059  | Plug-In Okapi | Okapi Plug-In: Configuration error - no Okapi server URL is configured! | Set a correct Okapi server URL in the configuration:
| <a id="E1150"></a>E1150  | Plug-In Okapi | Okapi Plug-In: The exported XLIFF contains empty targets, the Okapi process will probably fail then. | If the Okapi export failed investigate the XLIFF to find out the empty segments and why they are empty.
| <a id="E1151"></a>E1151  | Plug-In Okapi | Okapi Plug-In: Error in converting file {file} on export. See log details for more information. | An error described in message happend on converting the file.<br />Check the message, since the error could be independent from the given file, for example if some Okapi configuration was wrong, or the Okapi server is not available.<br />A full log of the happened exception is available in the log (level debug).
| <a id="E1340"></a>E1340  | Plug-In Okapi | Okapi Plug-In: The default bconf configuration file-name is not set. | The value was empty for the config with name:&nbsp;runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName or&nbsp;runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName<br />For more info see error log.
| <a id="E1387"></a>E1387  | Plug-In Okapi | Okapi Plug-In: Providing the BCONF to use in the import ZIP is deprecated | This is just a warning hinting to the use of the deprecated feature to provide the BCONF to use in the import ZIP
| <a id="E1390"></a>E1390  | Plug-In Okapi | Okapi Plug-In: The SRX file is not valid | The uploaded segmentation/SRX file is not valid. Details will be part of the message


#### Plug-In PangeaMt
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1270"></a>E1270   | Plug-In PangeaMt | PangeaMt&nbsp;Plug-In: No config given. | Make sure that config-data for the PangeaMt-Plugin exists.
| <a id="E1271"></a>E1271   | Plug-In PangeaMt | PangeaMt&nbsp;Plug-In: API-Server is not defined. | Make sure that PangeaMt's API-Server is set in the config.
| <a id="E1272"></a>E1272   | Plug-In PangeaMt | PangeaMt&nbsp;Plug-In: Apikey is not defined. | Make sure that the apikey for your PangeaMt-access is set in the config.


#### Openid connect
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1165"></a>E1165  | Openid | Error on openid authentication: {message} \n Request params: {request} \n Session: {session} \n Openid params: {openid} | Openid connect client exception. The error is thrown by the openidconnect library. This will output the original error message and some additional debug info
| <a id="E1173"></a>E1173  | Openid | The OpenIdUserData attribute {attribute} was not set by the requested OpenID server. | Make sure, that your IDP (identity provider) server provides the relevant attribute.
| <a id="E1174"></a>E1174  | Openid | No roles are provided by the OpenID Server to translate5. The default roles that are set in the configuration for the customer are used. | If you want to set the roles of the user through your IDP server, provide the role or roles attribute there.
| <a id="E1204"></a>E1204  | Openid | Unable to fetch the userInfo from the defined userinfo_endpoint. | The userinfo_endpoint request to the openid provider is not possible or the request was empty.
| <a id="E1328"></a>E1328  | Openid | OpenID connect authentication is only usable with SSL/HTTPS enabled! | The authentication cookie of translate5 must be delivered with the samesite flag set. In order that OpenID connect works, the samesite flag must be set to &quot;None&quot;, but this works only with HTTPs- / SSL enabled.
| <a id="E1329"></a>E1329  | Openid | OpenID connect: The default server and the claim roles are not defined. |
| <a id="E1330"></a>E1330  | Openid | The customer server roles are empty but there are roles from the provider. |
| <a id="E1331"></a>E1331  | Openid | Invalid claims roles for the allowed server customer roles |


#### FrontEndMessageBus
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1175"></a>E1175  | Plug-In FrontEndMessageBus | FrontEndMessageBus: Missing configuration - runtimeOptions.plugins.FrontEndMessageBus.messageBusURI must be set in configuration. | Set the missing messageBusURI configuration: <br />Message Bus URI, change default value according to your needs (as configured in config.php of used FrontEndMessageBus). Unix sockets are also possible, example: unix:///tmp/translate5MessageBus
| <a id="E1176"></a>E1176  | Plug-In FrontEndMessageBus | FrontEndMessageBus: Response status&nbsp;&quot;{status}&quot; in indicates failure in communication with message bus. | Check the error log of the Message Bus server for further information.
| <a id="E1177"></a>E1177  | Plug-In FrontEndMessageBus | FrontEndMessageBus: parse error in JSON response, the error was: &quot;{msg}&quot; | Check the error log of the Message Bus server for further information.
| <a id="E1178"></a>E1178  | Plug-In FrontEndMessageBus | FrontEndMessageBus: empty JSON response. | Check the error log of the Message Bus server for further information.


#### Plug-In VisualReview&nbsp;
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1184"></a>E1184  | Plug-In VisualReview | Visual Review: Missing visual review resource. {resources} | Required resource executable does not exist on the current installation. The event message will log the missing config. check the error log for more information.
| <a id="E1185"></a>E1185  | Plug-In VisualReview | Visual Review: Failed to merge the submitted PDF(s). Output: {shellOutput}; Command: {command} | The command line tool for pdf merge (currently pdfunite) was not able to merge the given pdf file(s).
| <a id="E1355"></a>E1355  | Plug-In VisualReview | Visual Review: Encryptet PDF(s) can not be merged. Output: {shellOutput} | One (or more) of the files to be merged was encrypted and can not be processed.
| <a id="E1186"></a>E1186  | Plug-In VisualReview | Visual Review failed on transforming the PDF to a HTML file | Fail on transform the merged PDF-file to a html file. This job will be done by pdf2htmlEX.
| <a id="E1187"></a>E1187  | Plug-In VisualReview | Cant find config variable (or variable is empty): runtimeOptions.plugins.VisualReview.shellCommandPdfMerge | The configured path to the command line tool &quot;pdfMerge&quot; (currently pdfunite) is not valid/provided.
| <a id="E1188"></a>E1188  | Plug-In VisualReview | Cant find config variable (or variable is empty): runtimeOptions.plugins.VisualReview.shellCommandPdf2Html | The configured path to the command line tool &quot;pdfconverter&quot; is not valid/provided.
| <a id="E1189"></a>E1189  | Plug-In VisualReview | Visual Review: Fail on segmentation of the HTML file | The segmentation of the HTML file failed. For more info check the error log.
| <a id="E1283"></a>E1283  | Plug-In VisualReview | Visual Review: The segmentation of the HTML failed: {reason} | The segmentation of a HTML file failed.
| <a id="E1197"></a>E1197  | Plug-In VisualReview | Visual Review: Segmentation result: {percentage}% ({segmentFoundCount} / {segmentCount}) | Just an info about the percentage how many segments could be found in the PDF.
| <a id="E1226"></a>E1226  | Plug-In VisualReview | Cant find config variable (or variable is empty): runtimeOptions.plugins.VisualReview.shellCommandPdfOptimizer | The shell command for the PDF-Optimizer is not defined or empty
| <a id="E1227"></a>E1227  | Plug-In VisualReview | Cant find config variable (or variable is empty): runtimeOptions.plugins.VisualReview.shellCommandGoogleChrome | The shell command for Google Chrome is not defined or empty
| <a id="E1228"></a>E1228  | Plug-In VisualReview | Unable to optimize the pdf using the {command} shell command | The optimization of the PDF failed
| <a id="E1229"></a>E1229  | Plug-In VisualReview | Visual Review Worker Initialization failed: {reason} | The Initialization of the specified Worker failed
| <a id="E1230"></a>E1230  | Plug-In VisualReview | Visual Review Text-Reflow: Transformation of HTML generated with pdfconverter failed: {reason} | The Text-Reflow of the pdfconverter output failed (so the output is used without Live-Editing)
| <a id="E1231"></a>E1231  | Plug-In VisualReview | Visual Review Text-Reflow: Transformation of HTML generated with pdfconverter had problems: {reason} | The Text-Reflow of the pdfconverter output had problems (though it's output is used as review ignoring the problems)
| <a id="E1254"></a>E1254  | Plug-In VisualReview | Visual Review Font-Replacing: The replacing of fonts in the HTML generated with pdfconverter failed: {reason} | The Font-Replacing in the pdfconverter output failed and no fonts have been replaced. The Live-Editing for this task now may has unsolvable problems with mis-styled or even missing characters when translating
| <a id="E1255"></a>E1255  | Plug-In VisualReview | Visual Review Font-Replacing: Some fonts were not found and need to be added: {list} | The Font-Replacing in the pdfconverter output found fonts that are not present in the Font-admnistration and need to be added there
| <a id="E1260"></a>E1260  | Plug-In VisualReview | Visual Review: No review files have been imported successfully, the visual review is disabled | The Import contained sources for a visual review but none of them could successfully be transformed to a review HTML
| <a id="E1261"></a>E1261  | Plug-In VisualReview | Visual Review Download: The submitted URL could not be fetched: {reason} | The User submitted a syntactically correct URL as review source but it could not be downloaded with wget
| <a id="E1262"></a>E1262  | Plug-In VisualReview | Visual Review Download: An error occurred downloading the submitted URL: {reason} | The URL submitted by the User could not be downloaded with wget
| <a id="E1263"></a>E1263  | Plug-In VisualReview | Visual Review Download: An error occurred processing the submitted URL: {reason} | The URL submitted by the User could not be processed
| <a id="E1279"></a>E1279  | Plug-In VisualReview | Visual Review XML/XSL Import: An error occurred processing the imported XML/XSL: {reason} | The imported XML/XSLT file had errors while processing them to create the visual review
| <a id="E1362"></a>E1362  | Plug-In VisualReview | Visual Review XML/XSL Import: A problem occurred processing the imported XML/XSL: {reason} | The imported XML/XSLT file had problems while processing them to create the visual review
| <a id="E1285"></a>E1285  | Plug-In VisualReview | Visual Review Import: Deprecated option &quot;automatic&quot; for the mapping-type used | The import via API used the deprecated option &quot;automatic&quot; for the mapping-type defining if the segmentation should be bound to the source or target
| <a id="E1286"></a>E1286  | Plug-In VisualReview | Visual Review Import: Can not add review-source type {type} to the already imported review-sources | Since not all import types (PDF, HTML via direct import, HTML via WGET, XML/XSLT) can be mixed this indicates a invalid mixing of types
| <a id="E1337"></a>E1337  | Plug-In VisualReview | Visual Review Import: IMPORTANT: The &quot;visualReview&quot; folder in the zip import package is deprecated from now on. In the future please always use the new folder &quot;visual&quot; instead. All files that need to be reviewed or translated will have to be placed in the new folder &quot;visual&quot; from now on. In some future version of translate5 the support for &quot;visualReview&quot; folder will be completely removed. Currently it still is supported, but will write a &quot;deprecated&quot; message to the php error-log. | &quot;visualReview&quot; folder is deprecated for the import, use &quot;visual&quot; instead.
| <a id="E1350"></a>E1350  | Plug-In VisualReview | Visual Review Import: The URL {url} can not be used as Review Source: {reason} | An URL submitted or encoded in the reviewHtml.txt import file can not be used as visual source
| <a id="E1351"></a>E1351  | Plug-In VisualReview | Visual Review Import: The URL {url} may causes problems being used as Review Source: {reason} | An URL submitted or encoded in the reviewHtml.txt may causes problems as visual source
| <a id="E1365"></a>E1365  | Plug-In VisualReview | Visual Review: The Google Cloud Vision API is not properly configured: {reason} | The API Key for the Google Vision API is not configured, not present, invalid or outdated
| <a id="E1366"></a>E1366  | Plug-In VisualReview | Visual Review Image Import: There have been errors converting the image: {reason} | An Imported Image could not be converted with ImageMagick. Presumably the Image-File is corrupt.
| <a id="E1367"></a>E1367  | Plug-In VisualReview | Visual Review Image Import: There have been errors analyzing the image: {reason} | An Imported Image could not be analyzed with OCR
| <a id="E1368"></a>E1368  | Plug-In VisualReview | Visual Review Image Import: There have been errors adjusting the text sizes: {reason} | The generated Review File caused problems in the Headless Browser
| <a id="E1373"></a>E1373  | Plug-In VisualReview | Visual Review Video Import: There have been errors importing the video: {reason} | There have been problems importing the Video, usually due to wrong Codec, too big, faulte metadata, etc.
| <a id="E1374"></a>E1374  | Plug-In VisualReview | Visual Review Video Import: There have been errors parsing the video spreadsheet: {reason} | The Spreadsheet had the wrong format&nbsp; (more than 3 columns etc.) or the Timecodes in the Spreadsheet had the wrong format
| <a id="E1375"></a>E1375  | Plug-In VisualReview | Visual Review Video Import: There have been errors parsing the video subtitle file: {reason} | The Subtitles file had an unexpected format / unexpected contents
| <a id="E1380"></a>E1380  | Plug-In VisualReview | Visual review bilingual visual PDF import: No bilingual visual detected | No bilingual markers have been found in the HTML converted from the imported PDF


#### Plug-In ModelFront&nbsp;
| EventCode        | Context       | EventMessage  | Description/Solution
| :--------------- |:------------- | :------------ | :-------------------
| <a id="E1266"></a>E1266  | ModelFront | ModelFront Plug-In: authentication parametars are not defined. | The apiUpr or apiToken zf_configuration parameters are not defined or empty.
| <a id="E1267"></a>E1267  | ModelFront | ModelFront Plug-In: source or target languages are not defined. | The source or target languages are not set.
| <a id="E1268"></a>E1268  | ModelFront | ModelFront Plug-In: Error on ModelFront api request. The error was: {message}. | Error happen on model front api request. For more info see the error log.
| <a id="E1269"></a>E1269  | ModelFront | ModelFront Plug-In: Error on processing the segments. Segment list with errors: {errors}. | ModelFront api responds with error for the requested segment. For more info about the error check the error log.


## EventCode Design rules / decisions
- Prefixed with &quot;E&quot; so that a search for the error code through the code is more reliable than just searching for a number
- No structure in the numbering to prevent discussions is it an error E12XX or E45XX
- Do not start at 1 and don't use leading zeros.
- Each usage of an error in the code should get separate code. Even if the error message / reason is the same. Reason is that the help desk might need to do different things in different cases.
- Information about the error here in the list should not be copied, but errors can point to another errors: &quot;E 4321: See E 1234&quot;. Thats work to maintain, but better as confusing the client by giving wrong hints.
- if it makes sense for the support / help desk to split up an error in different errorcodes because of different reasons, than this should be done in the code so far

## to get highest ecode:
- call <code>t5 dev:ecode</code> to get it globally
- from the current document use: <code>php -r '$text = file_get_contents("ERRORCODES.md"); preg_match_all("/(E[0-9]{4})/", $text, $codes); print_r(max(array_unique($codes[0])));echo "\n";'</code>
- or to get a whole list sorted from lowest to highest <code>php -r '$text = file_get_contents("ERRORCODES.md"); preg_match_all("/(E[0-9]{4})/", $text, $codes); $codes = array_unique($codes[0]); sort($codes); print_r($codes);echo "\n";'</code>