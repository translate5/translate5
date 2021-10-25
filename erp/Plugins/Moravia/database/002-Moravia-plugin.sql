ALTER TABLE `ERP_order` 
DROP INDEX `debitNumber` ;

UPDATE `Zf_configuration` SET `default`='{\"offer\":{\"state\":[\"offered\"]},\"project\":{\"state\":[\"ordered\"]},\"bill\":{\"state\":[\"billed\",\"paid\"]}}', `description`='Frontend view filters defined by view name (offer,project,bill). Example of view filter deffinition: {\"offer\":{\"state\":[\"offered\"]},\"project\":{\"state\":[\"ordered\"]},\"bill\":{\"state\":[\"billed\",\"paid\"]}}' 
WHERE `name`='runtimeOptions.viewfilters';
