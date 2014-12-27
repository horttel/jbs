<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/* VDS functions written by lissyara, for www.host-food.ru */
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Http.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Require_Once(SPrintF('%s/others/hosting/IDNA.php',SYSTEM_PATH));
#-------------------------------------------------------------------------------
function VmManager5_KVM_Logon($Settings,$Login,$Password){
	/****************************************************************************/
	$__args_types = Array('array','string','string');
	#-----------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	return Array('Url'=>$Settings['Params']['Url'],'Args'=>Array('lang'=>$Settings['Params']['Language'],'theme'=>$Settings['Params']['Theme'],'checkcookie'=>'no','username'=>$Login,'password'=>$Password,'func'=>'auth'));
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Create($Settings,$VPSOrder,$IP,$VPSScheme){
	/******************************************************************************/
	$__args_types = Array('array','array','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$IDNA = new Net_IDNA_php5();
	$Domain = $IDNA->encode($VPSOrder['Domain']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#-------------------------------------------------------------------------------
	# создаём юзера
	$Request = Array(
			'authinfo'		=> $authinfo,
			'out'			=> 'xml',		# Формат вывода
			'func'			=> 'user.edit',		# Целевая функция
			'sok'			=> 'ok',
			'allowcreatevm'		=> 'off',		# нет ограничений, нет смысла создавать неограниченных юзеров
			'name'			=> $VPSOrder['Login'],
			'passwd'		=> $VPSOrder['Password'],
			'confirm'		=> $VPSOrder['Password'],
			);
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Create]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('USER_ACCOUNT_CREATE_ERROR','Не удалось создать пользователя для виртуального сервера');
	#-------------------------------------------------------------------------------
	if(!IsSet($Doc['id']))
		return new gException('USER_ID_MISSING','Отсутствует идентификатор созданного пользователя');
	#-------------------------------------------------------------------------------
	$VmUserID = $Doc['id'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# создаём виртуалку
	$Request = Array(
			'authinfo'		=> $authinfo,
			'out'			=> 'xml',		# Формат вывода
			'func'			=> 'vm.edit',		# Целевая функция
			'sok'			=> 'ok',
			'hostnode'		=> 'auto',
			'blkiotune'		=> 500,			# этого пока нет в таблицах
			'password'		=> $VPSOrder['Password'],
			'confirm'		=> $VPSOrder['Password'],
			'cputune'		=> Ceil($VPSScheme['cpu']),
			'domain'		=> $VPSOrder['Domain'],
			'family'		=> 'ipv4',		# пока IPv6 всё ещё теория
			'inbound'		=> SPrintF('%u',$VPSScheme['chrate'] * 1024),
			'outbound'		=> SPrintF('%u',$VPSScheme['chrate'] * 1024),
			#'ip'			=> '1.2.3.4',
			'iptype'		=> 'public',		# машина имеет доступ в инет
			'mem'			=> Ceil($VPSScheme['mem']),
			'name'			=> $VPSOrder['Login'],
			'user'			=> $VmUserID,
			'vcpu'			=> $VPSScheme['ncpu'],
			'vmi'			=> SPrintF('ISPsystem__%s',Trim($VPSOrder['DiskTemplate'])),
			'vsize'			=> $VPSScheme['disklimit'],
			);
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Create]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('ACCOUNT_CREATE_ERROR','Не удалось создать виртуальный сервер');
	#-------------------------------------------------------------------------------
        if(!IsSet($Doc['id']))
		return new gException('VM_ID_MISSING','Отсутствует идентификатор созданной виртуальной машины');
	#-------------------------------------------------------------------------------
	$VmID = $Doc['id'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Request = Array(
			'authinfo'		=> $authinfo,
			'out'			=> 'xml',		# Формат вывода
			'func'			=> 'vm.edit',		# Целевая функция
			'elid'			=> $VmID,
			);
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Create]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-------------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('GET_VM_INFO_ERROR','Не удалось получить информацию о виртуальном сервере');
	#-------------------------------------------------------------------------------
        if(!IsSet($Doc['ip']))
		return new gException('VM_IP_MISSING','Отсутствует IP созданной виртуальной машины');
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[system/libs/VmManager5_KVM]: VPS order created with IP = %s',$Doc['ip']));
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('VPSOrders',Array('IP'=>$Doc['ip']),Array('ID'=>$VPSOrder['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Create]: не удалось прописать IP адрес для виртуального сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Active($Settings,$Login){
	/******************************************************************************/
	$__args_types = Array('array','string');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm','su'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Active]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Active','Не удалось получить список виртуальных машин');
	#-------------------------------------------------------------------------------
	foreach($Doc as $VM){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/libs/VmManager5_KVM]: VM = %s',print_r($VM,true)));
		if(!IsSet($VM['id']))
			continue;
		#-------------------------------------------------------------------------------
		$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.start','elid'=>$VM['id']));
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Active]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray();
		#-----------------------------------------------------------------------------
		$Doc = $XML['doc'];
		#-----------------------------------------------------------------------------
		if(IsSet($Doc['error']))
			return new gException('VM_ACTIVATE_ERROR','Не удалось включить виртуальный сервер');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Suspend($Settings,$Login,$VPSScheme){
	/******************************************************************************/
	$__args_types = Array('array','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm','su'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Suspend]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Suspend','Не удалось получить список виртуальных машин');
	#-------------------------------------------------------------------------------
	foreach($Doc as $VM){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/libs/VmManager5_KVM]: VM = %s',print_r($VM,true)));
		if(!IsSet($VM['id']))
			continue;
		#-------------------------------------------------------------------------------
		$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.stop','elid'=>$VM['id']));
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Suspend]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray();
		#-----------------------------------------------------------------------------
		$Doc = $XML['doc'];
		#-----------------------------------------------------------------------------
		if(IsSet($Doc['error']))
			return new gException('VM_SUSPEND_ERROR','Не удалось выключить виртуальный сервер');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Delete($Settings,$Login){
	/******************************************************************************/
	$__args_types = Array('array','string','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm','su'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Delete','Не удалось получить список виртуальных машин');
	#-------------------------------------------------------------------------------
	foreach($Doc as $VM){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/libs/VmManager5_KVM]: VM = %s',print_r($VM,true)));
		if(!IsSet($VM['id']))
			continue;
		#-------------------------------------------------------------------------------
		$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.delete','elid'=>$VM['id']));
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray();
		#-----------------------------------------------------------------------------
		$Doc = $XML['doc'];
		#-----------------------------------------------------------------------------
		if(IsSet($Doc['error']))
			return new gException('VM_DELETE_ERROR','Не удалось удалить виртуальный сервер');
		#-------------------------------------------------------------------------------
	}
	#------------------------------------------------------------------------------
	#------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'user'));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Delete','Не удалось получить список пользователей');
	#------------------------------------------------------------------------------
	foreach($Doc as $User){
		#------------------------------------------------------------------------------
		if(!IsSet($User['id']))
			continue;
		#------------------------------------------------------------------------------
		if($User['name'] == $Login)
			$VmUserID = $User['id'];
		#------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(!IsSet($VmUserID))
		return new gException('VmManager5_KVM_Delete',SPrintF('Не удалось найти пользователя (%s)',$Login));
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'user.delete','elid'=>$VmUserID));
	#-------------------------------------------------------------------------------
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-----------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-----------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('USER_DELETE_ERROR','Не удалось удалить пользователя виртуального сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#------------------------------------------------------------------------------
}



#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Scheme_Change($Settings,$VPSOrder,$VPSScheme){
	/******************************************************************************/
	$__args_types = Array('array','array','array');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm','su'=>$VPSOrder['Login']));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Delete','Не удалось получить список виртуальных машин');
	#-------------------------------------------------------------------------------
	foreach($Doc as $VM){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/libs/VmManager5_KVM]: VM = %s',print_r($VM,true)));
		if(!IsSet($VM['id']))
			continue;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Request = Array(
				'authinfo'		=> $authinfo,
				'out'			=> 'xml',		# Формат вывода
				'func'			=> 'vm.edit',		# Целевая функция
				'sok'			=> 'ok',
				'blkiotune'		=> 500,			# этого пока нет в таблицах
				'cputune'		=> Ceil($VPSScheme['cpu']),
				'inbound'		=> SPrintF('%u',$VPSScheme['chrate'] * 1024),
				'outbound'		=> SPrintF('%u',$VPSScheme['chrate'] * 1024),
				'mem'			=> Ceil($VPSScheme['mem']),
				'name'			=> $VPSOrder['Login'],
				'vcpu'			=> $VPSScheme['ncpu'],
				'elid'			=> $VM['id'],
				);
		#-------------------------------------------------------------------------------
		$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Scheme_Change]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray();
		#-------------------------------------------------------------------------------
		$Doc = $XML['doc'];
		#-------------------------------------------------------------------------------
		if(IsSet($Doc['error']))
			return new gException('SCHEME_CHANGE_ERROR','Не удалось изменить тарифный план для заказа VPS');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# меняем размер диска
		$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.volume','elid'=>$VM['id']));
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Delete]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
	        $XML = $XML->ToArray('elem');
		#-------------------------------------------------------------------------------
		$Doc = $XML['doc'];
		if(IsSet($Doc['error']))
			return new gException('VmManager5_KVM_Delete','Не удалось получить список дисков виртуальных машин');
		#-------------------------------------------------------------------------------
		foreach($Doc as $Volume){
			#-------------------------------------------------------------------------------
			if(!IsSet($Volume['id']))
				continue;
			#-------------------------------------------------------------------------------
			$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.volume.edit','size'=>$VPSScheme['disklimit'],'elid'=>$Volume['id'],'plid'=>$VM['id'],'sok'=>'ok'));
			#-------------------------------------------------------------------------------
			$Response = Trim($Response['Body']);
			#-------------------------------------------------------------------------------
			$XML = String_XML_Parse($Response);
			if(Is_Exception($XML))
				return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
			#-------------------------------------------------------------------------------
			$XML = $XML->ToArray();
			#-------------------------------------------------------------------------------
			$Doc = $XML['doc'];
			#-------------------------------------------------------------------------------
			if(IsSet($Doc['error']))
				return new gException('DISK_SIZE_CHANGE_ERROR','Не удалось изменить размер диска для заказа VPS');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Password_Change($Settings,$Login,$Password,$VPSOrder){
	/****************************************************************************/
	$__args_types = Array('array','string','string','array');
	#-----------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	$Request = Array(
			'authinfo'	=> SPrintF('%s:%s',$Settings['Login'],$Settings['Password']),
			'out'		=> 'xml',
			'func'		=> 'user.edit',
	                'name'		=> $Login,
			'passwd'	=> $Password,
			'confirm'	=> $Password,
	                'allowcreatevm'	=> 'off',
	);
        #---------------------------------------------------------------------------
        $Http = Array(
			'Address'	=> $Settings['Params']['IP'],
			'Port'		=> $Settings['Port'],
			'Host'		=> $Settings['Address'],
			'Protocol'	=> $Settings['Protocol'],
			'Hidden'	=> SPrintF('%s:%s',$Settings['Login'],$Settings['Password']),
			);
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Password_Change]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
	$Response = $Response['Body'];
	#-----------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Password_Change]: неверный ответ от сервера');
	#-----------------------------------------------------------------------------
	$XML = $XML->ToArray();
	#-----------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-----------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('PASSWORD_CHANGE_ERROR','Не удалось изменить пароль для заказа виртуального сервера');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara 2011-08-09 in 09:55 MSK
function VmManager5_KVM_AddIP($Settings,$Login,$ID,$Domain,$IP,$AddressType){
	/****************************************************************************/
        $__args_types = Array('array','string','string','string','string','string');
        $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
        #Debug("ExtraIP order ID = " . $ID);
	/****************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-----------------------------------------------------------------------------
	$Http = Array(
		#---------------------------------------------------------------------------
		'Address'  => $Settings['Params']['IP'],
		'Port'     => $Settings['Port'],
		'Host'     => $Settings['Address'],
		'Protocol' => $Settings['Protocol'],
		'Hidden'   => $authinfo
	);
        #-----------------------------------------------------------------------------
        if($AddressType == "IPv4"){
                $AddrType = "auto";
        }else{
                $AddrType = "auto6";
        }
        #-----------------------------------------------------------------------------
        $Request = Array(
                'authinfo'      => $authinfo,
                'func'          => 'vds.ip.edit',
                'out'           => 'xml',
                'ip'            => $AddrType,
                'otherip'       => '',
                'ipcount'       => '',
                'name'          => $Domain,
                'elid'          => '',
                'plid'          => $Login,
                'sok'           => 'ok'
        );
        #Debug(var_export($Settings, true));
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_AddIP]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
        $Response = Trim($Response['Body']);
        $XML = String_XML_Parse($Response);
        if(Is_Exception($XML))
                return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
        #-----------------------------------------------------------------------------
        $XML = $XML->ToArray();
        #-----------------------------------------------------------------------------
        $Doc = $XML['doc'];
        #-----------------------------------------------------------------------------
        if(IsSet($Doc['error']))
                return new gException('AddIP_ERROR','Не удалось добавить IP для виртуального сервера');
        #-----------------------------------------------------------------------------
        Debug("[system/libs/VmManager5_KVM]: to VPS added IP = " . $Doc['ip']);
        #-----------------------------------------------------------------------------
        $IsQuery = DB_Query("UPDATE `ExtraIPOrders` SET `Login`='" . $Doc['ip'] . "' WHERE `ID`='" . $ID . "'");
        if(Is_Error($IsQuery))
                return ERROR | @Trigger_Error('[VmManager5_KVM_AddIP]: не удалось прописать IP адрес для виртуального сервера');
        #-----------------------------------------------------------------------------
	#-----------------------------------------------------------------------------
	return TRUE;
}


# added by lissyara 2011-08-09 in 13:03 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_DeleteIP($Settings,$ExtraIP){
	/****************************************************************************/
        $__args_types = Array('array','string');
        $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
        #Debug("ExtraIP order ID = " . $ID);
	/****************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-----------------------------------------------------------------------------
	$Http = Array(
		#---------------------------------------------------------------------------
		'Address'  => $Settings['Params']['IP'],
		'Port'     => $Settings['Port'],
		'Host'     => $Settings['Address'],
		'Protocol' => $Settings['Protocol'],
		'Hidden'   => $authinfo
	);
        # func=vds.ip.delete&elid=91.227.18.39&plid=91.227.18.7
        $Request = Array(
                'authinfo'      => $authinfo,
                'func'          => 'vds.ip.delete',
                'out'           => 'xml',
                'elid'          => $ExtraIP,
                'plid'          => $Settings['UserLogin'],
                'sok'           => 'ok'
        );
        #Debug(var_export($Settings, true));
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_DeleteIP]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
        $Response = Trim($Response['Body']);
        $XML = String_XML_Parse($Response);
        if(Is_Exception($XML))
                return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
        #-----------------------------------------------------------------------------
        $XML = $XML->ToArray();
        #-----------------------------------------------------------------------------
        $Doc = $XML['doc'];
        #-----------------------------------------------------------------------------
        if(IsSet($Doc['error']))
                return new gException('AddIP_ERROR','Не удалось удалить IP у виртуального сервера');
        #-----------------------------------------------------------------------------
        #-----------------------------------------------------------------------------
	#-----------------------------------------------------------------------------
	return TRUE;
}

# added by lissyara 2011-10-07 in 10:28 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_MainUsage($Settings){
	/****************************************************************************/
        $__args_types = Array('array');
        $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	# TODO: надо сделать
	return rand(1,10);

	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-----------------------------------------------------------------------------
	$Http = Array(
		#---------------------------------------------------------------------------
		'Address'  => $Settings['Params']['IP'],
		'Port'     => $Settings['Port'],
		'Host'     => $Settings['Address'],
		'Protocol' => $Settings['Protocol'],
		'Hidden'   => $authinfo
	);
        # 
        $Request = Array(
                'authinfo'      => $authinfo,
                'func'          => 'mainusage',
                'out'           => 'xml',
                'sok'           => 'ok'
        );
        #Debug(var_export($Settings, true));
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),$Request);
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_MainUsage]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
        $Response = Trim($Response['Body']);
        $XML = String_XML_Parse($Response);
        if(Is_Exception($XML))
                return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
        #-----------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
        $Doc = $XML['doc'];
        if(IsSet($Doc['error']))
                return new gException('VmManager5_KVM_MainUsage','Не удалось получить нагрузку сервера');
        #---------------------------------------------------------------------------
        # перебираем, складываем
        $Out = Array(
                        'cpuu'  => 0,
                        'memu'  => 0,
                        'swapu' => 0,
                        'disk0' => 0
                );
        $NumStrings = SizeOf($Doc);
        foreach($Doc as $Usage){
                $Out['cpuu'] = $Out['cpuu'] + $Usage['cpuu'];
                $Out['memu'] = $Out['memu'] + $Usage['memu'];
                $Out['swapu'] = $Out['swapu'] + $Usage['swapu'];
                $Out['disk0'] = $Out['disk0'] + $Usage['disk0'];
        }
        # считаем средние значнеия
        $Out['cpuu'] = $Out['cpuu'] / SizeOf($Doc);
        $Out['memu'] = $Out['memu'] / SizeOf($Doc);
        $Out['swapu'] = $Out['swapu'] / SizeOf($Doc);
        $Out['disk0'] = $Out['disk0'] / SizeOf($Doc);
        
        Debug("[system/libs/VmManager5_KVM.php]: usage for " . $Settings['Address'] . " is " . $Out['cpuu'] ."/". $Out['memu'] ."/". $Out['swapu'] ."/". $Out['disk0']);
        return ($Out['cpuu'] + $Out['memu'] + $Out['swapu'] + $Out['disk0']);
	#-----------------------------------------------------------------------------
}

