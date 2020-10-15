<?php


namespace Classes;


use Dotenv\Dotenv;
use PDO;
use PDOException;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * class Database
 *
 * @author Aurel Dragut <aurel.dragut@gmail.com>
 * @package namespace Classes;
 */
class Database
{
	private string $host;
	private string $dbname;
	private string $dsn;
	private string $user;
	private string $pass;
	private array $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];

	public $pdo;
	public $twig;

	public function __construct()
	{
		//Laden der Konfigurationsdatei
		$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
		$dotenv->load();

		//Template-Engine-Auslastung
		$loader = new FilesystemLoader("templates");
		$this->twig = new Environment($loader, ['debug' => true,]);

		//pdo-Initialisierung
		$this->host = $_ENV['DB_HOST'];
		$this->dbname = $_ENV['DB_NAME'];
		$this->user = $_ENV['DB_USER'];
		$this->pass = $_ENV['DB_PASS'];
		$this->dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
		try {
			$this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), (int)$e->getCode());
		}
	}

	//Einträge in der Datenbank speichern
	public function save($table, $object, $fillable)
	{
		$fieldsList = implode(',', $fillable);
		$fieldsValue = [];
		foreach ($object as $key => $value) {
			if ($key == 'confirm_password') continue;
			if ($key == 'password') $fieldsValue[$key] = password_hash($value, PASSWORD_BCRYPT); else
				if ($key == 'bild') {
					$target_dir = "public/img/uploads/";
					$target_file = $target_dir . basename($_FILES["bild"]["name"]);
					$uploadOk = 1;
					$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
					if (isset($_POST["submit"])) {
						$check = getimagesize($_FILES["bild"]["tmp_name"]);
						if ($check !== false) $uploadOk = 1; else $uploadOk = 0;
					}

// Allow certain file formats
					if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
						&& $imageFileType != "gif") {
						echo "$imageFileType Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
						$uploadOk = 0;
					}

// Check if $uploadOk is set to 0 by an error
					if ($uploadOk == 0) {
						echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
					} else {
						if (!move_uploaded_file($_FILES["bild"]["tmp_name"], $target_file)) {
							echo "Sorry, there was an error uploading your file.";
						}
					}
					$fieldsValue[$key] = '/' . $target_file;
				} else {
					$fieldsValue[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
				}
		}
		if (in_array('schnecke', $fillable)) $fieldsValue['schnecke'] = str_replace(' ', '-', strtolower($fieldsValue['titel']));

		$fieldsValue = array_map(function ($m) {
			return $m = '\'' . $m . '\'';
		}, $fieldsValue);
		$fieldsValue = implode(',', $fieldsValue);

		//generate a prepare query with those fields
		$query = "INSERT INTO `$table` ($fieldsList) VALUES ($fieldsValue)";

		//run query
		try {
			$stmt = $this->pdo->query($query);
		} catch (PDOException $e) {
			return "Prepare failed: " . $e->getMessage();
		}
	}

	public function update($table, $id, $object, $fillable)
	{

		foreach ($object as $key => $value) {
			if ($key == 'confirm_password') continue;
			if ($key == 'password') $fieldsValue[$key] = password_hash($value, PASSWORD_BCRYPT);
			if ($key == 'bild') {
				if ($_FILES["bild"]["size"] > 0) {
					$target_dir = "public/img/uploads/";
					$target_file = $target_dir . basename($_FILES["bild"]["name"]);
					$uploadOk = 1;
					$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Check if image file is a actual image or fake image
					if (isset($_POST["submit"])) {
						$check = getimagesize($_FILES["bild"]["tmp_name"]);
						if ($check !== false) $uploadOk = 1; else $uploadOk = 0;
					}

// Allow certain file formats
					if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
						&& $imageFileType != "gif") {
						echo "$imageFileType Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
						$uploadOk = 0;
					}

// Check if $uploadOk is set to 0 by an error
					if ($uploadOk == 0) {
						echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
					} else {
						if (!move_uploaded_file($_FILES["bild"]["tmp_name"], $target_file)) {
							echo "Sorry, there was an error uploading your file.";
						}
					}
					$fieldsValue[$key] = '/' . $target_file;
				}
			} else {
				$fieldsValue[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
			}
		}
		if (in_array('schnecke', $fillable)) $fieldsValue['schnecke'] = str_replace(' ', '-', strtolower($fieldsValue['titel']));
		$fields = '';
		foreach ($fieldsValue as $key => $value) {
			$fields .= $key . '=\'' . $value . '\', ';
		}
		$fields = rtrim($fields, ', ');

		//generate a prepare query with those fields
		$query = "UPDATE `$table` set $fields WHERE `id` = '$id'";


		//run query
		try {
			$stmt = $this->pdo->query($query);
		} catch (PDOException $e) {
			echo "Query failed: " . $e->getMessage();
		}
	}

	public function query($query)
	{
		return $this->pdo->query($query);
	}

	public function index($table)
	{
		//read table fields list
		$tableFields = $this->pdo->query("SELECT * FROM `$table`");
		$fieldsList = $tableFields->fetchAll();

		return $fieldsList;
	}

	public function create($table = 'seiten', $action = '', $fillable = ['titel', 'schnecke', 'kopfueberschrift', 'kopftext', 'inhalt'])
	{
		$tableFields = $this->pdo->query("SHOW FIELDS FROM `$table`");
		$fieldsList = $tableFields->fetchAll();

		$fields = [];
		foreach ($fieldsList as $key => $value) {
			if (in_array($value['Field'], $fillable)) $fields[] = $value;
		}
		if (in_array($action, array('/admin/register', '/admin/users/save'))) {
			$fields[] = array("Field" => "confirm_password", "Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => NULL, "Extra" => "");
		}
		$formular['fields'] = $fields;
		$formular['action'] = $action;
		$this->twig->addExtension(new DebugExtension()); // ,['cache' => 'compilation_cache',]
		return $this->twig->render('admin/parts/formular.html.twig', ['formular' => $formular]);
	}

	public function edit($table, $id, $action, $fillable)
	{
		$tableFields = $this->pdo->query("SHOW FIELDS FROM `$table`");
		$fieldsList = $tableFields->fetchAll();

		$fields = [];
		foreach ($fieldsList as $key => $value) {
			if (in_array($value['Field'], $fillable)) $fields[] = $value;
		}
		if (in_array($action, array('/admin/register', '/admin/users/save'))) {
			$fields[] = array("Field" => "confirm_password", "Type" => "varchar(255)", "Null" => "NO", "Key" => "", "Default" => NULL, "Extra" => "");
		}

		$object = [];
		$objectData = $this->show($table, $id);
		foreach ($objectData as $key => $value) {
			if (in_array($key, $fillable) && $key !== 'bild') $object[$key] = $value;
		}

		$formular['fields'] = $fields;
		$formular['action'] = $action;

		$formular['inhalt'] = $object;

		return $this->twig->render('admin/parts/formular.html.twig', ['formular' => $formular]);
	}

	public function show($table, $id)
	{
		$stmt = $this->pdo->prepare("SELECT * FROM `$table` WHERE `id`=?");
		$stmt->execute([$id]);
		$result = $stmt->fetch();
		return $result;
	}

	public function delete($table, $slug)
	{

		// query to insert record
		$query = "DELETE FROM " . $table . " WHERE `id`= '$slug'";

		// prepare query
		$stmt = $this->pdo->prepare($query);

		// execute query
		if ($stmt->execute()) {
			return true;
		}
		return false;
	}
}
