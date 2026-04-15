# 硬件调试工具站

基于浏览器串口通信的硬件芯片调试工具站，支持中兴微、ASR、展锐三种芯片设备的串口调试。

## 📋 功能特性

### 前端功能
- ✅ 三个芯片调试页面（中兴微/ASR/展锐）
- ✅ Web Serial API串口通信
- ✅ AT指令发送和响应处理
- ✅ 波特率等参数配置
- ✅ 数据读取和实时刷新
- ✅ 驱动检测和提示
- ✅ 蓝色现代化主题界面

### 后台管理
- ✅ 管理员登录认证
- ✅ 网站基础设置（标题/描述/Logo文字/主题色）
- ✅ 关于页面自定义配置
- ✅ 联系信息管理（邮箱/QQ/QQ群/文档链接）
- ✅ 社交媒体链接配置（抖音/B站）
- ✅ 底部版权和版本号自定义
- ✅ 访问统计概览
- ✅ 各页面使用频率统计
- ✅ 串口连接成功率分析
- ✅ 操作日志记录
- ✅ Chart.js数据可视化

## 🛠️ 环境要求

- **PHP**: 7.4+ (推荐8.0+)
- **MySQL**: 5.7+ (推荐8.0+)
- **Web服务器**: Apache 2.4+ / Nginx 1.18+
- **浏览器**: Chrome 89+ 或 Chromium内核浏览器（用于Web Serial API）
- **HTTPS**: 生产环境必须（localhost除外）

## 📦 安装步骤

### 1. 下载项目文件

将项目文件放置到Web根目录，例如：
```
d:\wwwroot\127.0.0.1\
```

### 2. 配置数据库

编辑 `config/database.php` 文件：

```php
<?php
define('DB_HOST', 'localhost');      // 数据库主机
define('DB_NAME', 'hardware_tool');  // 数据库名称
define('DB_USER', 'root');           // 数据库用户名
define('DB_PASS', 'password');       // 数据库密码
define('DB_CHARSET', 'utf8mb4');     // 字符集
?>
```

### 3. 导入数据库

执行数据库安装脚本：

```bash
# 方法1: 命令行导入
mysql -u root -p < database/install.sql

# 方法2: phpMyAdmin导入
# 登录phpMyAdmin，选择"导入"，上传database/install.sql文件
```

默认管理员账号：
- **用户名**: admin
- **密码**: admin123
- ⚠️ **重要**: 首次登录后请立即修改密码！

### 4. 配置Web服务器

#### Apache配置 (.htaccess已包含)

确保Apache启用了mod_rewrite模块：

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

#### Nginx配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project;
    index index.php;

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # URL重写
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }
}
```

### 5. 设置文件权限

```bash
# Linux系统
chmod -R 755 /path/to/project
chmod -R 777 /path/to/project/assets/images  # 图片上传目录

