<?php
// Include router class
require __DIR__."/vendor/autoload.php";

use Classes\Database;
use Classes\Mail;
use Classes\Route;
use Classes\User;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$loader = new FilesystemLoader("templates");
$twig = new Environment($loader,['debug' => true,
    // ...
]); // ,['cache' => 'compilation_cache',]
$twig->addExtension(new DebugExtension());
$db = new Database();

$menu = [];
$seiten = $db->index('seiten');

for($i=0; $i<count($seiten);$i++) {
    $menu[$i]['titel'] = $seiten[$i]['titel'];
    $menu[$i]['schnecke'] = $seiten[$i]['schnecke'];
}

Route::add('/admin/login', function () use ($db) {
    echo $db->create('users','/admin/login', ['username','password']);
});

Route::add('/admin/login', function () use ($db) {
    $login = [];
    foreach ($_POST as $key => $value) {
        $login[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    }
    User::login($login);

}, 'post');

Route::add('/admin/register', function () use ($db) {
    echo $db->create('users','/admin/register', ['name','nachname','username','email','password']);
});

Route::add('/admin/register', function () use ($db) {
    $user =[];
    foreach ($_POST as $key => $value) {
        if ($key == 'password') $user[$key] = password_hash($value,PASSWORD_BCRYPT); else
            $user[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    }
    echo $db->save('users',$user, ['name','nachname','username','email','password']);
}, 'post');

Route::add('/admin', function () use ($db) {
   $tablesList = $db->query('show tables');
   $tablesList = $tablesList->fetchAll();
   $list = [];
   foreach ($tablesList as $table) {
       foreach ($table as $key => $value) {
           $list[] = $value;
       }
   }
});

Route::add('/kontakt', function (){
    $mailSend = new Mail();
    $result = $mailSend->mailSend('aurel.dragut@gmail.com','Aurel Dragut','Mail from Contact Form');
    header("Location:/kontakt?message=$result");
}, 'post');

Route::add('/posts/([0-9a-z\-]*)', function($page) use ($twig, $db, $menu) {

    $beitrag = $db->show('beitraege', $page);

    $beitrag['menu'] = $menu;

    echo $twig->render('page.html.twig', ['page' => $beitrag]);
});

// Accept only numbers as parameter. Other characters will result in a 404 error
Route::add('/([0-9a-z\-]*)', function($page) use ($twig, $db, $menu) {

    $page = $db->show('seiten', $page);

    $page['menu'] = $menu;
    if ($page['schnecke'] == 'blog') {
        $beitraege = $db->index('beitraege');
        $page['beitraege'] = $beitraege;
    }

    if ($page['schnecke'] == '') {
        $page['dienstleistungen'] = $db->index('dienstleistungen');
        $page['portfolio'] = $db->index('portfolio');
    }

    foreach ($_GET as $key => $value) {
        $page['get_'.$key] = $value;
    }

    echo $twig->render('page.html.twig', ['page' => $page]);
});

Route::run('/');
