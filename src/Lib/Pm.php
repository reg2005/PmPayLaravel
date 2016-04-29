<?php
/**
 * Created by PhpStorm.
 * User: evgeniy
 * Date: 24.04.16
 * Time: 14:44
 */
namespace reg2005\PmPayLaravel\Lib;

use reg2005\PayAssetsLaravel\Entities\Log;
use reg2005\PayAssetsLaravel\Entities\Proxy;
use reg2005\PayAssetsLaravel\Entities\Accounts;

use reg2005\PmPayLaravel\Third_party\PerfectMoney;
use reg2005\PmPayLaravel\Entities\History;
use reg2005\PmPayLaravel\Entities\Wallets;

use Exception;
use Carbon\Carbon;

class Pm {

    private $pm;

    private $account;

    public $type = 'PM';

    public function __construct()
    {

        //Log initialization
        $this->log = new Log();

        $this->log->accType = $this->type;

        $this->init();
    }

    public function get_account(){

        $acc = new Accounts;

        $this->account = $acc->get_pm();

        if(!$this->account)
            throw new Exception('Нет свободного PM аккаунта, уменьшите таймаут в модели, либо создайте больше аккаунтов');

        if(!isset($this->account->login) AND !$this->account->login)
            throw new Exception('QiwiAccount login not set OR empy');

        if(!isset($this->account->login) AND !$this->account->login AND strlen($this->account->login) > 3)
            throw new Exception('QiwiAccount password not set OR empy OR not valid');

        return $this->account;
    }

    public function init(){

        $this->get_account();

        $proxy = (new Proxy)->get_proxy();

        $this->pm = new PerfectMoney($this->account->login, $this->account->password, $proxy->ip);
    }

    public function run(){

        $res['wallets'] = $this->GetBalances();

        $res['history'] = $this->history();

        return $res;
    }

    public function GetBalances(){

        $balances = $this->pm->GetBalances();

        foreach($balances as $balance) {

            $balance['account_id'] = $this->account->id;

            (new Wallets)->insert_wallet_data($balance);

        }

        return $balances;
    }

    public function history(){

        $endDate = Carbon::now()->subDays(30)->format('d.m.Y');

        $transactions = $this->pm->GetHistory($endDate);

        //dd($transactions);

        $res = [];

        foreach($transactions as $item){

            if(intval($item['batch']) ) {

                $item['date'] = (new Carbon($item['date'] ))->toDateTimeString();

                $item['income'] = ($item['type'] == 'Income');

                $item['accountId'] = $this->account->id;

                $trans = (new History)->createTransaction($item);

                if($trans)
                    $res[] = $item;
            }
        }

        //Если есть обновления, посчитать вал и зафиксировать последнюю дату истории
        if( count($res) ){

            $turnovers = (new History)->countMounth();

            $lastTransaction = (new History)->getLastTransaction();

            $this->account->fill($turnovers);

            if($lastTransaction)
                $this->account->last_history = $lastTransaction->date();

            $this->account->save();

            return $turnovers;

        }

        return [];
    }

    public function __call($method, $arguments = [])
    {

        if (!method_exists($this->pm, $method) )
        {
            throw new Exception('Undefined method pm.class::' . $method . '() called');
        }

        return call_user_func_array( array($this->pm, $method), $arguments);
    }
}