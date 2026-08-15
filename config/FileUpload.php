<?php
/**
 * File Upload Handler
 * Secure file upload with validation for assignments, resources, resumes
 */

declare(strict_types=1);

class FileUploadHandler
{
    private string $uploadDir;
    private array $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'];
    private int $maxFileSize = 52428800; // 50MB
    private string $uploadUrl = '/uploads/';

    public function __construct(string $uploadDir = null)
    {
        $this->uploadDir = $uploadDir ??  __DIR__ . '/../uploads';
        $this->ensureUploadDirectory();
    }

    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Create .gitkeep file
        $gitkeepPath = $this->uploadDir . '/.gitkeep';
        if (!file_exists($gitkeepPath)) {
            touch($gitkeepPath);
        }
    }

    /**
     * Handle file upload with validation
     *
     * @param array $fileInput $_FILES['field_name'] array
     * @param string $subdirectory Optional subdirectory (e.g., 'assignments', 'resources')
     * @return array ['success' => bool, 'file' => string, 'error' => string]
     */
    public function upload(array $fileInput, string $subdirectory = ''): array
    {
        // Validate file upload
        if (!isset($fileInput['tmp_name']) || empty($fileInput['tmp_name'])) {
            return ['success' => false, 'error' => 'No file provided'];
        }

        if ($fileInput['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Temporary directory not available',
                UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
            ];
            return ['success' => false, 'error' => $errors[$fileInput['error']] ?? 'Unknown error'];
        }

        // Validate file size
        if ($fileInput['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'File exceeds maximum size of 50MB'];
        }

        // Validate file extension
        $extension = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $this->allowedExtensions)];
        }

        // Validate MIME type
        $mimeType = mime_content_type($fileInput['tmp_name']);
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'application/zip'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        // Create subdirectory if specified
        $targetDir = $this->uploadDir;
        if (!empty($subdirectory)) {
            $targetDir = $this->uploadDir . '/' . basename($subdirectory);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $targetDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($fileInput['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        // Make file readable
        chmod($filepath, 0644);

        $relativePath = ($subdirectory ? $subdirectory . '/' : '') . $filename;

        return [
            'success' => true,
            'file' => $relativePath,
            'url' => $this->uploadUrl . $relativePath,
            'size' => $fileInput['size'],
            'type' => $extension
        ];
    }

    /**
     * Delete uploaded file
     */
    public function delete(string $filepath): bool
    {
        $fullPath = $this->uploadDir . '/' . basename($filepath);
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Get file URL
     */
    public function getUrl(string $filepath): string
    {
        return $this->uploadUrl . basename($filepath);
    }

    /**
     * Validate if file exists
     */
    public function exists(string $filepath): bool
    {
        return file_exists($this->uploadDir . '/' . basename($filepath));
    }
}

/**
 * API Endpoint for file uploads
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/../includes/auth_check.php';

    requireLogin();

    $response = ['success' => false, 'error' => 'Invalid request'];

    if (!isset($_FILES['file'])) {
        $response['error'] = 'No file provided';
    } else {
        $handler = new FileUploadHandler();
        $subdirectory = $_POST['directory'] ?? 'general';
        $result = $handler->upload($_FILES['file'], $subdirectory);
        $response = $result;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
