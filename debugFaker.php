<?php
require_once("record.php");
require_once("fakerBase.php");

ini_set("memory_limit","-1");

class DebugFaker extends FakerBase {

    public $store = array(
        "merchant" => array(),
        "customer" => array(),
        "reseller" => array(),
        "users"     => array(),
    );

    protected $branching_factor = 3;



    protected function storeObject($record) : int {
        $this->store[$record->type][] = $record;
        return count($this->store[$record->type])-1;
    }

    protected function getObject($type, $id) : Record {
        return $this->store[$type][$id];
    }

    public function countObjects($type) : int {
        return count($this->store[$type]);
    }

    protected function getRandomRecord($type) : Record {
        $count = $this->countObjects($type);
        $out = $this->store[$type][rand(0,$count-1)];
        return $out;
    }

    protected function generateField($type, $field) : array {
        $id = rand(0, 100);
        if($type!="user"){
            return array(
                "id"        => $id,
                "name"      => $type . " " . $id,
                "address"   => "{$type} {$id},\n{$type} Street,\nTestville",
            );
        } else {
            return array(
                "id" => $id,
                "username" => "{$type}{$id}"
            );
        }
    }

}



$fakedb = new DebugFaker();

for ($i=0; $i<150; $i++) {
    $fakedb->generateRecord("merchant");
}
error_log("Complete: ");
error_log("Created " . $fakedb->countObjects("merchant") . " Merchants");
error_log("Created " . $fakedb->countObjects("customer") . " Customers");
error_log("Created " . $fakedb->countObjects("reseller") . " Resellers");

$tree = array();
foreach ($fakedb->store['merchant'] as $rec) {
    $tree[$rec->data['resellerName']] = !empty($tree[$rec->data['resellerName']]) ? $tree[$rec->data['resellerName']] : array();
    $tree[$rec->data['resellerName']][$rec->data['customerName']][] = $rec->data['name'];
}

//error_log(preg_replace(["/\n\s*\n/","/ '/"], ["\n", "->"], preg_replace(array("/\d+ =>/", "/array \(/", "/\),/", "/',/", "/' =>/"), "", var_export($tree,true))));
//error_log(var_export($tree,true));
?>
