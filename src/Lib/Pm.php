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
use reg2005\PayAssetsLaravel\Entities\SchedulePays;

use Exception;
use Carbon\Carbon;

class Pm {

    private $pm;

    private $account;

    private $proxy = NULL;

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

        $this->proxy = (new Proxy)->get_proxy();

        $this->pm = new PerfectMoney($this->account->login, $this->account->password, $this->proxy);
    }

    public function run(){



        $res['wallets'] = $this->GetBalances();

        $res['history'] = $this->history();

        $res['payout'] = $this->payout();


        return $res;
    }

    public function payout(){

        $schedules = (new SchedulePays)->getUnpayedsScore('PM');

        $res = [];

        foreach($schedules as $schedule){

            $payWallet = (new Wallets)->getWalletForPay(
                $schedule->amount,
                $schedule->currency,
                $schedule->destination
            );

            if (!$payWallet){

                $this->log->insert('Wallet for pay not found, Check enough money');

                return NULL;

            }

            (new SchedulePays)->setTimeout($schedule);

            $payResult = $this->payment(
                $schedule->destination,
                $schedule->amount,
                $payWallet->name,
                $payWallet->account,
                $schedule->comment
            );

            $pay = (new SchedulePays)->postPayInfoUpdate($schedule, $payResult['transaction'], $payResult['account'], $payWallet->id);

            $res[] = [$payResult, $payWallet];

        }

    return $res;


    }

    function payment($destination ='', $amount = '', $from = '', $account_id, $comment){


        $acc = new Accounts;

        $account = (new Accounts)->getById($account_id);

        $res = [
            'transaction' => NULL,
            'account' => ($account) ? $account->id : NULL,
        ];

        if(!$res)
            return NULL;

        $proxy = (new Proxy)->get_proxy();



        try{

            $pm = new PerfectMoney($account->login, $account->password, $proxy);

            $transaction = $pm->SendMoney($from, $destination, $amount, $comment);

            //$transaction['PAYMENT_BATCH_NUM'] = 1;

            if( isset($transaction['PAYMENT_BATCH_NUM']) ){
                $res['transaction'] = $transaction['PAYMENT_BATCH_NUM'];

                $message = 'Pay success! transaction: '.$res['transaction'].', amount: '.$amount.' . client '.$destination.' wallet: '.$from.', account: '.$accoun->login;

                $this->log->insert($message);
            }

            if( isset($transaction['ERROR']) ){

                $message = $transaction['ERROR'];

                $this->log->insert($message);
            }



        }catch(Exception $e){

            $message = 'Pay FAIL! amount: '.$amount.' . client '.$destination.' wallet: '.$from.', account: '.$account->login;

            $this->log->insert($message);

            $this->log->insert( $e->getMessage()) ;

        }

        return $res;

    }

    public function GetBalances(){

        $balances = $this->pm->GetBalances();

        foreach($balances as $balance) {

            $balance['account'] = $this->account->id;

            (new Wallets)->insert_wallet_data($balance);

            $balances = (new Wallets)->GetGroupBalances($this->account->id);

            (new Accounts)->updateDataById($balances, $this->account->id);

        }

        return $balances;
    }

    public function history(){

        $endDate = Carbon::now()->addDay(1)->format('d.m.Y');

        $startDate = (new History)->getLastTransaction($this->account->id);

        $startDate = ($startDate) ? ( new Carbon( $startDate->date ) )->format('d.m.Y') : NULL;

        $transactions = $this->pm->GetHistory($startDate, $endDate);

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
                $this->account->last_history = $lastTransaction->date;

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