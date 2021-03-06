<?php

namespace App\Models;

use App\Traits\InventoryTransactionHistoryTrait;

class InventoryTransactionHistory extends BaseModel
{
    use InventoryTransactionHistoryTrait;

    protected $table = 'inventory_transaction_histories';

    protected $fillable = array(
        'user_id',
        'transaction_id',
        'state_before',
        'state_after',
        'quantity_before',
        'quantity_after',
    );

    public function transaction()
    {
        return $this->belongsTo('App\Models\InventoryTransaction', 'transaction_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function makeReadable()
    {
        $before = $this->state_before;
        $after = $this->state_after;
        $message = '';

        if ($before == 'requested' && $after == 'requested') {
            $message = 'updated';
        } else {
            $message = $after;
        }

        return $message. ' ' .\App\Services\CustomDate::formatHuman($this->updated_at). ' by ' .$this->user->name;
    }
}