# added by lissyara, 2012-02-02 in 21:53 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_CheckIsActive($Settings,$Login){
	/****************************************************************************/
	$__args_types = Array('array','string');
	#-----------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-----------------------------------------------------------------------------
	$Http = Array(
		#---------------------------------------------------------------------------
		'Address'  => $Settings['Params']['IP'],
		'Port'     => $Settings['Port'],
		'Host'     => $Settings['Address'],
		'Protocol' => $Settings['Protocol'],
		'Hidden'   => $authinfo
	);
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm'));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_CheckIsActive]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-----------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-----------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-----------------------------------------------------------------------------
	$Doc = $XML['doc'];
	#-----------------------------------------------------------------------------
	if(IsSet($Doc['error']))
		return new gException('CHECK_ACCOUNT_ACTIVE_ERROR','Не удалось проверить состояние виртуального сервера');
	#-----------------------------------------------------------------------------
	$VPSs = $XML['doc'];
	#-----------------------------------------------------------------------------
	foreach($VPSs as $VPS)
		if(IsSet($VPS['id']))
			if($VPS['name'] == $Login)
				if(IsSet($VPS['stoped']))
					if(IsSet($VPS['admdown']))
						return FALSE;
	#-----------------------------------------------------------------------------
	# not found, or enabled
	Debug(SPrintF("[system/libs/VmManager5_KVM]: %s is enabled, disabled not by administrator, or not found",$Login));
	return TRUE;
}

