<?php namespace reg2005\PmPayLaravel\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Wallets extends Model {

    protected $fillable = ['name', 'currency', 'amount', 'account'];

    protected $table = 'reg2005_pm_wallets';

    public function insert_wallet_data($data){

        if ($data) {

            $item = $this->where('name', '=', $data['name'])->first();

            if ($item) {

                $item->fill($data);

                $item->save();

            }else{

                $new = new Wallets();

                $new->fill($data);

                $new->save();

            }


            return TRUE;
        }

        return NULL;

    }

    public function GetGroupBalances($id){
        $items = $this
            ->where('account', '=', $id)
            ->get();

        $res = [
            'USD' => 0,
            'EUR' => 0,
            'GOLD' => 0,
            'BTC' => 0,
        ];

        foreach($items as $item){
            $res[ $item->currency ] = $item->amount + $res[ $item->currency ];
        }

        return $res;
    }

    public function getWalletForPay($amount, $currency, $destination){

        //For tax fee
        $amount_tax = $amount + $amount/2;

        $item = $this
            ->orderBy('last_use', 'DESC')
            ->where('amount', '>', $amount_tax)
            ->where('name', '!=', $destination)
            ->where('currency', '=', $currency)
            ->first();

        if($item) {

            $item->last_use = Carbon::now();

            //$item->save();

        }

        return $item;

    }
}