<?php
class DB_MySQL
    {
        private $link=false;

        public function __construct($dsn)
            {
                $parameters=parse_url($dsn);
                if (key_exists('port', $parameters)) {
                    $port=':'.$parameters['port'];
                }
                else
                    {
                        $port=':3306';
                    }

                if ($parameters['host']=='localhost' or $parameters['host']=='127.0.0.1')
                    {
                        $link=mysql_connect($parameters['host'].$port, $parameters['user'], $parameters['pass']);
                    }
                else
                    {
                        $link=mysql_pconnect($parameters['host'].$port, $parameters['user'], $parameters['pass']);
                    }

                if (mysql_ping($link))
                    {
                        $dbname=substr($parameters['path'], 1);
                        if (mysql_select_db($dbname, $link)) {
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
                        $res=mysql_query($mysql_query, $this->link);
                        if (gettype($res)!='boolean')
                            {
                                if (gettype($res)=='resource')
                                    {
                                        $ans=array();

                                        if ($obj)
                                            {
                                                while ($a=mysql_fetch_object($res, $obj, $parameters))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }
                                        else
                                            {
                                                while ($a=mysql_fetch_assoc($res))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }

                                        $type='SELECT';
                                        $rows=mysql_num_rows($res);
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
                                        $rows=mysql_affected_rows($this->link);
                                    }
                                elseif (preg_match('~^update~i', $mysql_query)) //Edit
                                    {
                                        $type='UPDATE';
                                        $rows=mysql_affected_rows($this->link);
                                    }
                                elseif (preg_match('~^delete~i', $mysql_query)) //DELETE
                                    {
                                        $type='DELETE';
                                        $rows=mysql_affected_rows($this->link);
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
                                     'status'        =>(mysql_error($this->link)=="") ? 'OK' : 'MySQL error: '.mysql_error($this->link),
                                     'affected_rows' =>$rows);
                    }
                else {
                    return false;
                }
            }


        public function filter($string_to_escape)
            {
                return (mysql_real_escape_string($string_to_escape, $this->link));
            }

        public function getLastInsertId()
            {
                return mysql_insert_id($this->link);
            }

        public function getError()
            {
                return (mysql_error($this->link)!="") ? mysql_error($this->link) : false;
            }

        public function __destruct()
            {
                mysql_close($this->link);
            }
    }