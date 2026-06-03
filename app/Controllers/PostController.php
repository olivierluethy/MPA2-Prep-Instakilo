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
use App\Models\Repost;
use App\Models\Save;

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

        // Collect and validate uploaded files...
        $files = $this->normalizeFiles($request->files('images'));
        foreach ($files as $i => $file) {
            if (($error = $this->validateImage($file)) !== null) {
                $validator->addError('images', 'Bild ' . ($i + 1) . ": {$error}");
                break;
            }
        }

        // ...and images supplied by URL (downloaded server-side, SSRF-guarded).
        $urls = array_values(array_filter(
            array_map('trim', (array) ($_POST['image_urls'] ?? [])),
            static fn ($u) => $u !== ''
        ));
        $urlImages = [];
        if ($validator->passes()) {
            foreach ($urls as $i => $u) {
                $result = $this->downloadImage($u);
                if (is_string($result)) {
                    $validator->addError('images', 'Bild-URL ' . ($i + 1) . ": {$result}");
                    break;
                }
                $urlImages[] = $result;
            }
        }

        $total = count($files) + count($urlImages);
        if ($total < 1) {
            $validator->addError('images', 'Bitte mindestens ein Bild hinzufügen.');
        }
        if ($total > (int) config('uploads.max_files')) {
            $validator->addError('images', 'Zu viele Bilder (max. ' . config('uploads.max_files') . ').');
        }

        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), 422, $validator->errors());
        }

        // Persist the post, then its images: uploads first, then URL images.
        $postId = (new Post())->create((int) Auth::id(), $title, $description, $location, $takenOn, $isPublic);

        $images = new PostImage();
        $order = 0;
        foreach (array_values($files) as $file) {
            $images->add($postId, mime_content_type($file['tmp_name']), file_get_contents($file['tmp_name']), $order++);
        }
        foreach ($urlImages as $img) {
            $images->add($postId, $img['mime'], $img['data'], $order++);
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
     * Edit a comment. Only the comment's author may do so. JSON response.
     */
    public function commentUpdate(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $commentId = $request->intQuery('id');
        if ($commentId === null) {
            $this->fail('Missing comment id.', 422);
        }

        $comments = new Comment();
        if (!$comments->isOwnedBy($commentId, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Kommentare bearbeiten.', 403);
        }

        $body = trim(strip_tags($request->raw('body')));
        $validator = (new Validator(['body' => $body]))
            ->required('body', 'Kommentar darf nicht leer sein.')
            ->max('body', 1000, 'Kommentar ist zu lang (max. 1000 Zeichen).');
        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), 422);
        }

        $comments->update($commentId, $body);
        $this->ok(['id' => $commentId, 'body' => $body]);
    }

    /**
     * Delete a comment. Only the comment's author may do so. JSON response.
     */
    public function commentDelete(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $commentId = $request->intQuery('id');
        if ($commentId === null) {
            $this->fail('Missing comment id.', 422);
        }

        $comments = new Comment();
        $comment = $comments->find($commentId);
        if ($comment === null) {
            $this->fail('Kommentar nicht gefunden.', 404);
        }
        if ((int) $comment['user_id'] !== (int) Auth::id()) {
            $this->fail('Du darfst nur eigene Kommentare löschen.', 403);
        }

        $comments->delete($commentId);
        $this->ok([
            'id'           => $commentId,
            'commentCount' => $comments->countForPost((int) $comment['post_id']),
        ]);
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
     * Single post page (used by DM share links, reposts, search results).
     */
    public function show(Request $request): void
    {
        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->redirect('home');
        }

        $viewerId = Auth::id();
        $posts = (new Post())->byIds([$postId], $viewerId);

        if ($posts === []) {
            http_response_code(404);
            $this->view('errors.error', ['code' => 404, 'heading' => 'Beitrag nicht gefunden', 'detail' => ''], '404 – Instakilo');
            return;
        }

        $this->view('posts.show', [
            'post'       => $posts[0],
            'isLoggedIn' => $viewerId !== null,
            'viewerId'   => $viewerId,
        ], $posts[0]['title'] . ' – Instakilo');
    }

    /**
     * Edit-form data for a post (owner only). JSON response.
     */
    public function edit(Request $request): void
    {
        $this->requireAuth($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $post = new Post();
        if (!$post->isOwnedBy($postId, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Beiträge bearbeiten.', 403);
        }

        $row = $post->find($postId);
        $images = (new PostImage())->idsFor($postId);

        $this->ok([
            'post' => [
                'id'          => (int) $row['id'],
                'title'       => $row['title'],
                'description' => $row['description'] ?? '',
                'location'    => $row['location'] ?? '',
                'taken_on'    => $row['taken_on'] ?? '',
                'is_public'   => (int) $row['is_public'] === 1,
                'images'      => array_map(
                    static fn (int $id): array => ['id' => $id, 'url' => url('posts/image?id=' . $id)],
                    $images
                ),
            ],
        ]);
    }

    /**
     * Update a post (owner only): caption/details + add/remove/reorder images.
     */
    public function update(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $post = new Post();
        if (!$post->isOwnedBy($postId, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Beiträge bearbeiten.', 403);
        }

        $title       = (string) $request->input('title', '');
        $description = HtmlSanitizer::sanitize($request->raw('description'));
        $location    = (string) $request->input('location', '');
        $takenOn     = $request->input('taken_on');
        $isPublic    = $request->input('is_public') === '1';

        $validator = (new Validator(['title' => $title, 'location' => $location]))
            ->required('title', 'Bitte einen Titel eingeben.')
            ->max('title', 255, 'Titel ist zu lang (max. 255 Zeichen).')
            ->max('location', 255, 'Ort ist zu lang (max. 255 Zeichen).');
        if ($takenOn !== null && $takenOn !== '' && !$this->isValidDate($takenOn)) {
            $validator->addError('taken_on', 'Ungültiges Datum.');
        }

        $images = new PostImage();
        $existingIds = $images->idsFor($postId);
        $removeIds = array_map('intval', (array) ($_POST['remove_images'] ?? []));
        $removeCount = count(array_intersect($existingIds, $removeIds));

        $files = $this->normalizeFiles($request->files('images'));
        foreach ($files as $i => $file) {
            if (($error = $this->validateImage($file)) !== null) {
                $validator->addError('images', 'Bild ' . ($i + 1) . ": {$error}");
                break;
            }
        }

        // Validate the resulting image count BEFORE mutating anything.
        $finalCount = count($existingIds) - $removeCount + count($files);
        if ($finalCount < 1) {
            $validator->addError('images', 'Ein Beitrag braucht mindestens ein Bild.');
        }
        if ($finalCount > (int) config('uploads.max_files')) {
            $validator->addError('images', 'Zu viele Bilder (max. ' . config('uploads.max_files') . ').');
        }
        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), 422, $validator->errors());
        }

        // Apply: remove → reorder kept → append new.
        foreach ($removeIds as $rid) {
            $images->delete($rid, $postId);
        }
        $order = array_map('intval', (array) ($_POST['order'] ?? []));
        if ($order !== []) {
            $images->reorder($postId, array_values(array_diff($order, $removeIds)));
        }
        $sort = $images->maxSortOrder($postId) + 1;
        foreach (array_values($files) as $file) {
            $images->add($postId, mime_content_type($file['tmp_name']), file_get_contents($file['tmp_name']), $sort++);
        }

        $post->update($postId, $title, $description, $location, $takenOn, $isPublic);
        $this->ok(['postId' => $postId, 'redirect' => url('home')]);
    }

    /**
     * Delete a post (owner only). Images/likes/comments/saves/reposts cascade.
     */
    public function delete(Request $request): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $post = new Post();
        if (!$post->isOwnedBy($postId, (int) Auth::id())) {
            $this->fail('Du darfst nur eigene Beiträge löschen.', 403);
        }

        $post->delete($postId);

        if ($request->wantsJson()) {
            $this->ok(['deleted' => $postId]);
        }
        $this->redirect('home');
    }

    /* ---------- Save / Repost ---------- */

    public function save(Request $request): void
    {
        $this->toggleSave($request, true);
    }

    public function unsave(Request $request): void
    {
        $this->toggleSave($request, false);
    }

    /**
     * The current user's saved-post collection.
     */
    public function saved(Request $request): void
    {
        $this->requireAuth($request);
        $userId = (int) Auth::id();
        $ids = (new Save())->postIdsFor($userId);

        $this->view('posts.saved', [
            'posts'      => (new Post())->byIds($ids, $userId),
            'isLoggedIn' => true,
            'viewerId'   => $userId,
        ], 'Gespeichert – Instakilo');
    }

    public function repost(Request $request): void
    {
        $this->toggleRepost($request, true);
    }

    public function unrepost(Request $request): void
    {
        $this->toggleRepost($request, false);
    }

    private function toggleSave(Request $request, bool $save): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $saves = new Save();
        $userId = (int) Auth::id();
        $save ? $saves->save($postId, $userId) : $saves->unsave($postId, $userId);

        if ($request->wantsJson()) {
            $this->ok(['saved' => $save]);
        }
        $this->back();
    }

    private function toggleRepost(Request $request, bool $on): void
    {
        $this->requireAuth($request);
        $this->requireCsrf($request);

        $postId = $request->intQuery('id');
        if ($postId === null) {
            $this->fail('Missing post id.', 422);
        }

        $reposts = new Repost();
        $userId = (int) Auth::id();

        if ($on) {
            $preview = (new Post())->preview($postId);
            if ($preview === null) {
                $this->fail('Beitrag nicht gefunden.', 404);
            }
            if ((int) $preview['is_public'] !== 1) {
                $this->fail('Nur öffentliche Beiträge können geteilt werden.', 422);
            }
            if ((int) $preview['user_id'] === $userId) {
                $this->fail('Du kannst deinen eigenen Beitrag nicht reposten.', 422);
            }
            $reposts->repost($postId, $userId);
        } else {
            $reposts->unrepost($postId, $userId);
        }

        if ($request->wantsJson()) {
            $this->ok(['reposted' => $on, 'repostCount' => $reposts->countForPost($postId)]);
        }
        $this->back();
    }

    /**
     * Live public counters for a set of posts (likes/comments/reposts). Used by
     * the feed to reconcile cards across sessions without a reload. Public,
     * read-only, capped to avoid abuse.
     */
    public function stats(Request $request): void
    {
        $raw = (string) $request->query('ids', '');
        $ids = array_slice(
            array_filter(array_map('intval', explode(',', $raw)), static fn ($n) => $n > 0),
            0,
            60
        );
        $this->ok(['stats' => (new Post())->stats($ids)]);
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

    /**
     * Download an image from a URL with SSRF protection.
     *
     * @return array{mime:string, data:string}|string bytes+mime, or an error message
     */
    private function downloadImage(string $url): array|string
    {
        if (!preg_match('#^https?://#i', $url)) {
            return 'Nur http(s)-URLs erlaubt.';
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return 'Ungültige URL.';
        }
        // Block requests to private/reserved addresses (SSRF).
        $ip = gethostbyname($host);
        if (
            filter_var($ip, FILTER_VALIDATE_IP)
            && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            return 'Diese URL ist nicht erlaubt.';
        }

        $max = (int) config('uploads.max_file_size');
        $ctx = stream_context_create(['http' => [
            'timeout'        => 5,
            'follow_location' => 0, // no redirects → no redirect-based SSRF
            'ignore_errors'  => true,
            'user_agent'     => 'InstakiloBot/1.0',
        ]]);
        $fp = @fopen($url, 'rb', false, $ctx);
        if ($fp === false) {
            return 'Konnte die URL nicht laden.';
        }
        $data = @stream_get_contents($fp, $max + 1);
        fclose($fp);

        if ($data === false || $data === '') {
            return 'Leere Antwort von der URL.';
        }
        if (strlen($data) > $max) {
            return 'Bild ist zu groß (max. ' . round($max / 1024 / 1024, 1) . ' MB).';
        }
        $info = @getimagesizefromstring($data);
        if ($info === false || !in_array($info['mime'], config('uploads.allowed_mime', []), true)) {
            return 'Kein gültiges Bild (JPEG/PNG/GIF/WebP).';
        }
        return ['mime' => $info['mime'], 'data' => $data];
    }
}
