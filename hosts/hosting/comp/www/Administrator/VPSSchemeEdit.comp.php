<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
$VPSSchemeID = (integer) @$Args['VPSSchemeID'];
#-------------------------------------------------------------------------------
if($VPSSchemeID){
	#-------------------------------------------------------------------------------
	$VPSScheme = DB_Select('VPSSchemes','*',Array('UNIQ','ID'=>$VPSSchemeID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($VPSScheme)){
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
	$VPSScheme = Array(
				'GroupID'		=> 1,
				'UserID'		=> 1,
				'Name'			=> 'default1',
				'PackageID'		=> 'MB500',
				'CostDay'		=> 40,
				'CostMonth'		=> 1200,
				'CostInstall'		=> 100,
				'ServersGroupID'	=> 1,
				'Comment'		=> 'Идеальный тариф для ...',
				'IsReselling'		=> FALSE,
				'IsActive'		=> TRUE,
				'IsProlong'		=> TRUE,
				'IsSchemeChangeable'	=> TRUE,
				'IsSchemeChange'	=> TRUE,
				'MinDaysPay'		=> 31,
				'MinDaysProlong'	=> 14,
				'MaxDaysPay'		=> 1460,
				'MaxOrders'		=> 0,
				'MinOrdersPeriod'	=> 0,
				'SortID'		=> 10,
				'vdslimit'		=> 1,
				'disklimit'		=> 999,
				'maxdesc'		=> 1000,
				'blkiotune'		=> 500,
				'isolimitsize'		=> 1024,
				'isolimitnum'		=> 2,
				'snapshot_limit'	=> 0,
				'maxswap'		=> 10,
				'traf'			=> 1000000,
				'chrate'		=> 8,
				'QuotaUsers'		=> 20,
				'cpu'	        	=> 100,
				'ncpu'	        	=> 1,
				'mem'			=> 128,
				'bmem'			=> 128,
				'proc'			=> 64,
				'ipalias'		=> 0,
				'extns'			=> 'dnsprovider',
				'limitpvtdns'		=> 256,
				'limitpubdns'		=> 256,
				'backup'		=> 'bmonth'
			);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Messages = Messages();
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
$Title = ($VPSSchemeID?SPrintF('Редактирование тарифа VPS: %s',$VPSScheme['Name']):'Добавление нового тарифа виртуального сервера');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Owner','Владелец тарифа',$VPSScheme['GroupID'],$VPSScheme['UserID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Name',
    'value' => $VPSScheme['Name']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название тарифного плана',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'PackageID',
    'value' => $VPSScheme['PackageID']
  ),
  'Точное имя пакета в панели управления'
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Идентификатор пакета в панели',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostDay','value'=>SPrintF('%01.2f',$VPSScheme['CostDay'])));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Стоимость дня'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Используется в расчетах стоимости')),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostMonth','value'=>SPrintF('%01.2f',$VPSScheme['CostMonth'])));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Стоимость месяца'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Используется для отображения')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Summ',Array('name'=>'CostInstall','value'=>SPrintF('%01.2f',$VPSScheme['CostInstall'])));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Стоимость подключения'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Цена за инсталляцию')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
$ServersGroups = DB_Select('ServersGroups','*',Array('Where'=>'`ServiceID` = 30000'));
#-------------------------------------------------------------------------------
switch(ValueOf($ServersGroups)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('SERVERS_GROUPS_NOT_FOUND','Группы серверов не найдены. Необходимо добавить группу серверов для сервиса "VPS", в разделе "Дополнения -> Мастера настройки -> Сервера"');
case 'array':
	# No more...
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach($ServersGroups as $ServersGroup)
  $Options[$ServersGroup['ID']] = $ServersGroup['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ServersGroupID','style'=>'width: 240px',),$Options,$VPSScheme['ServersGroupID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа серверов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsActive','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($VPSScheme['IsActive'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsActive\'); return false;'),'Тариф активен'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsProlong','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($VPSScheme['IsProlong'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsProlong\'); return false;'),'Возможность продления'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsSchemeChangeable','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($VPSScheme['IsSchemeChangeable'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsSchemeChangeable\'); return false;'),'Возможность перехода на тариф'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsSchemeChange','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($VPSScheme['IsSchemeChange'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsSchemeChange\'); return false;'),'Возможность перехода с тарифа'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'MinDaysPay',
    'value' => $VPSScheme['MinDaysPay'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальное кол-во дней оплаты',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
    Array(
     'type'  => 'text',
     'name'  => 'MinDaysProlong',
     'value' => $VPSScheme['MinDaysProlong'],
     'prompt'=> 'Минимальное число дней, на которое можно продлевать заказ',
     'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальное кол-во дней продления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'MaxDaysPay',
    'value' => $VPSScheme['MaxDaysPay'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во дней оплаты',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
   Array(
    'type'  => 'text',
    'name'  => 'MaxOrders',
    'value' => $VPSScheme['MaxOrders'],
    'prompt'=> $Messages['Prompts']['MaxOrders'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во заказов',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'text','name'=>'MinOrdersPeriod','value'=>$VPSScheme['MinOrdersPeriod'],'prompt'=>$Messages['Prompts']['MinOrdersPeriod'],'style'=>'width: 100%;'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальный период между заказами',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'SortID',
    'value' => $VPSScheme['SortID'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
$Table[] = '-Общие ограничения';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'disklimit',
    'value' => $VPSScheme['disklimit'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Дисковое пространство (Мб.)'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Все системы')),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'ncpu',
    'prompt'=> 'количество/число процессоров выделыемых виртуальной машине',
    'value' => $VPSScheme['ncpu'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Количество процессоров'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Все системы')),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'cpu',
    'prompt'=> 'Частота каждого выделенного процессора (в случае виртуализации KVM, это число - приоритет cgroups)',
    'value' => $VPSScheme['cpu'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Частота процессора'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Все системы')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'mem',
    'value' => $VPSScheme['mem'],
    'prompt'=> 'сколько оперативной памяти выделить машине',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Память'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Все системы')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'chrate',
    'value' => $VPSScheme['chrate'],
    'prompt'=> 'Ограничение скорости канала, в мегабитах',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('NOBODY',new Tag('SPAN','Скорость канала, MBit/s'),new Tag('BR'),new Tag('SPAN',Array('class'=>'Comment'),'Все системы')),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = '-Ограничения для VdsManager4';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'bmem',
    'value' => $VPSScheme['bmem'],
    'prompt'=> 'количество дополнительной оперативной памяти, которое может использовать виртуальная машина, при наличии свободной оперативной памяти на хост-машине',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Burstable RAM',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'maxswap',
    'value' => $VPSScheme['maxswap'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Файл подкачки (swap)',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'maxdesc',
    'value' => $VPSScheme['maxdesc'],
    'prompt'=> 'Максимальное количество открытых файлов',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Число дескрипторов',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'proc',
    'value' => $VPSScheme['proc'],
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Число процессов',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
	'Form/Input',
	Array(
		'type'  => 'text',
		'name'  => 'traf',
		'value' => $VPSScheme['traf'],
		'style' => 'width: 100%;',
	)
);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Месячный трафик, Mb',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',
	Array('name'=>'extns','style'=>'width: 100%;'),
	Array('dnsnone'=>'нет','dnsprovider'=>'провайдера','dnsprivate'=>'собственные'),
	$VPSScheme['extns']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = Array('Сервера DNS',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'limitpvtdns',
    'value' => $VPSScheme['limitpvtdns'],
    'prompt'=> 'Лимит приватных DNS. Параметр зависим от возможности DnsLimits. Используется для ограничения числа создаваемых доменов',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ограничение собственных DNS:',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'limitpubdns',
    'value' => $VPSScheme['limitpubdns'],
    'prompt'=> 'Лимит DNS провайдера. Параметр зависим от возможности DnsLimits. Используется для ограничения числа создаваемых доменов',
    'style' => 'width: 100%;',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ограничение DNS провайдера',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',
	Array('name'=>'backup','style'=>'width: 100%;',),
	Array('bnone'=>'не делается','bday'=>'ежедневно','bweek'=>'еженедельно','bmonth'=>'ежемесячно'),
	$VPSScheme['backup']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = Array('Резервное копирование',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = '-Ограничения для VmManager 5 KVM';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'text',
			'name'  => 'blkiotune',
			'value' => $VPSScheme['blkiotune'],
			'prompt'=> 'Вес cgroups на дисковые операции. Позволяет понизить либо повысить приоритет по сравнению с остальными виртуальными машинами. Стандартное значение: 500',
			'style' => 'width: 100%;',
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Вес использования дискового I/O',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'text',
			'name'  => 'isolimitsize',
			'value' => $VPSScheme['isolimitsize'],
			'prompt'=> 'Ограничение по суммарному объёму ISO-образов ',
			'style' => 'width: 100%;',
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Объем ISO, Mb',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'text',
			'name'  => 'isolimitnum',
			'value' => $VPSScheme['isolimitnum'],
			'prompt'=> 'Ограничение по количеству ISO-образов, доступных для закачивания пользователем ',
			'style' => 'width: 100%;',
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Количество ISO',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'  => 'text',
			'name'  => 'snapshot_limit',
			'value' => $VPSScheme['snapshot_limit'],
			'prompt'=> 'Максимально возможное количество снимков виртуальной машины',
			'style' => 'width: 100%;',
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Количество снимков VM',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------




$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name'  => 'Comment',
    'style' => 'width:100%;',
    'rows'  => 3
  ),
  $VPSScheme['Comment']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = 'Описание тарифа';
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('align'=>'right'));
#-------------------------------------------------------------------------------
if($VPSSchemeID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'    => 'checkbox',
      'onclick' => 'form.VPSSchemeID.value = (checked?0:value);',
      'value'   => $VPSSchemeID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Div->AddChild($Comp);
  #-----------------------------------------------------------------------------
  $Div->AddChild(new Tag('SPAN',Array('class'=>'Comment'),'создать новый тариф'));
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => SPrintF("FormEdit('/Administrator/API/VPSSchemeEdit','VPSSchemeEditForm','%s');",$Title),
    'value'   => ($VPSSchemeID?'Сохранить':'Добавить')
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div->AddChild($Comp);
#-------------------------------------------------------------------------------
$Table[] = $Div;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'VPSSchemeEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'  => 'VPSSchemeID',
    'type'  => 'hidden',
    'value' => $VPSSchemeID
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
