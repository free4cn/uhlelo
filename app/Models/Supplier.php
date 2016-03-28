<?php

namespace App\Models;

use App\Traits\SupplierTrait;

class Supplier extends BaseModel
{
    use SupplierTrait;

    protected $table = 'suppliers';

    public function items()
    {
        return $this->belongsToMany('App\Models\Inventory', 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }

    public static function getSuppliersByBrand($id, $includeThis = false)
    {
        $instance = new static();
        $brand = $instance->find($id);

        // TODO: Make Brand able to have many Suppliers.
        $supplier = Supplier::where('id', $brand->supplied_by)->first();

        if($includeThis === true) {
            return array($supplier, $brand);
        }

        return array($supplier);
    }

    public static function getMainSuppliers()
    {
        $suppliers = Supplier::where('supplied_by', '>', 0)->get();
        $main_supplier_ids = $suppliers->unique('supplied_by')
                ->pluck('supplied_by')
                ->toArray();

        return Supplier::find($main_supplier_ids);
    }
}