# added by lissyara, 2012-02-03 in 09:59 MSK
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Reboot($Settings,$Login){
	/******************************************************************************/
	$__args_types = Array('array','string');
	#-------------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-------------------------------------------------------------------------------
	$Http = Array(
			'Address'  => $Settings['Params']['IP'],
			'Port'     => $Settings['Port'],
			'Host'     => $Settings['Address'],
			'Protocol' => $Settings['Protocol'],
			'Hidden'   => $authinfo
			);
	#-------------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm','su'=>$Login));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Reboot]: не удалось соедениться с сервером');
	#-------------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-------------------------------------------------------------------------------
        $XML = $XML->ToArray('elem');
	#-------------------------------------------------------------------------------
	$Doc = $XML['doc'];
	if(IsSet($Doc['error']))
		return new gException('VmManager5_KVM_Reboot','Не удалось получить список виртуальных машин');
	#-------------------------------------------------------------------------------
	foreach($Doc as $VM){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/libs/VmManager5_KVM]: VM = %s',print_r($VM,true)));
		if(!IsSet($VM['id']))
			continue;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'vm.restart','elid'=>$VM['id']));
		if(Is_Error($Response))
			return ERROR | @Trigger_Error('[VmManager5_KVM_Reboot]: не удалось соедениться с сервером');
		#-------------------------------------------------------------------------------
		$Response = Trim($Response['Body']);
		#-------------------------------------------------------------------------------
		$XML = String_XML_Parse($Response);
		if(Is_Exception($XML))
			return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
		#-------------------------------------------------------------------------------
		$XML = $XML->ToArray();
		#-------------------------------------------------------------------------------
		$Doc = $XML['doc'];
		#-------------------------------------------------------------------------------
		if(IsSet($Doc['error']))
			return new gException('ACCOUNT_REBOOT_ERROR','Не удалось перезагрузить виртуальный сервер');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

