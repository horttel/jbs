<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('HostingOrder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#Debug(SPrintF('[comp/Triggers/Statuses/HostingOrders/Waiting]: HostingOrder = %s',print_r($HostingOrder,true)));
#-------------------------------------------------------------------------------
$UserID = $GLOBALS['__USER']['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Service = DB_Select('Services',Array('ID','Code','Name'),Array('UNIQ','ID'=>$HostingOrder['ServiceID']));
switch(ValueOf($Service)){
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
$Scheme = DB_Select(SPrintF('%sSchemes',$Service['Code']),Array('*'),Array('UNIQ','ID'=>$HostingOrder['SchemeID']));
switch(ValueOf($Scheme)){
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
$Params = Array(
		'SchemeID'		=> $HostingOrder['SchemeID'],
		'OrderID'		=> $HostingOrder['OrderID'],
		'MaxOrders'		=> $Scheme['MaxOrders'],
		'MinOrdersPeriod'	=> $Scheme['MinOrdersPeriod'],
		'ServiceID'		=> $Service['ID'],
		'ServiceName'		=> $Service['Name'],
		'SchemeName'		=> $Scheme['Name']
		);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Services/Orders/OrdersHistory',$Params);
switch(ValueOf($Comp)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $Comp;
case 'array':
	return TRUE;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
