<?php
require_once("record.php");
require_once("fakerBase.php");

class DebugFaker extends FakerBase {

    public $store = array(
        "merchant" => array(),
        "customer" => array(),
        "reseller" => array(),
        "users"     => array(),
    );

    protected $branching_factor = 7;

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

    protected function generateField($type, $field) : string {
        return $type . "." . $field . rand(0, 100);
    }

}



$fakedb = new DebugFaker();

for ($i=0; $i<1000; $i++) {
    $fakedb->generateRecord("merchant");
}
error_log("Complete: ");
error_log("Created " . $fakedb->countObjects("merchant") . " Merchants");
error_log("Created " . $fakedb->countObjects("customer") . " Customers");
error_log("Created " . $fakedb->countObjects("reseller") . " Resellers");

$tree = array();
foreach ($fakedb->store['merchant'] as $rec) {
    $tree[$rec->data['resellerID']] = !empty($tree[$rec->data['resellerID']]) ? $tree[$rec->data['resellerID']] : array();
    $tree[$rec->data['resellerID']][$rec->data['customerID']][] = $rec->data['id'];
}

//error_log(preg_replace(["/\n\s*\n/","/ '/"], ["\n", "->"], preg_replace(array("/\d+ =>/", "/array \(/", "/\),/", "/',/", "/' =>/"), "", var_export($tree,true))));

?>
