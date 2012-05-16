MultiDBq
========

MultiDBq - singleton-styled MySQL wrapper class with build in profiler,
connections to multiple hosts and easy configuration

Main Features
========

1. Singleton asssembly of class  - low memory consumption, easy to access class from every scope of view
2. Easy to set multiple mysql-links
3. Buildin profiler - link,query, execution time et cetera...
4. Class automaticaly chooses the best mysql database driver from installed extensions (at least best in my opinion :-))

Sample
========

<?php
require 'lib/DB.php';

DB::addLink('main','mysql://username:SecretPassword@localhost/DB_name1');

DB::addLink('log','mysql://username:SecretPassword@mysql_host2.example.com/DB_name2');

/*
a lot of code
*/

function auth($username,$password)
{
DB::setLink('main');

$user=DB::q('SELECT * FROM `users` WHERE `name`="'.DB::f($_POST['Login']).'" and `password`="'.DB::f($_POST['Login']).'"');

if($user)

    {

    DB::update('users',array('online'=>true),'`id`="'.$user['id'].'"');

    DB::setLink('log');

    DB::insert('iplog',array('login'=>$user['Login'],'timestamp'=>time(),'result'=>'ok'));

    DB::setLink('main');

    return $user;

    }

else

    {

    return false;

    }

}

/*
a lot of code
*/
$users_online=DB::q('SELECT * FROM `users` WHERE `online`=1');

foreach($users_online as $user_online)

    {

    echo '<p>User'.$user_online['Login'].' is online!</p>';

    }


if(defined('DEBUG')) print_r(DB::s());

?>