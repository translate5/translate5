
UPDATE `Zf_configuration` SET `value`='[\"erp_Plugins_Moravia_Init\"]' 
WHERE `name`='runtimeOptions.plugins.active';

UPDATE `Zf_configuration` 
SET `value`='[{\"id\": 0, \"text\": \"0%\"},{\"id\": 16, \"text\": \"16%\"},{\"id\": 19, \"text\": \"19%\"}]' 
WHERE `name`='runtimeOptions.taxsets';
