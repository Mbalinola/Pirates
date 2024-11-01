<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.html');
    exit();
}

include 'db_connection.php';

// Check if a new message is being sent
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $sender_id = 3; // Admin ID (update this if your admin ID is different)
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Check if we're sending to a group or an individual
    if (isset($_POST['receiver_id'])) {
        // Send to an individual user
        $receiver_id = mysqli_real_escape_string($conn, $_POST['receiver_id']);
        $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES ('$sender_id', '$receiver_id', '$message')";
        mysqli_query($conn, $query);
    } elseif (isset($_POST['user_group'])) {
        // Send to a group (Approved or Rejected users)
        $user_group = $_POST['user_group'];
        $status = $user_group == 'approved' ? 'Approved' : 'Declined';
        $group_query = "SELECT id FROM registrations WHERE status = '$status'";
        $group_result = mysqli_query($conn, $group_query);

        while ($user = mysqli_fetch_assoc($group_result)) {
            $receiver_id = $user['id'];
            $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES ('$sender_id', '$receiver_id', '$message')";
            mysqli_query($conn, $query);
        }

        $_SESSION['message'] = 'Message sent to all ' . ucfirst($status) . ' users successfully.';
    }

    header("Location: messages.php");
    exit();
}

// Fetch all users for admin to select from
$users_query = "SELECT id, name FROM registrations";
$users_result = mysqli_query($conn, $users_query);

// Fetch messages for a selected user if provided
$messages = [];
if (isset($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
    $messages_query = "SELECT * FROM messages WHERE (sender_id = 1 AND receiver_id = '$user_id') OR (sender_id = '$user_id' AND receiver_id = 1) ORDER BY timestamp ASC";
    $messages_result = mysqli_query($conn, $messages_query);
    while ($row = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Admin Messages</title>
    <style>
        html {
            height: 100%;
            background-image: linear-gradient(to right top, #8e44ad 0%, #3498db 100%);
        }

        header {
            text-align: center;
            padding: 20px 0;
            color: white;
            position: relative;
            z-index: 1;
        }

        header h1 {
            font-family: "Open Sans", sans-serif;
            font-size: 2.5em;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        nav {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px 0;
            position: relative;
            z-index: 1;
        }

        nav ul {
            text-align: center;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 25%, rgba(255, 255, 255, 0.2) 75%, rgba(255, 255, 255, 0) 100%);
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1), inset 0 0 1px rgba(255, 255, 255, 0.6);
            border-radius: 10px;
            transition: background 0.5s ease;
        }

        nav ul li {
            display: inline-block;
        }

        nav ul li a {
            padding: 18px;
            font-family: "Open Sans", sans-serif;
            text-transform: uppercase;
            color: rgba(0, 35, 122, 0.5);
            font-size: 18px;
            text-decoration: none;
            display: block;
            transition: color 0.3s ease, background 0.3s ease;
            color: white;
        }

        nav ul li a:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1), inset 0 0 1px rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.1);
            color: rgba(0, 35, 122, 0.7);
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Messages</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Send a Message</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="notification success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="notification error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <form action="messages.php" method="POST">
            <label for="receiver_id">Select Recipient:</label>
            <select name="receiver_id" id="receiver_id">
                <option value="">Select an individual user</option>
                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($user_id) && $user_id == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="user_group">Or send to a group:</label>
            <select name="user_group" id="user_group">
                <option value="">Select a group</option>
                <option value="approved">All Approved Users</option>
                <option value="rejected">All Rejected Users</option>
            </select>

            <label for="message">Message:</label>
            <textarea name="message" id="message" rows="4" placeholder="Type your message..." required></textarea>
            
            <button type="submit">Send Message</button>
        </form>

        <?php if (!empty($messages)): ?>
            <h2>Conversation with User ID: <?php echo htmlspecialchars($user_id); ?></h2>
            <div class="messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="<?php echo $msg['sender_id'] == 1 ? 'message admin' : 'message user'; ?>">
                        <p><?php echo htmlspecialchars($msg['message']); ?></p>
                        <span><?php echo $msg['timestamp']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($user_id)): ?>
            <p>No messages with this user yet.</p>
        <?php endif; ?>
    </main>
</body>
</html>
