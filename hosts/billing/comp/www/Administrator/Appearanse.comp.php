<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DOM.class.php','modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Мастера настройки → Внешний вид');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Table[] = 'Базовое оформление системы';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','TopLogo');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Верхний логотип (png)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('style'=>'border:1px solid #DCDCDC;border:1px solid #DCDCDC;overflow:scroll;overflow-x:auto;overflow-y:auto;width:500px;height:150px;'),new Tag('IMG',Array('src'=>'SRC:{Images/TopLogo.png}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','Favicon');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Иконка (ico)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('IMG',Array('src'=>'/favicon.ico'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','FaviconPNG');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Иконка для числа тикетов (png, 16x16)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('IMG',Array('src'=>'SRC:{Images/favicon.png}'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Copyright = DB_Select('Config','Value',Array('UNIQ','Where'=>"`Param` = 'Copyright'"));
if(!Is_Array($Copyright))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('name'=>'Copyright','style'=>'width:100%;','rows'=>4),$Copyright['Value']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Нижняя надпись',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Оформление счетов, формат *.bmp, без сжатия';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','Logo');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Логотип (300x150)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('style'=>'border:1px solid #DCDCDC;overflow:scroll;overflow-x:auto;overflow-y:auto;width:300px;height:150px;'),new Tag('IMG',Array('src'=>'SRC:{Images/Logo.bmp}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','Stamp');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Печать (160x160)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('style'=>'border:1px solid #DCDCDC;overflow:scroll;overflow-x:auto;overflow-y:auto;width:160px;height:160px;'),new Tag('IMG',Array('src'=>'SRC:{Images/Stamp.bmp}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','dSign');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись директора (130x100)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('style'=>'border:1px solid #DCDCDC;overflow:scroll;overflow-x:auto;overflow-y:auto;width:130px;height:100px;'),new Tag('IMG',Array('src'=>'SRC:{Images/dSign.bmp}')));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','aSign');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись главного бухгалтера (130x100)',$Comp);
#-------------------------------------------------------------------------------
$Table[] = new Tag('DIV',Array('style'=>'border:1px solid #DCDCDC;overflow:scroll;overflow-x:auto;overflow-y:auto;width:130px;height:100px;'),new Tag('IMG',Array('src'=>'SRC:{Images/aSign.bmp}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>"FormEdit('/Administrator/API/Appearanse','AppearanseForm','Сохранение внешнего вида');",'value'=>'Сохранить'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'AppearanseForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','Administrator/Masters',$Form);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
