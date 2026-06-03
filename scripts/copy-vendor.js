/**
 * Copies the Quill editor distribution out of node_modules into
 * public/vendor/quill so it is served from our own origin (no third-party CDN).
 * Run as part of `npm run build`.
 */
const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');
const src = path.join(projectRoot, 'node_modules', 'quill', 'dist');
const dest = path.join(projectRoot, 'public', 'vendor', 'quill');

const files = ['quill.js', 'quill.snow.css'];

fs.mkdirSync(dest, { recursive: true });

for (const file of files) {
    const from = path.join(src, file);
    const to = path.join(dest, file);
    if (!fs.existsSync(from)) {
        console.error(`[copy-vendor] missing ${from} — is "quill" installed?`);
        process.exit(1);
    }
    fs.copyFileSync(from, to);
    console.log(`[copy-vendor] ${file} -> public/vendor/quill/${file}`);
}
