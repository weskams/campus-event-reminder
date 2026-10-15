<?php
declare(strict_types=1);

/**
 * Events, registrations and reminders.
 *
 * Times are stored as local DATETIME values in config('app.timezone').
 * db() sets MySQL's time zone to match, so NOW() agrees with PHP.
 */

/** How long before an event its reminder becomes due. */
const REMINDER_LEAD_TIME = 'PT1H';

/** Columns returned for every event listing. */
const EVENT_COLUMNS = '
    e.id, e.organiser_id, e.title, e.description, e.starts_at,
    e.venue, e.category, e.status, o.name AS organiser_name,
    (SELECT COUNT(*) FROM event_registrations c
     WHERE c.event_id = e.id) AS attendee_count';

function is_upcoming(array $event): bool
{
    return $event['status'] === 'active'
        && new DateTimeImmutable($event['starts_at']) > new DateTimeImmutable();
}

// ---------------------------------------------------------------------
// Reading events
// ---------------------------------------------------------------------

/**
 * WHERE clause for upcoming active events.
 *
 * $filters: search (string), category (string), within_hours (int)
 */
function upcoming_events_filter(array $filters): array
{
    $where = ["e.status = 'active'", 'e.starts_at >= NOW()'];
    $params = [];

    if (($filters['search'] ?? '') !== '') {
        $term = '%' . like_escape($filters['search']) . '%';
        $where[] = '(e.title LIKE :search_title OR e.venue LIKE :search_venue)';
        $params['search_title'] = $term;
        $params['search_venue'] = $term;
    }

    if (($filters['category'] ?? '') !== '') {
        $where[] = 'e.category = :category';
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['within_hours'])) {
        $where[] = 'e.starts_at <= NOW() + INTERVAL '
            . (int) $filters['within_hours'] . ' HOUR';
    }

    return [implode(' AND ', $where), $params];
}

/** Adds an is_registered column for the signed-in student, if any. */
function is_registered_column(?int $studentId): array
{
    if ($studentId === null) {
        return ['0 AS is_registered', []];
    }

    return [
        'EXISTS (SELECT 1 FROM event_registrations r
                 WHERE r.event_id = e.id AND r.student_id = :student_id)
         AS is_registered',
        ['student_id' => $studentId],
    ];
}

