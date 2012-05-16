MultiDBq
========

MultiDBq - singleton-styled MySQL wrapper class with build in profiler,
connections to multiple hosts and easy configuration

### Main Features

1. Singleton asssembly of class  - low memory consumption, easy to access class from every scope of view
2. Easy to set multiple mysql-links
3. Buildin profiler - link,query, execution time et cetera...
4. Class automaticaly chooses the best mysql database driver from installed extensions (at least best in my opinion :-))


### Requiraments
PHP > 5.3, php_mysql, php_pdo mods installed... MySQL server
### Sample

```php
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
```

## About

Copyright &copy; 2003-2012 [Anatoly Ostroumov](http://teksi.ru/webdev)

## License

Licensed under the [ISC License](http://www.opensource.org/licenses/ISC).

Copyright (c) 2003-2012 Anatoly Ostroumov <info@fotobase.org>

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.