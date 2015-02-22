<?php
#-------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Defined('ERROR')){
	#-------------------------------------------------------------------------------
	if(!Define('ERROR',0xABCDEF))
		Exit("Can't define ERROR constant");
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Function_Exists('Debug')){
	#-------------------------------------------------------------------------------
	function Debug($Message){ /* echo $Message; */ };
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function HTTP_Query($Data,$Charset,$Hidden,$IsLogging = TRUE){
	/******************************************************************************/
	$__args_types = Array('array','string','string','boolean');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$Log = Array();
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[HTTP_Query]: целевая кодировка (%s)',$Charset));
	#-------------------------------------------------------------------------------
	$Log[] = $Charset;
	#-------------------------------------------------------------------------------
	$Result = Array();
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Data) as $Key){
		#-------------------------------------------------------------------------------
		$Element = $Data[$Key];
		#-------------------------------------------------------------------------------
		if(Is_Array($Element)){
			#-------------------------------------------------------------------------------
			foreach($Element as $Value){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[HTTP_Query]: [%s]=(%s)',$Key,$Value));
				#-------------------------------------------------------------------------------
				$Log[] = SPrintF('%s=%s',$Key,$Value);
				#-------------------------------------------------------------------------------
				$Value = Mb_Convert_Encoding($Value,$Charset);
				#-------------------------------------------------------------------------------
				$Result[] = SPrintF('%s=%s',$Key,UrlEncode($Value));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[HTTP_Query]: [%s]=(%s)',$Key,$Element));
			#-------------------------------------------------------------------------------
			$Log[] = SPrintF('%s=%s',$Key,$Element);
			#-------------------------------------------------------------------------------
			$Element = Mb_Convert_Encoding($Element,$Charset);
			#-------------------------------------------------------------------------------
			$Result[] = SPrintF('%s=%s',$Key,UrlEncode($Element));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Log = SPrintF("%s\n\n",Implode("\n",$Log));
	#-------------------------------------------------------------------------------
	if($Hidden){
		#-------------------------------------------------------------------------------
		if(!Is_Array($Hidden))
			$Hidden = Array($Hidden);
		#-------------------------------------------------------------------------------
		foreach($Hidden as $Pattern)
			$Log = Str_Replace($Pattern,SPrintF('[HIDDEN=(%u)]',StrLen($Pattern)),$Log);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if($IsLogging){
		#-------------------------------------------------------------------------------
		$Tmp = System_Element('tmp');
		if(Is_Error($Tmp))
			return ERROR | @Trigger_Error('[HTTP_Query]: не удалось определить путь временной директории');
		#-------------------------------------------------------------------------------
		$IsWrite = IO_Write(SPrintF('%s/logs/http-send.log',$Tmp),$Log);
		if(Is_Error($IsWrite))
			return ERROR | @Trigger_Error('[HTTP_Query]: не удалось записать данные в лог файл');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return Implode('&',$Result);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function HTTP_Send($Target,$Settings,$Get = Array(),$Post = Array(),$Addins = Array()){
	/******************************************************************************/
	$__args_types = Array('string','array','array','string,array','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$Default = Array(
			'Protocol'	=> 'tcp',
			'Address'	=> 'localhost',
			'Port'		=> 8080,
			'Host'		=> 'localhost',
			'Basic'		=> '',
			'Charset'	=> 'UTF-8',
			'Hidden'	=> '',
			'IsLogging'	=> TRUE,
			);
	#-------------------------------------------------------------------------------
	Array_Union($Default,$Settings);
	#-------------------------------------------------------------------------------
	$IsLogging = (boolean)$Default['IsLoggin'];
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return ERROR | @Trigger_Error('[HTTP_Send]: не удалось определить путь временной директории');
	#-------------------------------------------------------------------------------
	$Config = Config();
	#-------------------------------------------------------------------------------
	$Address = $Default['Address'];
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[HTTP_Send]: соединяемся с (%s:%u)',$Address,$Default['Port']));
	#-------------------------------------------------------------------------------
	# https://bugs.php.net/bug.php?id=52913
	# пришлось заменить: $Address -> $Default['Host']
	$Socket = @FsockOpen(SPrintF('%s://%s',$Protocol = $Default['Protocol'],$Default['Host'] /*$Address*/),$Port = $Default['Port'],$nError,$sError,$Config['Other']['Libs']['HTTP']['SocketTimeout']);
	if(!Is_Resource($Socket)){
		#-------------------------------------------------------------------------------
		$IsWrite = IO_Write(SPrintF('%s/logs/http-send.log',$Tmp),SPrintF("%s://%s:%u ошибка соединения\n\n",$Protocol,$Address,$Port));
		if(Is_Error($IsWrite))
			return ERROR | @Trigger_Error('[HTTP_Send]: не удалось записать данные в лог файл');
		#-------------------------------------------------------------------------------
		return ERROR | @Trigger_Error('[HTTP_Send]: не удалось соединиться с удаленным хостом');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	# added by lissyara, 2012-01-04 in 08:42:54 MSK, for JBS-130
	Stream_Set_TimeOut($Socket, $Config['Other']['Libs']['HTTP']['StreamTimeout']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Charset = $Default['Charset'];
	#-------------------------------------------------------------------------------
	$Method = (Count($Post) > 0?'POST':'GET');
	#-------------------------------------------------------------------------------
	$Hidden = $Default['Hidden'];
	#-------------------------------------------------------------------------------
	if(Count($Get))
		$Target .= SPrintF('?%s',HTTP_Query($Get,$Charset,$Hidden,$IsLogging));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Headers[] = SPrintF('%s %s HTTP/1.0',$Method,$Target);
	#-------------------------------------------------------------------------------
	$Headers[] = SPrintF('Host: %s',$Default['Host']);
	#-------------------------------------------------------------------------------
	$Headers[] = 'Connection: close';
	#-------------------------------------------------------------------------------
	$Headers = Array_Merge($Headers,$Addins);
	#-------------------------------------------------------------------------------
	if($Basic = $Default['Basic']){
		#-------------------------------------------------------------------------------
		$Basic = Base64_Encode($Basic);
		#-------------------------------------------------------------------------------
		$Headers[] = SPrintF('Authorization: Basic %s',$Basic);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Body = '';
	#-------------------------------------------------------------------------------
	if($Post){
		#-------------------------------------------------------------------------------
		if(Is_Array($Post)){
			#-------------------------------------------------------------------------------
			if(Count($Post) > 0){
				#-------------------------------------------------------------------------------
				$Headers[] = 'Content-Type: application/x-www-form-urlencoded';
				#-------------------------------------------------------------------------------
				$Body = HTTP_Query($Post,$Charset,$Hidden,$IsLogging);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Body = Mb_Convert_Encoding($Post,$Charset);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if($Length = MB_StrLen($Body,'ASCII'))
		$Headers[] = SPrintF('Content-Length: %u',$Length);
	#-------------------------------------------------------------------------------
	$Query = SPrintF("%s\r\n\r\n%s",Implode("\r\n",$Headers),$Body);
	#-------------------------------------------------------------------------------
	Debug(SPrintF("[HTTP_Send]: делаем запрос:\n%s",$Query));
	#-------------------------------------------------------------------------------
	if(!@Fwrite($Socket,$Query))
		return ERROR | @Trigger_Error('[HTTP_Send]: не удалось записать в сокет');
	#-------------------------------------------------------------------------------
	# added by lissyara, 2014-01-28 in 14:19:08 MSK, for JBS-130
	Stream_Set_TimeOut($Socket, $Config['Other']['Libs']['HTTP']['StreamTimeout']);
	#-------------------------------------------------------------------------------
	$Receive = '';
	#-------------------------------------------------------------------------------
	do{
		#-------------------------------------------------------------------------------
		$Bytes = @FGets($Socket);
		#-------------------------------------------------------------------------------
		$Receive .= $Bytes;
		#-------------------------------------------------------------------------------
	}while($Bytes);
	#-------------------------------------------------------------------------------
	@Fclose($Socket);
	#-------------------------------------------------------------------------------
	if(Preg_Match('/Content-Type:[\sa-zA-Z0-9\/\-\;]+charset\=([a-zA-Z0-9\-]+)/i',$Receive,$Matches)){
		#-------------------------------------------------------------------------------
		$Receive = Mb_Convert_Encoding($Receive,'UTF-8',Next($Matches));
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Receive = Mb_Convert_Encoding($Receive,'UTF-8',$Default['Charset']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	Debug(SPrintF("[HTTP_Send]: получили ответ:\n%s",$Receive));
	#-------------------------------------------------------------------------------
	$Log = SPrintF("%s://%s:%u [%s]\n%s\n%s\n\n",$Protocol,$Address,$Port,Date('r'),$Query,$Receive);
	#-------------------------------------------------------------------------------
	if($Hidden){
		#-------------------------------------------------------------------------------
		if(!Is_Array($Hidden))
			$Hidden = Array($Hidden);
		#-------------------------------------------------------------------------------
		foreach($Hidden as $Pattern){
			#-------------------------------------------------------------------------------
			$Pattern = UrlEncode(Mb_Convert_Encoding($Pattern,$Charset));
			#-------------------------------------------------------------------------------
			$Log = Str_Replace($Pattern,SPrintF('[HIDDEN=(%u)]',StrLen($Pattern)),$Log);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if($Default['IsLogging']){
		#-------------------------------------------------------------------------------
		$IsWrite = IO_Write(SPrintF('%s/logs/http-send.log',$Tmp),$Log);
		if(Is_Error($IsWrite))
			return ERROR | @Trigger_Error('[HTTP_Send]: не удалось записать данные в лог файл');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Heads = $Body = Array();
	#-------------------------------------------------------------------------------
	foreach(Explode("\r\n\r\n",$Receive) as $Chunk){
		#-------------------------------------------------------------------------------
		if(Preg_Match('#^HTTP/1\.*#',$Chunk)){
			#-------------------------------------------------------------------------------
			$Heads[] = $Chunk;
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Body[] = $Chunk;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(SizeOf($Body) < 1)
		return ERROR | @Trigger_Error('[HTTP_Send]: ответ от сервера не верен');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return Array('Heads'=>Implode("\r\n\r\n",$Heads),'Body'=>Implode("\r\n\r\n",$Body));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	#$Receive = Preg_Split('/\r\n\r\n/',$Receive,PREG_SPLIT_DELIM_CAPTURE);
	#-------------------------------------------------------------------------------
	#if(Count($Receive) < 2)
	#	return ERROR | @Trigger_Error('[HTTP_Send]: ответ от сервера не верен');
	#-------------------------------------------------------------------------------
	#$Receive = Array_Combine(Array('Heads','Body'),$Receive);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	#return $Receive;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
