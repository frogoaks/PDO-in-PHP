<?PHP
/*************************************************************
**  Dancing Frog Technologies PDO Database Library          **
**                                                          **
**  Created by Ryan Oaks of Dancing Frog Technologies       **
**  Date:   September 29, 2015                              **
**  Purpose: To allow developers to easily to connect to    **
**      a database using the PDO module of PHP. This        **
**      library has been tested against MySQL, InnoDB.      **
**      An AJAX API as well as a PHP API is built into this **
**      library. For more information contact               **
**      frogoaks@gmail.com.                                 **
*************************************************************/
    class DBC
    {
        //Global class variables
        private $DB = 'mysql:host=localhost';           //To connect to a specific database use 
                                                        //;dbname=nameOfDB after host address
        private $USER = 'root';                         //DB username
        private $PASS = 'root';                         //DB password
        private $OPT = 'PDO::ATTR_PERSISTANT=>true';    //Keeps the connection alive
        private $con = NULL;                            //Global holder for connect object

        /*****************************************************
        **                                                  **
        **  Constructor                                     **
        **  Purpose: Used to create a new DBC object.       **
        **                                                  **
        *****************************************************/
        function __construct()
        {
            try
            {
                $this->con = new PDO($this->DB,$this->USER,$this->PASS,array($this->OPT));
                echo "0";           //0 = no errors for AJAX
            }
            catch(PDOException $e)
            {
                echo "1";           //1 = could not connect for AJAX
            }
        }

        //Querying functions
        //insert
        /*********************************************
        **                                          **
        **  Insert                                  **
        **  Purpose: To insert data into table.     **
        **      $table is the name of the table the **
        **      DB table to insert the data. Can    **
        **      include DB name if the connection   **
        **      is to the server only. ie DB.TABLE  **
        **                                          **
        **      $fields is an array of field names. **
        **      these are the fields in the database**
        **                                          **
        **      $vars is an array of arrays         **
        **      containing the data to insert into  **
        **      each field. If multiple rows need   **
        **      to be inserted multiple arrays      **
        **      are required within the containing  **
        **      array. ie array(array('data','1'),  **
        **      array('data','2'))                  **
        **                                          **
        *********************************************/
        function insert_query($table,$fields,$vars)
        {
            try
            {
                //Wrap in a transaction to safely insert data
                $this->con->beginTransaction();

                    $c=count($vars[0]); //Get number of data sets to insert
                    //Build query
                    foreach($vars as $var)
                    {
                        $query="";
                        $query .= "insert into $table (";
                        $i=0;
                        $f=count($fields); //Count fields
                        //Insert fields
                        foreach($fields as $field)
                        {
                            $query .= $field;
                            if($i<$f-1)
                            {
                                $query .= ",";
                            }
                            $i++;
                        }
                        $query .= ") values("; 
                        $i=0;
                        foreach($var as $v) //Insert data arrays
                        {
                            $query .= $v;
                            if($i<$c-1)
                            {
                                $query .= ",";
                            }
                            $i++;
                        }
                        $query .= ")";
                        $q = $this->con->prepare($query);
                        $q->execute();
                    }
                $this->con->commit();
                echo "0";   //0 = Query executed for AJAX
            }
            catch(PDOException $e)
            {
                echo "2";   //2 = Could not query DB for AJAX
                $this->con->rollBack();
            }
        }
        //select
        /*********************************************
        **                                          **
        **  Select                                  **
        **  Purpose:  To select data from the       **
        **      $table listed.                      **
        **                                          **
        **      $table is the name of the table the **
        **      DB table to insert the data. Can    **
        **      include DB name if the connection   **
        **      is to the server only. ie DB.TABLE  **
        **                                          **
        **      $fields is an array of the fields   **
        **      the query needs to return.          **
        **                                          **
        **      $condition is an SQL condition      **
        **      statment. Can be passed as null if  **
        **      all rows are needed.                **
        **                                          **
        **      $orderby is an SQL orderby          **
        **      statement. Can be passed as null if **
        **      order does not matter.              **
        **                                          **
        *********************************************/
        function select_query($table,$fields,$condition,$orderby)
        {
            try
            {
                //Build query
                $query = "select ";
                $i=0;
                $f=count($fields); //Count fields
                foreach($fields as $field)
                {
                    $query .= $field;
                    if($i<$f-1)
                    {
                        $query .= ",";
                    }
                    $i++;
                }
                $query .= " from ".$table;
                if(!(is_null($condition)))  //If there is no condition skip
                {
                    $query .= " where ".$condition;
                }
                if(!(is_null($orderby)))    //If there is no orderby skip
                {
                    $query .= " order by ".$orderby;
                }
                
                $q = $this->con->prepare($query);
                $q->execute();
                //Get the data back as an associated array
                $q->setFetchMode(PDO::FETCH_ASSOC); 
                $data = $q->fetchAll();
                
                $d=json_encode($data);  //Encode the data
                echo $d;                //Return the data as a json object
                return $d;           //Return the data
            }
            catch(PDOException $e)
            {
                echo "3";       //3 = Could not query database for AJAX
            }
        }
        
        //join select
        /*********************************************
        **                                          **
        **  Join                                    **
        **  Purpose:  To select data from the       **
        **      $table listed.                      **
        **                                          **
        **      $table0 and $table1 is the name of  **
        **      the table the                       **
        **      DB table to insert the data. Can    **
        **      include DB name if the connection   **
        **      is to the server only. ie DB.TABLE  **
        **      $table1 will join $table0.          **
        **                                          **
        **      $joinon is the SQL join statement.  **
        **                                          **
        **      $fields is an array of the fields   **
        **      the query needs to return. Must have**
        **      the table name attached.            **
        **      ie TABLE.FIELD                      **
        **                                          **
        **      $condition is an SQL condition      **
        **      statment. Can be passed as null if  **
        **      all rows are needed.                **
        **                                          **
        **      $orderby is an SQL orderby          **
        **      statement. Can be passed as null if **
        **      order does not matter.              **
        **                                          **
        **      All fields must be accompanied by   **
        **      the table name ie TABLE.FIELD       **
        **      With out the tbale name the MySQL   **
        **      engine will return an error.        **
        **                                          **
        *********************************************/
        function join_query($table0,$table1,$joinon,$fields,$condition,$orderby)
        {
            try
            {
                $query = "select ";
                $i=0;
                $f=count($fields);
                foreach($fields as $field)
                {
                    $query .= $field;
                    if($i<$f-1)
                    {
                        $query .= ",";
                    }
                    $i++;
                }
                $query .= " from ".$table0." join ".$table1." on ".$joinon;
                if(!(is_null($condition)))      //If $condition is null skip
                {
                    $query .= " where ".$condition;
                }
                if(!(is_null($orderby)))        //If $orderby is null skip
                {
                    $query .= " order by ".$orderby;
                }
                
                $q = $this->con->prepare($query);
                $q->execute();
                //Get the data back as an associated array
                $q->setFetchMode(PDO::FETCH_ASSOC);
                $data = $q->fetchAll();
                
                $d=json_encode($data);  //Encode the data
                echo $d;                //Return the data as a json object
                return $d;           //Return the data
            }
            catch(PDOException $e)
            {
                echo "4";               //4 = Could not query DB for AJAX
                $this->con->rollBack();
            }
        }
        //delete
        /*****************************************
        **                                      **
        **  Delete                              **
        **  Purpose: To remove data from        **
        **      selected table.                 **
        **                                      **
        **  $table is the table to delete from. **
        **                                      **
        **  $condition is the condition the     **
        **      row needs to meet to be removed **
        **      from the table.                 **
        **                                      **
        *****************************************/
        function delete_query($table,$condition)
        {
            try
            {
                $this->con->beginTransaction();
                    $query="delete from ".$table." where ".$condition;
                    $q = $this->con->prepare($query);
                    $q->execute();
                $this->con->commit();
                echo "0";          //Removed data for AJAX
            }
            catch(PDOException $e)
            {
                echo "5";          //Could not remove data for AJAX
            }
        }
        
        //custom
        /*****************************************
        **                                      **
        **  Custom                              **
        **  Purpose: To allow developers to     **
        **      write an SQL query. This allows **
        **      for custom SQL queries.         **
        **                                      **
        *****************************************/
        function custom_query($query)
        {
            echo $query;
            try
            {
                $this->con->beginTransaction();
                    $q = $this->con->prepare($query);
                    $q->execute();
                $this->con->commit();
                try
                {
                    //Get the data back as an associated array
                    $q->setFetchMode(PDO::FETCH_ASSOC);
                    $data = $q->fetchAll();
                    
                    $d=json_encode($data);  //Encode the data
                    echo $d;                //Return the data as a json object
                    return $d;           //Return the data
                }
                catch(Exception $e)
                {
                    echo "0";  //Nothing returned for AJAX
                }
                echo "0";       //Query ran for AJAX
            }
            catch(PDOException $e)
            {
                echo "6";      //Could not query DB for AJAX
                $this->con->rollBack();
            }
        }
    }
    /*********************************************************
    **                                                      **
    **  The following code is the javascript API.           **
    **                                                      **
    *********************************************************/
    

   /*********************************************************
    **                                                      **
    **  The following code is the PHP API.                  **
    **                                                      **
    *********************************************************/ 
?>