function count_upcoming_events(array $filters): int
{
    [$where, $params] = upcoming_events_filter($filters);

    $stmt = db()->prepare("SELECT COUNT(*) FROM events e WHERE $where");
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function find_upcoming_events(
    array $filters,
    int $limit,
    int $offset,
    ?int $studentId = null
): array {
    [$where, $params] = upcoming_events_filter($filters);
    [$registeredColumn, $registeredParams] = is_registered_column($studentId);

    $stmt = db()->prepare(
        'SELECT ' . EVENT_COLUMNS . ", $registeredColumn
         FROM events e
         JOIN organisers o ON o.id = e.organiser_id
         WHERE $where
         ORDER BY e.starts_at, e.id
         LIMIT " . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute($params + $registeredParams);

    return $stmt->fetchAll();
}

function find_event(int $id, ?int $studentId = null): ?array
{
    [$registeredColumn, $params] = is_registered_column($studentId);

    $stmt = db()->prepare(
        'SELECT ' . EVENT_COLUMNS . ", $registeredColumn
         FROM events e
         JOIN organisers o ON o.id = e.organiser_id
         WHERE e.id = :id"
    );
    $stmt->execute($params + ['id' => $id]);

    return $stmt->fetch() ?: null;
}

/** An active event owned by this organiser, or a 404/403 page. */
function load_owned_event(int $id, int $organiserId): array
{
    $event = find_event($id);

    if ($event === null || $event['status'] !== 'active') {
        not_found('This event does not exist or has been cancelled.');
    }

    if ((int) $event['organiser_id'] !== $organiserId) {
        render_error(403, 'Not your event', 'You can only manage events you created.');
    }

    return $event;
}

/** An organiser's upcoming events, or their past and cancelled ones. */
function find_organiser_events(int $organiserId, bool $upcoming): array
{
    $condition = $upcoming
        ? "e.status = 'active' AND e.starts_at >= NOW()
           ORDER BY e.starts_at"
        : "(e.status = 'cancelled' OR e.starts_at < NOW())
           ORDER BY e.starts_at DESC LIMIT 50";

    $stmt = db()->prepare(
        'SELECT ' . EVENT_COLUMNS . ", 0 AS is_registered
         FROM events e
         JOIN organisers o ON o.id = e.organiser_id
         WHERE e.organiser_id = :organiser_id AND $condition"
    );
    $stmt->execute(['organiser_id' => $organiserId]);

    return $stmt->fetchAll();
}

/** Upcoming events the student has registered for. */
function find_student_events(int $studentId): array
{
    $stmt = db()->prepare(
        'SELECT ' . EVENT_COLUMNS . ", 1 AS is_registered
         FROM event_registrations mine
         JOIN events e ON e.id = mine.event_id
         JOIN organisers o ON o.id = e.organiser_id
         WHERE mine.student_id = :student_id
           AND e.status = 'active'
           AND e.starts_at >= NOW()
         ORDER BY e.starts_at"
    );
    $stmt->execute(['student_id' => $studentId]);

    return $stmt->fetchAll();
}

function find_event_attendees(int $eventId): array
{
    $stmt = db()->prepare(
        'SELECT s.full_name, s.registration_number, s.email, er.registered_at
         FROM event_registrations er
         JOIN students s ON s.id = er.student_id
         WHERE er.event_id = :event_id
         ORDER BY er.registered_at'
    );
    $stmt->execute(['event_id' => $eventId]);

    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------
// Event form
// ---------------------------------------------------------------------

function empty_event_form(): array
{
    return [
        'title' => '',
        'description' => '',
        'event_date' => '',
        'event_time' => '',
        'venue' => '',
        'category' => 'General',
    ];
}

function read_event_form(): array
{
    $form = [];

    foreach (array_keys(empty_event_form()) as $field) {
        $form[$field] = post_string($field);
    }

    return $form;
}

function event_form_from_row(array $event): array
{
    return [
        'title' => $event['title'],
        'description' => $event['description'] ?? '',
        'event_date' => substr($event['starts_at'], 0, 10),
        'event_time' => substr($event['starts_at'], 11, 5),
        'venue' => $event['venue'],
        'category' => $event['category'],
    ];
}

/**
 * Validate a submitted event form.
 *
 * Returns [errors, starts_at] where starts_at is "Y-m-d H:i:s" when valid.
 */
function validate_event(array $form): array
{
    $errors = [];

    if ($form['title'] === '' || mb_strlen($form['title']) > 150) {
        $errors[] = 'Enter an event title of up to 150 characters.';
    }

    if ($form['venue'] === '' || mb_strlen($form['venue']) > 150) {
        $errors[] = 'Enter a venue of up to 150 characters.';
    }

    if (mb_strlen($form['description']) > 5000) {
        $errors[] = 'Keep the description under 5,000 characters.';
    }

    if (!in_array($form['category'], EVENT_CATEGORIES, true)) {
        $errors[] = 'Choose a valid category.';
    }

    $input = $form['event_date'] . ' ' . $form['event_time'];
    $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $input);

    if (!$startsAt || $startsAt->format('Y-m-d H:i') !== $input) {
        $errors[] = 'Enter a valid date and time.';

        return [$errors, null];
    }

    if ($startsAt <= new DateTimeImmutable()) {
        $errors[] = 'The event must be scheduled in the future.';
    }

    return [$errors, $startsAt->format('Y-m-d H:i:s')];
}

// ---------------------------------------------------------------------
// Changing events
// ---------------------------------------------------------------------

function create_event(int $organiserId, array $form, string $startsAt): int
{
    db()->prepare(
        'INSERT INTO events
            (organiser_id, title, description, starts_at, venue, category)
         VALUES
            (:organiser_id, :title, :description, :starts_at, :venue, :category)'
    )->execute([
        'organiser_id' => $organiserId,
        'title' => $form['title'],
        'description' => $form['description'] !== '' ? $form['description'] : null,
        'starts_at' => $startsAt,
        'venue' => $form['venue'],
        'category' => $form['category'],
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Save changes to an event. If the start time moved, every registered
 * student's reminder is rescheduled and shown again.
 *
 * Returns true if the start time changed.
 */
function update_event(array $event, array $form, string $startsAt): bool
{
    $timeChanged = $event['starts_at'] !== $startsAt;

    transaction(function (PDO $pdo) use ($event, $form, $startsAt, $timeChanged): void {
        $pdo->prepare(
            "UPDATE events
             SET title = :title,
                 description = :description,
                 starts_at = :starts_at,
                 venue = :venue,
                 category = :category
             WHERE id = :id AND status = 'active'"
        )->execute([
            'title' => $form['title'],
            'description' => $form['description'] !== '' ? $form['description'] : null,
            'starts_at' => $startsAt,
            'venue' => $form['venue'],
            'category' => $form['category'],
            'id' => $event['id'],
        ]);

        if ($timeChanged) {
            $pdo->prepare(
                "UPDATE reminders
                 SET scheduled_for = :scheduled_for, status = 'pending'
                 WHERE event_id = :event_id AND status <> 'cancelled'"
            )->execute([
                'scheduled_for' => reminder_time($startsAt),
                'event_id' => $event['id'],
            ]);
        }
    });

    return $timeChanged;
}

/** Cancel an event and withdraw its pending reminders. */
function cancel_event(int $eventId): void
{
    transaction(function (PDO $pdo) use ($eventId): void {
        $pdo->prepare(
            "UPDATE events SET status = 'cancelled'
             WHERE id = :id AND status = 'active'"
        )->execute(['id' => $eventId]);

        $pdo->prepare(
            "UPDATE reminders SET status = 'cancelled'
             WHERE event_id = :event_id AND status = 'pending'"
        )->execute(['event_id' => $eventId]);
    });
}

// ---------------------------------------------------------------------
// Registrations
// ---------------------------------------------------------------------

/** When a reminder for an event starting at $startsAt becomes due. */
function reminder_time(string $startsAt): string
{
    $remindAt = (new DateTimeImmutable($startsAt))
        ->sub(new DateInterval(REMINDER_LEAD_TIME));

    return max($remindAt, new DateTimeImmutable())->format('Y-m-d H:i:s');
}

/**
 * Register a student and schedule their reminder.
 *
 * Returns "registered", "already_registered" or "unavailable".
 */
function register_student_for_event(int $studentId, int $eventId): string
{
    return transaction(function (PDO $pdo) use ($studentId, $eventId): string {
        // The shared lock stops the event being cancelled mid-registration.
        $stmt = $pdo->prepare(
            "SELECT starts_at FROM events
             WHERE id = :id AND status = 'active' AND starts_at > NOW()
             LOCK IN SHARE MODE"
        );
        $stmt->execute(['id' => $eventId]);
        $startsAt = $stmt->fetchColumn();

        if ($startsAt === false) {
            return 'unavailable';
        }

        try {
            $pdo->prepare(
                'INSERT INTO event_registrations (student_id, event_id)
                 VALUES (:student_id, :event_id)'
            )->execute(['student_id' => $studentId, 'event_id' => $eventId]);
        } catch (PDOException $e) {
            if (is_duplicate_key($e)) {
                return 'already_registered';
            }

            throw $e;
        }

        $remindAt = reminder_time($startsAt);

        $pdo->prepare(
            "INSERT INTO reminders (student_id, event_id, reminder_type, scheduled_for)
             VALUES (:student_id, :event_id, 'one_hour_before', :scheduled_for)
             ON DUPLICATE KEY UPDATE
                 scheduled_for = :scheduled_for_again, status = 'pending'"
        )->execute([
            'student_id' => $studentId,
            'event_id' => $eventId,
            'scheduled_for' => $remindAt,
            'scheduled_for_again' => $remindAt,
        ]);

        return 'registered';
    });
}

/** Returns false if the student wasn't registered or the event has started. */
function unregister_student_from_event(int $studentId, int $eventId): bool
{
    return transaction(function (PDO $pdo) use ($studentId, $eventId): bool {
        $stmt = $pdo->prepare(
            'DELETE er FROM event_registrations er
             JOIN events e ON e.id = er.event_id
             WHERE er.student_id = :student_id
               AND er.event_id = :event_id
               AND e.starts_at > NOW()'
        );
        $stmt->execute(['student_id' => $studentId, 'event_id' => $eventId]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        $pdo->prepare(
            'DELETE FROM reminders
             WHERE student_id = :student_id AND event_id = :event_id'
        )->execute(['student_id' => $studentId, 'event_id' => $eventId]);

        return true;
    });
}

// ---------------------------------------------------------------------
// Reminders
// ---------------------------------------------------------------------

/** Shared WHERE clause for reminders that are due and still relevant. */
const DUE_REMINDERS_WHERE = "
    r.student_id = :student_id
    AND r.status = 'pending'
    AND r.scheduled_for <= NOW()
    AND e.status = 'active'
    AND e.starts_at > NOW()";

function find_due_reminders(int $studentId): array
{
    $stmt = db()->prepare(
        'SELECT r.id AS reminder_id, e.id AS event_id, e.title,
                e.starts_at, e.venue
         FROM reminders r
         JOIN events e ON e.id = r.event_id
         WHERE ' . DUE_REMINDERS_WHERE . '
         ORDER BY e.starts_at'
    );
    $stmt->execute(['student_id' => $studentId]);

    return $stmt->fetchAll();
}

function count_due_reminders(int $studentId): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM reminders r
         JOIN events e ON e.id = r.event_id
         WHERE ' . DUE_REMINDERS_WHERE
    );
    $stmt->execute(['student_id' => $studentId]);

    return (int) $stmt->fetchColumn();
}

function mark_reminder_read(int $reminderId, int $studentId): void
{
    db()->prepare(
        "UPDATE reminders SET status = 'read'
         WHERE id = :id AND student_id = :student_id AND status = 'pending'"
    )->execute(['id' => $reminderId, 'student_id' => $studentId]);
}
