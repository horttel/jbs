<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$ISPswGroupID	= (integer) @$Args['ISPswGroupID'];
$Name		=  (string) @$Args['Name'];
$Comment	=  (string) @$Args['Comment'];
$SortID		= (integer) @$Args['SortID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Is_Array(SelectServerSettingsByService(51000)))
	return SelectServerErrorMessage(51000);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Name)
	return new gException('NAME_IS_EMPTY','Не указано название группы');
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Comment)
	return new gException('COMMENT_IS_EMPTY','Не указан комментарий');
#-------------------------------------------------------------------------------
$Answer = Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
$IISPswGroup = Array('Name'=>$Name,'Comment'=>$Comment,'SortID'=>$SortID);
#-------------------------------------------------------------------------------
if($ISPswGroupID){
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('ISPswGroups',$IISPswGroup,Array('ID'=>$ISPswGroupID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$ServersGroupID = DB_Insert('ISPswGroups',$IISPswGroup);
	if(Is_Error($ServersGroupID))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Answer['ServersGroupID'] = $ServersGroupID;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Answer;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
