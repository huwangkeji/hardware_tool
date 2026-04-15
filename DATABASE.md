# 数据库配置说明

本项目支持 **MySQL** 和 **SQLite** 两种数据库,您可以根据实际情况选择。

---

## 🚀 快速开始(推荐 SQLite)

### 方式1: 使用 SQLite(最简单,无需额外配置)

SQLite 是 PHP 内置的数据库,不需要安装任何额外软件,适合个人项目和小型应用。

**步骤:**

1. 编辑 `config/database.php`,确保:
   ```php
   define('DB_TYPE', 'sqlite');  // 默认就是sqlite
   ```

2. 运行初始化脚本:
   ```bash
   php init_db.php
   ```
   
   或在浏览器中访问:
   ```
   http://localhost/init_db.php
   ```

3. 完成!数据库文件会自动创建在 `database/hardware_tool.db`

---

### 方式2: 使用 MySQL(适合生产环境)

如果您需要更高的并发性能或已经在使用MySQL,可以选择MySQL。

**步骤:**

1. 编辑 `config/database.php`:
   ```php
   define('DB_TYPE', 'mysql');   // 改为mysql
   
   // 配置MySQL连接信息
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hardware_tool');
   define('DB_USER', 'root');    // 修改为你的用户名
   define('DB_PASS', 'your_password');  // 修改为你的密码
   ```

2. 创建数据库并导入:
   ```bash
   mysql -u root -p < database/install.sql
   ```
   
   或使用phpMyAdmin:
   - 登录phpMyAdmin
   - 点击"导入"
   - 选择 `database/install.sql`
   - 点击"执行"

3. 运行初始化脚本验证:
   ```bash
   php init_db.php
   ```

---

## 📊 两种数据库对比

| 特性 | SQLite | MySQL |
|------|--------|-------|
| **安装难度** | ⭐ 无需安装 | ⭐⭐⭐ 需要安装MySQL |
| **性能** | ⭐⭐ 适合小型项目 | ⭐⭐⭐⭐⭐ 高并发 |
| **并发用户** | < 100 | 无限制 |
| **数据存储** | 单文件 | 独立服务 |
| **备份** | 复制文件即可 | 需要导出工具 |
| **适用场景** | 个人/小型项目 | 生产环境/大型项目 |

---

## 📝 数据库配置详解

### config/database.php 配置项

```php
// 数据库类型选择: 'mysql' 或 'sqlite'
define('DB_TYPE', 'sqlite');

// MySQL配置(仅在DB_TYPE='mysql'时使用)
define('DB_HOST', 'localhost');      // 数据库主机地址
define('DB_NAME', 'hardware_tool');  // 数据库名称
define('DB_USER', 'root');           // 数据库用户名
define('DB_PASS', '');               // 数据库密码
define('DB_CHARSET', 'utf8mb4');     // 字符集

// SQLite配置(仅在DB_TYPE='sqlite'时使用)
define('SQLITE_DB_FILE', __DIR__ . '/../database/hardware_tool.db');
```

---

## 🔧 常见问题

### Q1: 切换数据库类型?

只需修改 `config/database.php` 中的 `DB_TYPE`:
```php
define('DB_TYPE', 'sqlite');  // 或 'mysql'
```

然后运行 `php init_db.php` 重新初始化数据库。

### Q2: SQLite数据库文件在哪里?

默认位置: `database/hardware_tool.db`

您可以直接复制这个文件进行备份。

### Q3: MySQL连接失败?

检查以下几点:
1. MySQL服务是否启动
2. `config/database.php` 中的用户名和密码是否正确
3. 数据库是否已创建
4. PHP是否启用了PDO_MySQL扩展

测试PDO扩展:
```php
<?php
phpinfo();
// 搜索 "pdo_mysql",如果找到说明已启用
?>
```

### Q4: 如何备份数据库?

**SQLite:**
```bash
# 直接复制文件
cp database/hardware_tool.db database/hardware_tool_backup.db
```

**MySQL:**
```bash
# 导出数据库
mysqldump -u root -p hardware_tool > backup.sql

# 导入数据库
mysql -u root -p hardware_tool < backup.sql
```

### Q5: 如何重置数据库?

**SQLite:**
```bash
# 删除数据库文件
rm database/hardware_tool.db

# 重新初始化
php init_db.php
```

**MySQL:**
```sql
-- 删除并重新创建数据库
DROP DATABASE hardware_tool;
CREATE DATABASE hardware_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 然后重新导入
mysql -u root -p hardware_tool < database/install.sql
```

### Q6: 数据库权限问题?

**SQLite:**
```bash
# 确保database目录有写入权限
chmod 755 database/
chmod 666 database/hardware_tool.db  # 如果需要
```

**MySQL:**
```sql
-- 创建专用用户并授权
CREATE USER 'hardware_tool'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON hardware_tool.* TO 'hardware_tool'@'localhost';
FLUSH PRIVILEGES;
```

---

## 📋 数据库表结构

### 1. settings - 网站设置
存储网站标题、描述、主题色等配置

### 2. page_stats - 页面访问统计
按日期记录每个页面的访问量、成功/失败次数

### 3. serial_logs - 串口操作日志
记录每次串口操作的详细信息

### 4. admin_users - 管理员表
存储管理员账号信息

### 5. system_config - 系统配置
存储系统级配置项

---

## 🎯 推荐配置

### 个人开发/测试
```php
define('DB_TYPE', 'sqlite');  // 简单方便
```

### 小型网站(< 100用户)
```php
define('DB_TYPE', 'sqlite');  // SQLite足够
```

### 中大型网站(> 100用户)
```php
define('DB_TYPE', 'mysql');   // 使用MySQL
```

---

## 🔐 安全建议

1. **不要提交数据库文件到Git**
   - SQLite的 `database/*.db` 已在 `.gitignore` 中

2. **不要提交数据库密码**
   - MySQL的密码在 `config/database.php` 中
   - 建议使用环境变量

3. **定期备份数据库**
   - SQLite: 复制 `.db` 文件
   - MySQL: 使用 `mysqldump`

4. **限制数据库访问权限**
   - MySQL: 使用专用用户,不要使用root

---

## 📞 需要帮助?

- 查看详细文档: `README.md`
- 快速安装指南: `INSTALL.md`
- 运行环境检测: 访问 `http://localhost/check.php`

---

**最后更新**: 2024年
