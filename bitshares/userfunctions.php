<?php

		
require 'config.php';
require ROOT.'../config/config.inc.php';

require ROOT.'../modules/bitshares/bitshares.php';

function getOpenOrdersUser()
{

	$openOrderList = array();
	// find open orders status id (not paid)
    $Bitshares = new Bitshares();
	$orders = $Bitshares->getOpenOrders();
	foreach ($orders as $order) {
		$newOrder = array();
		$total = $order['total'];
		$total = number_format((float)$total,2);
		$newOrder['total'] = $total;
		$newOrder['currency_code'] = $currency->iso_code;
		$newOrder['order_id'] = $order['cart_id'];
		$newOrder['date_added'] = 0;
		array_push($openOrderList,$newOrder);
	}
	return $openOrderList;
}
function isOrderCompleteUser($memo, $order_id)
{

  // find orders with id order_id and status id (completed)
    $Bitshares = new Bitshares();
	$order = $Bitshares->getCompleteOrder($order_id);
	if($order != 0)
	{
			$total = $order['total'];
			$total = number_format((float)$total,2);
			$currency = Currency::getCurrencyInstance((int)$order['id_currency']);
			$asset = btsCurrencyToAsset($currency->iso_code);
			$id = $order['cart_id'];
			$hash =  btsCreateEHASH(accountName,$id, $total, $asset, hashSalt);
			$memoSanity = btsCreateMemo($hash);		
			if($memoSanity === $memo)
			{	
				return TRUE;
			}
	}
	return FALSE;	
}
function doesOrderExistUser($memo, $order_id)
{

  // find orders with id order_id and status id (not paid)
    $Bitshares = new Bitshares();
	$order = $Bitshares->getOpenOrder($order_id);
	
	if($order != 0)
	{
			$total = $order['total'];
			$total = number_format((float)$total,2);
			$currency = Currency::getCurrencyInstance((int)$order['id_currency']);
			$asset = btsCurrencyToAsset($currency->iso_code);
			$id = $order['cart_id'];
			$hash =  btsCreateEHASH(accountName,$id, $total, $asset, hashSalt);
			$memoSanity = btsCreateMemo($hash);
			if($memoSanity === $memo)
			{	
				$myorder = array();
				$myorder['order_id'] = $id;
				$myorder['total'] = $total;
				$myorder['asset'] = $asset;
				$myorder['memo'] = $memo;	
				return $myorder;
			}
	}
	return FALSE;
}

function completeOrderUser($order)
{
	
	$response = array();
	$Bitshares = new Bitshares();
    if (empty(Context::getContext()->link))
      Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent 	
    $porder = new Order((int)Order::getOrderByCartId($order['order_id']));
    if($porder == 0)
    {
		$response['error'] = 'Could not find this order in the system, please review the Order ID and Memo';
    }
    $new_history = new OrderHistory();
    $new_history->id_order = (int)$porder->id;
    $order_status = (int)Configuration::get('PS_OS_PAYMENT');
    $new_history->changeIdOrderState((int)$order_status, $porder, true);
    $new_history->addWithemail(true);

	$Bitshares->updateOrder($order['order_id'], $order['trx_id'], Configuration::get('PS_OS_PAYMENT'));
	$url = $Bitshares->getReturnURL($order['order_id']);
	if($url === "")
	{
		$response['error'] = 'Could not find this order in the system, please review the Order ID and Memo';
	}
	else
	{
		$response['url'] = $Bitshares->getReturnURL($order['order_id']);
	} 	 
	return $response;
}
function cancelOrderUser($order)
{
	
	$response = array();
	$Bitshares = new Bitshares();
    if (empty(Context::getContext()->link))
      Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent 	
    $porder = new Order((int)Order::getOrderByCartId($order['order_id']));
    
    if($porder == 0)
    {
		$response['error'] = 'Could not find this order in the system, please review the Order ID and Memo';
    }    
    $new_history = new OrderHistory();
    $new_history->id_order = (int)$porder->id;
    $order_status = (int)Configuration::get('PS_OS_CANCELED');
    $new_history->changeIdOrderState((int)$order_status, $porder, true);
    $new_history->addWithemail(true);

	$Bitshares->updateOrder($order['order_id'], 'Cancelled', Configuration::get('PS_OS_CANCELED'));
	$url = $Bitshares->getReturnURL($order['order_id']);
	if($url === "")
	{
		$response['error'] = 'Could not find this order in the system, please review the Order ID and Memo';
	}
	else
	{
		$response['url'] = $Bitshares->getReturnURL($order['order_id']);
	} 
	 
	return $response;
}
function cronJobUser()
{
	return 'Success!';
}
function createOrderUser()
{

	$order_id    = $_REQUEST['order_id'];
	$asset = btsCurrencyToAsset($_REQUEST['code']);
	$total = number_format((float)$_REQUEST['total'],2);
	$hash =  btsCreateEHASH(accountName,$order_id, $total, $asset, hashSalt);
	$memo = btsCreateMemo($hash);
	$ret = array(
		'accountName'     => accountName,
		'order_id'     => $order_id,
		'memo'     => $memo
	);
	$Bitshares = new Bitshares();
	$Bitshares->createOrder($order_id);
	return $ret;	
}

?>