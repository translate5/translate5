-- ensure before that no duplicate number exist!
update ERP_customer set number = null where number = '';
ALTER TABLE ERP_customer drop key (`number`);
ALTER TABLE ERP_customer add unique key (`number`);
