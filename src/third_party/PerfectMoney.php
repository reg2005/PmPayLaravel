<?php

namespace reg2005\PmPayLaravel\Third_party;
use Carbon\Carbon;
use Exception;

class PerfectMoney {

    private $account;
    private $pass;

    public function __construct( $Account, $Pass, $sProxy = null ) {
        $proxy = explode(':', $sProxy);
        $this->account = $Account;
        $this->pass = $Pass;
        $this->sProxy = $sProxy;
    }

    private function curl( $url ) {

        # Инициализация статических переменных :
        static $sReferer = null;

        # Инициализация переменных :
        $oCurl = curl_init( $url );

        # Настройки cURL :
        curl_setopt_array( $oCurl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ) );

        # Если требуется работать через Proxy :
        if( $this->sProxy ) {

            # Разбиваем строку по двоеточию в массив :
            $aExplode = explode( ':', $this->sProxy );

            # Если размер массива > 2 :
            if( count( $aExplode ) > 2 ) {

                # Подключение proxy к curl:
                curl_setopt_array( $oCurl, array(
                    CURLOPT_PROXY => $aExplode[0].':'.$aExplode[1],
                    CURLOPT_HTTPPROXYTUNNEL => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_PROXYTYPE => $aExplode[2] == 'socks5' ? CURLPROXY_SOCKS5 : ($aExplode[2] == 'socks4' ? CURLPROXY_SOCKS4 : CURLPROXY_HTTP)
                ) );

                # Если размер массива больше 4 :
                if( count( $aExplode ) > 4 ) {

                    # Авторизация в proxy к curl:
                    curl_setopt( $oCurl, CURLOPT_PROXYUSERPWD, $aExplode[3].':'.$aExplode[4] );
                }
            }
        }

        # Получение ответа :
        $this->sResponse = curl_exec( $oCurl );

        # Если произошла ошибка :
        if( curl_errno( $oCurl ) )
            throw new Exception( curl_errno( $oCurl ).' - '.curl_error( $oCurl ) );

        # Закрываем соединение :
        curl_close( $oCurl );

        return $this->sResponse;
    }

    # Метод : перевод средств на другой Qiwi.кошелек.
    # Принимает : кошелек, сумма, валюта, примечание.
    # Возвращает : уникальный номер транзакции.
    public function SendMoney( $from_account = 'U10267339', $to_account = 'U9805481', $dAmount = '0.01', $sComment = 'Спасибо!', $tid = '12345' ) {
        /*

		This script demonstrates transfer proccess between two
		PerfectMoney accounts using PerfectMoney API interface.

		*/

        if($dAmount < 0.2)
            throw new Exception('Small sum: '.$dAmount);

        $dAmount = (string) number_format($dAmount, 2);

        // trying to open URL to process PerfectMoney Spend request
        $out = $this->curl(
            'https://perfectmoney.is/acct/confirm.asp?AccountID='.$this->account.'&PassPhrase='.$this->pass.'&Payer_Account='.$from_account.'&Payee_Account='.$to_account.'&Memo='.$sComment.'&Amount='.$dAmount.'&PAY_IN=1&PAYMENT_ID='.$tid);

        // searching for hidden fields
        if(!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $out, $result, PREG_SET_ORDER)){
            throw new Exception('Ошибка PM отправки платежа');
        }

        $ar="";
        foreach($result as $item){
            $key=$item[1];
            $ar[$key]=$item[2];
        }

        return $ar;

    }

    # Метод : получение информации из истории транзакций.
    # Принимает : дату конца и определяет дату начала (отнимая 29 дней).
    # Обязательно! Разница между датами не более 30 дней!
    public function GetHistory($sStartDate = '10.1.2016',  $sFinishDate='17.1.2016' ) {

        if(!$sStartDate)
            $sStartDate = (new Carbon($sFinishDate) )->subDays(29)->format('d.m.Y');

        list($sd, $sm, $sy) = explode('.', $sStartDate);
        list($ed, $em, $ey) = explode('.', $sFinishDate);

        $sd = intval( $sd );
        $ed = intval( $ed );
        $sm = intval( $sm );
        $em = intval( $em );

        //die('https://perfectmoney.is/acct/historycsv.asp?startmonth='.$sm.'&startday='.$sd.'&startyear='.$sy.'&endmonth='.$em.'&endday='.$ed.'&endyear='.$ey.'&AccountID='.$this->account.'&PassPhrase='.$this->pass);
        /*

         This script demonstrates querying account history
         using PerfectMoney API interface.

         */

        // trying to open URL
        $f=$this->curl('https://perfectmoney.is/acct/historycsv.asp?startmonth='.$sm.'&startday='.$sd.'&startyear='.$sy.'&endmonth='.$em.'&endday='.$ed.'&endyear='.$ey.'&AccountID='.$this->account.'&PassPhrase='.$this->pass);
        //die(fgets($f));
        if(!$f){
           // echo 'error openning url';
        }

        // getting data to array (line per item)
        $lines=array();
        $lines = explode(PHP_EOL, $f);


        foreach($lines as $k=>$line)
            $lines[$k] = trim($line);



        // try parsing data to array
        if(stripos($lines[0], 'Time,Type,Batch,') === FALSE){
            return [];

        }else{

            // do parsing
            $ar=array();
            $n=count($lines);
            for($i=1; $i<$n; $i++){

                $item=explode(",", $lines[$i], 9);
                if(count($item)!=9) continue; // line is invalid - pass to next one
                $item_named['date']=$item[0];
                $item_named['time']=strtotime($item[0]);
                $item_named['type']=$item[1];
                $item_named['batch']=intval($item[2]);
                $item_named['currency']=$item[3];
                $item_named['amount']=$item[4];
                $item_named['fee']=$item[5];
                if($item_named['type'] == 'Income'){
                    $item_named['to']=$item[6];
                    $item_named['from']=$item[7];

                }else{
                    $item_named['from']=$item[6];
                    $item_named['to']=$item[7];
                }
                $item_named['memo']=$item[8];
                array_push($ar, $item_named);

            }

        }

        return $ar;

    }

    # Метод : получение информации о балансах.
    # Возвращает : ассоциативный массив, ключ - валюта, значение - баланс.
    public function GetBalances() {

        //$f=fopen('https://perfectmoney.is/acct/balance.asp?AccountID='.$this->account.'&PassPhrase='.$this->pass, 'rb');
        $out = $this->curl('https://perfectmoney.is/acct/balance.asp?AccountID='.$this->account.'&PassPhrase='.$this->pass);
        if($out===false){
            throw new Exception('error openning url');
        }

        // searching for hidden fields
        if(!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $out, $result, PREG_SET_ORDER)){
            throw new Exception('Ivalid output');
        }

        // putting data to array
        $ar="";
        foreach($result as $item){
            $ar[] = [
                'name' => $item[1],
                'currency' => $this->get_currency_from_id($item[1]),
                'amount' => $item[2]
            ];
        }

        return $ar;
    }

    public function get_currency_from_id($id = ''){

        $array = [
            'U' => 'USD',
            'E' => 'EUR',
            'B' => 'BTC',
            'G' => 'GOLD',
        ];

        $FL = $id[0];

        if(strlen($id) > 0 AND array_key_exists($FL, $array)){
            return $array[$FL];
            $array['E'];
        }


    }


}