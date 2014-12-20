<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Server');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Triggers/OnDelete/Servers]: Server = %s',print_r($Server,true)));
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('Orders',Array('Where'=>SPrintF('`ServerID` = %u',$Server['ID'])));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('DELETE_DENIED',SPrintF('Удаление сервера (%s) не возможно, %u заказ(ов) связаны с данным сервером',$Server['Address'],$Count));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# ставим любой сервер группы IsDefault, если удаляемый сервер IsDefault
if($Server['IsDefault'] && IntVal($Server['ServersGroupID']) > 0){
	#-------------------------------------------------------------------------------
	$iServer = DB_Select('Servers',Array('ID','Address'),Array('UNIQ','Where'=>SPrintF('`ServersGroupID` = %u AND `ID` != %u AND `IsActive` = "yes"',$Server['ServersGroupID'],$Server['ID']),'Limits'=>Array(0,1)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($iServer)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update('Servers',Array('IsDefault'=>TRUE),Array('ID'=>$iServer['ID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
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
if($Server['TemplateID'] == 'EmailClient'){
	#-------------------------------------------------------------------------------
	if(!$Config['Tasks']['Types']['CheckEmail']['IsActive'])
		break;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Servers',Array('Where'=>SPrintF('`TemplateID` = "EmailClient" AND `IsActive` = "yes" AND `IsDefault` = "yes" AND `ID` != %u',$Server['ID'])));
	#-------------------------------------------------------------------------------
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(!$Count)
		return new gException('DELETE_DENIED',SPrintF('Удаление сервера (%s) не возможно, т.к. это единственный или активный по умолчанию сервер для приёма почты. Для возможности удаления, сделайте активным другой сервер с шаблоном (EmailClient) или отключите задание (%s)',$Server['Address'],$Config['Tasks']['Types']['CheckEmail']['Name']));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(In_Array($Server['TemplateID'],Array('ICQ','Jabber','SMS'))){
	#-------------------------------------------------------------------------------
	if(!$Config['Tasks']['Types'][$Server['TemplateID']]['IsActive'])
		break;
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Servers',Array('Where'=>SPrintF('`TemplateID` = "%s" AND `IsActive` = "yes" AND `IsDefault` = "yes" AND `ID` != %u',$Server['TemplateID'],$Server['ID'])));
	#-------------------------------------------------------------------------------
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(!$Count)
		return new gException('DELETE_DENIED',SPrintF('Удаление сервера (%s) не возможно, т.к. это единственный или активный по умолчанию сервер для отправки %s сообщений. Для возможности удаления, сделайте активным другой сервер с шаблоном (%s) или отключите задание (%s)',$Server['Address'],$Server['TemplateID'],$Server['TemplateID'],$Config['Tasks']['Types'][$Server['TemplateID']]['Name']));
	#-------------------------------------------------------------------------------
}
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
