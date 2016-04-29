<?php namespace reg2005\PmPayLaravel\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class History extends Model {

    protected $fillable = ['date', 'accountId', 'time', 'type', 'income', 'batch', 'currency', 'amount', 'fee', 'to', 'from', 'memo'];

    protected $table = 'reg2005_pm_history';

    public function getLastTransaction($id = NULL){

        if($id)
            $this->where('accountId', '=', $id);

        $item = $item = $this
            ->orderBy('date', 'DESC')
            ->first();

        return $item;
    }

    public function countMounth(){

        $startOfMonth = (Carbon::now()->startOfMonth());

        $item['in_turnover_current_monthly'] = $this
            ->where('date', '>=', $startOfMonth )
            ->where('income', '=', TRUE)
            ->sum('amount');

        $item['out_turnover_current_monthly'] = $this
            ->where('date', '>=', $startOfMonth )
            ->where('income', '=', FALSE)
            ->sum('amount');

        return $item;
    }

    public function createTransaction($data){

        if(isset($data['batch'])) {

            $item = $this
                ->where('batch', '=', $data['batch'])
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