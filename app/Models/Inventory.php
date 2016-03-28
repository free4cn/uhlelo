<?php

namespace App\Models;

use App\Models\Detail;
use App\Traits\InventoryTrait;
use App\Traits\InventoryVariantTrait;

// TODO:    When creating or updating Barcode, need to check
//          if barcode already exist in database and throw Exception

class Inventory extends BaseModel
{
    use InventoryTrait;
    use InventoryVariantTrait;

    protected $table = 'inventories';

    public function category()
    {
        return $this->hasOne('App\Models\Category', 'id', 'category_id');
    }

    public function metric()
    {
        return $this->hasOne('App\Models\Metric', 'id', 'metric_id');
    }

    public function sku()
    {
        return $this->hasOne('App\Models\InventorySku', 'inventory_id', 'id');
    }

    public function barcode()
    {
        return $this->hasOne('App\Models\Barcode', 'inventory_id', 'id');
    }

    public function stocks()
    {
        return $this->hasMany('App\Models\InventoryStock', 'inventory_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany('App\Models\Supplier', 'inventory_suppliers', 'inventory_id')->withTimestamps()->withPivot('cost');
    }

    public function details()
    {
        return $this->hasOne('App\Models\Detail', 'inventory_id', 'id');
    }

    public function pictures()
    {
        return $this->belongsToMany('App\Models\Picture', 'pictures_inventories', 'inventory_id', 'picture_id')->withPivot('first_choice');
    }

    /**
     * Returns the current price from supplier.
     *
     * @param int $supplier_id
     *
     * @return mixed
     */
    public function getCurrentSupplierCost($supplier_id)
    {
        $suppliers = $this->suppliers;
        $pivots = $suppliers->pluck('pivot')->where('supplier_id', $supplier_id)->sortBy('created_at')->last();

        return $pivots->cost;
    }

    /**
     * Returns a supplier by the specified ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    private function getSupplierById($id)
    {
        return Supplier::find($id);
    }

    /**
    * Change the brand (supplier) of the Inventory.
    * TODO: Now it removes all suppliers of...
    *       the Inventory and add the new one.
    *
    * @param integer    supplier
    *
    * @return bool
    */
    public function changeSupplierTo($supplier)
    {
        $this->removeAllSuppliers();

        if ( $this->addSupplier($supplier) ){
            return true;
        }

        return false;
    }


    /**
    * Returns an item record by the specified Serialnr.
    *
    * @param string serialnr
    *
    * @return bool
    */
    public static function findBySerialnr($serialnr)
    {
        $item = Inventory::with('category')
            ->with('suppliers')
            ->where('serialnr', $serialnr)->first();

        if ($item) {
            return $item;
        }

        return false;
    }

    /**
     * Returns true/false if the current item has an Barcode.
     *
     * @return bool
     */
    public function hasBarcode()
    {
        if ($this->barcode) {
            return true;
        }
        return false;
    }

    /**
     * Returns true/false if the current item has Details.
     *
     * @return bool
     */
    public function hasDetails()
    {
        if ($this->details) {
            return true;
        }
        return false;
    }

    /**
     * Returns the item's Barcode.
     *
     * @return null|string
     */
    public function getBarcode()
    {
        if ($this->hasBarcode()) {
            return $this->barcode->barcode;
        }
        return;
    }

    /**
     * Returns the item's Details.
     *
     * @return null|string
     */
    public function getDetails()
    {
        if ($this->hasDetails()) {
            return $this->details;
        }
        return;
    }

    /**
     * Returns an item record by the specified Barcode.
     *
     * @param string $barcode
     *
     * @return bool
     */
    public static function findByBarcode($barcode)
    {
        $instance = new static();

        $barcode = $instance
            ->barcode()
            ->getRelated()
            ->with('item')
            ->where('barcode', $barcode)
            ->first();

        if ($barcode && $barcode->item) {
            return $barcode->item;
        }

        return false;
    }

    /**
     * Updates the items current Barcode or the Barcode
     * supplied with the specified code.
     *
     * @param null   $barcode
     *
     * @return mixed|bool
     */
    public function updateBarcode($barcode)
    {
        $this->dbStartTransaction();
        try {
            if ($this->barcode->update(compact('barcode'))) {
                $this->dbCommitTransaction();
                return $barcode;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }
        return false;
    }

    /**
     * Updates/saves the details
     *
     * @param $request
     *
     */
    public function saveDetails($details)
    {
        if($this->hasDetails()) {
            $this->details->update([
                'description' => $details->description,
                'title' => $details->title
            ]);
        } else {
            Detail::insert([
                'inventory_id' => $this->id,
                'description' => $details->description,
                'title' => $details->title
            ]);
        }

        return true;
    }

    /**
     * Create new Item
     *
     * @param Request       $request
     *
     * @return Inventory    $item
     */
    public static function createNewItem($request)
    {
        $item = new Inventory;

        $item->metric_id = 1;
        $item->name = $request->name;
        $item->serialnr = $request->serialnr;
        $item->save();

        return $item;
    }

    public function hasPicture($pictureId)
	{
		return ! is_null(
			$this->pictures()
                 ->where('picture_id', $pictureId)
                 ->first()
		);
	}

    /**
     * Get Next and Previous Items of an Item
     *
     * @return Array
     */
    public function getNextPrev()
    {
        return (object) ['prev' => $this->previous(), 'next' => $this->next()];
    }

    private function next()
	{
		return Inventory::where('id', '>', $this->id)->orderBy('id', 'asc')->first();
	}


	private function previous()
	{
		return Inventory::where('id', '<', $this->id)->orderBy('id', 'desc')->first();
	}
}
