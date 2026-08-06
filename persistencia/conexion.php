<?php
class Database
{
    private static $instancia = null;
    private $pdo;

    private function __construct()
    {
        // Nombre del servidor (localhost o . o (local) o tu IP)
        $serverName = "localhost";
        
        // Nombre de tu base de datos (cámbialo por el que tengas)
        $database = "BDDatos";
        
        // Autenticación de Windows → usuario y contraseña en null
        $user = null;
        $password = null;

        // DSN para SQL Server con las mismas opciones que usaste en SSMS
        $dsn = "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true;Encrypt=false";

        try {
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Error de conexión a SQL Server: " . $e->getMessage());
        }
    }

    public static function getConexion()
    {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }
        return self::$instancia->pdo;
    }
}