<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Disabled');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"quote(form.Message);return false;"),'Цитировать','TextQuote.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('TD',Array('width'=>25),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array(/*'onclick'=>"form.Message.value += '[image]http://server/image.gif[/image]\\n';",*/'id'=>'image'),'Добавить изображение','Image.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('TD',Array('width'=>25),$Comp));
#-------------------------------------------------------------------------------
//$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"form.Message.value += '[b][/b]';"),'Жирный текст','TextSize.gif');
$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"bb_code(document.getElementById('Message'),'b');return false;"),'Жирный текст','TextSize.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('TD',Array('width'=>25),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"bb_code(document.getElementById('Message'),'color=green','color');return false;"),'Цвет текста','TextColor.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('TD',Array('width'=>25),$Comp));
#-------------------------------------------------------------------------------
if(!In_Array('hidden',$Disabled)){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"bb_code(document.getElementById('Message'),'hidden');return false;"),'Вставить невидимый текст','TextLock.gif');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$NoBody->AddChild(new Tag('TD',Array('width'=>25),$Comp));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $NoBody;
#-------------------------------------------------------------------------------

?>
