<?php namespace reg2005\PmPayLaravel\Entities;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Wallets extends Model {

    protected $fillable = ['id', 'name', 'currency', 'amount', 'account_id'];

    protected $table = 'reg2005_pm_wallets';

    public function insert_wallet_data($data){

        if ($data) {

            $item = $this->where('id', '=', $data['id'])->first();

            if (!$item) {

                $new = new Wallets();

                $new->fill($data);

                $new->save();

                return TRUE;
            }
        }

        return NULL;

    }
}