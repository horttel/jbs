<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','ServiceName','ServiceOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# выбираем данные сервиса
$Order = DB_Select('OrdersOwners',Array('*','(SELECT `Params` FROM `Services` WHERE `OrdersOwners`.`ServiceID` = `Services`.`ID`) AS `Params`','(SELECT `NameShort` FROM `Services` WHERE `OrdersOwners`.`ServiceID` = `Services`.`ID`) AS `NameShort`','(SELECT `Email` FROM `Users` WHERE `Users`.`ID` = `OrdersOwners`.`UserID`) AS `Email`'),Array('UNIQ','ID'=>$ServiceOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($Order)){
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
$Number = Comp_Load('Formats/Order/Number',$ServiceOrderID);
if(Is_Error($Number))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = @$Order['Params']['Statuses']['OnCreate'];
#-------------------------------------------------------------------------------
# проверяем, надо ли выполнять задачу
if(IsSet($Settings['IsNoAction']) && $Settings['IsNoAction'] == 'yes')
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(IsSet($Settings['Script']) && Mb_StrLen(Trim($Settings['Script'])) > 0){
	#-------------------------------------------------------------------------------
	$File = Trim($Settings['Script']);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/ServiceCreate]: Script = %s',$File));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# находим полный путь к файлу
	if(SubStr($File,0,1) != '/')
		$File = SPrintF('%s/hosts/%s/scripts/%s',SYSTEM_PATH,HOST_ID,$File);
	#-------------------------------------------------------------------------------
	# проверяем наличие файла по этому пути
	if(!File_Exists($File))
		return new gException('FILE_NOT_FOUND',SPrintF("Файл '%s' не найден",$File));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Server = DB_Select('Servers',Array('*'),Array('UNIQ','ID'=>$Order['ServerID']));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Server)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		break;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Exec(SPrintF('"%s" "%s" "OnCreate" "%s" "%s" "%s" 2>&1',$File,$Order['Email'],$Number,$Order['Keys'],(Is_Array($Server)?Base64_Encode(JSON_Encode($Server)):'server not exists')),$Out,$ReturnValue);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/ServiceCreate]: exec return code = %s, Out = %s',$ReturnValue,print_r($Out,true)));
	#-------------------------------------------------------------------------------
	if($ReturnValue != 0)
		return new gException('ERROR_EXECUTE_COMMAND','Произошла ошибка при выполнении команды назначенной статусу');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Orders','StatusID'=>'Active','RowsIDs'=>$ServiceOrderID,'Comment'=>'Заказ создан','IsNoTrigger'=>TRUE));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Comp)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$Event = Array(
				'UserID'        => $Order['UserID'],
				'PriorityID'    => 'Hosting',
				'Text'          => SPrintF('Создан заказ #%s на услугу (%s)',$Number,$Order['NameShort'])
				);
		$Event = Comp_Load('Events/EventInsert',$Event);
		if(!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$GLOBALS['TaskReturnInfo'] = Array($Order['NameShort'],SprintF('#%s',$Number),$Settings['Script']);
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return new gException('NEED_MANUAL_ACTION','Задачу необходимо выполнять вручную, администратору');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
