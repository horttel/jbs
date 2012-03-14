<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$VisibleName	=  (string) @$Args['VisibleName'];
$PrefixAPI		=  (string) @$Args['PrefixAPI'];
$Address		=  (string) @$Args['Address'];
$Port			= (integer) @$Args['Port'];
$Protocol		=  (string) @$Args['Protocol'];
$Login			=  (string) @$Args['Login'];
$Password		=  (string) @$Args['Password'];
$BalanceLowLimit=  (double) @$Args['BalanceLowLimit'];
#-------------------------------------------------------------------------------
$Config = Config();
$ISPswProducer = $Config['IspSoft']['Settings'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!In_Array($Protocol,Array('tcp','ssl')))
	return new gException('WRONG_PROTOCOL','Неверный протокол сервера');
#-------------------------------------------------------------------------------
if(!$Password)
	return new gException('PASSWORD_NOT_FILLED','Не указан пароль от сервера');
#-------------------------------------------------------------------------------
if(!$Login)
	return new gException('LOGIN_NOT_FILLED','Не указан логин от сервера');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ISPswSettings = Array(
	'VisibleName'		=> $VisibleName,
	'PrefixAPI'			=> $PrefixAPI,
	'Address'			=> $Address,
	'Port'				=> $Port,
	'Protocol'			=> $Protocol,
	'Login'				=> $Login,
	'Password'			=> $Password,
);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach ($ISPswSettings as $Key => $Value){
	# проверяем все настройки
	if(!$Value)
		return new gException($Key . '_NOT_FILLED','Не заполнено поле ' . $Key);
	# удаляем совпадающие значения
	Debug("[comp/www/Administrator/API/ISPswProducerEdit]: '$Key' => '$Value', settings = " . $ISPswProducer[$Key]);
	if($ISPswProducer[$Key] == $Value){
		Debug("[comp/www/Administrator/API/ISPswProducerEdit]: unset '$Key' => '$Value'");
		unset($ISPswSettings[$Key]);
	}
}
#-------------------------------------------------------------------------------
$ISPswSettings['BalanceLowLimit'] = $BalanceLowLimit;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ConfigPath = SPrintF('%s/hosts/%s/config/Config.xml',SYSTEM_PATH,HOST_ID);
if(File_Exists($ConfigPath)){
	#-----------------------------------------------------------------------------
	$File = IO_Read($ConfigPath);
	if(Is_Error($File))
		return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------------
	$XML = String_XML_Parse($File);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error(500);
	#-----------------------------------------------------------------------------
	$Config = $XML->ToArray();
	#-----------------------------------------------------------------------------
	$Config = $Config['XML'];
}else{
	$Config = Array();
}
#---------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# TODO: необходимо удалить все значения одинаковые с общим конфигом в hosting,
# тут имеет смысл сохранять только отличающиеся
$PathPreffix = "IspSoft.Settings";
foreach ($ISPswSettings as $Key => $Value){
	Debug("[comp/www/Administrator/API/ISPswProducerEdit]: '$Key' => '$Value'");
	#---------------------------------------------------------------------------
	$Path = $PathPreffix . "." . $Key;
	#---------------------------------------------------------------------------
	$Path = Explode('.',$Path);
	#---------------------------------------------------------------------------
	$Current = &$Config;
	#---------------------------------------------------------------------------
	for($i=0;$i<$Count=Count($Path);$i++){
		#-------------------------------------------------------------------------
		$Element = $Path[$i];
		#-------------------------------------------------------------------------
		if(!IsSet($Current[$Element]))
			$Current[$Element] = ($i != $Count-1?Array():'');
		#-------------------------------------------------------------------------
		$Current = &$Current[$Element];
	}
	#---------------------------------------------------------------------------
	$Current = $Value;
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$File = IO_Write($ConfigPath,To_XML_String($Config),TRUE);
if(Is_Error($File))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsFlush = CacheManager::flush();
if(!$IsFlush)
	@Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
