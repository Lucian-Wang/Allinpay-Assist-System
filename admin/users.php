<?php
require_once 'header.php';
$db = get_admin_db();
$kw = trim($_GET['kw'] ?? '');

$where = '';
$params = [];
if ($kw !== '') {
    $where = "WHERE (username LIKE ? OR email LIKE ?)";
    $like_kw = "%{$kw}%";
    $params[] = $like_kw;
    $params[] = $like_kw;
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 15;
$offset = ($page - 1) * $page_size;

// 统计总数
$sql_count = "SELECT COUNT(*) FROM users $where";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_count / $page_size));

// 查询数据
$sql = "SELECT * FROM users $where ORDER BY id DESC LIMIT $page_size OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 分页导航
function render_pagination($page, $total_pages, $baseurl) {
    if ($total_pages <= 1) return '';
    $html = '<div class="pagination">';
    if ($page > 1) {
        $html .= '<a href="'.$baseurl.'page=1" class="btn">&laquo; 首页</a> ';
        $html .= '<a href="'.$baseurl.'page='.($page-1).'" class="btn">&lt; 上一页</a> ';
    }
    $html .= " 第 $page / $total_pages 页 ";
    if ($page < $total_pages) {
        $html .= '<a href="'.$baseurl.'page='.($page+1).'" class="btn">下一页 &gt;</a> ';
        $html .= '<a href="'.$baseurl.'page='.$total_pages.'" class="btn">末页 &raquo;</a>';
    }
    $html .= '</div>';
    return $html;
}
$baseurl = "users.php?".($kw!==''?"kw=".urlencode($kw)."&":'');
?>
<style>
    .btn {
        display: inline-block;
        padding: 5px 18px;
        border-radius: 4px;
        border: 1px solid #3183f4;
        background: #fff;
        color: #3183f4;
        text-decoration: none;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.2s, color 0.2s;
        min-width: 60px;
        text-align: center;
        box-sizing: border-box;
    }
    .btn:hover {
        background: #3183f4;
        color: #fff !important;
    }
    .content-card {
        max-width: 1000px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 32px #0001;
        margin: 48px auto;
        padding: 32px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 22px;
    }
    .card-title {
        font-size: 1.5em;
        font-weight: 600;
        color: #222;
        letter-spacing: 1px;
    }
    .card-actions {
        display: flex;
        gap: 16px;
        align-items: center;
    }
    .user-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }
    .user-table thead th {
        background: #f6f8fa;
        font-weight: 550;
        color: #444;
        border-bottom: 2px solid #eaeaea;
        padding: 12px 8px;
    }
    .user-table tbody td {
        padding: 12px 8px;
        text-align: center;
    }
    .user-table tbody tr:nth-child(odd) { background: #fafbfc; }
    .user-table tbody tr:hover { background: #e6f7ff; transition: background 0.2s;}
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 7px;
        margin: 24px 0;
    }
    .op-group {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    .op-group a {
        color: #409eff;
        background: #f2f7fc;
        padding: 3px 14px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.96em;
        transition: background .15s, color .15s;
    }
    .op-group a:hover {
        background: #409eff;
        color: #fff;
    }
    @media (max-width: 900px){
        .content-card {padding:10px;}
        .card-title {font-size:1.1rem;}
        .user-table th, .user-table td {padding:7px 2px;}
    }
</style>
<div class="content-card">
    <div class="card-header" style="max-width:1100px;margin:48px auto;">
        <div class="card-title">用户管理</div>
        <div class="card-actions">
            <form method="get" action="users.php" style="display:inline-block;">
                <input type="text" name="kw" value="<?=admin_esc($kw)?>" placeholder="用户名/邮箱" style="padding:6px 12px;width:160px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="users_add.php" class="btn" style="background:#409eff;color:#fff;">新建用户</a>
        </div>
    </div>
    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>邮箱</th>
                <th>创建时间</th>
                <th>更新时间</th>
                <th style="width:140px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($users)): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;">暂无用户</td></tr>
        <?php else: foreach ($users as $u): ?>
            <tr>
                <td><?=admin_esc($u['id'])?></td>
                <td><?=admin_esc($u['username'])?></td>
                <td><?=admin_esc($u['email'])?></td>
                <td><?=admin_esc($u['created_at'])?></td>
                <td><?=admin_esc($u['updated_at'])?></td>
                <td>
                    <div class="op-group">
                        <a href="users_edit.php?id=<?=$u['id']?>">编辑</a>
                        <a href="users_view.php?id=<?=$u['id']?>">详情</a>
                        <a href="users_delete.php?id=<?=$u['id']?>" onclick="return confirm('确定要删除该用户？')" style="color:#f56c6c;">删除</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once 'footer.php'; ?>