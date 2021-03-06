<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContractID = (integer) @$Args['ContractID'];
$Format     =  (string) @$Args['Format'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/HTMLDoc.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array('ID','UserID');
#-------------------------------------------------------------------------------
$Contract = DB_Select('Contracts',$Columns,Array('UNIQ','ID'=>$ContractID));
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
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('ContractRead',(integer)$__USER['ID'],(integer)$Contract['UserID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
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
$ContractID = $Contract['ID'];
#-------------------------------------------------------------------------------
$Number = Comp_Load('Formats/Contract/Number',$ContractID);
if(Is_Error($Number))
	return ERROR | @Trigger_Error(500);
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
$DOM->AddText('Title','Акт сверки с контрагентом');
#-------------------------------------------------------------------------------
$Comp1 = Comp_Load('Buttons/Standard',Array('onclick'=>SPrintF("javascript:AjaxCall('/VerifyReport',{ContractID:%u,Format:'PDF'},'Формирование акта сверки в формат PDF','document.location = \$Answer.Location');",$ContractID)),'Скачать в формате PDF','PDF.gif');
if(Is_Error($Comp1))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp2 = Comp_Load('Buttons/Standard',Array('onclick'=>SPrintF("javascript:AjaxCall('/VerifyReport',{ContractID:%u,Format:'CSV'},'Формирование акта сверки в формат CSV','document.location = \$Answer.Location');",$ContractID)),'Скачать в формате CSV','CSV.gif');
if(Is_Error($Comp2))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp1,'Name'=>'Скачать в формате PDF'),Array('Comp'=>$Comp2,'Name'=>'Скачать в формате CSV'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',new Tag('DIV',Array('id'=>'Rubbish'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Verifies = Array();
#-------------------------------------------------------------------------------
$Invoices = DB_Select('Invoices',Array('ID','CreateDate','Summ'),Array('Where' => Array(SPrintF('`ContractID` = %u',$ContractID),'`StatusID` = "Payed"')));
#-------------------------------------------------------------------------------
switch(ValueOf($Invoices)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('INVOICES_NOT_FOUND','Нет оплаченных счетов');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
foreach($Invoices as $Invoice){
	#-------------------------------------------------------------------------------
	$CreateDate = $Invoice['CreateDate'];
	#-------------------------------------------------------------------------------
	if(IsSet($Verifies[$CreateDate]))
		$CreateDate += Rand(1,100)/100;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Invoice/Number',$Invoice['ID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Verifies[$CreateDate] = Array('Founding'=>SPrintF('Оплата счета №%s',$Comp),'Measure'=>'-','Amount'=>'-','Cost'=>'-','Discont'=>'-','Debet'=>$Invoice['Summ'],'Credit'=>0);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$WorksComplite = DB_Select('WorksComplite',Array('Comment','CreateDate','Amount','Cost','Discont','(`Amount`*`Cost`*(1-`Discont`)) as `Summ`','(SELECT `Name` FROM `Services` WHERE `Services`.`ID` = `WorksComplite`.`ServiceID`) as `Service`','(SELECT `Measure` FROM `Services` WHERE `Services`.`ID` = `WorksComplite`.`ServiceID`) as `Measure`'),Array('Where' => SPrintF('`ContractID` = %u', $ContractID)));
#-------------------------------------------------------------------------------
switch(ValueOf($WorksComplite)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('POSTINGS_NOT_FOUND','Операции по договору не найдены');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
foreach($WorksComplite as $WorkComplite){
	#-------------------------------------------------------------------------------
	$CreateDate = $WorkComplite['CreateDate'];
	#-------------------------------------------------------------------------------
	if(IsSet($Verifies[$CreateDate]))
		$CreateDate += Rand(1,100)/100;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Percent',$WorkComplite['Discont']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Cost = Comp_Load('Formats/Currency',$WorkComplite['Cost']);
	if(Is_Error($Cost))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Verifies[$CreateDate] = Array('Founding'=>SPrintF('%s %s',$WorkComplite['Service'],$WorkComplite['Comment']),'Measure'=>$WorkComplite['Measure'],'Amount'=>(integer)$WorkComplite['Amount'],'Cost'=>$Cost,'Discont'=>$Comp,'Debet'=>0,'Credit'=>$WorkComplite['Summ']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
KSort($Verifies);
#-------------------------------------------------------------------------------
$Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Дата'),new Tag('TD',Array('class'=>'Head'),'Основание'),new Tag('TD',Array('class'=>'Head'),'Ед. изм.'),new Tag('TD',Array('class'=>'Head'),'Кол-во'),new Tag('TD',Array('class'=>'Head'),'Цена'),new Tag('TD',Array('class'=>'Head'),'Скидка'),new Tag('TD',Array('class'=>'Head'),'Дебет'),new Tag('TD',Array('class'=>'Head'),'Кредит')));
#-------------------------------------------------------------------------------
$Total = Array('Debet'=>0,'Credit'=>0);
#-------------------------------------------------------------------------------
foreach($Verifies as $CreateDate=>$Verify){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Date/Standard',(integer)$CreateDate);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Line = Array($Comp,$Verify['Founding'],$Verify['Measure'],$Verify['Amount'],$Verify['Cost'],$Verify['Discont']);
	#-------------------------------------------------------------------------------
	$Credit = $Verify['Credit'];
	#-------------------------------------------------------------------------------
	if($Credit){
		#-------------------------------------------------------------------------------
		$Total['Credit'] += $Credit;
		#-------------------------------------------------------------------------------
		$Credit = Comp_Load('Formats/Currency',$Verify['Credit']);
		if(Is_Error($Credit))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Credit = '-';
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Line[] = $Credit;
	#-------------------------------------------------------------------------------
	$Debet = $Verify['Debet'];
	#-------------------------------------------------------------------------------
	if($Debet){
		#-------------------------------------------------------------------------------
		$Total['Debet'] += $Debet;
		#-------------------------------------------------------------------------------
		$Debet = Comp_Load('Formats/Currency',$Verify['Debet']);
		if(Is_Error($Debet))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Debet = '-';
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Line[] = $Debet;
	#-------------------------------------------------------------------------------
	$Table[] = $Line;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Debet = Comp_Load('Formats/Currency',$Total['Debet']);
if(Is_Error($Debet))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Credit = Comp_Load('Formats/Currency',$Total['Credit']);
if(Is_Error($Credit))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>6),'Общий оборот'), $Credit, $Debet);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
switch($Format){
case 'CSV':
	#-------------------------------------------------------------------------------
	$Csv = Array();
	#-------------------------------------------------------------------------------
	foreach($Table as $Row){
		#-------------------------------------------------------------------------------
		$Array = Array();
		#-------------------------------------------------------------------------------
		foreach($Row as $Column){
			#-------------------------------------------------------------------------------
			if(Is_Object($Column)){
				#-------------------------------------------------------------------------------
				$Array[] = SPrintF('"%s"',$Column->Text);
				#-------------------------------------------------------------------------------
				$Attribs = $Column->Attribs;
				#-------------------------------------------------------------------------------
				if(IsSet($Attribs['colspan']))
					$Array = Array_Merge($Array,Array_Fill(0,$Attribs['colspan']-1,NULL));
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$Array[] = SPrintF('"%s"',$Column);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$CSV[] = Implode(';',$Array);
	}
	#-------------------------------------------------------------------------------
	$CSV = Implode("\r\n",$CSV);
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$File = SPrintF('VerifyReport%s.csv',Md5($_SERVER['REMOTE_ADDR']));
	#-------------------------------------------------------------------------------
	$IsWrite = IO_Write(SPrintF('%s/files/%s',$Tmp,$File),$CSV,TRUE);
	if(Is_Error($IsWrite))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok','Location'=>SPrintF('/GetTemp?File=%s&Name=VerifyReport%s.csv&Mime=application/csv',$File,$Number));
	#-------------------------------------------------------------------------------
case 'PDF':
	#-------------------------------------------------------------------------------
	$DOM->Delete('Rubbish');
	#-------------------------------------------------------------------------------
	$PDF = HTMLDoc_CreatePDF('VerifyReport',$DOM);
	#-------------------------------------------------------------------------------
	switch(ValueOf($PDF)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'string':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$File = SPrintF('VerifyReport%s.pdf',Md5($_SERVER['REMOTE_ADDR']));
	#-------------------------------------------------------------------------------
	$IsWrite = IO_Write(SPrintF('%s/files/%s',$Tmp,$File),$PDF,TRUE);
	#-------------------------------------------------------------------------------
	if(Is_Error($IsWrite))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok','Location'=>SPrintF('/GetTemp?File=%s&Name=VerifyReport%s.pdf&Mime=application/pdf',$File,$Number));
	#-------------------------------------------------------------------------------
default:
	#-------------------------------------------------------------------------------
	if(Is_Error($DOM->Build(FALSE)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return Array('Status'=>'Ok','DOM'=>$DOM->Object);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
