<?php
class DB_exception extends Exception
    {
    }

class DB
    {
        protected static $instance;
        private $links=array();
        private $active_link;
        private $stats=array();
        private $driver;


        private static function init()
            {
                if (is_null(self::$instance))
                    {
                        self::$instance=new DB();
                    }
                return self::$instance;
            }

        private function __construct()
            {

               /* if (extension_loaded('pdo_mysql'))
                    {
                        $this->driver='PDO';
                        require_once 'dbdrv/pdo_mysql.php';
                    }
                else*/if (extension_loaded('mysqli'))
                    {
                        $this->driver='MySQLi';
                        require_once 'dbdrv/mysqli.php';
                    }
                elseif (extension_loaded('mysql'))
                    {
                        require_once 'dbdrv/mysql.php';
                        $this->driver='MySQL';
                    }
                else
                    {
                        throw new DB_exception('Install PHP extensions for MySQL interaction!');
                    }
            }


    /**
     * This function adds a new link to database connections pool
     * @static
     * @param string $link_name - name of the new link to be created
     * @param string $dsn - DSN line for connection to database - mysql://username:SecretPassword@mysql_host1/DB_name1
     * @return bool - true on success
     * @throws DB_exception
     *
     * For example, we want to set 2 databases link, we can do it in this way
     * <code>
     * DB::addLink('main','mysql://username:SecretPassword@mysql_host1/DB_name1');
     * DB::addLink('archive','mysql://username:SecretPassword@mysql_host2/DB_name2');
     * </code>
     *
     * notice - after adding link this link becomes active
     * @see DB::setLink($link_name);
     */
        static public function addLink($link_name, $dsn)
            {
                $db=DB::init();

                if ($parameters=parse_url($dsn) and $link_name)
                    {
                        if ($parameters['scheme']=='mysql')
                            {
                                if ($db->driver=='PDO')
                                    {
                                        if ($link=new DB_PDO_MySQL($dsn))
                                            {
                                                $db->links[$link_name]=$link;
                                                $db->active_link=$link_name;
                                            }
                                        else
                                            {
                                                $db->links[$link_name]=false;
                                                trigger_error('Unable to establish link "'.$link_name.'"', E_USER_NOTICE);
                                            }
                                    }
                                elseif ($db->driver=='MySQLi')
                                    {
                                        if ($link=new DB_MySQLi($dsn))
                                            {
                                                $db->links[$link_name]=$link;
                                                $db->active_link=$link_name;
                                            }
                                        else
                                            {
                                                $db->links[$link_name]=false;
                                                trigger_error('Unable to establish link "'.$link_name.'"', E_USER_NOTICE);
                                            }

                                    }
                                elseif ($db->driver=='MySQL')
                                    {
                                        if ($link=new DB_MySQL($dsn))
                                            {
                                                $db->links[$link_name]=$link;
                                                $db->active_link=$link_name;
                                            }
                                        else
                                            {
                                                $db->links[$link_name]=false;
                                                trigger_error('Unable to establish link "'.$link_name.'"', E_USER_NOTICE);
                                            }
                                    }
                                else
                                    {
                                        throw new DB_exception('Install PHP extensions for MySQL interaction!');
                                    }
                            }
                    }
                else
                    {
                        throw new DB_exception('Wrong dsn or link name! $dsn format shall be like mysql://user:pwd@mysql_host/database_name  ');
                    }

                return true;
            }


        static public function addBackupLink($dsn)
            {
                DB::addLink('backup',$dsn);
            }
    /**
     * Function sets one of the added MySQL links to be active and be used for sending queries.
     * @static
     * @param $link_name
     * @throws DB_exception
     */
        static public function setLink($link_name)
            {
                $db=DB::init();
                if (array_key_exists($link_name, $db->links))
                    {
                        $db->active_link=$link_name;
                    }
                else
                    {
                        throw new DB_exception('Link with name "'.$link_name.'" doesn\'t exists!');
                    }
            }


    /**
     * Function returns the name of active database link used, or false if link is not setted
     * @static
     * @return bool
     */
        static public function getLinkName()
            {
                $db=DB::init();
                return ($db->active_link) ? $db->active_link : false;
            }



    /**
     * Function executes MySQL query on the active database link - @see DB::setLink($link_name);
     * @static
     * @param string $mysql_query - raw MySQL query to be executed - <i>protection from mysql injections HAS TO BE applied! - @see DB::f($string_to_escape)</i>
     * @param null $fetch_as_object - if you want to fetch result as object input there a name of object class
     * @param array $object_parameters - parameters needed to be setted to object to be created - @see mysql_fetch_object() at @link(http://php.net/manual/en/function.mysql-fetch-object.php) for details
     * @return bool - on update/insert/delete queries
     * @return array/array of objects - on select
     *
     *
     */

        static public function q($mysql_query, $fetch_as_object=null, $object_parameters=array())
            {

                $db=DB::init();
                if ($db->links[$db->active_link])
                    {
                        $ans=$db->links[$db->active_link]->query($mysql_query, $fetch_as_object, $object_parameters);
                        $db->stats[]=array('Link'          =>$db->active_link,
                                           'Query'         =>$ans['query'],
                                           'Type'          =>$ans['type'],
                                           'Time'          =>$ans['time'],
                                           'Status'        =>$ans['status'],
                                           'Affected_rows' =>$ans['affected_rows']);

                        return $ans['result'];
                    }
                else
                    {

                        $db->stats[]=array('Link'          =>$db->active_link,
                                           'Query'         =>$mysql_query,
                                           'Type'          =>'No connection to database!',
                                           'Time'          =>0,
                                           'Status'        =>'No connection to database!',
                                           'Affected_rows' =>0);

                        trigger_error('Unable to establish link "'.$db->active_link.'"', E_USER_NOTICE);
                        return false;
                    }

            }

    /**
     * Function escapes string from special characters witch can cause MySQL injections
     * @static
     * @param $string_to_escape
     * @return mixed
     *
     * @link http://php.net/manual/en/function.mysql-real-escape-string.php
     */
        static public function f($string_to_escape)
            {
                $db=DB::init();
                return trim($db->links[$db->active_link]->filter($string_to_escape));
            }


    /**
     * Insert data into table
     * @static
     * @param string $table_name - the name of a table, where we insert data
     * @param array $associated_array_of_values - values to insert
     * @return boolean - true on success, false on errors
     */
        public static function insert($table_name, $associated_array_of_values)
            {

                $columns='`'.implode('`,`', array_keys($associated_array_of_values)).'`';
                $values=array();
                foreach ($associated_array_of_values as $value)
                    {
                        $values[]=DB::f($value);
                    }
                $values='"'.implode('","', $values).'"';
                $q='INSERT INTO `'.$table_name.'`('.$columns.') VALUES ('.$values.')';
                $current_link=DB::getLinkName();
                $DB=DB::init();
                $a=$DB->q($q);
//                print_r(array_keys($DB->links));
//                die();
                if(in_array('backup',array_keys($DB->links)))
                {
                    $DB->setLink('backup');
                    $DB->q($q);
                    $DB->setLink($current_link);
                }

                return $a;
            }

    /**
     * Update data in a table
     * @static
     * @param string $table_name
     * @param array $assosiated_array_of_values - values to be updated
     * @param string $string_where - where condition
     * @return bool
     */
        public static function update($table_name, array $assosiated_array_of_values, $string_where)
            {
                $columns=array_keys($assosiated_array_of_values);
                $vals=array();
                foreach ($columns as $column)
                    {
                        $vals[]='`'.$column.'`="'.DB::f($assosiated_array_of_values[$column]).'"';
                    }
                $values=implode(',', $vals);
                $q='UPDATE `'.$table_name.'` SET '.$values.' WHERE '.$string_where;

                $current_link=DB::getLinkName();
                $DB=DB::init();
                $a=$DB->q($q);
                if(in_array('backup',array_keys($DB->links)))
                {
                    $DB->setLink('backup');
                    $DB->q($q);
                    $DB->setLink($current_link);
                }


                return $a;
            }


    /**
     * Returns the last id, which was setted due to autoincrement feature of table
     * @static
     * @return integer on null
     */
        static public function getLastInsertId()
            {
                $db=DB::init();
                return $db->links[$db->active_link]->getLastInsertId();
            }

    /**
     * Shows statistical inforamion of all queries witch was executed from the start of script execution
     * @static
     * @return mixed
     */
        static public function s()
            {
                $db=DB::init();
                return $db->stats;
            }

    /**
     * Returns the current driver
     * @static
     * @return string 'MySQL' or 'MySQLi' or 'PDO'
     */
        static public function getDriver()
            {
                $db=DB::init();
                return $db->driver;
            }
    }


