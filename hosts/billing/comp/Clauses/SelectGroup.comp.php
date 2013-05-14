<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
$ClausesGroups = DB_Select('ClausesGroups',Array('*'),Array('Where'=>'`IsPublish` = "yes"'/*,'SortOn'=>'SortID'*/));
#-------------------------------------------------------------------------------
switch(ValueOf($ClausesGroups)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$Options = Array();
	#-------------------------------------------------------------------------------
	$Options['Default'] = 'Все категории';
	$Options['Empty'] = '';
	#-------------------------------------------------------------------------------
	foreach($ClausesGroups as $ClausesGroup)
		$Options[$ClausesGroup['ID']] = $ClausesGroup['Name'];
	#$Options[$ClausesGroup['ID']] = SPrintF('%s/%s',$ClausesGroup['Name'],$ClausesGroup['Notice']);
	#-------------------------------------------------------------------------------
	$GroupID = 'Default';
	#-------------------------------------------------------------------------------
	$Session = &$Template['Session'];
	#-------------------------------------------------------------------------------
	if(IsSet($Session['GroupID']))
		$GroupID = $Session['GroupID'];
	#-------------------------------------------------------------------------------
	$Args = Args();
	#-------------------------------------------------------------------------------
	if(IsSet($Args['GroupID']))
		$GroupID = $Args['GroupID'];
	#-------------------------------------------------------------------------------
	$Session['GroupID'] = $GroupID;
	#-------------------------------------------------------------------------------
	$AddingWhere = &$Template['Source']['Adding']['Where'];
	#-------------------------------------------------------------------------------
	if($GroupID != 'Default')
		$AddingWhere[] = SPrintF('`GroupID` = %u',$GroupID);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'GroupID','onchange'=>'TableSuperReload();'),$Options,$GroupID,'Empty');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('NOBODY',new Tag('TD',Array('class'=>'Comment'),'Группа'),new Tag('TD',$Comp)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Count($Tr->Childs))
	return FALSE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>5),$Tr);
#-------------------------------------------------------------------------------

?>