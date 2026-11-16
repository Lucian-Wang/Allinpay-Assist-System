<?php
require_once 'header.php';
$db = get_admin_db();
$kw = trim($_GET['kw'] ?? '');

$where = '';
$params = [];
if ($kw !== '') {
    $where = "WHERE username LIKE ?";
    $params[] = "%{$kw}%";
}

// 分页
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 15;
$offset = ($page - 1) * $page_size;

// 总数
$sql_count = "SELECT COUNT(*) FROM admin_users $where";
$stmt = $db->prepare($sql_count);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();
$total_pages = max(1, ceil($total_count / $page_size));

// 列表
$sql = "SELECT * FROM admin_users $where ORDER BY id DESC LIMIT $page_size OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$baseurl = "admins.php?".($kw!==''?"kw=".urlencode($kw)."&":'');
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
    max-width: 700px;
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
.admin-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
}
.admin-table thead th {
    background: #f6f8fa;
    font-weight: 550;
    color: #444;
    border-bottom: 2px solid #eaeaea;
    padding: 12px 8px;
}
.admin-table tbody td {
    padding: 12px 8px;
    text-align: center;
}
.admin-table tbody tr:nth-child(odd) { background: #fafbfc; }
.admin-table tbody tr:hover { background: #e6f7ff; transition: background 0.2s;}
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
    .admin-table th, .admin-table td {padding:7px 2px;}
}
</style> 
<div class="content-card" style="max-width:1100px;margin:48px auto;">
    <div class="card-header">
        <div class="card-title">后台管理员管理</div>
        <div class="card-actions">
            <form method="get" action="admins.php" style="display:inline-block;">
                <input type="text" name="kw" value="<?=admin_esc($kw)?>" placeholder="用户名" style="padding:6px 12px;width:160px;">
                <button type="submit" class="btn">搜索</button>
            </form>
            <a href="admins_add.php" class="btn" style="background:#409eff;color:#fff;">新建管理员</a>
        </div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>创建时间</th>
                <th style="width:140px;">操作</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($admins)): ?>
            <tr><td colspan="4" style="text-align:center;color:#888;">暂无管理员</td></tr>
        <?php else: foreach ($admins as $a): ?>
            <tr>
                <td><?=admin_esc($a['id'])?></td>
                <td><?=admin_esc($a['username'])?></td>
                <td><?=admin_esc($a['created_at'])?></td>
                <td>
                    <div class="op-group">
                        <a href="admins_edit.php?id=<?=$a['id']?>">编辑</a>
                        <a href="admins_view.php?id=<?=$a['id']?>">详情</a>
                        <?php if ($a['id'] != 1): ?>
                        <a href="admins_delete.php?id=<?=$a['id']?>" onclick="return confirm('确定要删除该管理员？')" style="color:#f56c6c;">删除</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?=render_pagination($page, $total_pages, $baseurl);?>
</div>
<?php require_once 'footer.php'; ?>