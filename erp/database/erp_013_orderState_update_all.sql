UPDATE ERP_purchaseOrder
INNER JOIN ERP_order ON (ERP_purchaseOrder.orderId = ERP_order.id)
SET ERP_purchaseOrder.orderStatus = ERP_order.state;
