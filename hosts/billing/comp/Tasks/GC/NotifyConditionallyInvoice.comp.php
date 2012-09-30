<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# added by lissyara 2012-09-30 in 20:20 MSK, for JBS-109
# перебираем всех юзеров с условными инвойсами, смотрим сколкьо от статуса
# если от статуса больше 31 дня:
# 1. откатываем инвойс в статус "Удалён"
# 2. вычитаем сумму счёта из договора, на который счёт.
# 3. если балланс получился отрицательный - лочим все услуги этого договора


#-------------------------------------------------------------------------------
# выхлоп для сотрудников бухгалтерии
$Out = "";
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = "`StatusID` = 'Conditionally'";
#-------------------------------------------------------------------------------
$Invoices = DB_Select('InvoicesOwners',Array('ID','UserID','CreateDate','Summ','(SELECT `Email` FROM `Users` WHERE `Users`.`ID` = `InvoicesOwners`.`UserID`) AS `UserEmail`'),Array('SortOn'=>'UserID', 'IsDesc'=>TRUE, 'Where'=>$Where));
switch(ValueOf($Invoices)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return TRUE;
  case 'array':
    #---------------------------------------------------------------------------
    foreach($Invoices as $Invoice){
      #-------------------------------------------------------------------------
      $Out = $Out . "Неоплаченный счёт на сумму " . $Invoice['Summ'] . " от пользователя " . $Invoice['UserEmail'] . "\n";
      #-------------------------------------------------------------------------
      Debug(SPrintF("[Tasks/GC/NotifyConditionallyInvoice]: Уведомление о условно оплаченном счете #%d.",$Invoice['ID']));
      #----------------------------------TRANSACTION----------------------------
      if(Is_Error(DB_Transaction($TransactionID = UniqID('Tasks/GC/NotifyConditionallyInvoice'))))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $msg = new ConditionallyPayedInvoiceMsg($Invoice, (integer)$Invoice['UserID']);
      $IsSend = NotificationManager::sendMsg($msg);
      #-------------------------------------------------------------------------
      switch(ValueOf($IsSend)){
      case 'true':
        $Event = Array(
			'UserID'	=> $Invoice['UserID'],
			'PriorityID'	=> 'Billing',
			'Text'		=> SPrintF('Уведомление о условно оплаченном счете #%d, неоплачен более %d дней',$Invoice['ID'],$Params['DaysBeforeNotice'])
		      );
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
      break;
      #-------------------------------------------------------------------------
      case 'exception':
        $Event = Array(
			'UserID'	=> $Invoice['UserID'],
			'PriorityID'	=> 'Billing',
			'Text'		=> SPrintF('Уведомление о условно оплаченном счете #%d не доставлено. Не удалось оповестить пользователя ни одним из методов.',$Invoice['ID'])
		      );
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
      break;
      #-------------------------------------------------------------------------
      default:
        return ERROR | @Trigger_Error(500);
      }
      #-------------------------------------------------------------------------
      #-------------------------------------------------------------------------
      if(Is_Error(DB_Commit($TransactionID)))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
    }
    break;
  default:
    return ERROR | @Trigger_Error(101);
}


#Debug(SPrintF("[Tasks/GC/NotifyConditionallyInvoice]: отчёт для бухгалтерии %s",$Out));
#-------------------------------------------------------------------
#-------------------------------------------------------------------
# ищщем сотрудников бухгалтерии
$Entrance = Tree_Entrance('Groups',3200000);
#-------------------------------------------------------------------
switch(ValueOf($Entrance)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#---------------------------------------------------------------
	$String = Implode(',',$Entrance);
	#---------------------------------------------------------------
	$Employers = DB_Select('Users','ID',Array('Where'=>SPrintF('`GroupID` IN (%s)',$String)));
	#---------------------------------------------------------------
	switch(ValueOf($Employers)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# найти всех сотрудников, раз нет сотрудников в бухгалтерии
		$Entrance = Tree_Entrance('Groups',3000000);
		#-------------------------------------------------------------------
		switch(ValueOf($Entrance)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return ERROR | @Trigger_Error(400);
		case 'array':
			#---------------------------------------------------------------
			$String = Implode(',',$Entrance);
			#---------------------------------------------------------------
			$Employers = DB_Select('Users','ID',Array('Where'=>SPrintF('`GroupID` IN (%s)',$String)));
			#---------------------------------------------------------------
			switch(ValueOf($Employers)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'array':
				Debug(SPrintF("[Tasks/GC/NotifyConditionallyInvoice]: найдено %s сотрудников любых отделов",SizeOf($Employers)));
				break;
			default:
				return ERROR | @Trigger_Error(101);
			}
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		break;
	case 'array':
		Debug(SPrintF("[Tasks/GC/NotifyConditionallyInvoice]: найдено %s сотрудников отдела бухгалтерии",SizeOf($Employers)));
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#---------------------------------------------------------
#---------------------------------------------------------
foreach($Employers as $Employer){
	if($Employer['ID'] > 2000 || $Employer['ID'] == 100){
		#---------------------------------------------------------
        $msg = new DispatchMsg(Array('Theme'=>'Список условно оплаченных счетов','Message'=>$Out), (integer)$Employer['ID']);
		$IsSend = NotificationManager::sendMsg($msg);
		#---------------------------------------------------------
		switch(ValueOf($IsSend)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
		case 'true':
			# No more...
			Debug(SPrintF("[Tasks/GC/NotifyConditionallyInvoice]: Сообщение для сотрудника #%s отослано",$Employer['ID']));
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
	}
}
#---------------------------------------------------------
#---------------------------------------------------------
return TRUE;



?>
