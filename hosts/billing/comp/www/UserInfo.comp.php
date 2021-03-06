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
$UserID = (integer) @$Args['UserID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Columns = Array(
			'ID','RegisterDate','Name','GroupID','Email','EmailConfirmed',
			'Sign','OwnerID','IsManaged','LayPayMaxDays',
			'LayPayMaxSumm','LayPayThreshold','EnterDate','EnterIP',
			'Rating','IsActive','LockReason','IsNotifies','IsHidden','IsProtected','AdminNotice','Params',
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `OrdersOwners`.`UserID`=`Users`.`ID`) AS `NumOrders`',
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `OrdersOwners`.`UserID`=`Users`.`ID` AND `OrdersOwners`.`StatusID`="Active") AS `NumActiveOrders`',
			'(SELECT SUM(`Summ`) FROM `InvoicesOwners` WHERE `InvoicesOwners`.`UserID`=`Users`.`ID`) AS `TotalPayments`',
			'(SELECT SUM(`Summ`) FROM `InvoicesOwners` WHERE `InvoicesOwners`.`UserID`=`Users`.`ID` AND `InvoicesOwners`.`StatusID`="Payed") AS `SummPayments`',
		);
#-------------------------------------------------------------------------------
$User = DB_Select('Users',$Columns,Array('UNIQ','ID'=>$UserID));
#-------------------------------------------------------------------------------
switch(ValueOf($User)){
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
$IsPermission = Permission_Check('UserRead',(integer)$__USER['ID'],(integer)$User['ID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return new gException('USER_MANAGMENT_DISABLED','Просмотр информации запрещен');
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
#-------------------------------------------------------------------------------
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Пользователь #%u',$UserID));
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Идентификатор',SPrintF('#%u',$UserID));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Extended',$User['RegisterDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Дата регистрации',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Имя',$User['Name']);
#-------------------------------------------------------------------------------
$Sign = WordWrap($User['Sign'],100,"\n");
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись',new Tag('PRE',Array('class'=>'Standard'),$Sign?$Sign:'-'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Контактная информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Электронный адрес',$User['Email']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['EmailConfirmed'] > 0){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Extended',$User['EmailConfirmed']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Email подтверждён',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$NotificationMethods = $User['Params']['NotificationMethods'];
#-------------------------------------------------------------------------------
foreach(Array_Keys($NotificationMethods) as $MethodID){
	#-------------------------------------------------------------------------------
	if($NotificationMethods[$MethodID]['Address'])
		$Table[] = Array($Config['Notifies']['Methods'][$MethodID]['Name'],$NotificationMethods[$MethodID]['Address']);
	#-------------------------------------------------------------------------------
	if($NotificationMethods[$MethodID]['Confirmed']){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Date/Extended',$NotificationMethods[$MethodID]['Confirmed']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array(SPrintF('%s подтверждён',$Config['Notifies']['Methods'][$MethodID]['Name']),$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OwnerID = $User['OwnerID'];
#-------------------------------------------------------------------------------
if($OwnerID){
	#-------------------------------------------------------------------------------
	$Table[] = 'Партнерская программа';
	#-------------------------------------------------------------------------------
	$Owner = DB_Select('Users',Array('Name','Email'),Array('UNIQ','ID'=>$OwnerID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Owner)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$Table[] = Array('Партнер',SPrintF('%s (%s)',$Owner['Name'],$Owner['Email']));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Logic',$User['IsManaged']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Возможность управления',$Comp);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Условия отложенного платежа';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во дней',$User['LayPayMaxDays']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['LayPayMaxSumm']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальная сумма',$Comp);
#-------------------------------------------------------------------------------
#-----------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['LayPayThreshold']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Пороговая сумма',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# JBS-348
$Table[] = 'Активность пользователя';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Активных услуг',$User['NumActiveOrders']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['SummPayments']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Оплачено счетов на сумму',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['NumOrders'] != $User['NumActiveOrders'])
	$Table[] = Array('Всего заказано услуг',$User['NumOrders']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['TotalPayments']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['TotalPayments'] != $User['SummPayments']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Currency',$User['SummPayments']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Выписано счетов на сумму',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Информация о работе в системе';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Extended',$User['EnterDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата последнего входа',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('IP-адрес последнего входа',$User['EnterIP']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Служебная информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Рейтинг',$User['Rating']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsActive']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Активный пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['LockReason']){
	#-------------------------------------------------------------------------------
	$LockReason = Comp_Load('Formats/String',$User['LockReason'],25);
	if(Is_Error($LockReason))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Причина блокировки',$LockReason);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsNotifies']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Рассылать уведомления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsHidden']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скрытый пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsProtected']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Защищенный пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("ShowWindow('/Administrator/UserEdit',{UserID:'%u'});",$User['ID']),'value'=>'Редактировать'));
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('align'=>'right'),$Comp);
	#-------------------------------------------------------------------------------
	$Table[] = $Div;
	#-------------------------------------------------------------------------------
}
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
