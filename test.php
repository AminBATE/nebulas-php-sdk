<?php

set_time_limit(180);
include('./Nebulas/NebulasAPI.php');



$node_url = 'https://testnet.nebulas.io';


$address 	= 'n1LsRxczDwMzbPkUCWgypa5juEF9eWaReLH';
$passphrase = 'aaaaaaaaa';
$client = new NebulasAPI($node_url,$address,$passphrase);



// $postdata = array('address'=>'n1bdcZ5iYNXMX67EzrBvUTW4fc2iSx3cVbM');
// print_r($client->request($node_url.'/v1/user/accountstate',$postdata));



$type = $_GET['type'];

switch ($type) {
	//返回钱包信息
	case 'getInfo':
		print_r($client->getInfo());
		break;


	//检测地址
	case 'isAddress':
		print_r($client->isAddress('n1bdcZ5iYNXMX67EzrBvUTW4fc2iSx3cVbM'));
		break;


	//查询单笔交易记录
	case 'GetTransactionReceipt':
		$Transaction = $client->GetTransactionReceipt('c21df527bf23120e50f764163fc2045c42fe2d0fe284c33edae31eb2464159c2');
		print_r($Transaction);
		break;


	//查询区块信息
	case 'GetBlockByHeight':
		for ($x=220380; $x<=220428; $x++) {
			$GetBlockByHeight = $client->GetBlockByHeight($x);
			$transactions = $GetBlockByHeight['result'];

			// if (!isset($transactions[0]['to']) or $transactions[0]['to']!=$address) {
			// 	//转入地址不存在或转入地址不是本站转入地址就跳过
			// 	continue;
			// }

			print_r($transactions);
		} 
		


		// $GetBlockByHeight = $client->GetBlockByHeight(193639);
		// print_r($GetBlockByHeight);
		break;


	//发起转账
	case 'pay':
		$to = 'n1LsRxczDwMzbPkUCWgypa5juEF9eWaReLH';
		$Payment = $client->SendTransactionWithPassphrase($to,1);
		print_r($Payment);
		break;


	//返回节点信息
	case 'GetNebState':
		print_r($client->GetNebState());
		break;
	
	default:
		# code...
		break;
}


?>