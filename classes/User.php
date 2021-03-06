<?php


namespace Classes;


class User
{
    public array $fillable = ['name','nachname','username','email','password'];
    public static function login($login) {
        $db = new Database();
        $checkLogin = $db->query("select * from `users` where `username` ='".$login['username']."'");
        $checkLogin = $checkLogin->fetch();
        if (password_verify($login['password'], $checkLogin['password'])) {
            session_start();
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $login['username'];
            header('Location:/admin');
        } else {
            header('Location:/admin/login?message=Es gibt keine Benutzernamendaten, die diesen Benutzernamen und dieses Passwort enthalten');
        }
    }

    public static function logout() {
        unset($_SESSION['logged_in']);
        unset($_SESSION['username']);
    }
}
