<?php
class DB_MySQLi
    {
        private $link=false;

        public function __construct($dsn)
            {
                $parameters=parse_url($dsn);
                /*
                        if(key_exists('port',$parameters))
                        $port=':'.$parameters['port'];
                        else
                        $port=':3306';
                */
                if ($parameters['host']=='localhost' or $parameters['host']=='127.0.0.1')
                    {
                        $link=mysqli_connect($parameters['host'], $parameters['user'], $parameters['pass']);
                    }
                else
                    {
                        if (preg_match('~^5\.3\.~', phpversion())) {
                            $link=mysqli_connect('p:'.$parameters['host'], $parameters['user'], $parameters['pass']);
                        }
                        else
                            {
                                $link=mysqli_connect($parameters['host'], $parameters['user'], $parameters['pass']);
                            }
                    }

                if (mysqli_ping($link))
                    {
                        $dbname=substr($parameters['path'], 1);
                        if (mysqli_select_db($link, $dbname)) {
                            $this->link=$link;
                        }
                        else
                            {
                                throw new DB_exception('Database "'.$dbname.'" doesn\'t exists!');
                            }
                    }
                else
                    {
                        throw new DB_exception('Cannot connect to database!');
                    }
            }

        public function query($mysql_query, $obj=false, $parameters=array())
            {
                if ($obj)
                    {
                        if (!class_exists($obj))
                            {
                                $obj='stdClass';
                            }
                    }

                $now=microtime(true);

                $mysql_query=trim($mysql_query);
                if ($this->link)
                    {
                        $res=mysqli_query($this->link, $mysql_query);
                        if (gettype($res)!='boolean')
                            {
                                if (get_class($res)=='mysqli_result')
                                    {
                                        $ans=array();

                                        if ($obj)
                                            {
                                                while ($a=mysqli_fetch_object($res, $obj, $parameters))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }
                                        else
                                            {
                                                while ($a=mysqli_fetch_assoc($res))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }

                                        $type='SELECT';
                                        $rows=mysqli_num_rows($res);
                                    }
                                else
                                    {
                                        $type='UNKNOWN';
                                        $ans=$res;
                                        $rows=0;
                                    }

                            }
                        else
                            {
                                $ans=$res;
                                if (preg_match('~^insert~i', $mysql_query)) //Create
                                    {
                                        $type='INSERT';
                                        $rows=mysqli_affected_rows($this->link);
                                    }
                                elseif (preg_match('~^update~i', $mysql_query)) //Edit
                                    {
                                        $type='UPDATE';
                                        $rows=mysqli_affected_rows($this->link);
                                    }
                                elseif (preg_match('~^delete~i', $mysql_query)) //DELETE
                                    {
                                        $type='DELETE';
                                        $rows=mysqli_affected_rows($this->link);
                                    }
                                else
                                    {
                                        $type='UNKNOWN';
                                        $rows=false;
                                    }

                            }

                        $exectime=microtime(true)-$now;


                        return array('type'          =>$type,
                                     'query'         =>$mysql_query,
                                     'result'        =>$ans,
                                     'time'          =>round((1000*$exectime), 2),
                                     'status'        =>(mysqli_error($this->link)=="") ? 'OK' : 'MySQL error: '.mysqli_error($this->link),
                                     'affected_rows' =>$rows);
                    }
                else {
                    return false;
                }
            }


        public function filter($string_to_escape)
            {
                return (mysqli_real_escape_string($string_to_escape, $this->link));
            }

        public function getLastInsertId()
            {
                return mysqli_insert_id($this->link);
            }

        public function getError()
            {
                return (mysqli_error($this->link)!="") ? mysqli_error($this->link) : false;
            }

        public function __destruct()
            {
                mysqli_close($this->link);
            }
    }
