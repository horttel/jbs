<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContractID = (integer) @$Args['ContractID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array('ID','UserID','CreateDate','TypeID','Customer','IsUponConsider','ProfileID','Balance','StatusID','StatusDate');
#-------------------------------------------------------------------------------
$Contract = DB_Select('Contracts',$Columns,Array('UNIQ','ID'=>$ContractID));
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
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('ContractRead',(integer)$__USER['ID'],(integer)$Contract['UserID']);
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
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Number = Comp_Load('Formats/Contract/Number',$Contract['ID']);
if(Is_Error($Number))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Договор #%s',$Number));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Table[] = Array('Номер',$Number);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Standard',$Contract['CreateDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата создания',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Contract/Type',$Contract['TypeID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тип',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Баланс',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Заказчик';
#-------------------------------------------------------------------------------
$Table[] = Array('Имя',$Contract['Customer']);
#-------------------------------------------------------------------------------
$Table[] = Array('Способ отчетности',$Contract['IsUponConsider']?'Ежемесячный':'По факту');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ProfileID = $Contract['ProfileID'];
#-------------------------------------------------------------------------------
if($ProfileID){
	#-------------------------------------------------------------------------------
	$Number = Comp_Load('Formats/Contract/Number',$Contract['ProfileID']);
	if(Is_Error($Number))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Профиль',SPrintF('#%s',$Number));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("ShowWindow('/ProfileInfo',{ProfileID:%u});",$ProfileID),'value'=>'Просмотреть'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY',$Comp,new Tag('SPAN','|'));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("ShowWindow('/ProfileEdit',{ProfileID:%u});",$ProfileID),'value'=>'Редактировать'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody->AddChild($Comp);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Данные профиля',$NoBody);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Взаиморасчёты';
#-------------------------------------------------------------------------------
$Summ = DB_Select('Invoices','SUM(`Summ`) as `Summ`',Array('UNIQ','Where'=>SPrintF('`ContractID` = %u',$Contract['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Summ)){
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
$Comp = Comp_Load('Formats/Currency',$Summ['Summ']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Сумма оплаченных счетов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Summ = DB_Select('WorksComplite','SUM((`Amount`*`Cost`)*(1 - `Discont`)) as `Summ`',Array('UNIQ','Where'=>SPrintF('`ContractID` = %u',$Contract['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Summ)){
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
$Comp = Comp_Load('Formats/Currency',$Summ['Summ']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Выполнено работ',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Оказываемые услуги';
#-------------------------------------------------------------------------------
$Count = DB_Count('OrdersOwners',Array('Where'=>SPrintF('`StatusID` = "Active" AND `ContractID` = %u',$ContractID)));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Активные',$Count);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('OrdersOwners',Array('Where'=>SPrintF('`StatusID` = "Suspended" AND `ContractID` = %u',$ContractID)));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Заблокированные',$Count);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('OrdersOwners',Array('Where'=>SPrintF('`StatusID` = "Waiting" AND `ContractID` = %u',$ContractID)));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ожидающие оплату',$Count);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('OrdersOwners',Array('Where'=>SPrintF('`ContractID` = %u',$ContractID)));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Всего услуг',$Count);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Statuses/State','Contracts',$Contract);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table = Array_Merge($Table,$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
