UPDATE `ERP_order` o
INNER JOIN `ERP_production_data` p ON p.orderId =o.id
SET o.isCustomerView = 1