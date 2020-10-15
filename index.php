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

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$loader = new FilesystemLoader("templates");
$twig = new Environment($loader,['debug' => true,
    // ...
]); // ,['cache' => 'compilation_cache',]
$twig->addExtension(new DebugExtension());
$twig->addGlobal('session', $_SESSION);
$twig->addGlobal('post', $_SESSION);
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

Route::add('/admin/logout', function () {
    User::logout();
    header('Location:/admin/login');
});

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

Route::add('/admin', function () use ($db, $twig) {
    if (!$_SESSION['logged_in']) header('Location:/admin/login');
    $tablesList = $db->query('show tables');
    $tablesList = $tablesList->fetchAll();
    $list = [];
    $i=0;
    foreach ($tablesList as $key => $value) {

        foreach ($value as $key => $value) {
            $list[$i]['item'] = $value;
            $itemsCount = $db->index($value);
            $list[$i]['count'] = count($itemsCount);
        }
        $i++;
    }

    $page = [];
    $page['menu'] = $list;
    echo $twig->render('admin/page.html.twig', ['page' => $page]);
});

Route::add('/admin/([0-9a-z\-]*)/([0-9]*)/delete', function($page, $id) use ($twig, $db, $menu) {
    $db->delete($page, $id);
    header("Location:/admin/$page");
});
Route::add('/admin/([0-9a-z\-]*)/create', function($page) use ($twig, $db, $menu) {
    if (!$_SESSION['logged_in']) header('Location:/admin/login');
    $tablesList = $db->query('show tables');
    $tablesList = $tablesList->fetchAll();

    $list = [];
    $i=0;
    foreach ($tablesList as $key => $value) {

        foreach ($value as $key => $value) {
            $list[$i]['item'] = $value;
            $itemsCount = $db->index($value);
            $list[$i]['count'] = count($itemsCount);
        }
        $i++;
    }

    $items = [];
    $pageData = $db->index($page);

    foreach($pageData as $value) {
        $tableHeaders = array_keys($value);
    }

    for($i=0;$i<count($pageData);$i++) {
        $items[$i] = $pageData[$i];
    }

    $pageData['headers'] = $tableHeaders;
    $pageData['items'] = $items;

    switch ($page) {
        case 'beitraege':
            $fillable = ['titel','schnecke','inhalt','bild'];
            break;
        case 'dienstleistungen':
            $fillable = ['titel','inhalt'];
            break;
        case 'portfolio':
            $fillable = ['titel','url','inhalt','bild'];
            break;
        case 'seiten':
            $fillable = ['titel','schnecke','kopfueberschrift','kopftext','inhalt'];
            break;
        case 'users':
            $fillable = ['name','nachname','username','email','password'];
            break;
        default:
            break;
    }

    foreach ($_GET as $key => $value) {
        $page['get_'.$key] = $value;
    }
    $pageData['createIcon'] = '<a class="nav-link create" href="/admin/'.$page.'/create" alt=""><img src="/public/img/icons/create.jpg" width="32" /></a>';
    $pageData['menu'] = $list;
    echo $db->create($page,"/admin/$page/save", $fillable);
});

Route::add('/admin/([0-9a-z\-]*)/([0-9]*)/edit', function($page, $id) use ($twig, $db, $menu) {
    if (!$_SESSION['logged_in']) header('Location:/admin/login');
    $tablesList = $db->query('show tables');
    $tablesList = $tablesList->fetchAll();

    $list = [];
    $i=0;
    foreach ($tablesList as $key => $value) {

        foreach ($value as $key => $value) {
            $list[$i]['item'] = $value;
            $itemsCount = $db->index($value);
            $list[$i]['count'] = count($itemsCount);
        }
        $i++;
    }

    $items = [];
    $pageData = $db->index($page);

    foreach($pageData as $value) {
        $tableHeaders = array_keys($value);
    }

    for($i=0;$i<count($pageData);$i++) {
        $items[$i] = $pageData[$i];
    }

    $pageData['headers'] = $tableHeaders;
    $pageData['items'] = $items;

    switch ($page) {
        case 'beitraege':
            $fillable = ['titel','schnecke','inhalt','bild'];
            break;
        case 'dienstleistungen':
            $fillable = ['titel','inhalt'];
            break;
        case 'portfolio':
            $fillable = ['titel','url','inhalt','bild'];
            break;
        case 'seiten':
            $fillable = ['titel','schnecke','kopfueberschrift','kopftext','inhalt'];
            break;
        case 'users':
            $fillable = ['name','nachname','username','email','password'];
            break;
        default:
            break;
    }

    foreach ($_GET as $key => $value) {
        $page['get_'.$key] = $value;
    }
    $pageData['createIcon'] = '<a class="nav-link create" href="/admin/'.$page.'/create" alt=""><img src="/public/img/icons/create.jpg" width="32" /></a>';
    $pageData['menu'] = $list;
    echo $db->edit($page, $id, "/admin/$page/$id/update", $fillable);
});

