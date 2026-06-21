<?php
declare(strict_types=1);

function admin_nav_items(): array
{
    return [
        ['/admin/dashboard.php', 'Requests'],
        ['/admin/chat.php', 'Chats'],
        ['/admin/analytics.php', 'Analytics'],
        ['/admin/knowledge.php', 'Knowledge'],
        ['/admin/tavus.php', 'Tavus'],
        ['/admin/customers.php', 'Customers'],
        ['/admin/account.php', 'Account'],
        ['/admin/logout.php', 'Logout'],
    ];
}

function admin_nav_html(): string
{
    $current = current_path();
    $html = '';
    foreach (admin_nav_items() as [$path, $label]) {
        $active = $current === $path ? ' active' : '';
        $html .= '<a class="admin-nav-link' . $active . '" href="' . e(app_url($path)) . '">' . e($label) . '</a>';
    }
    return $html;
}

function admin_shell_head(string $title): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($title) . '</title><style>';
    echo admin_shell_css();
    echo '</style></head><body>';
}

function admin_shell_open(string $title, string $kicker, string $heading, string $intro = ''): void
{
    admin_shell_head($title);
    echo '<div class="admin-mobile-bar"><button class="admin-menu-button" type="button" data-admin-menu>Menu</button><strong>David Evans CRM</strong></div>';
    echo '<div class="admin-overlay" data-admin-overlay></div>';
    echo '<div class="admin-layout"><aside class="admin-sidebar" data-admin-sidebar><div class="admin-brand">David Evans CRM</div><nav class="admin-nav">' . admin_nav_html() . '</nav></aside><main class="admin-main">';
    echo '<section class="admin-hero"><div class="kicker">' . e($kicker) . '</div><h1>' . e($heading) . '</h1>';
    if ($intro !== '') {
        echo '<p class="muted">' . e($intro) . '</p>';
    }
    echo '</section>';
}

function admin_shell_close(): void
{
    echo '</main></div><script>' . admin_shell_js() . '</script></body></html>';
}

function admin_shell_css(): string
{
    return <<<'CSS'
*{box-sizing:border-box}body{margin:0;background:#f8faff;font-family:Inter,Arial,sans-serif;color:#111827}.admin-layout{display:grid;grid-template-columns:20% 80%;min-height:100vh;width:100%}.admin-sidebar{background:#07101e;color:#fff;padding:28px 24px;position:sticky;top:0;height:100vh;overflow:auto}.admin-brand{font-weight:900;font-size:19px;margin-bottom:34px}.admin-nav{display:grid;gap:10px}.admin-nav-link{display:block;color:#dbeafe;text-decoration:none;border-radius:14px;padding:13px 14px;font-size:14px;font-weight:800}.admin-nav-link:hover,.admin-nav-link.active{background:rgba(255,255,255,.1);color:#fff}.admin-main{min-width:0;padding:46px clamp(24px,4vw,64px)}.admin-hero{padding:20px 0 42px}.kicker{color:#2f68ff;font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}h1{font-size:clamp(34px,4vw,56px);letter-spacing:-.06em;line-height:.95;margin:12px 0 16px}.muted{color:#667085;line-height:1.65}.card,.panel{background:#fff;border:1px solid #edf0f6;border-radius:16px;padding:22px;box-shadow:0 18px 48px rgba(18,30,57,.055)}.grid{display:grid;gap:16px}.admin-mobile-bar{display:none}.admin-overlay{display:none}@media(max-width:900px){.admin-layout{display:block}.admin-mobile-bar{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;background:#07101e;color:#fff;padding:16px 18px}.admin-menu-button{border:0;border-radius:999px;background:#2f68ff;color:#fff;font-weight:900;padding:10px 14px}.admin-sidebar{position:fixed;z-index:70;left:0;top:0;width:min(320px,86vw);height:100vh;transform:translateX(-105%);transition:transform .22s ease;box-shadow:32px 0 80px rgba(0,0,0,.25)}.admin-sidebar.is-open{transform:translateX(0)}.admin-overlay{position:fixed;inset:0;z-index:60;background:rgba(7,16,30,.55)}.admin-overlay.is-open{display:block}.admin-main{padding:28px 18px 48px}}
CSS;
}

function admin_shell_js(): string
{
    return "(function(){var b=document.querySelector('[data-admin-menu]'),s=document.querySelector('[data-admin-sidebar]'),o=document.querySelector('[data-admin-overlay]');function t(){s&&s.classList.toggle('is-open');o&&o.classList.toggle('is-open')}if(b)b.addEventListener('click',t);if(o)o.addEventListener('click',t);})();";
}
