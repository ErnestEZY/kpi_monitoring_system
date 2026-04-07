<?php
/**
 * Authentication helpers
 * All session-based auth checks live here so every page
 * has a single, consistent place to call.
 */

/**
 * Redirect to login if the supervisor is not authenticated.
 * Call at the top of every protected page.
 */
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['supervisor_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Returns an <img> tag if the staff photo exists, otherwise null.
 * $photo  — value from staff.photo column (e.g. "Alice_Johnson.jpg")
 * $size   — CSS size string applied to width & height (default "60px")
 * $depth  — how many levels up from the calling file to assets/ (default "../")
 */
function staffPhotoTag(?string $photo, string $size = '60px', string $depth = '../'): ?string {
    if (!$photo) return null;
    $path = $depth . 'assets/photos/' . $photo;
    return sprintf(
        '<img src="%s" alt="photo" style="width:%s;height:%s;border-radius:50%;object-fit:cover;">',
        htmlspecialchars($path), $size, $size
    );
}

/**
 * Returns true when a valid supervisor session exists.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['supervisor_id']);
}

/**
 * Returns the logged-in supervisor's display name.
 */
function getSupervisorName(): string {
    return $_SESSION['supervisor_name'] ?? 'Supervisor';
}

/**
 * Returns the logged-in supervisor's ID, or null if not authenticated.
 * Callers that write to the DB should treat null as an error.
 */
function getSupervisorId(): ?int {
    $id = $_SESSION['supervisor_id'] ?? null;
    return $id !== null ? (int) $id : null;
}

/**
 * Returns the logged-in supervisor's email address.
 */
function getSupervisorEmail(): string {
    return $_SESSION['supervisor_email'] ?? '';
}

/**
 * Emit a JSON 401 and exit — used by API endpoints.
 */
function requireLoginApi(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}
