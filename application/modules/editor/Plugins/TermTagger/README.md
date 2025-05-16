# TERMTAGGER Plugin General functionality

for now, this is just a log to document specific details about the Termtagging



## Exceptions of the Termtagger and how they are handled

AbstractException
    => is not thrown but just the base-class

DownException
 * => will be thrown when the Http-Error is like "Unable to Connect to"
 * => will be thrown when a Worker is queued but no slot is available
 * => leads to the worker simply stop & end if other Termtagger-workers for the task are running
 * => leads to a "normal" delay (30 sec, incremental, up to 6 times / 30 min) for load-balanced services otherwise
 * => leads to a service is marked as "down" via down-list & logs a task-event-log for normal pooled services otherwise    

MalfunctionException
 * => will be thrown when the response was readable but had missing data
 * => leads to segments are set to single-reprocessing and to unprocessable in the second run. Adds task-event-log warning

NoResponseException
 * => will be thrown when the Http-Error is like "Unable to read response, or response is empty"
 * => or if termtaggers are behind a dedicated proxy and that gives 502 or 429 - which means response is empty from termtagger then
 * => sets the segments as unprocessed and delays the worker in a "repeated" delay of 2 sec up to 1h

OpenException
 * => will be thrown if the task has Terminology but no TBX-Hash or the TBX had no data or could not be parsed
 * => Sets the termonologie-flag for the task to false, logs a task-event-log and ends the worker

RequestException
 * => will be thrown if the Termtagger response could not be parsed
 * => leads to segments are set to single-reprocessing and to unprocessable in the second run. Adds task-event-log warning

TimeOutException
 * => will be thrown when the Http-Error is like "Read timed out after"
 * => leads to segments are set to single-reprocessing and to unprocessable in the second run. Adds task-event-log warning

CheckTbxTimeOutException
 * => will be thrown when the Http-Error is like "Read timed out after" when the TBX is loaded or it is checked if it is already loaded
 * => sets the segments as unprocessed and delays the worker in a "repeated" delay of 2 sec up to 1h
