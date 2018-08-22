<?php

/**
 * Record
 * 
 * This simple class encapsulates important behavour related 
 * to the storage of a database record and allows easy combination
 * of stored values
 * 
 * Will struggle with cyclic schema for now.
 * 
 * @author  Jack Arthur <jack.arthur@cardstream.com>
 */
class Record {


    public $data = array();

    public $type = "undefined";

    public $schema = array();

    /**
     * The schemas for each resource type defined here
     * 
     * The first key selects the @link{$type} of the resource we are
     * describing. The next key represents the property name and the 
     * value corresponds with the closest avaiable location of that property.
     * 
     * For foreign key and foreign table relationships the originating 
     * resource type can be declared followed by a dot and the property
     * name within that resource.
     * 
     * Properties with multiple possible originators can be provided
     * in an array and a random selection will be made if required 
     *
     * @var array
     */
    public static $schemas = array(

        "merchant"  => array(
            "id"                => "merchant.id",
            "name"              => "merchant.name",
            "address"           => "merchant.address",
            "customerID"        => "customer.id",
            "customerName"      => "customer.name",
            "customerAddress"   => "customer.address",
            "resellerID"        => "customer.resellerID",
            "resellerName"      => "customer.resellerName",
            "resellerAddress"   => "customer.resellerAddress",
        ),

        "customer"  => array(
            "id"                => "customer.id",
            "name"              => "customer.name",
            "address"           => "customer.address",
            "resellerID"        => "reseller.id",
            "resellerName"      => "reseller.name",
            "resellerAddress"   => "reseller.address",
        ),

        "reseller"  => array(
            "id"                => "reseller.id",
            "name"              => "reseller.name",
            "address"           => "reseller.address",
        ),

        "user"  => array(
            "id"        => "user.id",
            "username"  => "user.username",
            //"parentID"  => ["merchant.id","reseller.id","customer.id"]
        ),

    );

    /**
     * Topological Ordering of record inheritance, assuming records form 
     * a directed acyclic graph of inclusion. 
     * 
     * TODO: Calculate this dynamically from schema using topological sort
     *
     * @var array
     */
    public static $top_order = ["user","merchant","customer","reseller"];



    public function __construct($_type){
        $this->type = $_type;
        $this->schema = (isset(self::$schemas[$_type]) ? self::$schemas[$_type] : array());
    }

    /**
     * Merge an array into this record
     * 
     * Checks incoming record for keys that match the current schema
     * and fills in the gaps with their values;
     *
     * @param   Record      $incRecord
     * @param   boolean     $overwrite
     * @return void
     */
    public function merge_data($incRecord, $overwrite=false){
        foreach ($this->schema as $k=>$v) {

            if($overwrite || !isset($this->data[$k])){

                $tmp=explode(".",$v);

                if (count($tmp)>1 && $tmp[0]==($incRecord->type) && isset($incRecord->data[$tmp[1]])) {
                    $this->data[$k] = $incRecord->data[$tmp[1]];
                }
        }   
        }
    }




}

?>