<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Profile = DB_Select('Profiles',Array('*'),Array('UNIQ','ID'=>100));
#-------------------------------------------------------------------------------
switch(ValueOf($Profile)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('PROFILE_NOT_FOUND','Профиль не найден');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Main')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Новости компании %s',$Profile['Name']));
#-------------------------------------------------------------------------------
$Img = new Tag('IMG',Array('border'=>0,'height'=>32,'width'=>32,'src'=>'SRC:{Images/Icons/Rss.gif}'));
#-------------------------------------------------------------------------------
$A = new Tag('A',Array('class'=>'Image','href'=>SPrintF('http://%s/Rss/News',HOST_ID)),$Img);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',new Tag('TABLE',new Tag('TR',new Tag('TD',$A),new Tag('TD','RSS 2.0'))));
#-------------------------------------------------------------------------------
if(IsSet($GLOBALS['__USER'])){
  #-----------------------------------------------------------------------------
  $Permission = Permission_Check('ClauseEdit',(integer)$GLOBALS['__USER']['ID']);
  #-----------------------------------------------------------------------------
  switch(ValueOf($Permission)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'true':
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Buttons/Standard',Array('onclick'=>SPrintF("window.open('/Administrator/ClauseEdit?GroupID=2&Partition=News\/%s\/%s','ClauseEdit',SPrintF('left=%%u,top=%%u,width=800,height=680,toolbar=0, scrollbars=1, location=0',(screen.width-800)/2,(screen.height-600)/2));",Date('Y-m-d'),Date('G:i:s'))),'Добавить новость','Add.gif');
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp,'Name'=>'Добавить новость'));
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',$Comp);
    break;
    case 'false':
      # No more...
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$Where = "`GroupID` = 2 AND `IsPublish` = 'yes'";
#-------------------------------------------------------------------------------
$Items = DB_Select('Clauses','*',Array('Where'=>$Where,'IsDesc'=>TRUE,'SortOn'=>'PublicDate'));
#-------------------------------------------------------------------------------
switch(ValueOf($Items)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Information','Нет новостей.','Notice');
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Comp);
  break;
  case 'array':
    #---------------------------------------------------------------------------
    foreach($Items as $Item){
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Formats/Date/Standard',$Item['PublicDate']);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',new Tag('H1',Array('id'=>$Item['ID']),new Tag('SPAN',$Item['Title']),new Tag('BR'),new Tag('SPAN',Array('style'=>'font-size:14px;'),$Comp)));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Clauses/Load',$Item['ID'],TRUE);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',$Comp['DOM']);
    }
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
