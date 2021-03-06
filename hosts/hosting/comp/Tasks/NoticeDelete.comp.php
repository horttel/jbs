<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
$Config = Config();
$Settings = $Config['Tasks']['Types']['NoticeDelete'];
#-------------------------------------------------------------------------------
# достаём время выполнения
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecuteTime'=>$Settings['ExecuteTime'],'ExecuteDays'=>@$Settings['ExecuteDays'],'DefaultTime'=>MkTime(4,25,0,Date('n'),Date('j')+1,Date('Y'))));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
# если неактивна, то через день запуск
if(!$Settings['IsActive'])
	return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array();
#-------------------------------------------------------------------------------
$Where = Array('`Code` != "Default"','`IsHidden` = "no"');
#-------------------------------------------------------------------------------
$Services = DB_Select('Services',Array('ID','Code','Name'),Array('Where'=>$Where));
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][] = 'no services for delete notice';
	#-------------------------------------------------------------------------------
	return $ExecuteTime;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Services as $Service){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/NoticeDelete]: Service = %s',$Service['Code']));
	#-------------------------------------------------------------------------------
	#if($Service['Code'] != 'Domain')
	#	continue;
	#-------------------------------------------------------------------------------
	$Where = "`StatusID` = 'Suspended' AND ROUND((UNIX_TIMESTAMP() - `StatusDate`)/86400) IN (2,3,6,11,16,21,31,41,51,61,71,101)";
	#-------------------------------------------------------------------------------
	$Orders = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),Array('*'),Array('Where'=>$Where));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Orders)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		continue 2;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][$Service['Code']] = Array(SizeOf($Orders));
	#-------------------------------------------------------------------------------
	foreach($Orders as $Order){
		#-------------------------------------------------------------------------------
		if(In_Array($Service['Code'],Array('Hosting','Domain','DNSmanager'))){
			#-------------------------------------------------------------------------------
			$ClassName = SPrintF('%sNoticeDeleteMsg',$Service['Code']);
			#-------------------------------------------------------------------------------
			$msg = new $ClassName($Order,(integer)$Order['UserID']);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$msg = new Message(SPrintF('%sNoticeDelete',$Service['Code']),(integer)$Order['UserID'],Array(SPrintF('%sOrder',$Service['Code'])=>$Order));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$IsSend = NotificationManager::sendMsg($msg);
		#-------------------------------------------------------------------------------
		switch(ValueOf($IsSend)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
		case 'true':
			# No more...
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
