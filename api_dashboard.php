<?php
require_once 'db_connect.php';

// Fetch logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM api_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Get total count for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) as count FROM api_logs");
$total = $totalStmt->fetch()['count'];
$totalPages = ceil($total / $limit);

// Calculate stats
$statsStmt = $pdo->query("
    SELECT 
        endpoint,
        COUNT(*) as call_count,
        SUM(token_count) as total_tokens,
        AVG(token_count) as avg_tokens
    FROM api_logs 
    GROUP BY endpoint
");
$stats = $statsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Dashboard - ViralMagical</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 2rem;
            min-height: 100vh;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
        }
        .stat-card h3 {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
        }
        .stat-card .sub {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 0.5rem;
        }
        .logs-table {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
        }
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .endpoint-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
        }
        .model-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            background: rgba(0, 255, 100, 0.2);
        }
        .text-truncate {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        .pagination a {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: background 0.2s;
        }
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .pagination a.active {
            background: rgba(255, 255, 255, 0.3);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            padding: 2rem;
            overflow-y: auto;
        }
        .modal-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .modal-close {
            float: right;
            font-size: 2rem;
            cursor: pointer;
            opacity: 0.7;
        }
        .modal-close:hover {
            opacity: 1;
        }
        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 API Dashboard</h1>
        
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?php echo strtoupper($stat['endpoint']); ?></h3>
                    <div class="value"><?php echo number_format($stat['call_count']); ?></div>
                    <div class="sub">
                        <?php if ($stat['total_tokens']): ?>
                            <?php echo number_format($stat['total_tokens']); ?> tokens total
                            <br>~<?php echo number_format($stat['avg_tokens']); ?> avg
                        <?php else: ?>
                            No token data
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Endpoint</th>
                        <th>Model</th>
                        <th>Tokens</th>
                        <th>Prompt</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('M j, g:i A', strtotime($log['created_at'])); ?></td>
                            <td><span class="endpoint-badge"><?php echo htmlspecialchars($log['endpoint']); ?></span></td>
                            <td><span class="model-badge"><?php echo htmlspecialchars($log['model'] ?? 'N/A'); ?></span></td>
                            <td><?php echo $log['token_count'] ? number_format($log['token_count']) : '-'; ?></td>
                            <td class="text-truncate" onclick="showModal('<?php echo htmlspecialchars(addslashes($log['prompt']), ENT_QUOTES); ?>')">
                                <?php echo htmlspecialchars(substr($log['prompt'], 0, 100)); ?>...
                            </td>
                            <td class="text-truncate" onclick="showModal('<?php echo htmlspecialchars(addslashes($log['response']), ENT_QUOTES); ?>')">
                                <?php echo htmlspecialchars(substr($log['response'], 0, 100)); ?>...
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $i === (int)$page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next →</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">×</span>
            <h2>Full Content</h2>
            <pre id="modalText"></pre>
        </div>
    </div>

    <script>
        function showModal(text) {
            document.getElementById('modalText').textContent = text;
            document.getElementById('modal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