# added by lissyara, 2013-05-17 in 09:53 MSK, for JBS-280
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function VmManager5_KVM_Get_Users($Settings){
	/****************************************************************************/
	$__args_types = Array('array','string');
	#-----------------------------------------------------------------------------
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/****************************************************************************/
	$authinfo = SPrintF('%s:%s',$Settings['Login'],$Settings['Password']);
	#-----------------------------------------------------------------------------
	$Http = Array(
	#---------------------------------------------------------------------------
		'Address'  => $Settings['Params']['IP'],
		'Port'     => $Settings['Port'],
		'Host'     => $Settings['Address'],
		'Protocol' => $Settings['Protocol'],
		'Hidden'   => $authinfo
	);
	#-----------------------------------------------------------------------------
	$Response = Http_Send('/vmmgr',$Http,Array(),Array('authinfo'=>$authinfo,'out'=>'xml','func'=>'user'));
	if(Is_Error($Response))
		return ERROR | @Trigger_Error('[VmManager5_KVM_Get_Users]: не удалось соедениться с сервером');
	#-----------------------------------------------------------------------------
	$Response = Trim($Response['Body']);
	#-----------------------------------------------------------------------------
	$XML = String_XML_Parse($Response);
	if(Is_Exception($XML))
		return new gException('WRONG_SERVER_ANSWER',$Response,$XML);
	#-----------------------------------------------------------------------------
	$XML = $XML->ToArray('elem');
	#-----------------------------------------------------------------------------
	$Users = $XML['doc'];
	#-----------------------------------------------------------------------------
	if(IsSet($Users['error']))
		return new gException('GET_USERS_ERROR',$Users['error']);
	#-----------------------------------------------------------------------------
	$Result = Array();
	#-----------------------------------------------------------------------------
	foreach($Users as $User){
		#---------------------------------------------------------------------------
		if(!IsSet($User['id']))
			continue;
		#---------------------------------------------------------------------------
		if(!IsSet($User['name']))
			continue;
		#---------------------------------------------------------------------------
		if($User['name'] != $Settings['Login'])
			$Result[] = $User['name'];
		#-----------------------------------------------------------------------------
	}
	#-----------------------------------------------------------------------------
	#-----------------------------------------------------------------------------
	return $Result;
	#-----------------------------------------------------------------------------
}
#-----------------------------------------------------------------------------


?>