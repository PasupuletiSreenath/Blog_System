<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$postId) {
    header("Location: index.php");
    exit;
}

$csrfToken = generateCsrfToken();
$isLoggedIn = isset($_SESSION['user_id']);

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $content = sanitizeInput($_POST['comment'] ?? '');
        if ($content === '') {
            $_SESSION['flash_error'] = "Comment cannot be empty.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            if ($stmt->execute([$postId, $_SESSION['user_id'], $content])) {
                $_SESSION['flash_success'] = "Comment added successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to add comment.";
            }
        }
    }
    header("Location: post.php?id=" . $postId);
    exit;
}

// Fetch Post Details
$query = "
    SELECT posts.*, users.username, users.profile_pic, categories.name AS category_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    LEFT JOIN categories ON posts.category_id = categories.id
    WHERE posts.id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: index.php");
    exit;
}

// Fetch Tags
$tagStmt = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
$tagStmt->execute([$postId]);
$tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Comments
$commentStmt = $pdo->prepare("
    SELECT c.*, u.username, u.profile_pic 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at DESC
");
$commentStmt->execute([$postId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-0 overflow-hidden mb-5 border-0 shadow-lg">
            <?php if (!empty($post['image'])): ?>
                <img src="assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" class="w-100 object-fit-cover" style="max-height: 400px;" alt="Featured Image">
            <?php endif; ?>
            
            <div class="p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <?php if ($post['category_name']): ?>
                        <span class="badge badge-category rounded-pill py-2 px-3"><?php echo htmlspecialchars($post['category_name']); ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill py-2 px-3">Uncategorized</span>
                    <?php endif; ?>
                    
                    <span class="text-muted"><i class="bi bi-clock me-1"></i> <?php echo date("F d, Y", strtotime($post['created_at'])); ?></span>
                </div>
                
                <h1 class="fw-bold mb-4 text-dark"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="d-flex align-items-center mb-5 pb-4 border-bottom">
                    <?php
                    $pic = !empty($post['profile_pic']) ? 'assets/uploads/profiles/' . $post['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=random';
                    ?>
                    <img src="<?php echo htmlspecialchars($pic); ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                    <div>
                        <p class="mb-0 fw-bold">By <?php echo htmlspecialchars($post['username']); ?></p>
                        <p class="mb-0 text-muted small">Author</p>
                    </div>
                </div>
                
                <div class="post-content mb-5">
                    <?php echo $post['content']; // Outputting raw content from TinyMCE ?>
                </div>
                
                <?php if (count($tags) > 0): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Tags:</h6>
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge badge-tag rounded-pill px-3 py-2 me-2 mb-2"><i class="bi bi-hash"></i><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Comments Section -->
        <h3 class="fw-bold mb-4">Comments (<?php echo count($comments); ?>)</h3>
        
        <?php if ($isLoggedIn): ?>
            <div class="glass-card p-4 mb-5 border-0 shadow-sm">
                <h5 class="fw-bold mb-3">Leave a Reply</h5>
                <form method="POST" action="post.php?id=<?php echo $postId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <div class="mb-3">
                        <textarea name="comment" class="form-control bg-light border-0" rows="4" placeholder="Write your comment here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-send me-1"></i> Post Comment</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info rounded-3 shadow-sm mb-5">
                <i class="bi bi-info-circle-fill me-2"></i> You must be <a href="login.php" class="fw-bold text-decoration-none">logged in</a> to post a comment.
            </div>
        <?php endif; ?>
        
        <div class="mb-5">
            <?php if (count($comments) === 0): ?>
                <p class="text-muted text-center py-4 bg-glass rounded-3">No comments yet. Be the first to start the conversation!</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div class="comment-box shadow-sm d-flex">
                        <?php
                        $cpic = !empty($c['profile_pic']) ? 'assets/uploads/profiles/' . $c['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($c['username']) . '&background=random';
                        ?>
                        <img src="<?php echo htmlspecialchars($cpic); ?>" class="rounded-circle me-3 mt-1" style="width: 45px; height: 45px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($c['username']); ?></h6>
                                <small class="text-muted"><?php echo date("M d, Y h:i A", strtotime($c['created_at'])); ?></small>
                            </div>
                            <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
