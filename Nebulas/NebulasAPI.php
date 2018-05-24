<?php
/**
 * class NebulasAPI
 *
 * @author aminsire@qq.com
 * @time 2018-05-06
 * @Update time 2018-05-11
 */
//namespace Wallet\Coin\Nebulas;
set_time_limit(600);
class NebulasAPI {
    private $node_url;

    private $address;
    private $passphrase;

    public function __construct($node_url = 'https://mainnet.nebulas.io', $address = null, $passphrase = null){
        //NAS URL
        $this->node_url = $node_url;

        //钱包地址
        $this->address = $address;
        //钱包密码
        $this->passphrase = $passphrase;
        
    }

    //创建新账户
    //passphrase 新帐户密码 不少于9位数,该密码用于加密您的私钥,他不做为产生私钥的种子,您需要该密码 + 您的私钥以解锁您的钱包
    //私钥生成在 节点目录/keydir
    public function NewAccount($passphrase = null){
        if ($passphrase==null) {
            return false;
        }
        $POSTFIELDS = array('passphrase'=>$passphrase);

        $Request = json_decode($this->request($this->node_url.'/v1/admin/account/new',$POSTFIELDS),true);

        if (isset($Request['result']['address'])) {
            $address = $Request['result']['address'];
        }else{
            $address = false;
        }
        /*
        返回
        address 新帐户地址
         */

        return $address;
    }

    //检测地址
    //{"result":{"balance":"0","nonce":"0","type":87}}
    //87代表正常地址，88代表合同地址
    public function isAddress($address = null){
        $POSTFIELDS = array('address'=>$address);

        $Request = json_decode($this->request($this->node_url.'/v1/user/accountstate',$POSTFIELDS),true);

        if (isset($Request['result']['type'])) {
            return true;
        }else{
            return false;
        }
    }

    //返回指定账户余额,格式化后的余额
    public function getBalance($address = null){
        if ($address == null) {
            $address = $this->address;
        }
        $POSTFIELDS = array('address'=>$address);

        $Request = json_decode($this->request($this->node_url.'/v1/user/accountstate',$POSTFIELDS),true);

        if (isset($Request['result']['balance'])) {
            $balance = bcdiv($Request['result']['balance'],'1000000000000000000',18);//$Request['result']['balance']/1000000000000000000;
        }else{
            $balance = false;
        }

        return $balance;
    }

    //返回最新已加载的区块高度
    public function getBlockHeight(){
        $Request = json_decode($this->request($this->node_url.'/v1/user/nebstate'),true);

        if (isset($Request['result']['height'])) {
            $height = $Request['result']['height'];
        }else{
            $height = false;
        }

        return $height;
    }

    //GetNebState
    //返回neb的状态
    public function GetNebState(){
        $Request = json_decode($this->request($this->node_url.'/v1/user/nebstate'),true);

        /*
        返回
        chain_id 块链ID
        tail 当前的neb尾部哈希
        lib 当前的neb lib散列
        height 目前的neb尾巴块高度
        protocol_version 目前的neb协议版本
        synchronized 对等同步状态
        version neb版本
         */

        return $Request;
    }

    //GetGasPrice
    public function GetGasPrice(){
        $Request = json_decode($this->request($this->node_url.'/v1/user/getGasPrice'),true);

        if (isset($Request['result']['gas_price'])) {
            $gas_price = $Request['result']['gas_price'];
        }else{
            $gas_price = false;
        }

        return $gas_price;
    }


    //GetAccountState
    //返回帐户的状态。给定地址的平衡和随机数将被返回
    //参数:address 帐户地址的十六进制字符串。
    //参数:height阻止帐户状态与高度。如果未指定，则使用0作为尾部高度
    public function GetAccountState($address = null){
        $POSTFIELDS = array('address'=>$address);
        $Request = json_decode($this->request($this->node_url.'/v1/user/accountstate',$POSTFIELDS),true);

        /*
        返回
        balance 以1 /（10 ^ 18）nas为单位的当前余额
        nonce 当前交易次数
        type 地址类型，87代表正常地址，88代表合同地址
         */

        return $Request;
    }

