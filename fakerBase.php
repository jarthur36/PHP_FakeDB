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

    /**
     * Branching Factor
     * 
     * A number between 0 and 10 to determine branchy the database is on average
     */
    protected $branching_factor = 3;


    /**
     * Generate the values for a field
     * 
     * This should be overriden in implementation in order to
     * generate the desired field and any related dependant fields.
     */
    abstract protected function generateField($type,$field) : array;



    /**
     * Return a random record
     * 
     * Logic can be added here to influence the distrubition of children
     * in the resulting tree
     * 
     * @precond A record exists
     * 
     * @param   string  $type   The type of record to fetch
     * @return  Record
     */
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



    /**
     * Generate a record of a given $type
     * 
     * Recursively calls itself to generate/fetch parents if needed
     * 
     * @param   string  $type   The type of record to generate
     * 
     */
    public function generateRecord($type) : Record {
        $rec = new Record($type);
        $schema = $rec->schema;

        $foreign = array();
        $fields = array_slice($schema,0);
        $count = 0;
        foreach ($fields as $k=>$v) {
            $count++;
            $tmp = explode('.', $v);
            $ttype = $tmp[0];


            // Generate fields we can and queue up required parent resources
            if ($ttype==$type) {
                // We are the originator so we can safely generate
                $res = $this->generateField($type, $k);
                if (!empty($res)) { // this might want changing if empty is being deliberately generated
                    // Merge in any resulting keys
                    //$rec->data[$k] = $res;
                    $rec->data = $rec->data + $res;
                    foreach($res as $k1=>$v1) {
                        if (isset($fields[$k1])) {
                            unset($fields[$k1]);
                        }
                    }
                    //$fields1 = array_diff_key($fields, $res);
                    unset($res);
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
        $count = 0;
        $error_mode = false;
        while (!empty($foreign)) {
            $count++;
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