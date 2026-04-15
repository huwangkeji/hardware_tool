# 快速安装指南

## 🚀 5分钟快速开始

### 步骤1: 配置数据库 (1分钟)

编辑 `config/database.php`:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hardware_tool');
define('DB_USER', 'root');        // 修改为你的数据库用户名
define('DB_PASS', 'password');    // 修改为你的数据库密码
define('DB_CHARSET', 'utf8mb4');
?>
```

### 步骤2: 导入数据库 (1分钟)

**方法A: 使用命令行**
```bash
mysql -u root -p < database/install.sql
```

**方法B: 使用phpMyAdmin**
1. 打开phpMyAdmin
2. 点击"导入"标签
3. 选择 `database/install.sql` 文件
4. 点击"执行"

### 步骤3: 访问网站 (1分钟)

- **前台**: http://localhost/
- **后台**: http://localhost/admin/login.php

默认管理员账号:
- 用户名: `admin`
- 密码: `admin123`

### 步骤4: 生成Favicon图标 (2分钟)

1. 在浏览器中打开: http://localhost/generate-favicon.html
2. 点击"生成图标"按钮
3. 点击"下载图标"按钮
4. 将下载的 `favicon.ico` 文件移动到 `assets/images/` 目录

---

## ✅ 验证安装

访问以下页面确认安装成功:

- [ ] http://localhost/ - 首页应该显示三个芯片调试选项
- [ ] http://localhost/admin/login.php - 后台登录页面
- [ ] http://localhost/admin/index.php - 登录后应显示仪表盘

---

## 🔧 如果遇到问题

### 问题1: 数据库连接失败

**检查:**
```bash
# 测试数据库连接
php -r "require 'config/database.php'; \$db = getDB(); echo '连接成功!';"
```

**解决:**
- 确认MySQL服务已启动
- 检查 `config/database.php` 中的用户名和密码
- 确认数据库 `hardware_tool` 已创建

### 问题2: 页面显示空白

**检查PHP错误日志:**
```bash
# Linux
tail -f /var/log/apache2/error.log

# Windows (XAMPP)
查看 xampp/apache/logs/error.log
```

**常见原因:**
- PHP版本过低(需要7.4+)
- PDO扩展未启用
- 文件路径错误

### 问题3: 样式不显示

**解决:**
- 清除浏览器缓存(Ctrl+F5)
- 检查浏览器控制台是否有404错误
- 确认CSS文件存在且可读

---

## 📋 系统检查清单

运行以下命令检查环境:

```bash
# 检查PHP版本
php -v  # 应该 >= 7.4

# 检查MySQL版本
mysql --version  # 应该 >= 5.7

# 检查PDO扩展
php -m | grep pdo_mysql

# 检查Apache模块
apache2ctl -M | grep rewrite  # 应该有mod_rewrite
```

---

## 🎯 下一步

1. **修改管理员密码**
   - 登录后台 → 网站设置 → 修改密码

2. **自定义网站信息**
   - 登录后台 → 网站设置
   - 修改标题和描述

3. **测试串口功能**
   - 安装芯片驱动
   - 使用Chrome浏览器访问
   - 连接设备测试

4. **配置HTTPS(生产环境)**
   - 申请SSL证书
   - 配置Apache/Nginx
   - 启用HTTPS重定向

---

## 📞 需要帮助?

查看详细文档: `README.md`

常见问题解答也在 `README.md` 的"常见问题"部分。

---

**安装完成后,可以删除此文件。**
