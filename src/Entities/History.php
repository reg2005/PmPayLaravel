<?php namespace reg2005\PmPayLaravel\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use reg2005\PayAssetsLaravel\Entities\Exchange;

class History extends Model {

    protected $fillable = ['date', 'accountId', 'time', 'type', 'incoming', 'batch', 'currency', 'amount', 'fee', 'to', 'from', 'memo'];

    protected $table = 'reg2005_pm_history';

    public function getLastTransaction($id = NULL){

        if($id)
            $this->where('accountId', '=', $id);

        $item = $item = $this
            ->orderBy('date', 'DESC')
            ->first();

        return $item;
    }

    public function countMounth()
    {

        $currencies = [
            'USD', 'EUR', 'GOLD', 'RUB'
        ];



        $startOfMonth = (Carbon::now()->startOfMonth());

        $inUsd = 0;
        $outUsd = 0;

        foreach ($currencies as $currency){

            $item['in'][$currency] = $this
                ->where('currency', '=', $currency)
                ->where('date', '>=', $startOfMonth)
                ->where('incoming', '=', TRUE)
                ->sum('amount');

            $inUsd += (new Exchange())->Xchange($currency, $item['in'][$currency] );

            $item['out'][$currency] = $this
                ->where('currency', '=', $currency)
                ->where('date', '>=', $startOfMonth)
                ->where('incoming', '=', FALSE)
                ->sum('amount');

            $outUsd += (new Exchange())->Xchange($currency, $item['out'][$currency] );

        }

        $res['in_turnover_current_monthly'] = $inUsd;

        $res['out_turnover_current_monthly'] = $outUsd;

        return $res;
    }

    public function createTransaction($data){

        if(isset($data['batch'])) {

            $item = $this
                ->where('incoming', '=', $data['incoming'])
                ->where('amount', '=', $data['amount'])
                ->first();

            if (!$item) {

                $new = new History();

                $new->fill($data);

                $new->save();

                return TRUE;
            }

        }

        return NULL;

    }

}