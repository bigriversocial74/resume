<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$message = null;
$error = null;
$response = null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'settings') {
            set_agent_setting('tavus_video_enabled', !empty($_POST['tavus_video_enabled']) ? '1' : '0', (int)$admin['id']);
            set_agent_setting('tavus_test_mode', !empty($_POST['tavus_test_mode']) ? '1' : '0', (int)$admin['id']);
            set_agent_setting('tavus_active_replica_id', trim((string)($_POST['active_replica_id'] ?? '')), (int)$admin['id']);
            $message = 'Tavus settings updated.';
        } elseif ($action === 'build_hero' || $action === 'build_upload') {
            if (empty($_POST['media_confirm'])) {
                throw new RuntimeException('Confirm that you have rights to use this media before sending it to Tavus.');
            }
            if ($action === 'build_hero') {
                $profileId = tavus_create_hero_profile((int)$admin['id'], (string)($_POST['display_name'] ?? 'Dave Hero Avatar'), (string)($_POST['voice_name'] ?? ''), (string)($_POST['model_name'] ?? 'phoenix-4'));
            } else {
                $profileId = tavus_upload_profile((int)$admin['id'], $_FILES['source_file'] ?? [], (string)($_POST['display_name'] ?? 'Dave Avatar'), (string)($_POST['voice_name'] ?? ''), (string)($_POST['model_name'] ?? 'phoenix-4'));
            }
            $response = tavus_create_replica_from_profile($profileId, (int)$admin['id']);
            $message = 'Tavus avatar build started. The returned id was saved as the active replica when available.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$activeReplica = tavus_active_replica_id();
$tavusReady = !empty(tavus_config()['api_key']);
$videoEnabled = agent_setting('tavus_video_enabled', '1') === '1';
$testMode = tavus_test_mode();
$heroUrl = tavus_public_base_url() . '/images/dave_main.png';
$profiles = tavus_list_media_profiles();

admin_shell_open('Tavus Settings', 'Tavus', 'Hero video chat', 'Create or select the Tavus avatar used by the hero Chat With Dave experience.');
?>
<style>.two{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{display:grid;gap:7px;margin-bottom:14px}.field label{font-size:12px;font-weight:800;color:#667085}.input,select{width:100%;border:1px solid #dfe5ef;border-radius:10px;padding:13px;font:inherit}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:12px 18px;font-size:12px;font-weight:900;cursor:pointer;text-decoration:none}.btn.dark{background:#07101e}.ok{background:#ecfdf5;color:#065f46;padding:14px;border-radius:12px;margin-bottom:18px}.err{background:#fef2f2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px}.pill{display:inline-flex;border-radius:999px;background:#eef4ff;color:#2f68ff;font-size:11px;font-weight:900;padding:6px 9px;text-transform:uppercase;letter-spacing:.06em}.pill.off{background:#f3f4f6;color:#6b7280}.toggle{display:flex;gap:14px;align-items:center;flex-wrap:wrap}.toggle input{width:22px;height:22px}.preview{border-radius:16px;overflow:hidden;background:#07101e;min-height:320px;background-image:url('/images/dave_main.png');background-size:cover;background-position:center top}.profile{display:grid;grid-template-columns:1fr auto;gap:18px;border-top:1px solid #edf0f6;padding:18px 0}.profile:first-child{border-top:0}code{background:#f3f4f6;padding:3px 6px;border-radius:6px;word-break:break-all}.response{background:#07101e;color:#bfdbfe;border-radius:14px;padding:16px;overflow:auto;white-space:pre-wrap}.anchor-section{scroll-margin-top:24px}@media(max-width:900px){.two,.profile{grid-template-columns:1fr}}</style>
<?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<section class="two anchor-section" id="settings">
    <div class="panel"><h2>Status</h2><p><span class="pill <?= $tavusReady ? '' : 'off' ?>"><?= $tavusReady ? 'API key configured' : 'Missing API key' ?></span> <span class="pill <?= $activeReplica ? '' : 'off' ?>"><?= $activeReplica ? 'Replica selected' : 'No replica selected' ?></span></p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="settings"><div class="toggle"><input type="checkbox" name="tavus_video_enabled" value="1" <?= $videoEnabled ? 'checked' : '' ?>><strong>Enable hero video chat</strong><input type="checkbox" name="tavus_test_mode" value="1" <?= $testMode ? 'checked' : '' ?>><strong>Test mode</strong></div><div class="field"><label>Active Tavus replica id</label><input class="input" name="active_replica_id" value="<?= e($activeReplica) ?>" placeholder="Paste Tavus replica_id"></div><button class="btn" type="submit">Save Settings</button></form></div>
    <div class="panel"><h2>Hero image</h2><div class="preview"></div><p class="muted">Public URL:<br><code><?= e($heroUrl) ?></code></p></div>
</section>
<section class="two anchor-section" id="build" style="margin-top:18px">
    <div class="panel"><h2>Build from hero image</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="build_hero"><div class="field"><label>Display name</label><input class="input" name="display_name" value="Dave Hero Avatar" required></div><div class="field"><label>Voice name</label><input class="input" name="voice_name" placeholder="Example: anna" required></div><div class="field"><label>Model name</label><input class="input" name="model_name" value="phoenix-4"></div><label><input type="checkbox" name="media_confirm" value="1" required> I have rights to use this media.</label><p><button class="btn" type="submit">Build Avatar From Hero</button></p></form></div>
    <div class="panel"><h2>Upload and build</h2><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="build_upload"><div class="field"><label>Display name</label><input class="input" name="display_name" value="Dave Avatar" required></div><div class="field"><label>Voice name</label><input class="input" name="voice_name" placeholder="Required for image source"></div><div class="field"><label>Model name</label><input class="input" name="model_name" value="phoenix-4"></div><div class="field"><label>Source image or video</label><input class="input" type="file" name="source_file" accept="image/*,video/*" required></div><label><input type="checkbox" name="media_confirm" value="1" required> I have rights to use this media.</label><p><button class="btn" type="submit">Upload And Build</button></p></form></div>
</section>
<?php if ($response): ?><section class="panel anchor-section" id="response" style="margin-top:18px"><h2>Tavus response</h2><pre class="response"><?= e(json_encode($response, JSON_PRETTY_PRINT)) ?></pre></section><?php endif; ?>
<section class="panel anchor-section" id="profiles" style="margin-top:18px"><h2>Saved media profiles</h2><?php foreach ($profiles as $profile): ?><article class="profile"><div><h3><?= e($profile['display_name']) ?> <?= $profile['is_active'] ? '<span class="pill">active</span>' : '' ?></h3><p class="muted">Source: <?= e($profile['source_type']) ?> · Type: <?= e($profile['option_three'] ?: 'image') ?> · Status: <?= e($profile['status'] ?: 'draft') ?><br>Replica ID: <?= e($profile['provider_item_id'] ?: 'Not set') ?><br>URL: <code><?= e(tavus_media_url($profile) ?: 'Not available') ?></code></p></div></article><?php endforeach; ?><?php if (!$profiles): ?><p class="muted">No media profiles yet.</p><?php endif; ?></section>
<?php admin_shell_close();
