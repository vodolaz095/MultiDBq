<?php
class DB_PDO_MySQL
    {
        private $link=false;

        public function __construct($dsn)
            {
                $parameters=parse_url($dsn);
                /*
                            if (key_exists('port',$parameters))
                                {
                                $port = ':' . $parameters['port'];
                                }
                            else
                                {
                                $port = ':3306';
                                }
                */

                try
                    {
                        $dbname=substr($parameters['path'], 1);
                        $pdo_dsn='mysql:host='.$parameters['host'].';dbname='.$dbname;

                        $db=new PDO($pdo_dsn, $parameters['user'], $parameters['pass']);
                        if ($db)
                            {
                                $this->link=$db;
                            }
                    } catch (PDOException $e)
                    {
                        trigger_error(__FILE__.'>>'.__METHOD__.' error connecting to db!'.$e->getMessage());
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
                        $res=$this->link->query($mysql_query);
                        if ($res and get_class($res)=='PDOStatement')
                            {
                                $ans=true;
                                if (preg_match('~^insert~i', $mysql_query)) //Create
                                    {
                                        $type='INSERT';
                                        $rows=$res->rowCount();

                                    }
                                elseif (preg_match('~^update~i', $mysql_query)) //Edit
                                    {
                                        $type='UPDATE';
                                        $rows=$res->rowCount();

                                    }
                                elseif (preg_match('~^delete~i', $mysql_query)) //DELETE
                                    {
                                        $type='DELETE';
                                        $rows=$res->rowCount();

                                    }
                                else
                                    {
                                        $ans=array();

                                        if ($obj)
                                            {
                                                while ($a=$res->fetchObject($obj, $parameters))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }
                                        else
                                            {
                                                while ($a=$res->fetch(PDO::FETCH_ASSOC))
                                                    {
                                                        $ans[]=$a;
                                                    }
                                            }


                                        $type='SELECT';
                                        $rows=$res->rowCount();
                                    }
                            }
                        else
                            {
                                $type='UNKNOWN';
                                $ans=$res;
                                $rows=false;
                            }

                        $exectime=microtime(true)-$now;

                        return array('type'          =>$type,
                                     'query'         =>$mysql_query,
                                     'result'        =>$ans,
                                     'time'          =>round((1000*$exectime), 2),
                                     'status'        =>($this->link->errorInfo()) ? 'OK' : implode($this->link->errorInfo(), ', '),
                                     'affected_rows' =>$rows);
                    }
                else
                    {
                        return false;
                    }
            }


        public function filter($string_to_escape)
            {
                $tmp=$this->link->quote($string_to_escape);
                $tmp=substr($tmp, 1, strlen($tmp)-2); //убираем кавычку слева, убираем кавычку справа
                return ($tmp);
            }

        public function getLastInsertId()
            {
                return $this->link->lastInsertId();
            }

        public function getError()
            {
                return ($this->link->errorInfo()) ? 'OK' : implode($this->link->errorInfo(), ', ');
            }

        public function __destruct()
            {
                unset($this->link);
            }
    }