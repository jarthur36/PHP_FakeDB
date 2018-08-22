<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Base Faker Class
 * 
 * This class provides an interface and functionality for other software to generate 
 * Fake relational data for a database using custom generators and maintaining the 
 * integrity of foreign key relationships throughout.
 * 
 * @author Jack Arthur <jack.arthur@cardstream.com>
 * 
 */
abstract class FakerBase {


    protected $branching_factor = 3;


    abstract protected function generateField($type,$field) : String;


    abstract protected function getRandomRecord($type) : Record;

    /**
     * Store a record in the DB
     * 
     * This should be overriden in implementation in order to save a
     * Record object in the DB  
     *
     * @param   Record  $record
     * @return  integer an id with which the record can be retrieved
     */
    abstract protected function storeObject($record) : int;


    /**
     * Gets an object from the DB
     * 
     * 
     *
     * @param   string  $type
     * @param   integer $id
     * @return  Record
     */
    abstract protected function getObject($type, $id) : Record;


    /**
     * Counts the number of stored objects of $type
     *
     * @param string $type
     * @return int
     */
    abstract protected function countObjects($type) : int;




    public function generateRecord($type) : Record {
        $rec = new Record($type);
        $schema = $rec->schema;

        $foreign = array();
        foreach ($schema as $k=>$v) {
            $tmp = explode('.', $v);
            $ttype = $tmp[0];


            // Generate fields we can and queue up required parent resources
            if ($ttype==$type) {
                // We are the originator so we can safely generate
                $res = $this->generateField($type, $k);
                if (!empty($res)) { // this might want changing if empty is being deliberately generated
                    $rec->data[$k] = $res;
                } else {
                    error_log("No generator defined for field {$v}");
                }

            } else {
                if (!isset($foreign[$ttype])) {
                    $foreign[$k] = $ttype;
                }
            }
        }

        // Iterate through any parent objects that need creating and create them in order
        while (!empty($foreign)) {
            $sorted = array_values(array_intersect(Record::$top_order, array_values($foreign)));
            if (!empty($sorted)) {
                $ttype = $sorted[0];
                $count = $this->countObjects($ttype);
                if( $count==0 || (rand(0,10) >= $this->branching_factor)) {
                    $res = $this->generateRecord($ttype);
                } else {
                    $res = $this->getRandomRecord($ttype);
                }
                $rec->merge_data($res,false);
            } else {
                error_log("Some fields could not be generated as no path was found");
                die();
            }


            foreach ($foreign as $k=>$v){
                if(!empty($rec->data[$k])){
                    unset($foreign[$k]);
                }
            }
        
        }

        $this->storeObject($rec);
        return $rec;

    }
}