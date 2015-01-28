<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['Notes']['User']['Bonuses'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['Percent'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array('`UserID` = @local.__USER_ID','`DaysRemainded` > 0','`Discont` > 0','`ExpirationDate` > UNIX_TIMESTAMP()');
#-------------------------------------------------------------------------------
$Columns = Array(
			'*','(SELECT `NameShort` FROM `Services` WHERE `Services`.`ID` = `BonusesOwners`.`ServiceID`) AS `NameShort`',
			'(SELECT `Code` FROM `Services` WHERE `Services`.`ID` = `BonusesOwners`.`ServiceID`) AS `Code`',
			'(SELECT `ConsiderTypeID` FROM `Services` WHERE `Services`.`ID` = `BonusesOwners`.`ServiceID`) AS `ConsiderTypeID`',
			'(SELECT `Measure` FROM `Services` WHERE `Services`.`ID` = `BonusesOwners`.`ServiceID`) AS `Measure`'

		);
#-------------------------------------------------------------------------------
$Bonuses = DB_Select('BonusesOwners',$Columns,Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Bonuses)){
case 'array':
	#-------------------------------------------------------------------------------
	foreach($Bonuses as $Bonus){
		#-------------------------------------------------------------------------------
		$Percent = $Bonus['Discont'] * 100;
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Notes/User/Bonuses]: Code = %s; Percent = %s',$Bonus['Code'],$Percent));
		#-------------------------------------------------------------------------------
		if($Percent < $Settings['Percent'])
			continue;
		#-------------------------------------------------------------------------------
		if(Is_Null($Bonus['NameShort'])){
			#-------------------------------------------------------------------------------
			$Bonus['NameShort'] = 'Любой сервис';
			$Bonus['Code'] = 'Hosting';
			$Bonus['Measure'] = 'шт.';
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Params = Array('Bonus'=>$Bonus,'Percent'=>$Percent);
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY');
		$NoBody->AddHTML(TemplateReplace('Notes.User.Bonuses',$Params));
		$Result[] = $NoBody;
		UnSet($NoBody);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------


?>
