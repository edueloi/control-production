<?php
class Database {
    private static $instance = null;
    private $conn;
    private $dbFile = __DIR__ . '/../database/production.db';
    
    private function __construct() {
        try {
            // Criar diretório database se não existir
            $dbDir = dirname($this->dbFile);
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            // Conectar ao SQLite
            $this->conn = new PDO("sqlite:" . $this->dbFile);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar tabelas
            $this->createTables();
        } catch(PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function createTables() {
        // Tabela de usuários
        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de produtos
        $this->conn->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            barcode TEXT UNIQUE NOT NULL,
            description TEXT NOT NULL,
            cost REAL NOT NULL,
            price REAL NOT NULL,
            stock REAL DEFAULT 0,
            min_stock REAL DEFAULT 0,
            unit TEXT NOT NULL,
            type TEXT NOT NULL,
            image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de clientes
        $this->conn->exec("CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            cpf TEXT,
            cnpj TEXT,
            company_name TEXT,
            email TEXT NOT NULL,
            phone TEXT,
            whatsapp TEXT,
            address TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de produções
        $this->conn->exec("CREATE TABLE IF NOT EXISTS productions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            batch_size INTEGER NOT NULL,
            total_cost REAL NOT NULL,
            unit_cost REAL NOT NULL,
            profit_margin REAL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        // Tabela de ingredientes de produção
        $this->conn->exec("CREATE TABLE IF NOT EXISTS production_ingredients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            production_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity REAL NOT NULL,
            cost REAL NOT NULL,
            FOREIGN KEY (production_id) REFERENCES productions(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        // Tabela de vendas
        $this->conn->exec("CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER,
            subtotal REAL NOT NULL,
            discount REAL DEFAULT 0,
            discount_type TEXT DEFAULT 'value',
            total REAL NOT NULL,
            payment_method TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id)
        )");
        
        // Tabela de itens de venda
        $this->conn->exec("CREATE TABLE IF NOT EXISTS sale_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity REAL NOT NULL,
            price REAL NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (sale_id) REFERENCES sales(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        // Tabela de movimentações de estoque
        $this->conn->exec("CREATE TABLE IF NOT EXISTS stock_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            quantity REAL NOT NULL,
            reference_id INTEGER,
            reference_type TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        // Criar índices para melhor performance
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_products_barcode ON products(barcode)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_clients_cpf ON clients(cpf)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_clients_cnpj ON clients(cnpj)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(created_at)");
        $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_stock_movements_date ON stock_movements(created_at)");
    }
}
?>