    //SendTransactionWithPassphrase
    //transaction 事务参数，它与SendTransaction参数相同
    //passphrase  来自地址密码
    public function SendTransactionWithPassphrase($from = null, $to = null, $value = null ,$passphrase = null){
        if ($from == null or $to == null or $value == null) {
            return false;
        }
        if ($passphrase == null) {
            $passphrase = $this->passphrase;
        }

        //查询账户信息,需要用到交易次数nonce+1
        $GetAccountState = $this->GetAccountState($from);

        $transaction['from']        = $from;
        $transaction['to']          = $to;
        $transaction['value']       = bcmul($value, '1000000000000000000');//number_format($value*1000000000000000000,0,'.', ''); //18位0
        $transaction['nonce']       = $GetAccountState['result']['nonce']+1;
        $transaction['gasPrice']    = $this->GetGasPrice();//'1000000';
        $transaction['gasLimit']    = '20000';

        $POSTFIELDS = array('transaction'=>$transaction,'passphrase'=>$passphrase);

        //file_put_contents('api_log_'.date('Y-m-d').'.log',$value.var_export($POSTFIELDS, true)."\r\n",FILE_APPEND);

        $Request = json_decode($this->request($this->node_url.'/v1/admin/transactionWithPassphrase',$POSTFIELDS),true);

        /*
        返回
        txhash 事务散列
        contract_address 仅为部署合同事务返回
         */
        //记录返回日志方便调试
        //file_put_contents('iotaAPI_log_'.date('Y-m-d').'.log',var_export($pay[0],true)."\r\n",FILE_APPEND);

        return $Request;
    }

    //GetTransactionReceipt
    //https://github.com/nebulasio/wiki/blob/master/rpc.md/#gettransactionreceipt
    //通过tansaction hash获取transactionReceipt信息。如果交易没有提交或只提交并且没有打包在链上，它将会找不到错误
    //hash 事务哈希的十六进制字符串
    public function GetTransactionReceipt($hash){

        $POSTFIELDS = array('hash'=>$hash);

        $Request = json_decode($this->request($this->node_url.'/v1/user/getTransactionReceipt',$POSTFIELDS),true);

        /*
        返回
        hash tx散列的十六进制字符串。
        chainId 交易链ID。
        from 发件人帐户地址的十六进制字符串。
        to 接收方帐户地址的十六进制字符串。
        value 交易价值。
        nonce 事务随机数。
        timestamp 交易时间戳。
        type 交易类型。
        data 交易数据，返回有效载荷数据。
        gas_price 交易气价。
        gas_limit 交易气体限制。
        contract_address 交易合同地址。
        status 交易状态，0失败，1成功，2未决。
        gas_used 交易用气
         */

        return $Request;
    }


    //GetBlockByHeight
    //https://github.com/nebulasio/wiki/blob/master/rpc.md#getblockbyheight
    //height 事务散列的高度
    //full_fill_transaction 如果为true，则返回完整的事务对象，如果为false，则仅返回事务的散列
    public function GetBlockByHeight($height){

        $POSTFIELDS = array('height'=>$height,'full_fill_transaction'=>true);

        $Request = json_decode($this->request($this->node_url.'/v1/user/getBlockByHeight',$POSTFIELDS),true);

        /*
        返回
        hash 块哈希的十六进制字符串。
        parent_hash 块父亲散列的十六进制字符串。
        height 块高度。
        nonce 阻止随机数。
        coinbase 硬币库地址的十六进制字符串。
        timestamp 阻止时间戳。
        chain_id 块链ID。
        state_root 状态根的十六进制字符串。
        txs_root txs根的十六进制字符串。
        events_root 事件根的十六进制字符串。
        consensus_root
        Timestamp 共识状态的时间
        Proposer 当前共识状态的提议者
        DynastyRoot 朝代根的十六进制字符串
        miner 这块的矿工

        is_finality 块是终极的
        transactions 块交易片。
        transaction GetTransactionReceipt响应信息。
         */
        
        return $Request;
    }

    

    /* 请求REST API方法
    *  提交数据,APIURL,POSTDATA
    *  返回ERROR或原始数据
    */
    public function request($url = null,$inputData = null) {
        if (!isset($url)) {
            $url = $this->node_url;
        }

        //$inputData = array("address" => "n1LsRxczDwMzbPkUCWgypa5juEF9eWaReLH");
        //$inputData = '{address: "n1LsRxczDwMzbPkUCWgypa5juEF9eWaReLH"}';
        if (is_array($inputData)) {
            $data_string = json_encode($inputData);
        }else{
            $data_string = $inputData;
        }

        if ($data_string) {
            //POST
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );
        }else{
            //GET
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        
        $result = curl_exec($ch);

        // 检查节点响应是否正常（如果请求错误，则返回“ERROR”字符串）
        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
              case 200:
                // ok - do nothing
                break;
              default:
              //$result = "ERROR".$result;
            }
        } else {
            //$result = "ERROR".$result;
        }

        curl_close($ch);

        return $result;

    }
}

?>
