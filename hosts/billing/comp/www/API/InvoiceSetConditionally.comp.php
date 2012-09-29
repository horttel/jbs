<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$InvoicesIDs  = (array) @$Args['RowsIDs'];
#-------------------------------------------------------------------------------
if(Count($InvoicesIDs) < 1)
  return new gException('ACCOUNTS_NOT_SELECTED','Счёт для условной оплаты не выбран');
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$GLOBALS['__USER']['IsAdmin'])
  return new gException('TMP_ONLY_FOR_ADMINs','Данная возможность находится в разработке');
#-------------------------------------------------------------------------------
if(SizeOf($InvoicesIDs) > 1)
  return new gException('CONDITIONALLY_PAYED_MORE_ONE_INVOICE','Условно зачислить можно лишь один счёт');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём счёт
$Invoice = DB_Select('InvoicesOwners',Array('ID','UserID','StatusID','IsPosted','Summ'),Array('UNIQ','ID'=>$InvoicesIDs[0]));
#-------------------------------------------------------------------------------
switch(ValueOf($Invoice)){
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
# проверяем, имеет ли юзер отношение к этому счёту
$IsPermission = Permission_Check('InvoiceEdit',(integer)$GLOBALS['__USER'],(integer)$Invoice['UserID']);
#-------------------------------------------------------------------------
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
# проверяем статус счёта - только неоплаченные или отменённые можно проводить условно
#-------------------------------------------------------------------------------
$Statuses = Array('Waiting','Rejected','Deleted');
if(!In_Array($Invoice['StatusID'],$Statuses))
  return new gException('BAD_INVOICE_STATUS','Провести условно можно только счета ожидающие оплаты');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем нету ли у юзера условных счетов
$Count = DB_Count('InvoicesOwners',Array('Where'=>SPrintF("`StatusID` = 'Conditionally' AND `UserID` = %u",$Invoice['UserID'])));
if($Count)
  return new gException('TOO_MANY_CONDITIONALLY_INVOICES','Вначале, оплатите предыдущий условно зачисленный счёт');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем не отрицательный ли у него балланс, на каком-либо договоре
$Count = DB_Count('ContractsOwners',Array('Where'=>SPrintF("`Balance` < 0 AND `UserID` = %u",$Invoice['UserID'])));
if($Count)
  return new gException('NEGATIVE_CONTRACT_BALANCE','У вас есть задолженность по одному из договоров. До её погашения, вы не сможете пользоваться нашими услугами в кредит');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем что он наоплачивал на ту сумму, начиная с которой можно проводить счета условно
$PayedSumm = DB_Select('InvoicesOwners',Array('SUM(Summ) AS `Summ`'),Array('UNIQ','Where'=>SPrintF("`StatusID` = 'Payed' AND `UserID` = %u",$Invoice['UserID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($PayedSumm)){
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
if($PayedSumm['Summ'] < $GLOBALS['__USER']['LayPayThreshold'])
  return new gException('TOO_SMALL_SUMM_PAYED_INVOICES',SPrintF('Сумма ваших оплаченных счетов (%01.2f) недостаточна для проведения счетов условно. Данная возможность будет доступна по достижении суммы оплат равной (%01.2f)',$PayedSumm['Summ'],$GLOBALS['__USER']['LayPayThreshold']));
# проверяем что сумма счёта не превышает сумму на которую юзер может проводить счета условно
if($Invoice['Summ'] > $GLOBALS['__USER']['LayPayMaxSumm'])
  return new gException('TOO_BIG_INVOICE_SUMM',SPrintF('Сумма счёта (%01.2f) слишком велика. Максимальная сумма которая может быть зачислена условно, равна (%01.2f)',$Invoice['Summ'],$GLOBALS['__USER']['LayPayMaxSumm']));
# проверяем что именно оплачивается этим счётом - доступны не все услуги

# проводим счёт условно

return new gException('END_OF_FILE','Тестирование прошло успешно');

?>
