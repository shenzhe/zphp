<?php
namespace ZPHP\Manager;

    /**
     * $config = array(
     * 'mongo_hostbase' => 'localhost:27017',
     * 'mongo_database' => 'mongo',
     * 'mongo_username' => '',
     * 'mongo_password' => '',
     * 'mongo_persist' => FALSE,//废弃
     * 'mongo_persist_key' => 'persist',
     * 'mongo_replica_set' => FALSE,
     * 'mongo_query_safety' => 'w',
     * 'mongo_suppress_connect_error' => FALSE,
     * 'mongo_host_db_flag' => FALSE
     * )
     */

/**
 * Class Mongo
 * @package ZPHP\Manager
 */
class Mongo
{
    /**
     * Config file data
     *
     * @var array
     * @access private
     */
    private $_config_data = [ ];

    /**
     * Connection resource.
     *
     * @var mixed
     * @access private
     */
    private $_connection = NULL;

    /**
     * Database handle.
     *
     * @var resource
     * @access private
     */
    private $_dbhandle = NULL;

    /**
     * Generated connection string.
     *
     * @var mixed
     * @access private
     */
    private $_connection_string = '';

    /**
     * Database host.
     *
     * @var mixed
     * @access private
     */
    private $_host = [ 'localhost:27017' ];

    /**
     * Database user.
     *
     * @var mixed
     * @access private
     */
    private $_user = '';

    /**
     * Database user password.
     *
     * @var mixed
     * @access private
     */
    private $_pass = '';

    /**
     * Database name.
     *
     * @var strings
     * @access private
     */
    private $_dbname = '';

    /**
     * Persist connection.
     *
     * @var boolean
     * @access private
     */
    private $_persist = TRUE;

    /**
     * Persist key.
     *
     * @var string
     * @access private
     */
    private $_persist_key = 'mongo';

    /**
     * Use replica set.
     *
     * @var FALSE|string
     * @access private
     */
    private $_replica_set = FALSE;

    /**
     * Query safety value.
     *
     * @var string
     * @access private
     */
    private $_query_safety = 'safe';

    /**
     * Selects array.
     *
     * @var array
     * @access private
     */
    private $_selects = [ ];

    /**
     * Wheres array.
     *
     * Public to make debugging easier.
     *
     * @var array
     * @access public
     */
    public $wheres = [ ];

    /**
     * Sorts array.
     *
     * @var array
     * @access private
     */
    private $_sorts = [ ];

    /**
     * Updates array.
     *
     * Public to make debugging easier
     *
     * @var array
     * @access public
     */
    public $updates = [ ];

    /**
     * Results limit.
     *
     * @var integer
     * @access private
     */
    private $_limit = 999999;

    /**
     * Query log.
     *
     * @var integer
     * @access private
     */
    private $_query_log = [ ];

    /**
     * Result offset.
     *
     * @var integer
     * @access private
     */
    private $_offset = 0;

    /**
     * 记录错误信息
     * @var string
     */
    private $_last_error = '';

    /**
     * Constructor
     *
     * Automatically check if the Mongo PECL extension has been installed/enabled.
     *
     * @access public
     * @return void
     */
    public function __construct( $config = [ ] )
    {
        if( !class_exists('Mongo') ) {
            $this->_show_error('mongodb not extension' , 500);
        }

        if( empty( $config ) ) {
            $this->_show_error('config not empty');
        }

        $this->load($config);
    }

    /**
     * Load.
     *
     * Load config and connect
     *
     * @param mixed $config Name of the config file or array of params
     *
     * @access public
     * @return void
     */
    public function load( $config = [ ] )
    {
        if( !$this->_connection ) {
            $this->_config_data = $config;
            $this->_connection_string();
            $this->_connect();
        }
    }

