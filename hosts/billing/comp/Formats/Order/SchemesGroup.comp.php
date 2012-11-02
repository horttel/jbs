<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('SchemesGroupID','Length');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();

return 'Надо сделать список типа сервис/тариф';

#-------------------------------------------------------------------------------
$Where = SPrintF('`ID` IN (SELECT `SchemeID` FROM `SchemesGroupsItems` WHERE `SchemesGroupID` = %u)',$SchemesGroupID);
#-------------------------------------------------------------------------------
$DomainSchemes = DB_Select('Schemes',Array('Name','(SELECT `Name` FROM `Registrators` WHERE `Registrators`.`ID` = `RegistratorID`) as `RegistratorName`'),Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainSchemes)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    foreach($DomainSchemes as $DomainScheme)
      $Result[] = SPrintF('%s (%s)',$DomainScheme['Name'],$DomainScheme['RegistratorName']);
    #---------------------------------------------------------------------------
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Result = Implode(', ',$Result);
#-------------------------------------------------------------------------------
if(!Is_Null($Length)){
  #-----------------------------------------------------------------------------
  $Result = Comp_Load('Formats/String',$Result,$Length);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error(500);
}
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------

?>