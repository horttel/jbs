<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('SystemID','InvoiceID','Summ');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Invoices']['PaymentSystems']['Moneybookers'];
#-------------------------------------------------------------------------------
$Send = $Settings['Send'];
#-------------------------------------------------------------------------------
$Send['transaction_id'] = $InvoiceID;
#-------------------------------------------------------------------------------
$Send['amount'] = Round($Summ/$Settings['Course'],2);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Invoice/Number',$InvoiceID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Send['detail1_text'] = SPrintF('%s, %s (%s)',$Comp,Translit($__USER['Name']),$__USER['Email']);
#-------------------------------------------------------------------------------
$Protocol = (@$_SERVER['SERVER_PORT'] != 80?'https':'http');
#-------------------------------------------------------------------------------
$Send['return_url'] = SPrintF('%s://%s/Invoices',$Protocol,HOST_ID);
$Send['cancel_url'] = SPrintF('%s://%s/Invoices?Error=yes',$Protocol,HOST_ID);
$Send['status_url'] = SPrintF('%s://%s/Merchant/Moneybookers',$Protocol,HOST_ID);
#-------------------------------------------------------------------------------
return $Send;
#-------------------------------------------------------------------------------

?>
