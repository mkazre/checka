<?php

namespace App\Controllers;

use App\Models\PhotoModel;

/**
 * Serves uploaded photos/thumbnails from the writable directory (kept out of
 * the web root). Access requires a logged-in session (see Routes filter).
 */
class Media extends BaseController
{
    public function serve(int $id)
    {
        return $this->output($id, 'path');
    }

    public function thumb(int $id)
    {
        return $this->output($id, 'thumb_path');
    }

    private function output(int $id, string $col)
    {
        $photo = model(PhotoModel::class)->find($id);
        if ($photo === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $relative = $photo[$col] ?: $photo['path'];
        $full     = WRITEPATH . 'uploads/' . ltrim($relative, '/');

        if (! is_file($full)) {
            // fall back to the full image if a thumb is missing
            $full = WRITEPATH . 'uploads/' . ltrim($photo['path'], '/');
            if (! is_file($full)) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }
        }

        $mime = $photo['mime'] ?: (function_exists('mime_content_type') ? mime_content_type($full) : 'application/octet-stream');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setBody(file_get_contents($full));
    }
}
