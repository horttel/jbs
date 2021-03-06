<?php
#-------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
function Upload_Get($Name,$Hash = FALSE){
  /****************************************************************************/
  #$__args_types = Array('string');
  #-----------------------------------------------------------------------------
  $__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
  /****************************************************************************/
  $Args = Args();
  #-----------------------------------------------------------------------------
  $Hash = (IsSet($Args[$Name])?$Args[$Name]:$Hash);
  #-----------------------------------------------------------------------------
  if(!$Hash)
    return new gException('HASH_IS_EMPTY','Хешь файла загрузки пуст');
  #-----------------------------------------------------------------------------
  $Tmp = System_Element('tmp');
  if(Is_Error($Tmp))
    return ERROR | @Trigger_Error('[Upload_Get]: не удалось получить путь до временной директории');
  #-----------------------------------------------------------------------------
  $Uploads = SPrintF('%s/uploads',$Tmp);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Uploads))
    return new gException('HASH_IS_EMPTY','Директория файлов загрузки не создана');
  #-----------------------------------------------------------------------------
  $Path = SPrintF('%s/%s',$Uploads,$Hash);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Path))
    return new gException('FILE_NOT_FOUND','Файл не найден на сервере');
  #-----------------------------------------------------------------------------
  $Data = IO_Read($Path);
  if(Is_Error($Data))
    return ERROR | @Trigger_Error('[Upload_Get]: не удалось прочитать файл');
  #-----------------------------------------------------------------------------
  $Names = IO_Read(SPrintF('%s/names.txt',$Uploads));
  if(Is_Error($Names))
    return ERROR | @Trigger_Error('[Upload_Get]: не удалось прочитать файл имен');
  #-----------------------------------------------------------------------------
  $Names = JSON_Decode($Names,TRUE);
  #-----------------------------------------------------------------------------
  $Name = (IsSet($Names[$Hash])?$Names[$Hash]:'Default');
  #-----------------------------------------------------------------------------
  return Array('Name'=>$Name,'Data'=>$Data);
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2011-12-02 in 13:41 MSK, for JBS-210
function SaveUploadedFile($Table, $ID, $File){
	#-------------------------------------------------------------------------------
        $FilePaths = GetFilePath($Table, $ID);
	#-------------------------------------------------------------------------------
        # создаём директорию
        if(!File_Exists($FilePaths['FileDir']))
                if(!MkDir($FilePaths['FileDir'], 0700, true))
                        return new gException('CANNOT_CREATE_DIRECTORY','Не удалось создать директорию для сохранения файла');
	#-------------------------------------------------------------------------------
        # сохраняем файл
        $fp = FOpen($FilePaths['FilePath'], 'w');
        FWrite($fp, $File);
        FClose($fp);
	#-------------------------------------------------------------------------------
        return TRUE;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2011-12-02 in 14:10 MSK, for JBS-210
function GetUploadedFile($Table, $ID){
	#-------------------------------------------------------------------------------
        $FilePaths = GetFilePath($Table, $ID);
	#-------------------------------------------------------------------------------
        # проверяем наличие файла
        if(File_Exists($FilePaths['FilePath'])){
		#-------------------------------------------------------------------------------
                $Data = IO_Read($FilePaths['FilePath']);
                if(Is_Error($Data))
                        return ERROR | @Trigger_Error('[Upload_Get]: не удалось прочитать файл');
		#-------------------------------------------------------------------------------
                return  Array('Data'=>$Data);
		#-------------------------------------------------------------------------------
        }else{
		#-------------------------------------------------------------------------------
                return FALSE;
		#-------------------------------------------------------------------------------
        }
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2011-12-02 in 15:15 MSK, for JBS-210
function GetFilePath($Table, $ID){
	#-------------------------------------------------------------------------------
        # директория файлов
        $DirPath = SPrintF('%s/hosts/%s/files/%s',SYSTEM_PATH,HOST_ID,$Table);
	#-------------------------------------------------------------------------------
        # путь к файлу
	$SubDirPath = '';
	#-------------------------------------------------------------------------------
	$IDa = $ID;
	#-------------------------------------------------------------------------------
	while ($IDa > 0) {
		#-------------------------------------------------------------------------------
		$SubDirPath = SPrintF('/%s%s',($IDa % 100),$SubDirPath);
		$IDa = Floor($IDa / 100);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
        $FileDirPath = SPrintF('%s%s',$DirPath,$SubDirPath);
	#-------------------------------------------------------------------------------
        $FilePath = SPrintF('%s/%s.bin',$FileDirPath,$ID);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
        return Array('FileDir'=>$FileDirPath, 'FilePath'=>$FilePath);
	#-------------------------------------------------------------------------------
}


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2011-12-02 in 19:58 MSK, for JBS-210
function GetUploadedFileSize($Table, $ID){
	#-------------------------------------------------------------------------------
        $FilePaths = GetFilePath($Table, $ID);
	#-------------------------------------------------------------------------------
        # проверяем наличие файла
        if(File_Exists($FilePaths['FilePath'])){
		#-------------------------------------------------------------------------------
		$st = Stat($FilePaths['FilePath']);
		#-------------------------------------------------------------------------------
		return $st['size'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
        # файла нет - размера нет, возвращаем FALSE
        return FALSE;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2013-02-16 in 16:41 MSK, for JBS-621
function DeleteUploadedFile($Table,$ID){
	#-------------------------------------------------------------------------------
	$Path = GetFilePath($Table, $ID);
	#-------------------------------------------------------------------------------
	if(File_Exists($Path['FilePath'])){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[system/libs/Upload]: delete file: %s',$Path['FilePath']));
		#-------------------------------------------------------------------------------
		if(!UnLink($Path['FilePath']))
			return new gException('CANNOT_DELETE_FILE',SPrintF('Не удалось удалить файл: %s',$File['FilePath']));
		#-------------------------------------------------------------------------------
		@RmDir($Path['FileDir']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# added by lissyara, 2014-01-10 in 12:21 MSK, for JBS-JBS-748
function GetFileMimeType($Table,$ID){
	#-------------------------------------------------------------------------------
	$Path = GetFilePath($Table, $ID);
	#-------------------------------------------------------------------------------
	if(File_Exists($Path['FilePath'])){
		#-------------------------------------------------------------------------------
		$Mime = FInfo_File(FInfo_Open(FILEINFO_MIME_TYPE),$Path['FilePath']);
		Debug(SPrintF('[system/libs/Upload]: get file type: %s (%s)',$Path['FilePath'],$Mime));
		#-------------------------------------------------------------------------------
		//return Mime_Content_Type($Path['FilePath']);
		return $Mime;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	return 'application/octetstream';
	#-------------------------------------------------------------------------------
}



?>
