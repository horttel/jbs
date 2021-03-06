<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$InvoiceID	= (integer) @$Args['InvoiceID'];
$CreateDate	= (integer) @$Args['CreateDate'];
$PaymentSystemID=  (string) @$Args['PaymentSystemID'];
$Summ		=  (double) @$Args['Summ'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Invoice = DB_Select('InvoicesOwners',Array('ID','Summ','UserID','ContractID','IsPosted'),Array('UNIQ','ID'=>$InvoiceID));
#-------------------------------------------------------------------------------
switch(ValueOf($Invoice)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('InvoiceEdit',(integer)$__USER['ID'],(integer)$Invoice['UserID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return ERROR | @Trigger_Error(700);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$InvoiceID = $Invoice['ID'];
#-------------------------------------------------------------------------------
if($Invoice['IsPosted'])
	if(!$__USER['IsAdmin'])
		return new gException('ACCOUNT_PAYED','Счет оплачен и не может быть изменен');
#-------------------------------------------------------------------------------
# сумму счёта можно править только в случае если это пополнение средств, и ничего другого
$InvoiceItems = DB_Select('InvoicesItems','*',Array('Where'=>SPrintF('`InvoiceID` = %u ',$InvoiceID)));
#-------------------------------------------------------------------------------
switch(ValueOf($InvoiceItems)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------TRANSACTION-------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID('InvoiceEdit'))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($__USER['IsAdmin']){
	#-------------------------------------------------------------------------------
	if(SizeOf($InvoiceItems) > 1 && $Summ != $Invoice['Summ'])
		return new gException('INVOICE_HAVE_MORE_ONE_ITEM','Сумму счёта можно изменять только если он на пополнение средств');
	#-------------------------------------------------------------------------------
	if($Summ != $Invoice['Summ'])
		foreach($InvoiceItems as $InvoiceItem)
			if($InvoiceItem['ServiceID'] != 1000)
				return new gException('INVOICE_HAVE_NOT_1000_SERVICES','Сумму счёта можно изменять только если он на пополнение средств');
	#-------------------------------------------------------------------------------		
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Contract = DB_Select('Contracts',Array('ID','CreateDate','TypeID'),Array('UNIQ','ID'=>$Invoice['ContractID']));
#-------------------------------------------------------------------------------
switch(ValueOf($Contract)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$PaymentSystems = $Config['Invoices']['PaymentSystems'];
#-------------------------------------------------------------------------------
if(!$PaymentSystemID)
	return new gException('PAYMENT_SYSTEM_NOT_SELECTED','Платежная система не указана');
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$PaymentSystems = $Config['Invoices']['PaymentSystems'];
#-------------------------------------------------------------------------------
if(!IsSet($PaymentSystems[$PaymentSystemID]))
	return new gException('PAYMENT_SYSTEM_NOT_FOUND','Платежная система не найдена');
#-------------------------------------------------------------------------------
$PaymentSystem = $PaymentSystems[$PaymentSystemID];
#-------------------------------------------------------------------------------
if(!$PaymentSystem['ContractsTypes'][$Contract['TypeID']])
	return new gException('WRONG_CONTRACT_TYPE','Данный вид договора не может быть использован для выписывания счета данного типа');
#-------------------------------------------------------------------------------
if($Summ){
	#-------------------------------------------------------------------------------
	# проверяем минимально домустимую сумму счёта
	if($Summ < $PaymentSystem['MinimumPayment'])
		return new gException('PAYMENT_SYSTEM_MinimumPayment','Сумма платежа меньше, чем разрешено платёжной системой');
	#-------------------------------------------------------------------------------
	# проверяем максимально допустимую сумму счёта
	if($Summ > $PaymentSystem['MaximumPayment'])
		return new gException('PAYMENT_SYSTEM_MaximumPayment','Сумма платежа больше, чем разрешено платёжной системой');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IInvoice = Array('CreateDate'=>$CreateDate,'PaymentSystemID'=>$PaymentSystemID);
#-------------------------------------------------------------------------------
if($__USER['IsAdmin'] && $Summ != $Invoice['Summ']){
	#-------------------------------------------------------------------------------
	#надо обновить сумму, в Invoices и в InvoicesItems
	$IInvoice['Summ'] = $Summ;
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('InvoicesItems',Array('Summ'=>$Summ),Array('Where'=>SPrintF('`InvoiceID` = %u',$InvoiceID)));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Invoices',$IInvoice,Array('ID'=>$InvoiceID));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Invoices/Build',$InvoiceID);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(DB_Commit($TransactionID)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
