<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
$Table = new Tag('TABLE',Array('class'=>'Standard','width'=>'100%','style'=>'background-image:url(SRC:{Images/Grid.png});'));
#-------------------------------------------------------------------------------
# Последовательность вывода
$Sequence = $Template['Sequence'];
# Шапка таблицы
$Columns = $Template['Columns'];
# Источник данных
$Source = $Template['Source'];
# Внешнее оформление
$Appearance = $Template['Appearance'];
#-------------------------------------------------------------------------------
$Data = $Source['Data'];
#-------------------------------------------------------------------------------
if(!Count($Data)){
  #-----------------------------------------------------------------------------
  $Message = ($Source[$Source['Conditions']['Count'] < 1?'Conditions':'Adding']['Message']);
  #-----------------------------------------------------------------------------
  $Table->AddChild(new Tag('TR',new Tag('TD',Array('style'=>'color:#848484;'),$Message)));
  #-----------------------------------------------------------------------------
  return $Table;
}
#-------------------------------------------------------------------------------
if(!Function_Exists('Table_Super_Replace')){
  #-----------------------------------------------------------------------------
  function Table_Super_Replace($Array,$Matches){
    #---------------------------------------------------------------------------
    $Result = Array();
    #---------------------------------------------------------------------------
    if(Is_Array($Array)){
      #-------------------------------------------------------------------------
      foreach(Array_Keys($Array) as $ElementID){
        #-----------------------------------------------------------------------
        $Element = $Array[$ElementID];
        #-----------------------------------------------------------------------
        $Result[$ElementID] = (Is_Array($Element)?Table_Super_Replace($Element,$Matches):Str_Replace(Array_Keys($Matches),Array_Values($Matches),$Element));
      }
    }
    #---------------------------------------------------------------------------
    return $Result;
  }
}
#-------------------------------------------------------------------------------
$Result = new Tag('TD');
#-------------------------------------------------------------------------------
foreach($Data as $Row){
  #-----------------------------------------------------------------------------
  $Replace = Array_ToLine($Row,'%');
  #-----------------------------------------------------------------------------
  $Inner = new Tag('TABLE',Array('class'=>'Widget','cellspacing'=>0,'style'=>'max-width:300px;display:inline-table;'));
  #-----------------------------------------------------------------------------
  if(StrLen($Comp = $Appearance['Row']['Comp'])){
    #---------------------------------------------------------------------------
    $Args = Table_Super_Replace($Appearance['Row']['Args'],$Replace);
    #---------------------------------------------------------------------------
    Array_UnShift($Args,$Comp);
    #---------------------------------------------------------------------------
    $Comp = Call_User_Func_Array('Comp_Load',$Args);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Inner->AddAttribs($Comp);
  }
  #-----------------------------------------------------------------------------
  $Panel = new Tag('TD');
  #-----------------------------------------------------------------------------
  foreach($Sequence as $ColumnID){
    #---------------------------------------------------------------------------
    $Column = $Columns[$ColumnID];
    #---------------------------------------------------------------------------
    if(IsSet($Column['Hidden']))
      continue;
    #---------------------------------------------------------------------------
    $Td = new Tag('TD',Array('class'=>'Standard'));
    #---------------------------------------------------------------------------
    $Value = (IsSet($Row[$ColumnID])?$Row[$ColumnID]:'');
    #---------------------------------------------------------------------------
    if(StrLen($Comp = $Column['Comp'])){
      #-------------------------------------------------------------------------
      $Args = Table_Super_Replace($Column['Args'],$Replace);
      #-------------------------------------------------------------------------
      if(IsSet($Args['Length']))
        $Args['Length'] = 150;
      #-------------------------------------------------------------------------
      Array_UnShift($Args,$Comp);
      #-------------------------------------------------------------------------
      $Value = Call_User_Func_Array('Comp_Load',$Args);
      if(Is_Error($Value))
        return ERROR | @Trigger_Error('[comp/Tables/Super]: не удалось отформатировать значение');
    }
    #---------------------------------------------------------------------------
    #if($Head = $Column['Head'])
    #  $Td->AddChild(new Tag('DIV',Array('align'=>'left','style'=>'font-size:10px;color:#848484;'),$Head));
    #---------------------------------------------------------------------------
    switch(ValueOf($Value)){
      case 'object':
        #-----------------------------------------------------------------------
        if(In_Array($Value->Name,Array('INPUT','BUTTON','IMG'))){
          #---------------------------------------------------------------------
          $Panel->AddChild($Value);
          #---------------------------------------------------------------------
          continue 2;
        }
        #-----------------------------------------------------------------------
        $Td->AddChild($Value);
      break;
      default:
        $Td->AddChild(new Tag('CNAME',$Value));
    }
    #---------------------------------------------------------------------------
    $Inner->AddChild(new Tag('TR',$Td));
  }
  #---------------------------------------------------------------------------
  if(Count($Panel->Childs))
    $Inner->AddChild(new Tag('TR',$Panel),TRUE);
  #-----------------------------------------------------------------------------
  $Result->AddChild($Inner);
}
#-------------------------------------------------------------------------------
$Table->AddChild(new Tag('TR',$Result));
#-------------------------------------------------------------------------------
return $Table;
#-------------------------------------------------------------------------------

?>
