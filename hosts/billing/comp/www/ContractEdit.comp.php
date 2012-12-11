<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContractID = (integer) @$Args['ContractID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Contract = DB_Select('Contracts',Array('CreateDate','UserID','IsUponConsider','ProfileID'),Array('UNIQ','ID'=>$ContractID));
#-------------------------------------------------------------------------------
switch(ValueOf($Contract)){
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
$Permission = Permission_Check('ContractConsiderEdit',(integer)$GLOBALS['__USER']['ID'],(integer)$Contract['UserID']);
#---------------------------------------------------------------------------
switch(ValueOf($Permission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return ERROR | @Trigger_Error(700);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#---------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#---------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$DOM->AddText('Title','Изменение договора');
#---------------------------------------------------------------------------
$Table = Array();
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'IsUponConsider'),Array('По факту','Ежемесячный'),$Contract['IsUponConsider']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Table[] = Array('Способ отчетности',$Comp);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'    => 'button',
			'onclick' => "FormEdit('/API/ContractEdit','ContractEditForm','Изменение договора');",
			'value'   => 'Изменить'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Table[] = $Comp;
#---------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ContractEditForm','onsubmit'=>'return false;'),$Comp);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'ContractID',
			'type'  => 'hidden',
			'value' => $ContractID
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
$Form->AddChild($Comp);
#---------------------------------------------------------------------------
#---------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#---------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#---------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#---------------------------------------------------------------------------

?>