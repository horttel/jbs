//------------------------------------------------------------------------------
/** @author Alex keda, for www.host-food.ru */
//------------------------------------------------------------------------------
function WhoIs($DomainName, $DomainZone, $DomainSchemeID){
	//------------------------------------------------------------------------------
	var $Form = document.forms['WhoIsForm'];
	//------------------------------------------------------------------------------
	var $HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if(!$HTTP.Resource){
		//------------------------------------------------------------------------------
		//alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer){
		//------------------------------------------------------------------------------
		var $Anchor = $DomainZone.replace(/\./g, "-");
		//------------------------------------------------------------------------------
		var $WhoIsInfo = document.getElementById($Anchor + 'Info');
		//------------------------------------------------------------------------------
		var $Order = document.getElementById($Anchor + 'Order');
		//------------------------------------------------------------------------------
		var $Status = document.getElementById($Anchor);
		//------------------------------------------------------------------------------
		$WhoIsInfo.innerHTML = '';
		//------------------------------------------------------------------------------
		switch($Answer.Status){
		case 'Error':
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color: orange;">Error</span>';
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Fail':
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color: orange;">Fail</span>';
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Exception':
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color: orange;">недоступно</span>';
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Borrowed':
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color:red; cursor:pointer;" onclick="$(\'#' + $Anchor +'Info\').slideToggle();">занят</span>';
			//------------------------------------------------------------------------------
			$Order.innerHTML = '<span style="color:red; cursor:pointer;" onclick="$(\'#' + $Anchor +'Info\').slideToggle();">WhoIs</span>';
			//------------------------------------------------------------------------------
			var $ExpirationDate = $Answer.ExpirationDate;
			//------------------------------------------------------------------------------
			if($ExpirationDate){
				//------------------------------------------------------------------------------
				var $Date = new Date($Answer.ExpirationDate*1000).ToStringDate();
				//------------------------------------------------------------------------------
				$WhoIsInfo.innerHTML += SPrintF('<H2>Дата окончания: %s</H2>',$Date);
				//------------------------------------------------------------------------------
			}
			//------------------------------------------------------------------------------
			var $Ul = '<UL class="Standard">';
			//------------------------------------------------------------------------------
			for(var $i=1;$i<10;$i++){
				//------------------------------------------------------------------------------
				$NsName = $Answer[SPrintF('Ns%uName',$i)];
				//------------------------------------------------------------------------------
				if($NsName)
					$Ul += SPrintF('<LI>%s',$NsName);
				//------------------------------------------------------------------------------
				$NsIP = $Answer[SPrintF('Ns%uIP',$i)];
				//------------------------------------------------------------------------------
				if($NsIP)
					$Ul += SPrintF(' %s',$NsIP);
				//------------------------------------------------------------------------------
				$Ul += '</LI>';
				//------------------------------------------------------------------------------
			}
			//------------------------------------------------------------------------------
			$WhoIsInfo.innerHTML += SPrintF('%s</UL>',$Ul);
			//------------------------------------------------------------------------------
			$WhoIsInfo.innerHTML += SPrintF('<PRE class="Standard" style="overflow:hidden; width:560px;">%s</PRE>',$Answer.Info);
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Free':
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color:green; cursor:pointer;" onclick="ShowWindow(\'/DomainOrder\',{DomainName:\'' + $DomainName +'\',DomainSchemeID:' + $DomainSchemeID + '});">свободен</span>';
			//------------------------------------------------------------------------------
			$Order.innerHTML = '<span style="color:green; cursor:pointer;" onclick="ShowWindow(\'/DomainOrder\',{DomainName:\'' + $DomainName +'\',DomainSchemeID:' + $DomainSchemeID + '});">заказать</span>';
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		default:
			//------------------------------------------------------------------------------
			$Status.innerHTML = '<span style="color: orange;">default</span>';
			//------------------------------------------------------------------------------
		}
		//------------------------------------------------------------------------------
	};
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	if(!$HTTP.Send('/API/WhoIs?DomainName=' + $DomainName + '&DomainZone=' + $DomainZone)){
		//------------------------------------------------------------------------------
		//alert('Не удалось отправить запрос на сервер');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
