<?php
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'php_comments';

date_default_timezone_set('Asia/Ho_Chi_Minh');
try {
    $pdo = new PDO('mysql:host=' . $DATABASE_HOST . ';dbname=' . $DATABASE_NAME . ';charset=utf8', $DATABASE_USER, $DATABASE_PASS);
} catch (PDOException $exception) {
    exit(json_encode(['error' => 'Failed to connect to database: ' . $exception->getMessage()]));
}

$allowed_tables = ['cmt_p1', 'cmt_p2', 'cmt_p3'];
$table_name = isset($_GET['table']) && in_array($_GET['table'], $allowed_tables) ? $_GET['table'] : exit(json_encode(['error' => 'Invalid table specified!']));

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second'
    );

    foreach ($string as $k => $value) {
        if ($k === 'w' && $weeks) {
            $string[$k] = $weeks . ' ' . ($weeks > 1 ? $value . 's' : $value);
        } elseif ($k !== 'w' && $diff->$k) {
            $string[$k] = $diff->$k . ' ' . ($diff->$k > 1 ? $value . 's' : $value);
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function show_comments($comments, $parent_id = -1) {
    $html = '';
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parent_id) {
            $html .= '<div class="comment">
                <div>
                    <h3 class="name">' . htmlspecialchars($comment['name'], ENT_QUOTES) . '</h3>
                    <span class="date">' . time_elapsed_string($comment['submit_date']) . '</span>
                </div>
                <p class="content">' . nl2br(htmlspecialchars($comment['content'], ENT_QUOTES)) . '</p>
                <a class="reply_comment_btn" href="#" data-comment-id="' . $comment['id'] . '">Reply</a>
                ' . show_write_comment_form($comment['id']) . '
                <div class="replies">
                    ' . show_comments($comments, $comment['id']) . '
                </div>
            </div>';
        }
    }
    return $html;
}

function show_write_comment_form($parent_id = -1) {
    return '<div class="write_comment" data-comment-id="' . $parent_id . '">
        <form>
            <input name="parent_id" type="hidden" value="' . $parent_id . '">
            <input name="name" type="text" placeholder="Your Name" required>
            <textarea name="content" placeholder="Write your comment here..." required></textarea>
            <button type="submit">Submit Comment</button>
        </form>
    </div>';
}

if (isset($_GET['page_id'])) {
    if (isset($_POST['name'], $_POST['content'])) {
        $stmt = $pdo->prepare("INSERT INTO $table_name (page_id, parent_id, name, content, submit_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$_GET['page_id'], $_POST['parent_id'], $_POST['name'], $_POST['content']]);
        // exit(json_encode(['success' => 'Your comment has been submitted!']));
        exit('Your comment has been submitted!');
    }

    $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE page_id = ? ORDER BY submit_date ASC");
    $stmt->execute([$_GET['page_id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total_comments FROM $table_name WHERE page_id = ?");
    $stmt->execute([$_GET['page_id']]);
    $comments_info = $stmt->fetch(PDO::FETCH_ASSOC);

    $comments_info = $comments_info ?: ['total_comments' => 0];
} else {
    exit(json_encode(['error' => 'No page ID specified!']));
}
?>

<div class="comment_header">
    <span class="total"><?=$comments_info['total_comments']?> comments</span>
    <a href="#" class="write_comment_btn" data-comment-id="-1">Write Comment</a>
</div>
<?=show_write_comment_form()?>
<?=show_comments($comments)?>