<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get chat type and target
$chat_type = $_GET['type'] ?? 'direct';
$target_id = $_GET['id'] ?? null;

// Get user's groups for group chat
$user_groups = [];
$result = $conn->query("SELECT g.id, g.name FROM groups g 
                       INNER JOIN group_members gm ON g.id = gm.group_id 
                       WHERE gm.user_id = $user_id");
while ($row = $result->fetch_assoc()) {
    $user_groups[] = $row;
}

// Get all users for direct chat (search functionality)
$search_query = $_GET['search'] ?? '';
$all_users = [];
if (!empty($search_query)) {
    $search_term = "%" . $conn->real_escape_string($search_query) . "%";
    $result = $conn->query("SELECT id, username, full_name, email FROM users 
                           WHERE (username LIKE '$search_term' OR full_name LIKE '$search_term' OR email LIKE '$search_term')
                           AND id != $user_id 
                           ORDER BY username ASC 
                           LIMIT 20");
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
}

// Get messages
$messages = [];
if ($chat_type === 'direct' && $target_id) {
    $target_id = intval($target_id);
    $result = $conn->query("SELECT m.*, u.username, u.full_name 
                            FROM messages m 
                            INNER JOIN users u ON m.sender_id = u.id 
                            WHERE ((m.sender_id = $user_id AND m.receiver_id = $target_id) 
                                   OR (m.sender_id = $target_id AND m.receiver_id = $user_id))
                            AND m.group_id IS NULL 
                            ORDER BY m.created_at ASC");
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Mark as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id AND sender_id = $target_id AND is_read = 0");
} elseif ($chat_type === 'group' && $target_id) {
    $target_id = intval($target_id);
    // Check if user is member
    $check = $conn->query("SELECT id FROM group_members WHERE group_id = $target_id AND user_id = $user_id");
    if ($check->num_rows > 0) {
        $result = $conn->query("SELECT m.*, u.username, u.full_name 
                                FROM messages m 
                                INNER JOIN users u ON m.sender_id = u.id 
                                WHERE m.group_id = $target_id 
                                ORDER BY m.created_at ASC");
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
}

// Get recent conversations
$conversations = [];
// Direct messages
$result = $conn->query("SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = $user_id THEN receiver_id 
                            ELSE sender_id 
                        END as other_user_id,
                        u.username, u.full_name,
                        (SELECT message FROM messages m2 
                         WHERE (m2.sender_id = $user_id AND m2.receiver_id = other_user_id) 
                            OR (m2.sender_id = other_user_id AND m2.receiver_id = $user_id)
                         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                        (SELECT created_at FROM messages m2 
                         WHERE (m2.sender_id = $user_id AND m2.receiver_id = other_user_id) 
                            OR (m2.sender_id = other_user_id AND m2.receiver_id = $user_id)
                         ORDER BY m2.created_at DESC LIMIT 1) as last_message_time
                        FROM messages m
                        INNER JOIN users u ON u.id = CASE 
                            WHEN m.sender_id = $user_id THEN m.receiver_id 
                            ELSE m.sender_id 
                        END
                        WHERE (m.sender_id = $user_id OR m.receiver_id = $user_id) 
                        AND m.group_id IS NULL
                        GROUP BY other_user_id, u.username, u.full_name
                        ORDER BY last_message_time DESC");
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}

// Get target info for display
$target_user = null;
$target_group = null;
if ($target_id) {
    if ($chat_type === 'direct') {
        $result = $conn->query("SELECT username, full_name FROM users WHERE id = $target_id");
        if ($result->num_rows > 0) {
            $target_user = $result->fetch_assoc();
        }
    } else {
        $result = $conn->query("SELECT name FROM groups WHERE id = $target_id");
        if ($result->num_rows > 0) {
            $target_group = $result->fetch_assoc();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Notely</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/chat.css">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: calc(100vh - 200px);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .chat-sidebar {
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }
        
        .chat-main {
            display: flex;
            flex-direction: column;
            background: var(--card-bg);
        }
        
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .message {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            word-wrap: break-word;
        }
        
        .message.sent {
            align-self: flex-end;
            background: var(--primary-color);
            color: white;
        }
        
        .message.received {
            align-self: flex-start;
            background: var(--bg-color);
            color: var(--text-primary);
        }
        
        .message-info {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
        }
        
        .chat-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .conversation-item:hover {
            background: var(--bg-color);
        }
        
        .conversation-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
            }
            .chat-sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="card" style="padding: 0;">
            <div class="chat-container">
                <div class="chat-sidebar">
                    <div style="padding: 1rem; border-bottom: 1px solid var(--border-color);">
                        <h2>💬 Chat</h2>
                    </div>
                    
                    <div style="padding: 1rem;">
                        <h3 style="margin-bottom: 0.75rem;">Pesan Langsung</h3>
                        
                        <!-- Search User Form -->
                        <form method="GET" style="margin-bottom: 1rem;">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($chat_type); ?>">
                            <?php if ($target_id): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($target_id); ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Cari user untuk chat..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; font-size: 0.875rem;">
                        </form>
                        
                        <!-- Search Results -->
                        <?php if (!empty($search_query)): ?>
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--primary-color);">
                                <h4 style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Hasil Pencarian:</h4>
                                <?php if (empty($all_users)): ?>
                                    <p style="color: var(--text-secondary); font-size: 0.875rem; padding: 0.5rem;">User tidak ditemukan</p>
                                <?php else: ?>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <?php foreach ($all_users as $user): ?>
                                            <div class="conversation-item" 
                                                 onclick="window.location.href='?type=direct&id=<?php echo $user['id']; ?>'">
                                                <strong><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></strong>
                                                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                                    @<?php echo htmlspecialchars($user['username']); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Recent Conversations -->
                        <?php if (empty($search_query)): ?>
                            <?php if (empty($conversations)): ?>
                                <p style="color: var(--text-secondary); font-size: 0.875rem;">Belum ada percakapan</p>
                            <?php else: ?>
                                <h4 style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Percakapan Terbaru:</h4>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item <?php echo ($chat_type === 'direct' && $target_id == $conv['other_user_id']) ? 'active' : ''; ?>" 
                                         onclick="window.location.href='?type=direct&id=<?php echo $conv['other_user_id']; ?>'">
                                        <strong><?php echo htmlspecialchars($conv['full_name'] ?: $conv['username']); ?></strong>
                                        <?php if ($conv['last_message']): ?>
                                            <p style="font-size: 0.875rem; margin-top: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: 1rem; border-top: 1px solid var(--border-color);">
                        <h3 style="margin-bottom: 0.5rem;">Grup</h3>
                        <?php if (empty($user_groups)): ?>
                            <p style="color: var(--text-secondary); font-size: 0.875rem;">Belum ada grup</p>
                        <?php else: ?>
                            <?php foreach ($user_groups as $group): ?>
                                <div class="conversation-item <?php echo ($chat_type === 'group' && $target_id == $group['id']) ? 'active' : ''; ?>" 
                                     onclick="window.location.href='?type=group&id=<?php echo $group['id']; ?>'">
                                    <strong>👥 <?php echo htmlspecialchars($group['name']); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chat-main">
                    <?php if ($target_id): ?>
                        <div class="chat-header">
                            <h3>
                                <?php 
                                if ($chat_type === 'direct' && $target_user) {
                                    echo htmlspecialchars($target_user['full_name'] ?: $target_user['username']);
                                } elseif ($chat_type === 'group' && $target_group) {
                                    echo '👥 ' . htmlspecialchars($target_group['name']);
                                }
                                ?>
                            </h3>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($messages as $msg): ?>
                                <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <?php if ($chat_type === 'group' && $msg['sender_id'] != $user_id): ?>
                                        <strong><?php echo htmlspecialchars($msg['full_name'] ?: $msg['username']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    <div class="message-info">
                                        <?php echo getTimeAgo($msg['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <form class="chat-input" method="POST" action="api/send_message.php" id="chatForm">
                            <input type="hidden" name="chat_type" value="<?php echo $chat_type; ?>">
                            <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
                            <input type="text" name="message" placeholder="Ketik pesan..." required autocomplete="off">
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </form>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">
                            <p>Pilih percakapan untuk mulai chat</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/chat.js"></script>
</body>
</html>

