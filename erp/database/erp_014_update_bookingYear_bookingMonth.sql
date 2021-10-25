UPDATE ERP_purchaseOrder 
SET ERP_purchaseOrder.bookingYear =YEAR(ERP_purchaseOrder.billDate),
ERP_purchaseOrder.bookingMonth= month(ERP_purchaseOrder.billDate)-1