Route::add('/admin/([0-9a-z\-]*)/save', function($page) use ($twig, $db, $menu) {
    $$page =[];

    foreach ($_POST as $key => $value) {
        if ($key == 'schnecke') $$page[$key] = str_replace(' ','-',strtolower($$page['titel'])); else
        $$page[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    }
    switch ($page) {
        case 'beitraege':
            $fillable = ['titel','schnecke','inhalt','bild'];
            break;
        case 'dienstleistungen':
            $fillable = ['titel','inhalt'];
            break;
        case 'portfolio':
            $fillable = ['titel','url','bild','inhalt'];
            break;
        case 'seiten':
            $fillable = ['titel','schnecke','kopfueberschrift','kopftext','inhalt'];
            break;
        case 'users':
            $fillable = ['name','nachname','username','email','password'];
            break;
        default:
            break;
    }
    foreach ($_FILES as $key => $value) {
        $$page[$key] = $value;
    }

    $db->save($page, $$page, $fillable);
    header("Location:/admin/$page");
}, 'post');

Route::add('/admin/([0-9a-z\-]*)/([0-9]*)/update', function($page,$id) use ($twig, $db, $menu) {
    $$page =[];

    foreach ($_POST as $key => $value) {
        if ($key == 'schnecke') $$page[$key] = str_replace(' ','-',strtolower($$page['titel'])); else
            $$page[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    }
    switch ($page) {
        case 'beitraege':
            $fillable = ['titel','schnecke','inhalt','bild'];
            break;
        case 'dienstleistungen':
            $fillable = ['titel','inhalt'];
            break;
        case 'portfolio':
            $fillable = ['titel','url','bild','inhalt'];
            break;
        case 'seiten':
            $fillable = ['titel','schnecke','kopfueberschrift','kopftext','inhalt'];
            break;
        case 'users':
            $fillable = ['name','nachname','username','email','password'];
            break;
        default:
            break;
    }
    foreach ($_FILES as $key => $value) {
        $$page[$key] = $value;
    }

    $db->update($page,$id,$$page, $fillable);
    header("Location:/admin/$page");
}, 'post');

// Accept only numbers as parameter. Other characters will result in a 404 error
Route::add('/admin/([0-9a-z\-]*)', function($page) use ($twig, $db, $menu, $logged_in) {
    if (!$_SESSION['logged_in']) header('Location:/admin/login');
	$tablesList = $db->query('show tables');
	$tablesList = $tablesList->fetchAll();

    $list = [];
    $i=0;
    foreach ($tablesList as $key => $value) {

        foreach ($value as $key => $value) {
            $list[$i]['item'] = $value;
            $itemsCount = $db->index($value);
            $list[$i]['count'] = count($itemsCount);
        }
        $i++;
    }

	$items = [];
	$pageData = $db->index($page);

	foreach($pageData as $value) {
		$tableHeaders = array_keys($value);
	}

	for($i=0;$i<count($pageData);$i++) {
		$items[$i] = $pageData[$i];
	}

	$tableItems = $items;

	$pageData['headers'] = $tableHeaders;
	$pageData['items'] = $tableItems;

	foreach ($_GET as $key => $value) {
		$page['get_'.$key] = $value;
	}
	$pageData['createIcon'] = '<a class="nav-link create" href="/admin/'.$page.'/create" alt=""><img src="/public/img/icons/create.jpg" width="32" /></a>';
	$pageData['menu'] = $list;
	$pageData['pageTable'] = $page;
	$pageData['logged_in'] = $logged_in;
	echo $twig->render('admin/page.html.twig', ['page' => $pageData]);
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

    if ($page == '') $page = 'home';
    $page = $db->query("SELECT id from `seiten` where `schnecke` = '$page'");
    $page = $page->fetch();
    $page = $db->show('seiten', $page['id']);

    $page['menu'] = $menu;
    if ($page['schnecke'] == 'blog') {
        $beitraege = $db->index('beitraege');
        $page['beitraege'] = $beitraege;
    }

    if ($page['schnecke'] == 'home') {
        $page['dienstleistungen'] = $db->index('dienstleistungen');
        $page['portfolio'] = $db->index('portfolio');
    }

    foreach ($_GET as $key => $value) {
        $page['get_'.$key] = $value;
    }

    echo $twig->render('page.html.twig', ['page' => $page]);
});

Route::run('/');
