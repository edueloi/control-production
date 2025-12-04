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
            
            // Executar migrações para adicionar novas colunas
            $this->runMigrations();
            
            // Criar índices após migrações
            $this->createIndexes();
            
            // Criar admin padrão se não existir usuários
            $this->createDefaultAdmin();
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
        // Tabela de usuários - COMPLETA
        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            phone TEXT,
            avatar TEXT,
            role TEXT DEFAULT 'user',
            status TEXT DEFAULT 'active',
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de produtos - COMPLETA
        $this->conn->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            barcode TEXT,
            description TEXT NOT NULL,
            category TEXT,
            brand TEXT,
            supplier TEXT,
            cost REAL NOT NULL,
            price REAL NOT NULL,
            stock REAL DEFAULT 0,
            min_stock REAL DEFAULT 0,
            max_stock REAL,
            unit TEXT NOT NULL,
            type TEXT NOT NULL,
            image TEXT,
            notes TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        
        // Tabela de clientes - COMPLETA
        $this->conn->exec("CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            cpf TEXT,
            cnpj TEXT,
            company_name TEXT,
            email TEXT NOT NULL,
            phone TEXT,
            whatsapp TEXT,
            address TEXT NOT NULL,
            city TEXT,
            state TEXT,
            zip_code TEXT,
            notes TEXT,
            credit_limit REAL DEFAULT 0,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        
        // Tabela de produções
        $this->conn->exec("CREATE TABLE IF NOT EXISTS productions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            batch_size INTEGER NOT NULL,
            total_cost REAL NOT NULL,
            unit_cost REAL NOT NULL,
            profit_margin REAL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
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
        
        // Tabela de vendas - COMPLETA
        $this->conn->exec("CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER,
            user_id INTEGER,
            subtotal REAL NOT NULL,
            discount REAL DEFAULT 0,
            discount_type TEXT DEFAULT 'value',
            total REAL NOT NULL,
            payment_method TEXT NOT NULL,
            payment_status TEXT DEFAULT 'paid',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
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
        
        // Tabela de categorias
        $this->conn->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            icon TEXT,
            color TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de fornecedores
        $this->conn->exec("CREATE TABLE IF NOT EXISTS suppliers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            cnpj TEXT UNIQUE,
            email TEXT,
            phone TEXT,
            whatsapp TEXT,
            address TEXT,
            city TEXT,
            state TEXT,
            zip_code TEXT,
            notes TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de configurações do sistema
        $this->conn->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'text',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Tabela de logs de atividades
        $this->conn->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            entity_type TEXT,
            entity_id INTEGER,
            description TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
        
        // Tabela de receitas (templates de produção)
        $this->conn->exec("CREATE TABLE IF NOT EXISTS recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            product_id INTEGER NOT NULL,
            ingredients TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
    }
    
    private function createDefaultAdmin() {
        try {
            // Verificar se já existe algum usuário
            $count = $this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
            
            if ($count == 0) {
                // Criar usuário admin padrão
                $adminPassword = password_hash('Admin@1234', PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['Administrador', 'admin@admin.com', $adminPassword, 'admin', 'active']);
            }
        } catch(PDOException $e) {
            // Ignorar erros
        }
    }
    
    private function createIndexes() {
        try {
            // Criar índices para melhor performance
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_products_barcode ON products(barcode)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_products_category ON products(category)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_clients_cpf ON clients(cpf)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_clients_cnpj ON clients(cnpj)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(created_at)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_sales_user ON sales(user_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_stock_movements_date ON stock_movements(created_at)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON stock_movements(product_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON activity_logs(user_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_activity_logs_date ON activity_logs(created_at)");
        } catch(PDOException $e) {
            // Ignorar erros de índices
        }
    }
    
    private function runMigrations() {
        try {
            // Verificar se as novas colunas já existem antes de adicionar
            
            // Adicionar colunas na tabela users
            $this->addColumnIfNotExists('users', 'phone', 'TEXT');
            $this->addColumnIfNotExists('users', 'avatar', 'TEXT');
            $this->addColumnIfNotExists('users', 'role', 'TEXT DEFAULT "user"');
            $this->addColumnIfNotExists('users', 'status', 'TEXT DEFAULT "active"');
            $this->addColumnIfNotExists('users', 'last_login', 'DATETIME');
            
            // Adicionar user_id nas tabelas (multi-tenancy)
            $this->addColumnIfNotExists('products', 'user_id', 'INTEGER NOT NULL DEFAULT 1');
            $this->addColumnIfNotExists('clients', 'user_id', 'INTEGER NOT NULL DEFAULT 1');
            $this->addColumnIfNotExists('productions', 'user_id', 'INTEGER NOT NULL DEFAULT 1');
            
            // Adicionar colunas na tabela products
            $this->addColumnIfNotExists('products', 'category', 'TEXT');
            $this->addColumnIfNotExists('products', 'brand', 'TEXT');
            $this->addColumnIfNotExists('products', 'supplier', 'TEXT');
            $this->addColumnIfNotExists('products', 'max_stock', 'REAL');
            $this->addColumnIfNotExists('products', 'notes', 'TEXT');
            $this->addColumnIfNotExists('products', 'status', 'TEXT DEFAULT "active"');
            
            // Adicionar colunas na tabela clients
            $this->addColumnIfNotExists('clients', 'city', 'TEXT');
            $this->addColumnIfNotExists('clients', 'state', 'TEXT');
            $this->addColumnIfNotExists('clients', 'zip_code', 'TEXT');
            $this->addColumnIfNotExists('clients', 'notes', 'TEXT');
            $this->addColumnIfNotExists('clients', 'credit_limit', 'REAL DEFAULT 0');
            $this->addColumnIfNotExists('clients', 'status', 'TEXT DEFAULT "active"');
            
            // Adicionar colunas na tabela sales
            $this->addColumnIfNotExists('sales', 'user_id', 'INTEGER');
            $this->addColumnIfNotExists('sales', 'payment_status', 'TEXT DEFAULT "paid"');
            $this->addColumnIfNotExists('sales', 'notes', 'TEXT');
            
            // Criar índices para user_id
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_products_user ON products(user_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_clients_user ON clients(user_id)");
            $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_productions_user ON productions(user_id)");
            
        } catch(PDOException $e) {
            // Ignorar erros de colunas já existentes
        }
    }
    
    private function addColumnIfNotExists($table, $column, $definition) {
        try {
            // Tentar obter informações da coluna
            $result = $this->conn->query("PRAGMA table_info($table)");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            
            $columnExists = false;
            foreach ($columns as $col) {
                if ($col['name'] === $column) {
                    $columnExists = true;
                    break;
                }
            }
            
            // Se a coluna não existe, adicionar
            if (!$columnExists) {
                $this->conn->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        } catch(PDOException $e) {
            // Ignorar erros
        }
    }
}
?>