# Windows系统通常不需要特殊设置
```

### 6. 访问网站

- **前台**: http://localhost/
- **后台**: http://localhost/admin/login.php

## 🔧 使用说明

### 前端使用

1. **打开浏览器**: 使用Chrome 89+或Chromium内核浏览器
2. **访问首页**: 选择需要调试的芯片类型（中兴微/ASR/展锐）
3. **连接设备**:
   - 点击"选择串口"按钮
   - 在弹出的对话框中选择对应的COM端口
   - 配置波特率等参数（默认115200）
   - 点击"连接"
4. **发送AT指令**: 在输入框中输入AT指令，点击"发送"
5. **查看响应**: 在日志区域查看设备返回的数据

### 后台管理

1. **登录后台**: 访问 `/admin/login.php`，使用管理员账号登录
2. **网站设置**: 
   - 修改网站标题和描述
   - 设置自定义Logo文字
   - 配置关于页面内容
   - 调整主题颜色
   - 修改管理员密码
3. **联系信息设置**:
   - 配置联系邮箱、QQ、QQ群
   - 设置文档链接
   - 配置抖音和B站链接（文字和URL）
   - 自定义底部版权和版本号
4. **查看统计**:
   - 仪表盘显示总体数据
   - 详细统计页面可查看趋势图表
   - 串口操作日志记录

## 📁 目录结构

```
d:\wwwroot\127.0.0.1\
├── index.php                      # 前端入口
├── config/
│   ├── database.php              # 数据库配置
│   └── app.php                   # 应用配置
├── includes/
│   ├── functions.php             # 通用函数库
│   ├── auth.php                  # 后台认证
│   ├── header.php                # 公共头部
│   └── footer.php                # 公共底部
├── pages/                        # 前台页面
│   ├── home.php                  # 首页
│   ├── zte.php                   # 中兴微调试页
│   ├── asr.php                   # ASR调试页
│   ├── unisoc.php                # 展锐调试页
│   └── about.php                 # 关于页面
├── admin/                        # 后台管理
│   ├── login.php                 # 登录页面
│   ├── index.php                 # 仪表盘
│   ├── settings.php              # 网站设置
│   ├── statistics.php            # 访问统计
│   └── logout.php                # 退出登录
├── api/                          # API接口
│   └── stats.php                 # 统计数据API
├── assets/                       # 静态资源
│   ├── css/
│   │   ├── style.css             # 前端样式
│   │   └── admin.css             # 后台样式
│   ├── js/
│   │   └── main.js               # 主脚本
│   └── images/
│       └── favicon.ico           # ICO图标
├── database/                     # 数据库文件
│   ├── install.sql               # 数据库安装脚本
│   └── hardware_tool.db          # SQLite数据库（可选）
├── logs/                         # 日志目录
│   └── debug.log                 # 调试日志
├── serial-service/               # Node.js串口服务（可选）
├── temp_download/                # 临时下载目录
└── .htaccess                     # Apache配置
```

## 🔐 安全建议

1. **修改默认密码**: 首次登录后立即修改管理员密码
2. **启用HTTPS**: 生产环境必须使用HTTPS（特别是Web Serial API）
3. **定期备份**: 定期备份数据库
4. **更新依赖**: 及时更新PHP和MySQL版本
5. **防火墙**: 限制后台访问IP
6. **SQL注入防护**: 已使用预处理语句，不要修改为直接拼接SQL

## 🐛 常见问题

### Q1: 无法使用串口功能？

**A**: Web Serial API有以下要求：
- 必须使用Chrome 89+或Chromium内核浏览器
- 必须在HTTPS环境下（localhost除外）
- 必须安装对应芯片的驱动程序
- 用户必须手动选择串口（浏览器安全限制）

### Q2: 后台登录失败？

**A**: 检查以下项：
- 数据库是否正确导入
- `config/database.php`配置是否正确
- 默认账号: admin / admin123

### Q3: 统计数据不显示？

**A**: 可能原因：
- 数据库中还没有访问记录（正常现象，使用几次后就有数据了）
- 检查`page_stats`表是否有数据
- 检查浏览器控制台是否有JavaScript错误

### Q4: 样式显示不正常？

**A**: 检查：
- CSS文件路径是否正确
- 浏览器是否缓存了旧文件（Ctrl+F5强制刷新）
- Web服务器是否正确配置MIME类型

### Q5: 如何重置管理员密码？

**A**: 执行以下SQL：

```sql
UPDATE admin_users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';
-- 密码重置为: admin123
```

### Q6: 如何配置社交媒体链接？

**A**: 
1. 登录后台 → 网站设置 → 联系信息
2. 在"社交媒体链接"部分填写：
   - 抖音：显示文字和跳转链接
   - B站：显示文字和跳转链接
3. 点击"保存联系信息"
4. 访问前台关于页面查看效果

### Q7: 如何自定义底部版权信息？

**A**:
1. 登录后台 → 网站设置 → 联系信息
2. 在"底部信息"部分填写：
   - 版权信息：如 `© 2026 硬件调试工具. All rights reserved.`
   - 版本号：如 `1.0.0`
3. 点击"保存联系信息"
4. 刷新前台页面查看效果

## 📊 数据库表说明

### settings - 网站设置
存储网站标题、描述、Logo文字、主题色、关于页面内容、联系信息、社交媒体链接、底部版权等配置

### page_stats - 页面访问统计
按日期记录每个页面的访问量、成功/失败次数

### serial_logs - 串口操作日志
记录每次串口操作的详细信息

### admin_users - 管理员表
存储管理员账号信息

## 🔄 升级说明

如需从旧版本升级：

1. 备份当前文件和数据库
2. 替换所有PHP文件
3. 检查数据库是否有新字段（对比install.sql）
4. 清除浏览器缓存

## 📝 开发计划

- [ ] Node.js串口服务中间件
- [ ] 更多芯片类型支持
- [ ] AT指令模板库
- [ ] 数据导出功能
- [ ] 多语言支持
- [ ] 移动端优化

## 📄 许可证

本项目仅供学习和个人使用。

## 👨‍💻 技术支持

如遇到问题，请检查：
1. 本文档的"常见问题"部分
2. 浏览器控制台的错误信息
3. PHP错误日志

## 🎓 技术栈

### 后端
- PHP 7.4+（原生，无框架）
- MySQL 5.7+（PDO连接）
- Apache/Nginx

### 前端
- HTML5
- CSS3（CSS Grid, Flexbox, CSS Variables）
- JavaScript（ES6+）
- Web Serial API

### 第三方库
- Chart.js 4.4.0（数据可视化）

### 开发模式
- 简化MVC架构
- RESTful API设计
- 响应式设计

---

**版本**: 1.0.0  
**最后更新**: 2026年4月  
**PHP要求**: 7.4+  
**MySQL要求**: 5.7+  
**浏览器要求**: Chrome 89+（Web Serial API）
