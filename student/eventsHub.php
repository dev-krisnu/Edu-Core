<?php
/**
 * Events Hub - Campus Events Calendar
 * Browse and register for campus events
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole(['student']);

$currentUser = getCurrentUser();
$pdo = getDbConnection();

// Fetch events
$stmt = $pdo->query("
    SELECT * FROM events 
    ORDER BY event_date DESC
    LIMIT 20
");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Hub - EduCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/educore.css" rel="stylesheet">
    <link href="../assets/css/themes.css" rel="stylesheet">
    <style>
        .events-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .event-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .event-card:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(99, 102, 241, 0.25);
        }

        .event-header {
            background: linear-gradient(135deg, #6366F1, #22D3EE);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .event-date {
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 10px 14px;
            border-radius: 8px;
        }

        .event-day {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
        }

        .event-month {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
        }

        .event-badge {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .event-body {
            padding: 20px;
        }

        .event-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #F5F4FF;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(245, 244, 255, 0.7);
        }

        .meta-item i {
            color: #22D3EE;
            width: 18px;
        }

        .event-description {
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .event-footer {
            display: flex;
            gap: 8px;
            padding-top: 16px;
            border-top: 1px solid rgba(99, 102, 241, 0.2);
        }

        .event-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-register {
            background: linear-gradient(120deg, #6366F1, #22D3EE);
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-details {
            background: rgba(99, 102, 241, 0.1);
            color: #67E8F9;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .btn-details:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .attendance-count {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: rgba(245, 244, 255, 0.6);
            padding: 8px 12px;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 6px;
            margin-top: 12px;
        }

        .attendance-count i {
            color: #6366F1;
        }

        .no-events {
            text-align: center;
            padding: 60px 20px;
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 14px;
            color: rgba(245, 244, 255, 0.6);
        }

        .no-events i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }

            .event-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body data-role="student">
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
                    <h1 class="h-display" style="margin: 0 0 8px 0;">Events Hub</h1>
                    <p style="color: rgba(255, 255, 255, 0.6); margin: 0;">Discover and register for exciting campus events</p>
                </div>

                <!-- Events Grid -->
                <?php if (count($events) > 0): ?>
                    <div class="events-grid">
                        <?php foreach ($events as $event): 
                            $eventDate = new DateTime($event['event_date']);
                            $attendance = rand(15, 150);
                        ?>
                            <div class="event-card">
                                <div class="event-header">
                                    <div>
                                        <div class="event-date">
                                            <div class="event-day"><?php echo $eventDate->format('d'); ?></div>
                                            <div class="event-month"><?php echo $eventDate->format('M'); ?></div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="event-badge" style="margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($event['category'] ?? 'Event'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="event-body">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            <i class="bi bi-clock"></i>
                                            <span><?php echo $eventDate->format('h:i A'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?php echo htmlspecialchars($event['location'] ?? 'Auditorium'); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-person-check"></i>
                                            <span><?php echo htmlspecialchars($event['organizer'] ?? 'Admin'); ?></span>
                                        </div>
                                    </div>

                                    <p class="event-description">
                                        <?php echo htmlspecialchars(substr($event['description'] ?? 'Event details', 0, 80)) . '...'; ?>
                                    </p>

                                    <div class="attendance-count">
                                        <i class="bi bi-people"></i>
                                        <span><?php echo $attendance; ?> Attending</span>
                                    </div>

                                    <div class="event-footer">
                                        <button class="event-btn btn-register" onclick="alert('Registered for: ' + '<?php echo addslashes($event['title']); ?>')">
                                            <i class="bi bi-check-circle"></i> Register
                                        </button>
                                        <button class="event-btn btn-details">
                                            <i class="bi bi-info-circle"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-events">
                        <i class="bi bi-calendar2-x"></i>
                        <p>No events scheduled at the moment. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
