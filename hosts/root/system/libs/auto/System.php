<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/*------------------------------------------------------------------------------
Задача:
Сделать возможность загрузки системных частей (библиотек, классов, модулей).
------------------------------------------------------------------------------*/
function System_Load($Name){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Paths = Func_Get_Args();
  if(!Count($Paths))
    return ERROR | @Trigger_Error('[System_Load]: не передан ни один путь к системному компоненту');
  #-----------------------------------------------------------------------------
  $Loaded = &Link_Get('System','array');
  #-----------------------------------------------------------------------------
  foreach($Paths as $Path){
    #---------------------------------------------------------------------------
    if(System_IsLoaded($Path))
      continue;
    #---------------------------------------------------------------------------
    $Loaded[] = $Path;

    #---------------------------------------------------------------------------
    $Path = System_Element(SPrintF('system/%s',$Path));
    if(Is_Error($Path))
      return ERROR | @Trigger_Error('[System_Load]: включение не найдено');
    #---------------------------------------------------------------------------
    if(Is_Error(Load($Path)))
      return ERROR | @Trigger_Error('[System_Load]: не удалось загрузить включение');
Debug($Path);
    #---------------------------------------------------------------------------
    Debug(SPrintF('[System_Load]: компонент системы (%s) был загружен',$Path));
  }
}
/*------------------------------------------------------------------------------
      Задача:
Проверить загружалась ли данная системная часть.
------------------------------------------------------------------------------*/
function System_IsLoaded($Path){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Loaded = (array)Link_Get('System');
  #-----------------------------------------------------------------------------
  return In_Array($Path,$Loaded);
}
/*------------------------------------------------------------------------------
      Задача:
Получить список виртуальных хостов в порядке их наследования для элемента.
------------------------------------------------------------------------------*/
function System_HostsIDs($Element,$HostsIDs = Array()){
  /****************************************************************************/
  $__args_types = Array('string','array,string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  if(!Is_Array($HostsIDs))
    $HostsIDs = Array($HostsIDs);
  #-----------------------------------------------------------------------------
  if(!Count($HostsIDs))
    $HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
  #-----------------------------------------------------------------------------
  $CacheID = SPrintF('System_HostsIDs[%s]',Md5(SPrintF('%s-%s',Implode(':',$HostsIDs),$Element)));
  #-----------------------------------------------------------------------------
  $Result = CacheManager::get($CacheID);
  if($Result)
    return $Result;
  #-----------------------------------------------------------------------------
  $Result = Array();
  #-----------------------------------------------------------------------------
  foreach($HostsIDs as $HostID){
    #---------------------------------------------------------------------------
    $Path = SPrintF('%s/hosts/%s/%s',SYSTEM_PATH,$HostID,$Element);
    #---------------------------------------------------------------------------
    if(File_Exists($Path))
      $Result[] = $HostID;
  }
  #-----------------------------------------------------------------------------
  if(!Count($Result))
    return ERROR | @Trigger_Error("[System_HostsIDs]: не удалось найти хосты для элемента ($Element)");
  #-----------------------------------------------------------------------------
  CacheManager::add($CacheID,$Result);
  #-----------------------------------------------------------------------------
  return $Result;

}
/*------------------------------------------------------------------------------
      Задача:
Получить полный путь для элемента с учетом наследования виртуальных хостов.
------------------------------------------------------------------------------*/
function System_Element($Element){
  /****************************************************************************/
  $__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $HostsIDs = System_HostsIDs($Element);
  if(Is_Error($HostsIDs))
    return ERROR | @Trigger_Error("[System_Element]: список хостов содержащих элемент не найдены");
  #-----------------------------------------------------------------------------
  if (is_array($HostsIDs)) {
    Reset($HostsIDs);
  }

  $HostID = Current($HostsIDs);
  #-----------------------------------------------------------------------------
  return SPrintF('%s/hosts/%s/%s',SYSTEM_PATH,$HostID,$Element);
}
/*------------------------------------------------------------------------------
      Задача:
Объединить XML-структуры для элемента с учетом наследования хостов.
------------------------------------------------------------------------------*/
function System_XML($Element,$HostsIDs = Array()){
  /****************************************************************************/
  $__args_types = Array('string','array,string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $HostsIDs = System_HostsIDs($Element,$HostsIDs);
  if(Is_Error($HostsIDs))
    return ERROR | @Trigger_Error('[System_XML]: список хостов содержащих элемент не найдены');
  #-----------------------------------------------------------------------------
  $IsUseCache = (boolean)(Preg_Match('/^tmp\//',$Element) < 1);
  #-----------------------------------------------------------------------------
  if($IsUseCache){
    #---------------------------------------------------------------------------
    $CacheID = SPrintF('System_XML[%s]',Md5(SPrintF('%s-%s',Implode(':',$HostsIDs),$Element)));
    #---------------------------------------------------------------------------
    $Result = CacheManager::get($CacheID);
    if($Result)
      return $Result;
  }
  #-----------------------------------------------------------------------------
  $Result = Array();
  #-----------------------------------------------------------------------------
  foreach(Array_Reverse($HostsIDs) as $HostID){
    #---------------------------------------------------------------------------
    $Path = SPrintF('%s/hosts/%s/%s',SYSTEM_PATH,$HostID,$Element);
    #---------------------------------------------------------------------------
    $File = IO_Read($Path);
    if(Is_Error($File))
      return ERROR | @Trigger_Error('[System_XML]: не удалось прочитать XML-файл');
    #---------------------------------------------------------------------------
    $XML = String_XML_Parse($File,FALSE);
    if(Is_Exception($XML))
      return ERROR | @Trigger_Error('[System_XML]: не удалось разобрать XML-строку');
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
  if($IsUseCache)
    CacheManager::add($CacheID,$Result);
  #-----------------------------------------------------------------------------
  return $Result;
}
#-------------------------------------------------------------------------------
function System_Read($Element){
  #-----------------------------------------------------------------------------
  $Path = System_Element($Element);
  if(Is_Error($Path))
    return ERROR | @Trigger_Error('[System_Read]: не удалось найти элемент');
  #-----------------------------------------------------------------------------
  $Result = IO_Read($Path);
  if(Is_Error($Result))
    return ERROR | @Trigger_Error('[System_Read]: не удалось прочитать данные');
  #-----------------------------------------------------------------------------
  return $Result;
}
//****************************************************************************//
?>
