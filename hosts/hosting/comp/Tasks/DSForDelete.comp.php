<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$DeleteTimeout = $Config['Tasks']['Types']['DSForDelete']['DeleteTimeout'] * 24 * 3600;
#-------------------------------------------------------------------------------
$Where = Array('`StatusID` = "Suspended"','`StatusDate` + 2592000 - UNIX_TIMESTAMP() <= 0');
#-------------------------------------------------------------------------------
$DSOrders = DB_Select('DSOrders','ID',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($DSOrders)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = Array('Deleted'=>Array(SizeOf($DSOrders)));
	#-------------------------------------------------------------------------------
	foreach($DSOrders as $DSOrder){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'DSOrders','StatusID'=>'Deleted','RowsIDs'=>$DSOrder['ID'],'Comment'=>'Срок блокировки заказа окончен'));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Comp)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			# No more...
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$StartHour    = $Config['Tasks']['Types']['DSForDelete']['StartHour'];
$StartMinutes = $Config['Tasks']['Types']['DSForDelete']['StartMinutes'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return MkTime($StartHour,$StartMinutes,0,Date('n'),Date('j')+1,Date('Y'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
