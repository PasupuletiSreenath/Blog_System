<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$format = $_GET['format'] ?? 'csv';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : "";
$authorFilter = isset($_GET['author']) ? (int)$_GET['author'] : 0;

// Build query
$conditions = [];
$params = [];

if ($search !== "") {
    $conditions[] = "(posts.title LIKE :search OR posts.content LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($authorFilter > 0) {
    $conditions[] = "posts.user_id = :author";
    $params[':author'] = $authorFilter;
}

$whereSql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "
    SELECT posts.title, posts.content, posts.created_at, users.username, categories.name AS category_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    LEFT JOIN categories ON posts.category_id = categories.id
    $whereSql
    ORDER BY posts.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=logbook_posts_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, array('Title', 'Author', 'Category', 'Date', 'Content'));
    
    foreach ($posts as $post) {
        // Strip HTML from TinyMCE content for CSV
        $plainContent = trim(preg_replace('/\s+/', ' ', strip_tags($post['content'])));
        fputcsv($output, array(
            $post['title'],
            $post['username'],
            $post['category_name'] ?? 'Uncategorized',
            date("Y-m-d H:i:s", strtotime($post['created_at'])),
            $plainContent
        ));
    }
    fclose($output);
    exit;
} elseif ($format === 'pdf') {
    // Basic HTML print view that serves as PDF generation
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Export PDF - The Logbook</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .post { margin-bottom: 40px; page-break-inside: avoid; }
            .post-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .post-meta { font-size: 14px; color: #666; font-style: italic; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
            .post-content { font-size: 14px; }
            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="no-print" style="margin-bottom: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print to PDF</button>
            <a href="index.php" style="margin-left: 10px;">Back to Blog</a>
        </div>
        
        <div class="header">
            <h1>The Logbook - Exported Posts</h1>
            <p>Generated on <?php echo date('F d, Y H:i:s'); ?></p>
        </div>
        
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                <div class="post-meta">
                    By <?php echo htmlspecialchars($post['username']); ?> | 
                    Category: <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?> | 
                    Date: <?php echo date("F d, Y", strtotime($post['created_at'])); ?>
                </div>
                <div class="post-content">
                    <?php echo $post['content']; // HTML content from TinyMCE ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($posts) === 0): ?>
            <p style="text-align: center;">No posts found for the selected criteria.</p>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
} else {
    die("Invalid format requested.");
}
