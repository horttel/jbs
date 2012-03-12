<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/*------------------------------------------------------------------------------
      Задача:
Получить список хостов с учетом их наследования для элемента стиля.
------------------------------------------------------------------------------*/
function Styles_HostsIDs($Element){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Result = Array();
  #-----------------------------------------------------------------------------
  $HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
  #-----------------------------------------------------------------------------
  if(IsSet($_COOKIE['StyleID']))
    Array_UnShift($HostsIDs,$_COOKIE['StyleID']);
  #-----------------------------------------------------------------------------
  foreach($HostsIDs as $HostID){
    #---------------------------------------------------------------------------
    $Path = SPrintF('%s/styles/%s/%s',SYSTEM_PATH,$HostID,$Element);
    #---------------------------------------------------------------------------
    if(File_Exists($Path))
      $Result[] = $HostID;
  }
  #-----------------------------------------------------------------------------
  if(Count($Result) < 1)
    return ERROR | @Trigger_Error(SPrintF('[Styles_HostsIDs]: не удалось найти хосты для элемента (%s)',$Element));
  #-----------------------------------------------------------------------------
  return $Result;

}
/*------------------------------------------------------------------------------
      Задача:
Получить полный путь до элемента.
------------------------------------------------------------------------------*/
function Styles_Element($Element){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $HostsIDs = Styles_HostsIDs($Element);
  if(Is_Error($HostsIDs))
    return ERROR | @Trigger_Error("[Styles_Element]: список хостов содержащих элемент не найдены");
  #-----------------------------------------------------------------------------
  $HostID = Current($HostsIDs);
  #-----------------------------------------------------------------------------
  return SPrintF('%s/styles/%s/%s',SYSTEM_PATH,$HostID,$Element);
}
/*------------------------------------------------------------------------------
      Задача:
Объединить XML-структуры для элемента с учетом наследования хостов.
------------------------------------------------------------------------------*/
function Styles_XML($Element){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $CacheID = SPrintF('Styles_XML[%s]',Md5($Element));
  #-----------------------------------------------------------------------------
  $Result = CacheManager::get($CacheID);
  if($Result)
    return $Result;
  #-----------------------------------------------------------------------------
  $HostsIDs = Styles_HostsIDs($Element);
  if(Is_Error($HostsIDs))
    return ERROR | @Trigger_Error('[Styles_XML]: список хостов содержащих элемент не найдены');
  #-----------------------------------------------------------------------------
  $Result = Array();
  #-----------------------------------------------------------------------------
  foreach(Array_Reverse($HostsIDs) as $HostID){
    #---------------------------------------------------------------------------
    $Path = SPrintF('%s/styles/%s/%s',SYSTEM_PATH,$HostID,$Element);
    #---------------------------------------------------------------------------
    $File = IO_Read($Path);
    if(Is_Error($File))
      return ERROR | @Trigger_Error('[Styles_XML]: не удалось прочитать XML-файл');
    #---------------------------------------------------------------------------
    $XML = String_XML_Parse($File);
    if(Is_Exception($XML))
      return ERROR | @Trigger_Error('[Styles_XML]: не удалось разобрать XML-строку');
    #---------------------------------------------------------------------------
    $Child = Current($XML->Childs);
    #---------------------------------------------------------------------------
    if(IsSet($Child->Attribs['recursive']))
      $Result = Array();
    #---------------------------------------------------------------------------
    $Adding = $XML->ToArray();
    #---------------------------------------------------------------------------
    $Adding = $Adding['XML'];
    #---------------------------------------------------------------------------
    Array_Union($Result,$Adding);
  }
  #-----------------------------------------------------------------------------
  CacheManager::add($CacheID,$Result);
  #-----------------------------------------------------------------------------
  return $Result;
}
/*------------------------------------------------------------------------------
      Задача:
Получить полный адрес элемента.
------------------------------------------------------------------------------*/
function Styles_Url($Element){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $HostsIDs = Styles_HostsIDs($Element);
  if(Is_Error($HostsIDs))
    return ERROR | @Trigger_Error('[Styles_Url]: список хостов содержащих элемент не найдены');
  #-----------------------------------------------------------------------------
  $HostID = Current($HostsIDs);
  #-----------------------------------------------------------------------------
  return SPrintF('%s://%s/styles/%s/%s',@$_SERVER['SERVER_PORT'] != 80?'https':'http',@$_SERVER['HTTP_HOST'],$HostID,$Element);
}
/*------------------------------------------------------------------------------
      Задача:
Дать возможность создания меню.
------------------------------------------------------------------------------*/
function Styles_Menu($Path){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Result = $Chain = Array();
  #-----------------------------------------------------------------------------
  do{
    #---------------------------------------------------------------------------
    $Menu = Styles_XML(SPrintF('Menus/%s.xml',$Path));
    if(Is_Error($Menu))
      return ERROR | @Trigger_Error('[Styles_Menu]: не удалось загрузить файл меню');
    #---------------------------------------------------------------------------
    Array_UnShift($Chain,$Menu);
    #---------------------------------------------------------------------------
    $Path = (IsSet($Menu['RootID'])?$Menu['RootID']:FALSE);
    #---------------------------------------------------------------------------
  }while($Path);
  #-----------------------------------------------------------------------------
  foreach($Chain as $Menu)
    Array_Union($Result,$Menu);
  #-----------------------------------------------------------------------------
  $Items = &$Result['Items'];
  #-----------------------------------------------------------------------------
  if(IsSet($Result['Comp'])){
    #---------------------------------------------------------------------------
    $Comp = Comp_Load($Result['Comp']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(Is_Array($Comp))
      Array_Union($Items,$Comp);
  }
  #-----------------------------------------------------------------------------
  foreach(Array_Keys($Items) as $ItemID){
    #---------------------------------------------------------------------------
    $Item = &$Items[$ItemID];
    #---------------------------------------------------------------------------
    if(!Is_Array($Item))
      continue;
    #---------------------------------------------------------------------------
    if(IsSet($Item['UnActive']))
      UnSet($Items[$ItemID]);
    #---------------------------------------------------------------------------
    $IsActive = FALSE;
    #---------------------------------------------------------------------------
    foreach($Item['Paths'] as $Path){
      #-------------------------------------------------------------------------
      $IsActive = Preg_Match(SPrintF('/%s/',$Path),$GLOBALS['__URI']) || Preg_Match(SPrintF('/%s/',$Path),$_SERVER['REQUEST_URI']);
      #-------------------------------------------------------------------------
      if($IsActive)
        break;
    }
    #---------------------------------------------------------------------------
    $Item['IsActive'] = $IsActive;
  }
  #-----------------------------------------------------------------------------
  KSort($Items);
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
?>