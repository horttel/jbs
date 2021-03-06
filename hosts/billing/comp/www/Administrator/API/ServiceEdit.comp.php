<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Upload.php','libs/Image.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$ServiceID		= (integer) @$Args['ServiceID'];
$GroupID		= (integer) @$Args['GroupID'];
$UserID			= (integer) @$Args['UserID'];
$ServicesGroupID	= (integer) @$Args['ServicesGroupID'];
$Name			=  (string) @$Args['Name'];
$NameShort		=  (string) @$Args['NameShort'];
$Item			=  (string) @$Args['Item'];
$Measure		=  (string) @$Args['Measure'];
$ConsiderTypeID		=  (string) @$Args['ConsiderTypeID'];
$CostOn			=   (float) @$Args['CostOn'];
$Cost			=   (float) @$Args['Cost'];
$PartnersRewardPercent	=  (double) @$Args['PartnersRewardPercent'];
$IsActive		= (boolean) @$Args['IsActive'];
$IsProlong		= (boolean) @$Args['IsProlong'];
$IsConditionally	= (boolean) @$Args['IsConditionally'];
$Statuses		=   (array) @$Args['Statuses'];
$IsAutoInvoicing	= (boolean) @$Args['IsAutoInvoicing'];
$SortID			= (integer) @$Args['SortID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(SPrintF("`Name` = '%s'",$Name));
#-------------------------------------------------------------------------------
if($ServiceID)
	$Where[] = SPrintF("`ID` != %u",$ServiceID);
#-------------------------------------------------------------------------------
$Count = DB_Count('Services',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('DUPLICATE_SERVICE_NAME',SPrintF('Услуга с именем "%s" уже есть. Выберите другое имя услуги',$Name));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(SPrintF("`NameShort` = '%s'",$NameShort));
#-------------------------------------------------------------------------------
if($ServiceID)
	$Where[] = SPrintF("`ID` != %u",$ServiceID);
#-------------------------------------------------------------------------------
$Count = DB_Count('Services',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('DUPLICATE_SERVICE_SHORT_NAME',SPrintF('Услуга с коротким именем "%s" уже есть. Выберите другое сокращённое имя услуги',$NameShort));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(SPrintF("`Item` = '%s'",$Item));
#-------------------------------------------------------------------------------
if($ServiceID)
	$Where[] = SPrintF("`ID` != %u",$ServiceID);
#-------------------------------------------------------------------------------
$Count = DB_Count('Services',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Count)
	return new gException('DUPLICATE_SERVICE_ITEM',SPrintF('Услуга с именем "%s" для раздела меню уже есть. Выберите другое имя услуги для меню',$Item));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/API/ServiceEdit]: Statuses = %s',print_r($Statuses,true)));
#-------------------------------------------------------------------------------
$IService = Array(
		#-------------------------------------------------------------------------------
		'GroupID'		=> $GroupID,
		'UserID'		=> $UserID,
		'ServicesGroupID'	=> $ServicesGroupID,
		'Name'			=> $Name,
		'NameShort'		=> $NameShort,
		'Item'			=> $Item,
		'Measure'		=> $Measure,
		'ConsiderTypeID'	=> $ConsiderTypeID,
		'CostOn'		=> $CostOn,
		'Cost'			=> $Cost,
		'PartnersRewardPercent'	=> $PartnersRewardPercent,
		'IsActive'		=> $IsActive,
		'IsProlong'		=> $IsProlong,
		'IsConditionally'	=> $IsConditionally,
		'IsAutoInvoicing'	=> $IsAutoInvoicing,
		'Params'		=> Array('Statuses'=>$Statuses),
		'SortID'		=> $SortID
		#-------------------------------------------------------------------------------
		);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Answer = Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
if($ServiceID){
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Services',$IService,Array('ID'=>$ServiceID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$ServiceID = DB_Insert('Services',$IService);
	if(Is_Error($ServiceID))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Answer['ServiceID'] = $ServiceID;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Upload = Upload_Get('Emblem');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$Emblem = Image_Resize($Upload['Data'],72,72);
	if(Is_Error($Emblem))
		return new gException('EMBLEM_RESIZE_ERROR','Ошибка изменения размеров эмблемы');
	#-------------------------------------------------------------------------------
	if(!SaveUploadedFile('Services', $ServiceID, $Emblem))
		return new gException('CANNOT_SAVE_UPLOADED_FILE','Не удалось сохранить загруженный файл');
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsFlush = CacheManager::flush();
if(!$IsFlush)
	@Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Answer;
#-------------------------------------------------------------------------------

?>
