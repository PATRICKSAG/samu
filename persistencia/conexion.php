<?php
class Database
{
    private static $instancia = null;
    private $pdo;

    private function __construct()
    {
        // Datos extraídos de tu captura y tu mensaje
        $serverName = "DESKTOP-NPDHDAT"; 
        $database = "BDDatos";
        
        /* IMPORTANTE: Como usas "Autenticación de Windows", 
           en PHP debes dejar usuario y contraseña como null o usar un usuario de SQL Server (sa).
        */
        $user = null; 
        $password = null;

        // DSN específico para SQL Server (sqlsrv)
        $dsn = "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true";

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