<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HtmlSanitizer;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\PostImage;

/**
 * Posts: creation (multi-image upload), likes, comments and image streaming.
 */
final class PostController extends Controller
{
    /**
     * Create a post with one or more images. Submitted via fetch/FormData, so
     * the response is always JSON.
     */
    public function store(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $title       = (string) $request->input('title', '');
        $rawDesc     = $request->raw('description');
        $location    = (string) $request->input('location', '');
        $takenOn     = $request->input('taken_on');
        $isPublic    = $request->input('is_public') === '1';

        // Sanitize the Quill HTML before it ever touches the database.
        $description = HtmlSanitizer::sanitize($rawDesc);

        $validator = (new Validator([
            'title'    => $title,
            'location' => $location,
        ]))
            ->required('title', 'Bitte einen Titel eingeben.')
            ->max('title', 255, 'Titel ist zu lang (max. 255 Zeichen).')
            ->max('location', 255, 'Ort ist zu lang (max. 255 Zeichen).');

        if ($takenOn !== null && $takenOn !== '' && !$this->isValidDate($takenOn)) {
            $validator->addError('taken_on', 'Ungültiges Datum.');
        }

        // Collect and validate the uploaded images.
        $files = $this->normalizeFiles($request->files('images'));
        if ($files === []) {
            $validator->addError('images', 'Bitte mindestens ein Bild auswählen.');
        }
        if (count($files) > (int) config('uploads.max_files')) {
            $validator->addError('images', 'Zu viele Bilder (max. ' . config('uploads.max_files') . ').');
        }
        foreach ($files as $i => $file) {
            if (($error = $this->validateImage($file)) !== null) {
                $validator->addError('images', "Bild " . ($i + 1) . ": {$error}");
                break;
            }
        }

        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), 422, $validator->errors());
        }

        // Persist the post, then its images in submitted order.
        $postId = (new Post())->create(
            (int) Auth::id(),
            $title,
            $description,
            $location,
            $takenOn,
            $isPublic
        );

        $images = new PostImage();
        foreach (array_values($files) as $order => $file) {
            $images->add(
                $postId,
                mime_content_type($file['tmp_name']),
                file_get_contents($file['tmp_name']),
                $order
            );
        }

        $this->ok(['postId' => $postId, 'redirect' => url('home')], 201);
    }

    public function like(Request $request): void
    {
        $this->toggleLike($request, true);
    }

    public function unlike(Request $request): void
    {
        $this->toggleLike($request, false);
    }

    /**
     * Add a comment to a post. Authenticated + CSRF-protected. JSON response.
     */
    public function comment(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        // Comments are plain text: strip any markup, then escape on output.
        $body = trim(strip_tags($request->raw('body')));

        $validator = (new Validator(['body' => $body]))
            ->required('body', 'Kommentar darf nicht leer sein.')
            ->max('body', 1000, 'Kommentar ist zu lang (max. 1000 Zeichen).');
        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), 422);
        }

        $comments = new Comment();
        if (!$comments->postExists($postId)) {
            $this->fail('Beitrag nicht gefunden.', 404);
        }

        $created = $comments->create($postId, (int) Auth::id(), $body);

        $this->ok([
            'comment' => [
                'id'         => (int) $created['id'],
                'user_id'    => (int) $created['user_id'],
                'username'   => $created['username'],
                'body'       => $created['body'],
                'created_at' => format_datetime($created['created_at']),
            ],
            'commentCount' => $comments->countForPost($postId),
        ], 201);
    }

    /**
     * Paginated comment list for a post. Public (read-only). JSON response.
     */
    public function comments(Request $request): void
    {
        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $page = max(1, (int) ($request->query('page', '1') ?? 1));
        $model = new Comment();
        $rows = $model->forPost($postId, $page);
        $total = $model->countForPost($postId);

        $this->ok([
            'comments' => array_map(
                static fn (array $r): array => [
                    'id'         => (int) $r['id'],
                    'user_id'    => (int) $r['user_id'],
                    'username'   => $r['username'],
                    'body'       => $r['body'],
                    'created_at' => format_datetime($r['created_at']),
                ],
                $rows
            ),
            'page'    => $page,
            'total'   => $total,
            'hasMore' => ($page * Comment::PER_PAGE) < $total,
        ]);
    }

    /**
     * Stream a post image blob with caching headers.
     */
    public function image(Request $request): void
    {
        $id = $request->intQuery('id');
        $image = $id !== null ? (new PostImage())->find($id) : null;

        if ($image === null) {
            http_response_code(404);
            exit;
        }

        $data = $image['image_data'];
        $etag = '"' . md5('post-image-' . $id . strlen($data)) . '"';
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }

        header('Content-Type: ' . $image['image_type']);
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: public, max-age=604800, immutable');
        header('ETag: ' . $etag);
        echo $data;
        exit;
    }

    private function toggleLike(Request $request, bool $like): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $likes = new Like();
        $userId = (int) Auth::id();

        $like ? $likes->like($postId, $userId) : $likes->unlike($postId, $userId);

        if ($request->wantsJson()) {
            $this->ok(['liked' => $like, 'likeCount' => $likes->countForPost($postId)]);
        }
        $this->redirect('home');
    }

    /**
     * Flatten PHP's awkward multi-file $_FILES structure into a list of
     * single-file arrays, skipping empty slots.
     *
     * @return array<int, array{name:string, type:string, tmp_name:string, error:int, size:int}>
     */
    private function normalizeFiles(array $files): array
    {
        if (empty($files['name'])) {
            return [];
        }
        $names = (array) $files['name'];
        $out = [];
        foreach ($names as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $name,
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Server-side image validation: upload status, size and real MIME type
     * (verified from the file contents, not the client-supplied type).
     */
    private function validateImage(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'Upload fehlgeschlagen.';
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Ungültiger Upload.';
        }
        $maxSize = (int) config('uploads.max_file_size');
        if (($file['size'] ?? 0) > $maxSize) {
            return 'Bild ist zu groß (max. ' . round($maxSize / 1024 / 1024, 1) . ' MB).';
        }
        $info = @getimagesize($file['tmp_name']);
        $allowed = config('uploads.allowed_mime', []);
        if ($info === false || !in_array($info['mime'], $allowed, true)) {
            return 'Nur JPEG-, PNG-, GIF- oder WebP-Bilder sind erlaubt.';
        }
        return null;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