    /**
     * Switch database.
     *
     * <code>
     * $this->mongo_db->switch_db('foobar');
     * </code>
     *
     * @param string $database Database name
     *
     * @access public
     * @return boolean
     */
    public function switch_db( $database = '' )
    {
        if( empty( $database ) ) {
            $this->_show_error('To switch MongoDB databases, a new database name must be specified' , 500);
        }

        try {
            // Regenerate the connection string and reconnect
            $this->_config_data[ 'mongo_database' ] = $database;
            $this->_connection_string();
            $this->_connect();
        } catch ( Exception $exception ) {
            $this->_show_error('Unable to switch Mongo Databases: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Drop a database.
     *
     * <code>
     * $this->mongo_db->drop_db("foobar");
     * </code>
     *
     * @param string $database Database name
     *
     * @access public
     * @return boolean
     */
    public function drop_db( $database = '' )
    {
        if( empty( $database ) ) {
            $this->_show_error('Failed to drop MongoDB database because name is empty' , 500);
        } else {
            try {
                $this->_connection->{$database}->drop();

                return TRUE;
            } catch ( Exception $exception ) {
                $this->_show_error('Unable to drop Mongo database `' . $database . '`: ' . $exception->getMessage() , 500);
            }

        }
    }

    /**
     * Drop a collection.
     *
     * <code>
     * $this->mongo_db->drop_collection('foo', 'bar');
     * </code>
     *
     * @param string $database Database name
     * @param string $collection Collection name
     *
     * @access public
     * @return boolean
     */
    public function drop_collection( $database = '' , $collection = '' )
    {
        $database = $database ? $database : $this->_dbname;
        if( empty( $database ) ) {
            $this->_show_error('Failed to drop MongoDB collection because database name is empty' , 500);
        }

        if( empty( $collection ) ) {
            $this->_show_error('Failed to drop MongoDB collection because collection name is empty' , 500);
        } else {
            try {
                $this->_connection->{$database}->{$collection}->drop();

                return TRUE;
            } catch ( Exception $exception ) {
                $this->_show_error('Unable to drop Mongo collection `' . $collection . '`: ' . $exception->getMessage() , 500);
            }
        }
    }

    /**
     * Set select parameters.
     *
     * Determine which fields to include OR which to exclude during the query process.
     * Currently, including and excluding at the same time is not available, so the
     * $includes array will take precedence over the $excludes array.  If you want to
     * only choose fields to exclude, leave $includes an empty array().
     *
     * <code>
     * $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
     * </code>
     *
     * @param array $includes Fields to include in the returned result
     * @param array $excludes Fields to exclude from the returned result
     *
     * @access public
     * @return object
     */
    public function select( $includes = [ ] , $excludes = [ ] )
    {
        if( !is_array($includes) ) {
            $includes = [ ];
        }

        if( !is_array($excludes) ) {
            $excludes = [ ];
        }

        if( !empty( $includes ) ) {
            foreach( $includes as $include ) {
                $this->_selects[ $include ] = 1;
            }
        } else {
            foreach( $excludes as $exclude ) {
                $this->_selects[ $exclude ] = 0;
            }
        }

        return $this;
    }

    /**
     * Set where paramaters
     *
     * Get the documents based on these search parameters.  The $wheres array should
     * be an associative array with the field as the key and the value as the search
     * criteria.
     *
     * <code>
     * $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
     * </code>
     *
     * @param array|string $wheres Array of where conditions. If string, $value must be set
     * @param mixed $value Value of $wheres if $wheres is a string
     *
     * @access public
     * @return object
     */
    public function where( $wheres = [ ] , $value = NULL )
    {
        if( is_array($wheres) ) {
            foreach( $wheres as $where => $value ) {
                $this->wheres[ $where ] = $value;
            }
        } else {
            $this->wheres[ $wheres ] = $value;
        }

        return $this;
    }

    /**
     * or_where.
     *
     * Get the documents where the value of a $field may be something else
     *
     * <code>
     * $this->mongo_db->or_where(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
     * </code>
     *
     * @param array $wheres Array of where conditions
     *
     * @access public
     * @return object
     */
    public function or_where( $wheres = [ ] )
    {
        if( count($wheres) > 0 ) {
            if( !isset( $this->wheres[ '$or' ] ) OR !is_array($this->wheres[ '$or' ]) ) {
                $this->wheres[ '$or' ] = [ ];
            }

            foreach( $wheres as $where => $value ) {
                $this->wheres[ '$or' ][] = [ $where => $value ];
            }
        }

        return $this;
    }

    /**
     * where_in.
     *
     * Get the documents where the value of a $field is in a given $in array().
     *
     * <code>
     * $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
     * </code>
     *
     * @param string $field Name of the field
     * @param array $in_values Array of values that $field could be
     *
     * @access public
     * @return object
     */
    public function where_in( $field = '' , $in_values = [ ] )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$in' ] = $in_values;

        return $this;
    }

    /**
     * where_in_all.
     *
     * Get the documents where the value of a $field is in all of a given $in array().
     *
     * <code>
     * $this->mongo_db->where_in_all('foo', array('bar', 'zoo', 'blah'))->get('foobar');
     * </code>
     *
     * @param string $field Name of the field
     * @param array $in_values Array of values that $field must be
     *
     * @access public
     * @return object
     */
    public function where_in_all( $field = '' , $in_values = [ ] )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$all' ] = $in_values;

        return $this;
    }

    /**
     * Where not in
     *
     * Get the documents where the value of a $field is not in a given $in array().
     *
     * <code>
     * $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
     * </code>
     *
     * @param string $field Name of the field
     * @param array $in_values Array of values that $field isnt
     *
     * @access public
     * @return object
     */
    public function where_not_in( $field = '' , $in_values = [ ] )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$nin' ] = $in_values;

        return $this;
    }

    /**
     * where_gt
     *
     * Get the documents where the value of a $field is greater than $value
     *
     * <code>
     * $this->mongo_db->where_gt('foo', 20);
     * </code>
     *
     * @param string $field Name of the field
     * @param mixed $value Value that $field is greater than
     *
     * @access public
     * @return object
     */
    public function where_gt( $field = '' , $value = NULL )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$gt' ] = $value;

