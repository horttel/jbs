<?php


#-------------------------------------------------------------------------------
/** @author Лапшин С.М. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder','StartDate','FinishDate','Details');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Artichow.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Result = Array('Title'=>'Распределение заказов на ExtraIP по серверам');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('P','Данный вид статистики содержит информацию о количестве заказов закрепленных за каждым сервером.'));
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$ExtraIPs = DB_Select('ExtraIPs',Array('ID','Address','(SELECT COUNT(*) FROM `ExtraIPOrders` WHERE `ExtraIPOrders`.`ServerID` = `ExtraIPs`.`ID`) as `Count`'));
#-------------------------------------------------------------------------------
switch(ValueOf($ExtraIPs)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return $Result;
  case 'array':
    #---------------------------------------------------------------------------
    $Params = $Labels = Array();
    #---------------------------------------------------------------------------
    $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Адрес сервера'),new Tag('TD',Array('class'=>'Head'),'Кол-во заказов')));
    #---------------------------------------------------------------------------
    foreach($ExtraIPs as $ExtraIPServer){
      #-------------------------------------------------------------------------
      $Params[] = $ExtraIPServer['Count'];
      $Labels[] = $ExtraIPServer['Address'];
      #-------------------------------------------------------------------------
      $Table[] = Array($ExtraIPServer['Address'],(integer)$ExtraIPServer['Count']);
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Extended',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    if(Count($Params) > 1){
      #-------------------------------------------------------------------------
      $File = SPrintF('%s.jpg',Md5('ExtraIPs1'));
      #-------------------------------------------------------------------------
      Artichow_Pie('Распределение заказов по серверам',SPrintF('%s/%s',$Folder,$File),$Params,$Labels);
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('BR'));
      #-------------------------------------------------------------------------
      $NoBody->AddChild(new Tag('IMG',Array('src'=>$File)));
    }
    #---------------------------------------------------------------------------
    $Result['DOM'] = $NoBody;
    #---------------------------------------------------------------------------
    return $Result;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
