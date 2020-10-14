<?php


namespace Classes;


use Dotenv\Dotenv;
use PDO;
use PDOException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * class Database
 *
 * @author Aurel Dragut <aurel.dragut@gmail.com>
 * @package namespace Classes;
 */
class Database
{
    private String $host;
    private String $dbname;
    private String $dsn;
    private String $user;
    private String $pass;
    private array $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];

    public $pdo;
    public $twig;
    
    public function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();
        $loader = new FilesystemLoader("templates");
        $this->twig = new Environment($loader,['debug' => true,]);

        $this->host = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->user = $_ENV['DB_USER'];
        $this->pass = $_ENV['DB_PASS'];
        $this->dsn = "mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'].";charset=utf8mb4";
        try {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function save($table, $object, $fillable) {

        $fields = [];
        foreach ($fillable as $key => $value) {
            $fields[$key] = $value;
        }

        $fieldsArray = array_map(function ($m) { return ':'.$m; }, $fields);

        $fieldsList = implode(',',$fieldsArray);

        //generate a prepare query with those fields
        $query = "INSERT INTO `$table` (".str_replace(':','',$fieldsList).") VALUES ($fieldsList)";

        //run query
        try {
            $stmt = $this->pdo->prepare($query);
        } catch (PDOException $e) {
            echo "Prepare failed: " . $e->getMessage();
        }

        //bind items to query
        foreach ($object as $key => $value) {
            if ($key == 'confirm_password') continue;
            try {
                $stmt->bindValue(':' . $key, $value);
            } catch (PDOException $e) {
                echo "Binding parameters failed: " . $e->getMessage();
            }
        }

        //testing execution
        try {
            $stmt->execute();
        } catch (PDOException $e){
            return "Execute failed: " . $e->getMessage();
        }
    }

    public function update($table, $id, $object, $fillable) {

        $fieldValues = [];
        foreach ($fillable as $key => $value) {
            $fieldValues[$value] = $object[$value];
        }
        $fields = '';
        foreach ($fieldValues as $key => $value) {
            $fields .= $key.'='.$value.', ';
        }
        $fields = rtrim($fields,', ');

        //generate a prepare query with those fields
        $query = "UPDATE `$table` set $fields WHERE `id` = '$id'";

        //run query
        try {
            $stmt = $this->pdo->prepare($query);
        } catch (PDOException $e) {
            echo "Prepare failed: " . $e->getMessage();
        }

        //bind items to query
        foreach ($object as $key => $value) {
            if ($key == 'confirm_password') continue;
            try {
                $stmt->bindValue(':' . $key, $value);
            } catch (PDOException $e) {
                echo "Binding parameters failed: " . $e->getMessage();
            }
        }

        //testing execution
        try {
            $stmt->execute();
        } catch (PDOException $e){
            return "Execute failed: " . $e->getMessage();
        }
    }

    public function query($query) {
        return $this->pdo->query($query);
    }

    public function index($table) {
        //read table fields list
        $tableFields = $this->pdo->query("SELECT * FROM `$table`");
        $fieldsList = $tableFields->fetchAll();

        return $fieldsList;
    }

    public function create($table='seiten', $action='', $fillable = ['titel','schnecke','kopfueberschrift','kopftext','inhalt']) {
        $tableFields = $this->pdo->query("SHOW FIELDS FROM `$table`");
        $fieldsList = $tableFields->fetchAll();

        $fields = [];
        foreach ($fieldsList as $key => $value) {
            if (in_array($value['Field'], $fillable)) $fields[] = $value;
        }
        if ($action == '/admin/register') {
            $fields[] = array( "Field" => "confirm_password", "Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => NULL, "Extra" => "");
        }
        $formular['fields'] = $fields;
        $formular['action'] = $action;
        $this->twig->addExtension(new \Twig\Extension\DebugExtension()); // ,['cache' => 'compilation_cache',]
        return $this->twig->render('parts/formular.html.twig', ['formular' => $formular]);
    }

    public function show($table, $slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM `$table` WHERE `schnecke`=?");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        return $result;
    }
}
