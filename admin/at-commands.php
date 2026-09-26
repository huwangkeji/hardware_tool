<?php
/**
 * 管理后台 - AT指令管理
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/app.php';

requireAdmin();

$settings = getSettings();
$message  = '';
$error    = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) {
        $error = 'CSRF 验证失败，请刷新页面重试';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            $db = getDB();
            $now = date('Y-m-d H:i:s');

            switch ($action) {
                case 'create':
                    $db->prepare("INSERT INTO at_commands
                        (chip_type, command, title, description, syntax, parameters, response, example, category, sort_order, is_published, created_at, updated_at)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                       ->execute([
                           trim($_POST['chip_type'] ?? ''),
                           trim($_POST['command'] ?? ''),
                           trim($_POST['title'] ?? ''),
                           $_POST['description'] ?? '',
                           $_POST['syntax'] ?? '',
                           $_POST['parameters'] ?? '',
                           $_POST['response'] ?? '',
                           $_POST['example'] ?? '',
                           trim($_POST['category'] ?? '基础'),
                           (int)($_POST['sort_order'] ?? 0),
                           isset($_POST['is_published']) ? 1 : 0,
                           $now, $now,
                       ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'AT指令已创建'];
                    redirect('/admin/at-commands.php');
                    break;

                case 'update':
                    $db->prepare("UPDATE at_commands SET
                        chip_type=?, command=?, title=?, description=?, syntax=?, parameters=?, response=?, example=?, category=?, sort_order=?, is_published=?, updated_at=?
                        WHERE id=?")
                       ->execute([
                           trim($_POST['chip_type'] ?? ''),
                           trim($_POST['command'] ?? ''),
                           trim($_POST['title'] ?? ''),
                           $_POST['description'] ?? '',
                           $_POST['syntax'] ?? '',
                           $_POST['parameters'] ?? '',
                           $_POST['response'] ?? '',
                           $_POST['example'] ?? '',
                           trim($_POST['category'] ?? '基础'),
                           (int)($_POST['sort_order'] ?? 0),
                           isset($_POST['is_published']) ? 1 : 0,
                           $now,
                           (int)($_POST['id'] ?? 0),
                       ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'AT指令已更新'];
                    redirect('/admin/at-commands.php');
                    break;

                case 'delete':
                    $db->prepare("DELETE FROM at_commands WHERE id=?")
                       ->execute([(int)($_POST['id'] ?? 0)]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'AT指令已删除'];
                    redirect('/admin/at-commands.php');
                    break;
            }
        } catch (Exception $e) {
            $error = '操作失败：' . $e->getMessage();
        }
    }
}

// 读取 flash 消息
$flash = $_SESSION['flash'] ?? null;
if ($flash) {
    $message = $flash['message'] ?? '';
    unset($_SESSION['flash']);
}

// 获取数据
$platforms = getChipPlatforms();
$commands = getATCommands();
$categories = getATCommandCategories();

$current_admin_page = 'at-commands';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AT指令管理 - 管理后台</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { margin: 0; background: #f0f2f5; }
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-content { flex: 1; margin-left: 240px; padding: 24px; }
        .admin-sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 240px; background: #001529; color: #fff; display: flex; flex-direction: column; z-index: 100; }
        .admin-logo { padding: 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #003a8c; }
        .admin-nav { flex: 1; padding: 12px 0; }
        .admin-nav .nav-item { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); text-decoration: none; transition: all 0.2s; }
        .admin-nav .nav-item:hover, .admin-nav .nav-item.active { color: #fff; background: #1890ff; }
        .admin-nav-bottom { padding: 12px 0; border-top: 1px solid #003a8c; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 12px 24px; color: rgba(255,255,255,0.65); }
        .logout-btn:hover { color: #ff4d4f; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 24px; color: #333; }
        .btn-add { padding: 8px 16px; background: #1890ff; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .panel { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .panel h2 { margin: 0 0 16px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        th { color: #999; font-weight: 500; }
        .btn-edit, .btn-del { padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-edit { background: #e6f7ff; color: #1890ff; }
        .btn-del { background: #fff1f0; color: #ff4d4f; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .flash.success { background: #f6ffed; border: 1px solid #b7eb8f; color: #389e0d; }
        .flash.error { background: #fff2f0; border: 1px solid #ffccc7; color: #cf1322; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-box { background: #fff; border-radius: 8px; padding: 24px; width: 600px; max-width: 90vw; max-height: 80vh; overflow-y: auto; }
        .modal-box h2 { margin: 0 0 20px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-size: 14px; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 6px 10px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .form-group textarea { min-height: 60px; resize: vertical; }
        .btn-save { padding: 8px 24px; background: #1890ff; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .btn-cancel { padding: 8px 24px; background: #f0f0f0; color: #555; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="page-header">
            <h1>AT指令管理</h1>
            <button class="btn-add" onclick="openCreate()">+ 新增指令</button>
        </div>

        <?php if ($message): ?>
            <div class="flash success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="panel">
            <table>
                <thead>
                    <tr><th>ID</th><th>芯片</th><th>指令</th><th>标题</th><th>分类</th><th>排序</th><th>发布</th><th>操作</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($commands as $cmd): ?>
                    <tr>
                        <td><?php echo (int)$cmd['id']; ?></td>
                        <td><?php echo h($cmd['chip_type']); ?></td>
                        <td><?php echo h($cmd['command']); ?></td>
                        <td><?php echo h($cmd['title']); ?></td>
                        <td><?php echo h($cmd['category']); ?></td>
                        <td><?php echo (int)$cmd['sort_order']; ?></td>
                        <td><?php echo $cmd['is_published'] ? '✅' : '❌'; ?></td>
                        <td>
                            <button class="btn-edit" onclick='openEdit(<?php echo json_encode($cmd, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS); ?>)'>编辑</button>
                            <form method="post" action="" style="display:inline" onsubmit="return confirm('确认删除？')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$cmd['id']; ?>">
                                <button type="submit" class="btn-del">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($commands)): ?>
                    <tr><td colspan="8" style="text-align:center;color:#999;">暂无数据</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 编辑/新增弹窗 -->
<div class="modal" id="cmdModal">
    <div class="modal-box">
        <h2 id="modalTitle">新增AT指令</h2>
        <form method="post" action="" id="cmdForm">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="f_id">
            <div class="form-group">
                <label>芯片类型</label>
                <select name="chip_type" id="f_chip_type">
                    <?php foreach ($platforms as $p): ?>
                    <option value="<?php echo h($p['type']); ?>"><?php echo h($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>指令</label><input type="text" name="command" id="f_command" required></div>
            <div class="form-group"><label>标题</label><input type="text" name="title" id="f_title"></div>
            <div class="form-group"><label>描述</label><textarea name="description" id="f_description"></textarea></div>
            <div class="form-group"><label>语法</label><textarea name="syntax" id="f_syntax"></textarea></div>
            <div class="form-group"><label>参数</label><textarea name="parameters" id="f_parameters"></textarea></div>
            <div class="form-group"><label>响应</label><textarea name="response" id="f_response"></textarea></div>
            <div class="form-group"><label>示例</label><textarea name="example" id="f_example"></textarea></div>
            <div class="form-group"><label>分类</label><input type="text" name="category" id="f_category" value="基础"></div>
            <div class="form-group"><label>排序</label><input type="number" name="sort_order" id="f_sort_order" value="0"></div>
            <div class="form-group"><label><input type="checkbox" name="is_published" id="f_is_published" checked> 发布</label></div>
            <button type="submit" class="btn-save">保存</button>
            <button type="button" class="btn-cancel" onclick="closeModal()">取消</button>
        </form>
    </div>
</div>

<script>
function openCreate() {
    document.getElementById('modalTitle').textContent = '新增AT指令';
    document.getElementById('formAction').value = 'create';
    document.getElementById('cmdForm').reset();
    document.getElementById('f_category').value = '基础';
    document.getElementById('f_sort_order').value = '0';
    document.getElementById('f_is_published').checked = true;
    document.getElementById('cmdModal').classList.add('show');
}
function openEdit(cmd) {
    document.getElementById('modalTitle').textContent = '编辑AT指令';
    document.getElementById('formAction').value = 'update';
    document.getElementById('f_id').value = cmd.id;
    document.getElementById('f_chip_type').value = cmd.chip_type;
    document.getElementById('f_command').value = cmd.command;
    document.getElementById('f_title').value = cmd.title;
    document.getElementById('f_description').value = cmd.description;
    document.getElementById('f_syntax').value = cmd.syntax;
    document.getElementById('f_parameters').value = cmd.parameters;
    document.getElementById('f_response').value = cmd.response;
    document.getElementById('f_example').value = cmd.example;
    document.getElementById('f_category').value = cmd.category;
    document.getElementById('f_sort_order').value = cmd.sort_order;
    document.getElementById('f_is_published').checked = cmd.is_published == 1;
    document.getElementById('cmdModal').classList.add('show');
}
function closeModal() {
    document.getElementById('cmdModal').classList.remove('show');
}
</script>
</body>
</html>
