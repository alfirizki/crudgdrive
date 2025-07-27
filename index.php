<?php
require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Setup Google Client
$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->addScope(Google_Service_Drive::DRIVE);

$service = new Google_Service_Drive($client);

// Email kamu agar bisa akses
$shareWith = 'youremail@gmail.com'; // GANTI dengan email Google kamu

// Buat Folder
function createFolder($service, $name) {
    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $name,
        'mimeType' => 'application/vnd.google-apps.folder'
    ]);

    $folder = $service->files->create($fileMetadata, ['fields' => 'id']);
    return $folder->id;
}

// Upload File ke Folder
function uploadFile($service, $filePath, $fileName, $parentId = null) {
    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $fileName,
        'parents' => [$parentId]
    ]);

    $content = file_get_contents($filePath);
    $file = $service->files->create($fileMetadata, [
        'data' => $content,
        'mimeType' => mime_content_type($filePath),
        'uploadType' => 'multipart',
        'fields' => 'id'
    ]);

    return $file->id;
}

// Share file atau folder
function shareWithEmail($service, $fileId, $email) {
    $permission = new Google_Service_Drive_Permission([
        'type' => 'user',
        'role' => 'reader',
        'emailAddress' => $email
    ]);
    $service->permissions->create($fileId, $permission, ['sendNotificationEmail' => false]);
}

// Tampilkan daftar file di dalam folder (hanya hasil upload tadi)
function listFilesInFolder($service, $folderId) {
    $query = "'" . $folderId . "' in parents and trashed = false";
    $files = $service->files->listFiles([
        'q' => $query,
        'fields' => 'files(id, name, mimeType, webViewLink, webContentLink)'
    ]);

    echo "<h3>📁 File di dalam folder:</h3>";
    foreach ($files->getFiles() as $file) {
        $viewUrl = $file->getWebViewLink();
        $downloadUrl = $file->getWebContentLink();
        echo "📄 <strong>{$file->getName()}</strong><br>";
        echo "🔗 <a href='$viewUrl' target='_blank'>Lihat</a> | ";
        echo "<a href='$downloadUrl' target='_blank'>Download</a><br><br>";
    }
}

// Eksekusi
echo "<h2>📤 Upload & List File Google Drive</h2>";

$folderId = createFolder($service, 'FolderUploadPHP');
echo "✅ Folder dibuat: $folderId<br>";
shareWithEmail($service, $folderId, $shareWith);
echo "🔓 Folder dibagikan ke $shareWith<br>";

$fileId = uploadFile($service, 'contoh.txt', 'FileContoh.txt', $folderId);
echo "✅ File diupload: $fileId<br>";
shareWithEmail($service, $fileId, $shareWith);
echo "🔓 File dibagikan ke $shareWith<br>";

// Tampilkan file yang baru diupload ke folder
listFilesInFolder($service, $folderId);
