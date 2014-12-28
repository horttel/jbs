<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$ServersGroupID = (integer) @$Args['ServersGroupID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ServersGroupID){
	#-------------------------------------------------------------------------------
	$ServersGroup = DB_Select('ServersGroups','*',Array('UNIQ','ID'=>$ServersGroupID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($ServersGroup)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		# No more...
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$ServersGroup = Array(
				'Name'		=> 'Сервера хостинга, линейка VIP',
				'ServiceID'	=> 0,
				'FunctionID'	=> 'NotDefined',
				'Params'	=> Array('Count'=>0),
				'Comment'	=> 'Москва, M9, пятая стойка с правого угла',
				'SortID'	=> 10
				);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array('`IsActive` = "yes"','`IsHidden` != "yes"');
#-------------------------------------------------------------------------------
$Services = DB_Select('ServicesOwners',Array('ID','Code','Item','NameShort'),Array('Where'=>$Where,'SortOn'=>'SortID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$ServiceOptions = Array('Сервисы отсутствуют');
	#-------------------------------------------------------------------------------
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$ServiceOptions = Array('Любой активный сервис');
	#---------------------------------------------------------------------------
	foreach($Services as $Service)
		$ServiceOptions[$Service['ID']] = SPrintF('%s (%s)',$Service['Code'],$Service['NameShort']);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Services = Comp_Load('Form/Select',Array('name'=>'ServiceID','style'=>'width: 100%;'),$ServiceOptions,$ServersGroup['ServiceID']);
if(Is_Error($Services))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/GetSchemes.js}')));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Title = ($ServersGroupID?SPrintF('Редактирование группы серверов "%s"',$ServersGroup['Name']):'Добавление новой группы серверов');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ServersGroupEditForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'		=> 'text',
			'name'		=> 'Name',
			'style'		=> 'width: 100%;',
			'value'		=> $ServersGroup['Name'],
			'prompt'	=> 'Имя группы серверов, для отображения'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название группы',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Настройки';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Сервис',$Services);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Functions = $Config['Servers']['Balancing']['Functions'];
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Functions) as $FunctionID)
	$Options[$FunctionID] = $Functions[$FunctionID]['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'FunctionID','style'=>'width: 100%;','prompt'=>'По какому принципу производить балансировку серверов в группе, если это актуально для данной группы'),$Options,$ServersGroup['FunctionID']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Функция балансировки',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Автоматический заказ услуги';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'Count','style'=>'width: 100%;','value'=>(IsSet($ServersGroup['Params']['Count'])?$ServersGroup['Params']['Count']:0),'prompt'=>'Количество автоматически заказываемых услуг. После установки числа, настройки надо сохранить, и открыть заново на редактирование'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Число сервисов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(IsSet($ServersGroup['Params']['Count']) && $ServersGroup['Params']['Count'] > 0){
	#-------------------------------------------------------------------------------
	$Scripts = Array();
	#-------------------------------------------------------------------------------
	$Options = Array('Любой тариф');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Statuses = $Config['Statuses'];
	#-------------------------------------------------------------------------------
	$Array = Array();
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Statuses) as $TypeID){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/Administrator/ServersGroupEdit]: TypeID = %s',$TypeID));
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Statuses[$TypeID]) as $StatusID){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/Administrator/ServersGroupEdit]: StatusID = %s',$StatusID));
			#-------------------------------------------------------------------------------
			if(Preg_Match('/Order/',$TypeID))
				if(!IsSet($Array[$StatusID]))
					$Array[$StatusID] = $Statuses[$TypeID][$StatusID]['Name'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = new Tag('TD',Array('colspan'=>2,'width'=>300,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),new Tag('SPAN',new Tag('SPAN','Если вы не понимаете зачем это используется - лучше проставьте ноль в количество сервисов, и сохраните данные формы.'),new Tag('HR',Array('size'=>1)),new Tag('SPAN','В общих чертах, задумано как заказ дополнительных сервисов при активации какого-то заказа. Например, заказ вторичных DNS серверов, по бесплатному тарифному плану, при активации заказа на VPS.')));
	#-------------------------------------------------------------------------------
	for ($i = 1; $i <= $ServersGroup['Params']['Count']; $i++){
		#-------------------------------------------------------------------------------
		$Table[] = SPrintF('Настройки автоматически заказываемой услуги #%u',$i);
		#-------------------------------------------------------------------------------
		$Status		= SPrintF('Status%u',$i);
		$Service	= SPrintF('Service%u',$i);
		$Scheme		= SPrintF('Scheme%u',$i);
		$IsNoDuplicate	= SPrintF('IsNoDuplicate%u',$i);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Select',Array('name'=>$Status,'style'=>'width:100%','prompt'=>'При наступлении какого статуса активировать зависимую услугу. Обычно, это "На создании" или "Активен"'),$Array,(IsSet($ServersGroup['Params'][$Status])?$ServersGroup['Params'][$Status]:'OnCreate'));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Статус',$Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$OnChange = SPrintF("GetSchemes(this.value,'%s','%s');",$Scheme,(IsSet($ServersGroup['Params'][$Service])?$ServersGroup['Params'][$Service]:0));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Select',Array('name'=>$Service,'OnChange'=>$OnChange,'style'=>'width:100%'),$ServiceOptions,(IsSet($ServersGroup['Params'][$Service])?$ServersGroup['Params'][$Service]:0));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Сервис',$Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Select',Array('name'=>$Scheme,'id'=>$Scheme,'disabled'=>TRUE,'style'=>'width:100%'),$Options,(IsSet($ServersGroup['Params'][$Scheme])?$ServersGroup['Params'][$Scheme]:0));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Тариф',$Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>$IsNoDuplicate,'value'=>'yes','prompt'=>'Не производить заказ, если у пользователя уже есть такая услуга с таким тарифом'));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(IsSet($ServersGroup['Params'][$IsNoDuplicate]) && $ServersGroup['Params'][$IsNoDuplicate])
			$Comp->AddAttribs(Array('checked'=>'yes'));
		#-------------------------------------------------------------------------------
		$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>SPrintF('ChangeCheckBox(\'%s\'); return false;',$IsNoDuplicate)),'Не дублировать'),$Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Scripts[] = SPrintF("GetSchemes('%s','%s','%s');",$ServersGroup['Params'][$Service],$Scheme,$ServersGroup['Params'][$Scheme]);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/Administrator/ServersGroupEdit]: onload = %s',Implode(' ',$Scripts)));
	$DOM->AddAttribs('Body',Array('onload'=>Implode(' ',$Scripts)));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Прочие параметры';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',Array('rows'=>3,'cols'=>41,'name'=>'Comment','prompt'=>'Описание группы серверов, для внутреннего использования'),$ServersGroup['Comment']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Комментарий',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'		=> 'text',
			'name'		=> 'SortID',
			'style'		=> 'width: 100%;',
			'value'		=> $ServersGroup['SortID'],
			'prompt'	=> 'В каком порядке сортировать группы, при отображении'
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'    => 'button',
			'onclick' => SPrintF("FormEdit('/Administrator/API/ServersGroupEdit','ServersGroupEditForm','%s');",$Title),
			'value'   => ($ServersGroupID?'Сохранить':'Добавить')
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
if($ServersGroupID){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'name'  => 'ServersGroupID',
				'type'  => 'hidden',
				'value' => $ServersGroupID
				)
			);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
