# Database API

SPE uses a minimal PDO wrapper (`Db.php`) with a `QueryType` enum for type-safe query results.

## QueryType Enum

```php
enum QueryType: string {
    case All = 'all';      // fetchAll() - array of rows
    case One = 'one';      // fetch() - single row or false
    case Column = 'column'; // fetchColumn() - single value
}
```

## Db Class

```php
final class Db extends PDO {
    private const array OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct(array $config) {
        $dsn = match ($config['type']) {
            'mysql' => "mysql:host={$config['host']};dbname={$config['name']}",
            default => "sqlite:{$config['path']}"
        };
        parent::__construct($dsn, $config['user'] ?? '', $config['pass'] ?? '', self::OPTIONS);
    }
}
```

## CRUD Methods

### Create

```php
$id = $db->create('posts', [
    'title' => 'My Post',
    'content' => 'Post content...',
    'author' => 'John',
    'created' => date('Y-m-d H:i:s')
]);
// Returns: int (last insert ID)
```

### Read

```php
// All rows
$posts = $db->read('posts', '*', 'type = :type', ['type' => 'post'], QueryType::All);
// Returns: array of rows

// Single row
$post = $db->read('posts', '*', 'id = :id', ['id' => 1], QueryType::One);
// Returns: array or false

// Single value
$count = $db->read('posts', 'COUNT(*)', 'type = :type', ['type' => 'post'], QueryType::Column);
// Returns: mixed (single value)

// With ordering and limit
$recent = $db->read(
    'posts',
    '*',
    'type = :type ORDER BY created DESC LIMIT :limit OFFSET :offset',
    ['type' => 'post', 'limit' => 10, 'offset' => 0],
    QueryType::All
);
```

### Update

```php
$success = $db->update('posts',
    ['title' => 'New Title', 'updated' => date('Y-m-d H:i:s')],
    'id = :id',
    ['id' => 1]
);
// Returns: bool
```

### Delete

```php
$success = $db->delete('posts', 'id = :id', ['id' => 1]);
// Returns: bool
```

## Database Schemas

### blog.db

```sql
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image TEXT,
    icon TEXT,
    author TEXT,
    author_id INTEGER,
    type TEXT DEFAULT 'post',  -- 'post', 'page', 'doc'
    created DATETIME,
    updated DATETIME
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE,
    description TEXT
);

CREATE TABLE post_categories (
    post_id INTEGER,
    category_id INTEGER,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### users.db

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT UNIQUE NOT NULL,
    fname TEXT,
    lname TEXT,
    altemail TEXT,
    webpw TEXT,           -- password_hash()
    cookie TEXT,          -- Remember me token
    otp TEXT,             -- Password reset token
    otpttl INTEGER,       -- OTP expiry timestamp
    acl INTEGER DEFAULT 1, -- 0=admin, 1=user, 9=disabled
    grp INTEGER DEFAULT 0,
    anote TEXT,
    created DATETIME,
    updated DATETIME
);
```

## Usage Examples

### Pagination

```php
public function list(): array {
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $total = $this->db->read('posts', 'COUNT(*)', 'type = :type',
        ['type' => 'post'], QueryType::Column);

    $items = $this->db->read('posts', '*',
        'type = :type ORDER BY created DESC LIMIT :limit OFFSET :offset',
        ['type' => 'post', 'limit' => $perPage, 'offset' => $offset],
        QueryType::All
    );

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ]
    ];
}
```

### Search

```php
$query = trim($_GET['q'] ?? '');
$where = 'type = :type';
$params = ['type' => 'post'];

if ($query !== '') {
    $where .= ' AND (title LIKE :search OR content LIKE :search)';
    $params['search'] = '%' . $query . '%';
}

$results = $db->read('posts', '*', $where . ' ORDER BY created DESC', $params, QueryType::All);
```

### Many-to-Many (Categories)

```php
// Get categories for a post
$categories = $db->read(
    'categories c JOIN post_categories pc ON c.id = pc.category_id',
    'c.*',
    'pc.post_id = :id',
    ['id' => $postId],
    QueryType::All
);

// Sync categories for a post
public static function syncForPost(Db $db, int $postId, array $categoryIds): void {
    $db->delete('post_categories', 'post_id = :id', ['id' => $postId]);
    foreach ($categoryIds as $catId) {
        $db->create('post_categories', [
            'post_id' => $postId,
            'category_id' => (int)$catId
        ]);
    }
}
```
