<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsSearch');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if ( FALSE ){
$Profiles = DB_Select('Profiles',Array('UserID','Attribs'),Array('Where'=>"`TemplateID` IN ('Natural','Individual','Juridical')"));
#-------------------------------------------------------------------------------
switch(ValueOf($Profiles)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return FALSE;
  case 'array':
    #---------------------------------------------------------------------------
    $Filters = Array();
    #---------------------------------------------------------------------------
    foreach($Profiles as $Profile){
      #-------------------------------------------------------------------------
      $Attribs = $Profile['Attribs'];
      #-------------------------------------------------------------------------
      if(!IsSet($Attribs['pCity']))
        continue;
      #-------------------------------------------------------------------------
      $pCity = $Attribs['pCity'];
      #-------------------------------------------------------------------------
      if(!$pCity)
        continue;
      #-------------------------------------------------------------------------
      $FilterID = Md5($pCity);
      #-------------------------------------------------------------------------
      if(!IsSet($Filters[$FilterID]))
        $Filters[$FilterID] = Array('Name'=>$pCity,'UsersIDs'=>Array());
      #-------------------------------------------------------------------------
      $Filters[$FilterID]['UsersIDs'][] = $Profile['UserID'];
    }
    #---------------------------------------------------------------------------
    foreach($Filters as $FilterID=>$Filter)
      $Filters[$FilterID]['UsersIDs'] = Array_Unique($Filters[$FilterID]['UsersIDs']);
    #---------------------------------------------------------------------------
    Array_UnShift($Filters,'Города');
    #---------------------------------------------------------------------------
    return $Filters;
  default:
    return ERROR | @Trigger_Error(101);
}
}
return FALSE;
#-------------------------------------------------------------------------------

?>
