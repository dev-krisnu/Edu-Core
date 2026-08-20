<?php
/**
 * Admin - Events Management
 * Create and manage campus events
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['super_admin']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

$message = '';
$messageType = '';

// Handle event creation/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if ($_POST['action'] === 'create_event') {
        $event_name = trim($_POST['event_name']);
        $description = trim($_POST['description']);
        $event_date = trim($_POST['event_date']);
        $location = trim($_POST['location']);
        $category = trim($_POST['category']);

        if ($event_name && $event_date && $location && $category) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO events (event_name, description, event_date, location, category, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$event_name, $description, $event_date, $location, $category]);
                $message = 'Event created successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'edit_event') {
        $event_id = intval($_POST['event_id'] ?? 0);
        $event_name = trim($_POST['event_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = trim($_POST['event_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if ($event_id && $event_name && $event_date && $location && $category) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE events SET event_name = ?, description = ?, event_date = ?, location = ?, category = ?
                    WHERE id = ?
                ");
                $stmt->execute([$event_name, $description, $event_date, $location, $category, $event_id]);
                $message = 'Event updated successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'All fields are required to update an event.';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'delete_event') {
        $event_id = intval($_POST['event_id'] ?? 0);
        if ($event_id) {
            try {
                // event_attendance rows reference this event; clear them
                // first so the delete doesn't fail on the FK constraint.
                $pdo->prepare("DELETE FROM event_attendance WHERE event_id = ?")->execute([$event_id]);
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$event_id]);
                $message = 'Event deleted successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch all events
$stmt = $pdo->prepare("
    SELECT e.*, COUNT(DISTINCT ea.id) as total_registrations
    FROM events e
    LEFT JOIN event_attendance ea ON e.id = ea.event_id
    GROUP BY e.id
    ORDER BY e.event_date DESC
    LIMIT 50
");
$stmt->execute([]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category counts
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM events GROUP BY category");
$categoryStats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categoryStats[$row['category']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - EduCore</title>
    <?php $portalBase = '..'; include __DIR__ . '/../includes/portal_head.php'; ?>
    <style>
        .events-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 6px;
            color: rgba(245, 244, 255, 0.8);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #F5F4FF;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .submit-btn {
            padding: 12px 24px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-start;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .events-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            overflow: hidden;
        }

        .events-table th {
            background: rgba(239, 68, 68, 0.1);
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid rgba(239, 68, 68, 0.3);
            color: #F5F4FF;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .events-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.1);
            color: rgba(245, 244, 255, 0.8);
        }

        .events-table tbody tr:hover {
            background: rgba(239, 68, 68, 0.05);
        }

        .category-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.2);
            color: #FCA5A5;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
        }

        .status-completed {
            background: rgba(34, 211, 238, 0.2);
            color: #67E8F9;
        }

        .action-btn {
            padding: 6px 12px;
            background: linear-gradient(120deg, #EF4444, #FCA5A5);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 6px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10B981;
            color: #6EE7B7;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border-color: #EF4444;
            color: #FCA5A5;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 28px 0 16px 0;
            color: #F5F4FF;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .events-table {
                font-size: 0.85rem;
            }

            .events-table th,
            .events-table td {
                padding: 8px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="portal-page" data-role="admin">
    <div class="aurora-bg">
        <div class="aurora-blob b1"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grain"></div>

    <div class="container-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main style="flex: 1; z-index: 2; padding: 40px 20px;">
            <div class="events-container">
                <!-- Header -->
                <div style="margin-bottom: 30px;">
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Events Management</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Create and manage campus events</p>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Create Event Form -->
                <div class="form-section">
                    <h2 style="margin: 0 0 20px 0; color: #F5F4FF;">
                        <i class="bi bi-calendar-plus"></i> Create New Event
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="create_event">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Event Name</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="event_name" 
                                    placeholder="e.g., Annual Tech Summit" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="workshop">Workshop</option>
                                    <option value="seminar">Seminar</option>
                                    <option value="conference">Conference</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Event Date</label>
                                <input 
                                    type="datetime-local" 
                                    class="form-input" 
                                    name="event_date" 
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    name="location" 
                                    placeholder="e.g., Auditorium Hall" 
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label class="form-label">Description</label>
                            <textarea 
                                class="form-textarea" 
                                name="description" 
                                placeholder="Event description..."
                            ></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="bi bi-check-circle"></i> Create Event
                        </button>
                    </form>
                </div>

                <!-- Events List -->
                <h2 class="section-title">
                    <i class="bi bi-calendar-event"></i> All Events (<?php echo count($events); ?>)
                </h2>

                <?php if (count($events) > 0): ?>
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Category</th>
                                <th>Date & Time</th>
                                <th>Location</th>
                                <th>Registrations</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): 
                                $eventDate = new DateTime($event['event_date']);
                                $now = new DateTime();
                                $status = $eventDate < $now ? 'completed' : 'active';
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event['event_name']); ?></strong></td>
                                    <td><span class="category-badge"><?php echo ucfirst($event['category']); ?></span></td>
                                    <td><?php echo $eventDate->format('M d, Y h:i A'); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo $event['total_registrations']; ?> attendees</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="action-btn" type="button"
                                            onclick='openEditEventModal(<?php echo json_encode([
                                                "id" => $event["id"],
                                                "event_name" => $event["event_name"],
                                                "description" => $event["description"],
                                                "event_date" => (new DateTime($event["event_date"]))->format("Y-m-d\TH:i"),
                                                "location" => $event["location"],
                                                "category" => $event["category"],
                                            ]); ?>)'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?php echo addslashes($event['event_name']); ?>? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>">
                                            <button type="submit" class="action-btn">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: rgba(239, 68, 68, 0.05); border-radius: 12px; color: rgba(245, 244, 255, 0.6);">
                        <i class="bi bi-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No events created yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Event Modal -->
    <div id="editEventOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:999; align-items:center; justify-content:center;">
        <div style="background:#1a1a3e; border:1px solid rgba(239,68,68,0.3); border-radius:16px; padding:28px; max-width:520px; width:90%;">
            <h2 style="color:#F5F4FF; margin:0 0 20px 0;"><i class="bi bi-pencil-square"></i> Edit Event</h2>
            <form method="POST" id="editEventForm">
                <input type="hidden" name="action" value="edit_event">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Event Name</label>
                        <input type="text" class="form-input" name="event_name" id="edit_event_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" id="edit_category" required>
                            <option value="">Select Category</option>
                            <option value="academic">Academic</option>
                            <option value="sports">Sports</option>
                            <option value="cultural">Cultural</option>
                            <option value="workshop">Workshop</option>
                            <option value="seminar">Seminar</option>
                            <option value="conference">Conference</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Date</label>
                        <input type="datetime-local" class="form-input" name="event_date" id="edit_event_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-input" name="location" id="edit_location" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" id="edit_description"></textarea>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="submit-btn" style="flex:1;"><i class="bi bi-check-circle"></i> Save Changes</button>
                    <button type="button" class="action-btn" style="flex:0 0 auto;" onclick="closeEditEventModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openEditEventModal(event) {
            document.getElementById('edit_event_id').value = event.id;
            document.getElementById('edit_event_name').value = event.event_name;
            document.getElementById('edit_category').value = event.category;
            document.getElementById('edit_event_date').value = event.event_date;
            document.getElementById('edit_location').value = event.location;
            document.getElementById('edit_description').value = event.description || '';
            document.getElementById('editEventOverlay').style.display = 'flex';
        }
        function closeEditEventModal() {
            document.getElementById('editEventOverlay').style.display = 'none';
        }
    </script>
</body>
</html>