        return $this;
    }

    /**
     * where_gte
     *
     * Get the documents where the value of a $field is greater than or equal to $value
     *
     * <code>
     * $this->mongo_db->where_gte('foo', 20);
     * </code>
     *
     * @param string $field Name of the field
     * @param mixed $value Value that $field is greater than or equal to
     *
     * @access public
     * @return object
     */
    public function where_gte( $field = '' , $value = NULL )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$gte' ] = $value;

        return $this;
    }

    /**
     * where_lt.
     *
     * Get the documents where the value of a $field is less than $x
     *
     * <code>
     * $this->mongo_db->where_lt('foo', 20);
     * </code>
     *
     * @param string $field Name of the field
     * @param mixed $value Value that $field is less than
     *
     * @access public
     * @return object
     */
    public function where_lt( $field = '' , $value = NULL )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$lt' ] = $value;

        return $this;
    }

    /**
     * where_lte.
     *
     * Get the documents where the value of a $field is less than or equal to $x
     *
     * <code>
     * $this->mongo_db->where_lte('foo', 20);
     * </code>
     *
     * @param string $field Name of the field
     * @param mixed $value Value that $field is less than or equal to
     *
     * @access public
     * @return object
     */
    public function where_lte( $field = '' , $value = NULL )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$lte' ] = $value;

        return $this;
    }

    /**
     * where_between
     *
     * Get the documents where the value of a $field is between $x and $y
     *
     * <code>
     * $this->mongo_db->where_between('foo', 20, 30);
     * </code>
     *
     * @param string $field Name of the field
     * @param int $value_x Value that $field is greater than or equal to
     * @param int $value_y Value that $field is less than or equal to
     *
     * @access public
     * @return object
     */
    public function where_between( $field = '' , $value_x = 0 , $value_y = 0 )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$gte' ] = $value_x;
        $this->wheres[ $field ][ '$lte' ] = $value_y;

        return $this;
    }

    /**
     * where_between_ne
     *
     * Get the documents where the value of a $field is between but not equal to $x and $y
     *
     * <code>
     * $this->mongo_db->where_between_ne('foo', 20, 30);
     * </code>
     *
     * @param string $field Name of the field
     * @param int $value_x Value that $field is greater than or equal to
     * @param int $value_y Value that $field is less than or equal to
     *
     * @access public
     * @return object
     */
    public function where_between_ne( $field = '' , $value_x , $value_y )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$gt' ] = $value_x;
        $this->wheres[ $field ][ '$lt' ] = $value_y;

        return $this;
    }

    /**
     * where_ne
     *
     * Get the documents where the value of a $field is not equal to $x
     *
     * <code>
     * $this->mongo_db->where_ne('foo', 1)->get('foobar');
     * </code>
     *
     * @param string $field Name of the field
     * @param mixed $value Value that $field is not equal to
     *
     * @access public
     * @return object
     */
    public function where_ne( $field = '' , $value )
    {
        $this->_where_init($field);
        $this->wheres[ $field ][ '$ne' ] = $value;

        return $this;
    }

    /**
     * where_near
     *
     * Get the documents nearest to an array of coordinates (your collection must have a geospatial index)
     *
     * <code>
     * $this->mongo_db->where_near('foo', array('50','50'))->get('foobar');
     * </code>
     *
     * @param string $field Name of the field
     * @param array $coords Array of coordinates
     * @param integer $distance Value of the maximum distance to search
     * @param boolean $spherical Treat the Earth as spherical instead of flat (useful when searching over large distances)
     *
     * @access public
     * @return object
     */
    function where_near( $field = '' , $coords = [ ] , $distance = NULL , $spherical = FALSE )
    {
        $this->_where_init($field);

        if( $spherical ) {
            $this->wheres[ $field ][ '$nearSphere' ] = $coords;
        } else {
            $this->wheres[ $field ][ '$near' ] = $coords;
        }

        if( $distance !== NULL ) {
            $this->wheres[ $field ][ '$maxDistance' ] = $distance;
        }

        return $this;
    }

    /**
     * like
     *
     * Get the documents where the (string) value of a $field is like a value. The defaults
     * allow for a case-insensitive search.
     *
     * <code>
     * $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
     * </code>
     *
     * @param string $field The field
     * @param string $value The value to match against
     * @param string $flags Allows for the typical regular expression flags:<br>i = case insensitive<br>m = multiline<br>x = can contain comments<br>l = locale<br>s = dotall, "." matches everything, including newlines<br>u = match unicode
     * @param boolean $enable_start_wildcard If set to anything other than TRUE, a starting line character "^" will be prepended to the search value, representing only searching for a value at the start of a new line.
     * @param boolean $enable_end_wildcard If set to anything other than TRUE, an ending line character "$" will be appended to the search value, representing only searching for a value at the end of a line.
     *
     * @access public
     * @return object
     */
    public function like( $field = '' , $value = '' , $flags = 'i' , $enable_start_wildcard = TRUE , $enable_end_wildcard = TRUE )
    {
        $field = (string)trim($field);
        $this->_where_init($field);
        $value = (string)trim($value);
        $value = quotemeta($value);

        if( $enable_start_wildcard !== TRUE ) {
            $value = '^' . $value;
        }

        if( $enable_end_wildcard !== TRUE ) {
            $value .= '$';
        }

        $regex = '/' . $value . '/' . $flags;
        $this->wheres[ $field ] = new MongoRegex($regex);

        return $this;
    }

    /**
     * order_by
     *
     * Sort the documents based on the parameters passed. To set values to descending order, you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be set to 1 (ASC).
     *
     * <code>
     * $this->mongo_db->order_by(array('foo' => 'ASC'))->get('foobar');
     * </code>
     *
     * @param array $fields Array of fields with their sort type (asc or desc)
     *
     * @access public
     * @return object
     */
    public function order_by( $fields = [ ] )
    {
        foreach( $fields as $field => $order ) {
            if( $order === -1 OR $order === FALSE OR strtolower($order) === 'desc' ) {
                $this->_sorts[ $field ] = -1;
            } else {
                $this->_sorts[ $field ] = 1;
            }
        }

        return $this;
    }

    /**
     * limit.
     *
     * Limit the result set to $limit number of documents
     *
     * <code>
     * $this->mongo_db->_limit($x);
     * </code>
     *
     * @param int $limit The maximum number of documents that will be returned
     *
     * @access public
     * @return object
     */
    public function limit( $limit = 99999 )
    {
        if( $limit !== NULL AND is_numeric($limit) AND $limit >= 1 ) {
            $this->_limit = (int)$limit;
        }

        return $this;
    }

    /**
     * offset.
     *
     * Offset the result set to skip $x number of documents
     *
     * <code>
     * $this->mongo_db->_offset($x);
     * </code>
     *
     * @param int $offset The number of documents to offset the search by
     *
     * @access public
     * @return object
     */
    public function offset( $offset = 0 )
    {
        if( $offset !== NULL AND is_numeric($offset) AND $offset >= 1 ) {
            $this->_offset = (int)$offset;
        }

        return $this;
    }

    /**
     * Get where.
     *
     * Get the documents based upon the passed parameters
     *
     * <code>
     * $this->mongo_db->get_where('foo', array('bar' => 'something'));
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $where Array of where conditions
     *
     * @access public
     * @return array
     */
    public function get_where( $collection = '' , $where = [ ] )
    {
        return $this->where($where)->get($collection);
    }

    /**
     * Get.
     *
     * Return the found documents
     *
     * <code>
     * $this->mongo_db->get('foo');
     * </code>
     *
     * @param string $collection Name of the collection
     * @param bool $return_cursor Return the native document cursor
     *
     * @access public
     * @return array
     */
    public function get( $collection = '' , $return_cursor = FALSE )
    {
        if( empty( $collection ) ) {
            $this->_show_error('In order to retrieve documents from MongoDB, a collection name must be passed' , 500);
        }

        $cursor = $this->_dbhandle
            ->{$collection}
            ->find($this->wheres , $this->_selects)
            ->limit($this->_limit)
            ->skip($this->_offset)
            ->sort($this->_sorts);

        // Clear
        $this->_clear($collection , 'get');

        // Return the raw cursor if wanted
        if( $return_cursor === TRUE ) {
            return $cursor;
        }

        $documents = [ ];

        while( $cursor->hasNext() ) {
            try {
                $documents[] = $cursor->getNext();
            } catch ( MongoCursorException $exception ) {
                $this->_show_error($exception->getMessage() , 500);
            }
        }

        return $documents;
    }

    /**
     * Count.
     *
     * Count the number of found documents
     *
     * <code>
     * $this->mongo_db->count('foo');
     * </code>
     *
     * @param string $collection Name of the collection
     *
     * @access public
     * @return int
     */
    public function count( $collection = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('In order to retrieve a count of documents from MongoDB, a collection name must be passed' , 500);
        }

        $count = $this->_dbhandle
            ->{$collection}
            ->find($this->wheres)
            ->limit($this->_limit)
            ->skip($this->_offset)
            ->count();

        $this->_clear($collection , 'count');

        return $count;
    }

    /**
     * Insert.
     *
     * Insert a new document
     *
     * <code>
     * $this->mongo_db->insert('foo', array('foo'=>'bar'));
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $insert The document to be inserted
     * @param array $options Array of options
     *
     * @access public
     * @return boolean
     */
    public function insert( $collection = '' , $insert = [ ] , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to insert into' , 500);
        }

        if( count($insert) === 0 OR !is_array($insert) ) {
            $this->_show_error('Nothing to insert into Mongo collection or insert is not an array' , 500);
        }

        $options = array_merge(
            [
                $this->_query_safety => TRUE
            ] ,
            $options
        );

        try {
            $this->_dbhandle
                ->{$collection}
                ->insert($insert , $options);

            if( isset( $insert[ '_id' ] ) ) {
                return $insert[ '_id' ];
            } else {
                return FALSE;
            }
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Insert of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Insert.
     *
     * Insert a new document
     *
     * <code>
     * $this->mongo_db->insert('foo', array('foo'=>'bar'));
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $insert The document to be inserted
     * @param array $options Array of options
     *
     * @access public
     * @return boolean
     */
    public function batch_insert( $collection = '' , $insert = [ ] , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to insert into' , 500);
        }

        if( count($insert) === 0 || !is_array($insert) ) {
            $this->_show_error('Nothing to insert into Mongo collection or insert is not an array' , 500);
        }

        $options = array_merge(
            [
                $this->_query_safety => TRUE
            ] ,
            $options
        );

        try {
            return $this->_dbhandle
                ->{$collection}
                ->batchInsert($insert , $options);
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Insert of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Update a document.
     *
     * Updates a document
     *
     * <code>
     * $this->mongo_db->update('foo');
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $options Array of update options
     *
     * @access public
     * @return boolean
     */
    public function update( $collection = '' , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to update' , 500);
        }

        if( count($this->updates) === 0 ) {
            $this->_show_error('Nothing to update in Mongo collection or update is not an array' , 500);
        }

        try {
            $options = array_merge([ $this->_query_safety => TRUE , 'multiple' => FALSE ] , $options);
            $result = $this->_dbhandle->{$collection}->update($this->wheres , $this->updates , $options);
            $this->_clear($collection , 'update');

            if( $result[ 'updatedExisting' ] > 0 ) {
                return $result[ 'updatedExisting' ];
            }

            return FALSE;
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Update of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Update all documents.
     *
     * Updates a document
     *
     * <code>
     * $this->mongo_db->update_all('foo');
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $options Array of update options
     *
     * @access public
     * @return boolean
     */
    public function update_all( $collection = '' , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to update' , 500);
        }

        if( count($this->updates) === 0 ) {
            $this->_show_error('Nothing to update in Mongo collection or update is not an array' , 500);
        }

        try {
            $options = array_merge([ $this->_query_safety => TRUE , 'multiple' => TRUE ] , $options);
            $result = $this->_dbhandle->{$collection}->update($this->wheres , $this->updates , $options);
            $this->_clear($collection , 'update_all');

            if( $result[ 'updatedExisting' ] > 0 ) {
                return $result[ 'updatedExisting' ];
            }

            return FALSE;
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Update of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Inc.
     *
     * Increments the value of a field
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->inc(array('num_comments' => 1))->update('blog_posts');
     * </code>
     *
     * @param array|string $fields Array of field names (or a single string field name) to be incremented
     * @param int $value Value that the field(s) should be incremented by
     *
     * @access public
     * @return object
     */
    public function inc( $fields = [ ] , $value = 0 )
    {
        $this->_update_init('$inc');

        if( is_string($fields) ) {
            $this->updates[ '$inc' ][ $fields ] = $value;
        } elseif( is_array($fields) ) {
            foreach( $fields as $field => $value ) {
                $this->updates[ '$inc' ][ $field ] = $value;
            }
        }

        return $this;
    }

    /**
     * Dec.
     *
     * Decrements the value of a field
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->dec(array('num_comments' => 1))->update('blog_posts');
     * </code>
     *
     * @param array|string $fields Array of field names (or a single string field name) to be decremented
     * @param int $value Value that the field(s) should be decremented by
     *
     * @access public
     * @return object
     */
    public function dec( $fields = [ ] , $value = 0 )
    {
        $this->_update_init('$inc');

        if( is_string($fields) ) {
            $this->updates[ '$inc' ][ $fields ] = -$value;
        } elseif( is_array($fields) ) {
            foreach( $fields as $field => $value ) {
                $this->updates[ '$inc' ][ $field ] = -$value;
            }
        }

        return $this;
    }

    /**
     * Set.
     *
     * Sets a field to a value
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->set('posted', 1)->update('blog_posts');
     * $this->mongo_db->where(array('blog_id'=>123))->set(array('posted' => 1, 'time' => time()))->update('blog_posts');
     * </code>
     *
     * @param array|string $fields Array of field names (or a single string field name)
     * @param mixed $value Value that the field(s) should be set to
     *
     * @access public
     * @return object
     */
    public function set( $fields , $value = NULL )
    {
        $this->_update_init('$set');

        if( is_string($fields) ) {
            $this->updates[ '$set' ][ $fields ] = $value;
        } elseif( is_array($fields) ) {
            foreach( $fields as $field => $value ) {
                $this->updates[ '$set' ][ $field ] = $value;
            }
        }

        return $this;
    }

    /**
     * Unset.
     *
     * Unsets a field (or fields)
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->unset('posted')->update('blog_posts');
     * $this->mongo_db->where(array('blog_id'=>123))->set(array('posted','time'))->update('blog_posts');
     *
     * @param array|string $fields Array of field names (or a single string field name) to be unset
     *
     * @access public
     * @return object
     */
    public function unset_field( $fields )
    {
        $this->_update_init('$unset');

        if( is_string($fields) ) {
            $this->updates[ '$unset' ][ $fields ] = 1;
        } elseif( is_array($fields) ) {
            foreach( $fields as $field ) {
                $this->updates[ '$unset' ][ $field ] = 1;
            }
        }

        return $this;
    }

    /**
     * Add to set.
     *
     * Adds value to the array only if its not in the array already
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->addtoset('tags', 'php')->update('blog_posts');
     * $this->mongo_db->where(array('blog_id'=>123))->addtoset('tags', array('php', 'codeigniter', 'mongodb'))->update('blog_posts');
     * </code>
     *
     * @param string $field Name of the field
     * @param string|array $values Value of the field(s)
     *
     * @access public
     * @return object
     */
    public function addtoset( $field , $values )
    {
        $this->_update_init('$addToSet');

        if( is_string($values) ) {
            $this->updates[ '$addToSet' ][ $field ] = $values;
        } elseif( is_array($values) ) {
            $this->updates[ '$addToSet' ][ $field ] = [ '$each' => $values ];
        }

        return $this;
    }

    /**
     * Push.
     *
     * Pushes values into a field (field must be an array)
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->push('comments', array('text'=>'Hello world'))->update('blog_posts');
     * $this->mongo_db->where(array('blog_id'=>123))->push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex')->update('blog_posts');
     * </code>
     *
     * @param array|string $fields Array of field names (or a single string field name)
     * @param mixed $value Value of the field(s) to be pushed into an array or object
     *
     * @access public
     * @return object
     */
    public function push( $fields , $value = [ ] )
    {
        $this->_update_init('$push');

        if( is_string($fields) ) {
            $this->updates[ '$push' ][ $fields ] = $value;
        } elseif( is_array($fields) ) {
            foreach( $fields as $field => $value ) {
                $this->updates[ '$push' ][ $field ] = $value;
            }
        }

        return $this;
    }

    /**
     * Pop.
     *
     * Pops the last value from a field (field must be an array)
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->pop('comments')->update('blog_posts');
     * $this->mongo_db->where(array('blog_id'=>123))->pop(array('comments', 'viewed_by'))->update('blog_posts');
     * </code>
     *
     * @param string $field Name of the field to be popped
     *
     * @access public
     * @return object
     */
    public function pop( $field )
    {
        $this->_update_init('$pop');

        if( is_string($field) ) {
            $this->updates[ '$pop' ][ $field ] = -1;
        } elseif( is_array($field) ) {
            foreach( $field as $pop_field ) {
                $this->updates[ '$pop' ][ $pop_field ] = -1;
            }
        }

        return $this;
    }

    /**
     * Pull.
     *
     * Removes by an array by the value of a field
     *
     * <code>
     * $this->mongo_db->pull('comments', array('comment_id'=>123))->update('blog_posts');
     * </code>
     *
     * @param string $field Name of the field
     * @param array $value Array of identifiers to remove $field
     *
     * @access public
     * @return object
     */
    public function pull( $field = '' , $value = [ ] )
    {
        $this->_update_init('$pull');

        $this->updates[ '$pull' ] = [ $field => $value ];

        return $this;
    }

    /**
     * Rename field.
     *
     * Renames a field
     *
     * <code>
     * $this->mongo_db->where(array('blog_id'=>123))->rename_field('posted_by', 'author')->update('blog_posts');
     * </code>
     *
     * @param string $old_name Name of the field to be renamed
     * @param string $new_name New name for $old_name
     *
     * @access public
     * @return object
     */
    public function rename_field( $old_name , $new_name )
    {
        $this->_update_init('$rename');
        $this->updates[ '$rename' ] = [ $old_name => $new_name ];

        return $this;
    }

    /**
     * Delete.
     *
     * delete document from the passed collection based upon certain criteria
     *
     * <code>
     * $this->mongo_db->delete('foo');
     * </code>
     *
     * @param string $collection Name of the collection
     *
     * @access public
     * @return object
     */
    public function delete( $collection = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to delete from' , 500);
        }

        try {
            $this->_dbhandle->{$collection}->remove($this->wheres , [ $this->_query_safety => TRUE , 'justOne' => TRUE ]);
            $this->_clear($collection , 'delete');

            return TRUE;
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Delete of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Delete all.
     *
     * Delete all documents from the passed collection based upon certain criteria
     *
     * <code>
     * $this->mongo_db->delete_all('foo', $data = array());
     * </code>
     *
     * @param string $collection Name of the collection
     *
     * @access public
     * @return object
     */
    public function delete_all( $collection = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection selected to delete from' , 500);
        }

        if( isset( $this->wheres[ '_id' ] ) AND !( $this->wheres[ '_id' ] instanceof MongoId ) ) {
            $this->wheres[ '_id' ] = new MongoId($this->wheres[ '_id' ]);
        }

        try {
            $this->_dbhandle->{$collection}->remove($this->wheres , [ $this->_query_safety => TRUE , 'justOne' => FALSE ]);
            $this->_clear($collection , 'delete_all');

            return TRUE;
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('Delete of data into MongoDB failed: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Command.
     *
     * Runs a MongoDB command (such as GeoNear). See the MongoDB documentation for more usage scenarios - http://dochub.mongodb.org/core/commands
     *
     * <code>
     * $this->mongo_db->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>true));
     * </code>
     *
     * @param array $query The command query
     *
     * @access public
     * @return object
     */
    public function command( $query = [ ] )
    {
        try {
            $execute = $this->_dbhandle->command($query);

            return $execute;
        } catch ( MongoCursorException $exception ) {
            $this->_show_error('MongoDB command failed to execute: ' . $exception->getMessage() , 500);
        }
    }

    /**
     * Add indexes.
     *
     * Ensure an index of the keys in a collection with optional parameters. To set values to descending order, you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be set to 1 (ASC).
     *
     * <code>
     * $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $fields Array of fields to be indexed. Key should be the field name, value should be index type
     * @param array $options Array of options
     *
     * @access public
     * @return object
     */
    public function add_index( $collection = '' , $fields = [ ] , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection specified to add index to' , 500);
        }

        if( empty( $fields ) OR !is_array($fields) ) {
            $this->_show_error('Index could not be created to MongoDB Collection because no keys were specified' , 500);
        }

        foreach( $fields as $field => $value ) {
            if( $value === -1 OR $value === FALSE OR strtolower($value) === 'desc' ) {
                $keys[ $field ] = -1;
            } else {
                $keys[ $field ] = 1;
            }
        }

        if( $this->_dbhandle->{$collection}->ensureIndex($fields , $options) === TRUE ) {
            $this->_clear($collection , 'add_index');

            return $this;
        } else {
            $this->_show_error('An error occurred when trying to add an index to MongoDB Collection' , 500);
        }
    }

    /**
     * Remove indexes.
     *
     * Remove an index of the keys in a collection. To set values to descending order, you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be set to 1 (ASC).
     *
     * <code>
     * $this->mongo_db->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
     * </code>
     *
     * @param string $collection Name of the collection
     * @param array $keys Array of index keys to be removed. Array key should be the field name, the value should be -1
     *
     * @access public
     * @return object
     */
    public function remove_index( $collection = '' , $keys = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection specified to remove index from' , 500);
        }

        if( empty( $keys ) OR !is_array($keys) ) {
            $this->_show_error('Index could not be removed from MongoDB Collection because no keys were specified' , 500);
        }

        if( $this->_dbhandle->{$collection}->deleteIndex($keys) === TRUE ) {
            $this->_clear($collection , 'remove_index');

            return $this;
        } else {
            $this->_show_error('An error occurred when trying to remove an index from MongoDB Collection' , 500);
        }
    }

    /**
     * Remove all indexes
     *
     * Remove all indexes from a collection.
     *
     * <code>
     * $this->mongo_db->remove_all_index($collection);
     * </code>
     *
     * @param string $collection Name of the collection
     *
     * @access public
     * @return array|object
     */
    public function remove_all_indexes( $collection = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection specified to remove all indexes from' , 500);
        }
        $this->_dbhandle->{$collection}->deleteIndexes();
        $this->_clear($collection , 'remove_all_indexes');

        return $this;
    }

    /**
     * List indexes.
     *
     * Lists all indexes in a collection.
     *
     * <code>
     * $this->mongo_db->list_indexes($collection);
     * </code>
     *
     * @param string $collection Name of the collection
     *
     * @access public
     * @return array|object
     */
    public function list_indexes( $collection = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection specified to remove all indexes from' , 500);
        }

        return $this->_dbhandle->{$collection}->getIndexInfo();
    }

    /**
     * Mongo Date.
     *
     * Create new MongoDate object from current time or pass timestamp to create mongodate.
     *
     * <code>
     * $this->mongo_db->date($timestamp);
     * </code>
     *
     * @param int|null $timestamp A unix timestamp (or NULL to return a MongoDate relative to time()
     *
     * @access public
     * @return array|object
     */
    public function date( $timestamp = NULL )
    {
        if( $timestamp === NULL ) {
            return new MongoDate();
        }

        return new MongoDate($timestamp);
    }

    /**
     * Get database reference
     *
     * Get mongo object from database reference using MongoDBRef
     *
     * <code>
     * $this->mongo_db->get_dbref($object);
     * </code>
     *
     * @param object $object A dbref object
     *
     * @access public
     * @return array|object
     */
    public function get_dbref( $object )
    {
        if( empty( $object ) || !isset( $object ) ) {
            $this->_show_error('To use MongoDBRef::get() ala get_dbref() you must pass a valid reference object' , 500);
        }

        return MongoDBRef::get($this->_dbhandle , $object);
    }

    /**
     * Create database reference.
     *
     * Create mongo dbref object to store later
     *
     * <code>
     * $ref = $this->mongo_db->create_dbref($collection, $id);
     * </code>
     *
     * @param string $collection Collection name
     * @param string $field Field name
     * @param string $db_name Database name
     *
     * @access public
     * @return array|object
     */
    public function create_dbref( $collection = '' , $field = '' , $db_name = '' )
    {
        if( empty( $collection ) ) {
            $this->_show_error('In order to retrieve documents from MongoDB, a collection name must be passed' , 500);
        }

        if( empty( $field ) || !isset( $field ) ) {
            $this->_show_error('To use MongoDBRef::create() ala create_dbref() you must pass a valid field id of the object which to link' , 500);
        }

        $database = ( $db_name !== '' ) ? $db_name : $this->_dbhandle;

        return MongoDBRef::create($collection , $field , $database);
    }

    /**
     * @param string $collection
     * @param array $command
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public function aggregate( $collection = '' , $command = [ ] , $options = [ ] )
    {
        if( empty( $collection ) ) {
            $this->_show_error('No Mongo collection specified to remove all indexes from' , 500);
        }

        if( empty( $command ) ) {
            $this->_show_error('Option cannot be empty' , 500);
        }

        return $this->_dbhandle->{$collection}->aggregate($command , $options);
    }

    /**
     * last_query.
     *
     * Return the last query
     *
     * <code>
     * print_r($this->mongo_db->last_query());
     * </code>
     *
     * @access public
     * @return array
     */
    public function last_query()
    {
        return $this->_query_log;
    }

    /**
     * 获取最后一条错误信息
     * @return string
     */
    public function last_error()
    {
        $error = $this->_last_error;
        $this->_last_error = '';

        return $error;
    }

    public function getDb()
    {
        return $this->_dbhandle;
    }

    public function close()
    {
        return $this->_connection->close(TRUE);
    }

    /**
     * Connect to MongoDB
     *
     * Establish a connection to MongoDB using the connection string generated in
     * the connection_string() method.  If 'mongo_persist_key' was set to true in the
     * config file, establish a persistent connection.  We allow for only the 'persist'
     * option to be set because we want to establish a connection immediately.
     *
     * @return object
     * @access private
     */
    private function _connect()
    {
        $options = [ ];

        if( $this->_persist === TRUE ) {
            $options[ 'persist' ] = $this->_persist_key;
        }

        if( $this->_replica_set !== FALSE ) {
            $options[ 'replicaSet' ] = $this->_replica_set;
        }

        try {
            $this->_connection = new \MongoClient($this->_connection_string , $options);
            $this->_dbhandle = $this->_connection->{$this->_dbname};

            return $this;
        } catch ( \MongoConnectionException $exception ) {
            if( $this->_config_data[ 'mongo_suppress_connect_error' ] ) {
                $this->_show_error('Unable to connect to MongoDB' , 500);
            } else {
                $this->_show_error('Unable to connect to MongoDB: ' . $exception->getMessage() , 500);
            }
        }
    }

    /**
     * Build connectiong string.
     *
     * @access private
     * @return void
     */
    private function _connection_string()
    {
        $this->_host = trim($this->_config_data[ 'mongo_hostbase' ]);
        $this->_user = trim($this->_config_data[ 'mongo_username' ]);
        $this->_pass = trim($this->_config_data[ 'mongo_password' ]);
        $this->_dbname = trim($this->_config_data[ 'mongo_database' ]);
        $this->_persist = $this->_config_data[ 'mongo_persist' ];
        $this->_persist_key = trim($this->_config_data[ 'mongo_persist_key' ]);
        $this->_replica_set = $this->_config_data[ 'mongo_replica_set' ];
        $this->_query_safety = trim($this->_config_data[ 'mongo_query_safety' ]);
        $dbhostflag = (bool)$this->_config_data[ 'mongo_host_db_flag' ];

        $connection_string = 'mongodb://';

        if( empty( $this->_host ) ) {
            $this->_show_error('The Host must be set to connect to MongoDB' , 500);
        }

        if( empty( $this->_dbname ) ) {
            $this->_show_error('The database name must be set to connect to MongoDB' , 500);
        }

        if( !empty( $this->_user ) AND !empty( $this->_pass ) ) {
            $connection_string .= $this->_user . ':' . $this->_pass . '@';
        }

        $connection_string .= $this->_host;

        if( $dbhostflag === TRUE ) {
            $this->_connection_string = trim($connection_string) . '/' . $this->_dbname;
        } else {
            $this->_connection_string = trim($connection_string);
        }
    }

    /**
     * Reset the class variables to default settings.
     *
     * @access private
     * @return void
     */
    private function _clear( $collection , $action )
    {
        $this->_query_log = [
            'collection' => $collection ,
            'action'     => $action ,
            'wheres'     => $this->wheres ,
            'updates'    => $this->updates ,
            'selects'    => $this->_selects ,
            'limit'      => $this->_limit ,
            'offset'     => $this->_offset ,
            'sorts'      => $this->_sorts
        ];

        $this->_selects = [ ];
        $this->updates = [ ];
        $this->wheres = [ ];
        $this->_limit = 999999;
        $this->_offset = 0;
        $this->_sorts = [ ];
    }

    /**
     * Where initializer.
     *
     * Prepares parameters for insertion in $wheres array().
     *
     * @param string $field Field name
     *
     * @access private
     * @return void
     */
    private function _where_init( $field )
    {
        if( !isset( $this->wheres[ $field ] ) ) {
            $this->wheres[ $field ] = [ ];
        }
    }

    /**
     * Update initializer.
     *
     * Prepares parameters for insertion in $updates array().
     *
     * @param string $field Field name
     *
     * @access private
     * @return void
     */
    private function _update_init( $field = '' )
    {
        if( !isset( $this->updates[ $field ] ) ) {
            $this->updates[ $field ] = [ ];
        }
    }

    /**
     * Show error.
     *
     * If using CodeIgniter use show_error otherwise throw an exception.
     *
     * @param string $error_message Error message
     * @param int $response_code Response code
     *
     * @access private
     * @return void
     */
    private function _show_error( $error_message = '' , $response_code = 500 )
    {

        $this->_last_error = $error_message;

        return;
        if( !function_exists('show_error') ) // If we're not using CodeIgniter throw a normal exception
        {
            throw new Exception ($error_message);
        } else // CodeIgniter show_error() function
        {
            show_error($error_message , $response_code);
        }
    }

}

// End of file mongo_db.php