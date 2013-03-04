<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Upload.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Files = DB_Select('Services',Array('ID','Emblem'),Array('Where'=>"`Emblem` IS NOT NULL"));
#-------------------------------------------------------------------------------
switch(ValueOf($Files)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#---------------------------------------------------------------------------
	foreach($Files as $File){
		#---------------------------------------------------------------------------
		Debug(SPrintF("[patches/billing/files/1000059]: save file #%u ",$File['ID']));
		#-------------------------------------------------------------------------
		if(!SaveUploadedFile('Services', $File['ID'], $File['Emblem']))
			Debug("[patches/billing/files/1000059]: cannot save file " . $File['ID']);
		#-------------------------------------------------------------------------
		$Erase = DB_Query("UPDATE `Services` SET `Emblem` = NULL WHERE ID = " . $File['ID']);
		if(Is_Error($Erase))
			return ERROR | @Trigger_Error('101');
		#-------------------------------------------------------------------------
	}
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
?>