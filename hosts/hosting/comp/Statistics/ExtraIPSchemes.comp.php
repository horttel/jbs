<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsCreate','Folder','StartDate','FinishDate','Details');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array('Title'=>'Распределение заказов на  IP адреса по тарифам');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
if(!$IsCreate)
  return $Result;
#-------------------------------------------------------------------------------
$ExtraIPOrders = DB_Select('ExtraIPSchemes',Array('Name','(SELECT COUNT(*) FROM `ExtraIPOrders` WHERE `SchemeID` = `ExtraIPSchemes`.`ID` AND `StatusID`="Active") as `Count`'),Array('SortOn'=>'SortID'));
#-------------------------------------------------------------------------------
switch(ValueOf($ExtraIPOrders)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return $Result;
  case 'array':
    #---------------------------------------------------------------------------
    $NoBody->AddChild(new Tag('P','Данный вид статистики дает детальную информацию о количестве активных заказов на каждом из тарифов.'));
    #---------------------------------------------------------------------------
    $Table = Array(Array(new Tag('TD',Array('class'=>'Head'),'Наименование тарифа'),new Tag('TD',Array('class'=>'Head'),'Кол-во заказов')));
    #---------------------------------------------------------------------------
    $sGroupName = UniqID();
    #---------------------------------------------------------------------------
    foreach($ExtraIPOrders as $ExtraIPOrder){
      #-------------------------------------------------------------------------
#      if($sGroupName != $ExtraIPOrder['sGroupName']){
#        #-----------------------------------------------------------------------
#        $sGroupName = $ExtraIPOrder['sGroupName'];
#        #-----------------------------------------------------------------------
#        $Table[] = $sGroupName;
#      }
      #-------------------------------------------------------------------------
      $Table[] = Array($ExtraIPOrder['Name'],(integer)$ExtraIPOrder['Count']);
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Extended',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
    #---------------------------------------------------------------------------
    $Result['DOM'] = $NoBody;
    #---------------------------------------------------------------------------
    return $Result;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
