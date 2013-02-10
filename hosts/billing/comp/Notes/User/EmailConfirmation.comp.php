<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!CacheManager::isEnabled())
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#if($GLOBALS['__USER']['EmailConfirmed'] < Time() - 365 * 24 * 3600 /* пусть раз в год подтверждают */){
if($GLOBALS['__USER']['EmailConfirmed'] < 1 /* пусть просто подтверждают... */){
	#-------------------------------------------------------------------------------
	$Path = System_Element('templates/modules/EmailConfirmation.html');
	if(Is_Error($Path))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Parse = SPrintF('<NOBODY>%s</NOBODY>',Trim(IO_Read($Path)));
	$NoBody = new Tag('NOBODY');
	$NoBody->AddHTML(SPrintF($Parse,$GLOBALS['__USER']['Email']));
	$NoBody->AddChild(new Tag('STRONG',new Tag('A',Array('href'=>"javascript:ShowWindow('/UserPersonalDataChange');"),'[Мои настройки]')));
	#-------------------------------------------------------------------------
	$Result[] = $NoBody;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------


?>
