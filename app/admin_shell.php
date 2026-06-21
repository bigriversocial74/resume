<?php
declare(strict_types=1);

function app_url(string $path): string
{
    return $path;
}

function admin_nav_items(): array
{
    return [
        ['label' => 'Requests', 'href' => '/admin/dashboard.php', 'icon' => '⌂'],
        ['label' => 'Chats', 'href' => '/admin/chat.php', 'icon' => '☏'],
        ['label' => 'Analytics', 'href' => '/admin/analytics.php', 'icon' => '◷'],
        ['label' => 'Knowledge', 'href' => '/admin/knowledge.php', 'icon' => '▤'],
        [
            'label' => 'Tavus',
            'href' => '/admin/tavus.php',
            'icon' => '◉',
            'children' => [
                ['label' => 'Settings', 'href' => '/admin/tavus.php#settings'],
                ['label' => 'Build Avatar', 'href' => '/admin/tavus.php#build'],
                ['label' => 'Saved Profiles', 'href' => '/admin/tavus.php#profiles'],
            ],
        ],
        ['label' => 'Customers', 'href' => '/admin/customers.php', 'icon' => '◇'],
        ['label' => 'Account', 'href' => '/admin/account.php', 'icon' => '⚙'],
    ];
}

function admin_nav_clean_path(string $href): string
{
    return strtok($href, '#') ?: $href;
}

function admin_nav_html(): string
{
    $current = current_path();
    $html = '';
    foreach (admin_nav_items() as $item) {
        $children = $item['children'] ?? [];
        $href = (string)($item['href'] ?? '#');
        $icon = (string)($item['icon'] ?? '•');
        $isActive = $current === admin_nav_clean_path($href);
        if ($children) {
            foreach ($children as $child) {
                if ($current === admin_nav_clean_path((string)$child['href'])) {
                    $isActive = true;
                }
            }
            $open = $isActive ? ' is-open' : '';
            $active = $isActive ? ' active' : '';
            $html .= '<div class="admin-nav-group' . $open . '" data-nav-group>';
            $html .= '<button class="admin-nav-parent' . $active . '" type="button" data-nav-accordion><span class="nav-left"><i>' . e($icon) . '</i><em>' . e((string)$item['label']) . '</em></span><b>⌄</b></button>';
            $html .= '<div class="admin-subnav">';
            foreach ($children as $child) {
                $childHref = (string)$child['href'];
                $childActive = $current === admin_nav_clean_path($childHref) ? ' active' : '';
                $html .= '<a class="admin-subnav-link' . $childActive . '" href="' . e($childHref) . '">' . e((string)$child['label']) . '</a>';
            }
            $html .= '</div></div>';
        } else {
            $active = $isActive ? ' active' : '';
            $html .= '<a class="admin-nav-link' . $active . '" href="' . e($href) . '"><span class="nav-left"><i>' . e($icon) . '</i><em>' . e((string)$item['label']) . '</em></span></a>';
        }
    }
    return $html;
}

function admin_user_label(): string
{
    $user = current_user();
    if (!$user) {
        return 'Account';
    }
    return (string)($user['full_name'] ?: $user['username'] ?: $user['email'] ?: 'Account');
}

function de_logo_html(): string
{
    return '<span class="de-logo" aria-hidden="true"><b>DE</b></span>';
}

function admin_shell_head(string $title): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($title) . '</title><style>';
    echo admin_shell_css();
    echo '</style></head><body>';
}

function admin_shell_open(string $title, string $kicker, string $heading, string $intro = ''): void
{
    $userLabel = admin_user_label();
    admin_shell_head($title);
    echo '<header class="admin-topbar"><div class="admin-top-left"><button class="admin-menu-button" type="button" data-admin-menu>☰</button><a class="admin-logo-lockup" href="/admin/dashboard.php">' . de_logo_html() . '<span>Developer Portal</span></a></div><div class="admin-top-right"><a class="upgrade-link" href="/admin/tavus.php#build">Upgrade Plan</a><details class="user-menu"><summary><span class="avatar-mini">' . e(mb_strtoupper(mb_substr($userLabel, 0, 1))) . '</span><span>' . e($userLabel) . '</span></summary><div class="user-menu-panel"><a href="/admin/account.php">Account Settings</a><a href="/admin/tavus.php#settings">Video Settings</a><a href="/admin/logout.php">Logout</a></div></details></div></header>';
    echo '<div class="admin-overlay" data-admin-overlay></div>';
    echo '<div class="admin-layout"><aside class="admin-sidebar" data-admin-sidebar><nav class="admin-nav">' . admin_nav_html() . '</nav><div class="sidebar-footer"><span>DE</span></div></aside><main class="admin-main">';
    echo '<section class="admin-hero"><div><div class="kicker">' . e($kicker) . '</div><h1>' . e($heading) . '</h1>';
    if ($intro !== '') {
        echo '<p class="muted">' . e($intro) . '</p>';
    }
    echo '</div></section>';
}

function admin_shell_close(): void
{
    echo '</main></div><script>' . admin_shell_js() . '</script></body></html>';
}

function admin_shell_css(): string
{
    return <<<'CSS'
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#fafafa;font-family:Inter,Arial,sans-serif;color:#242424;font-size:13px}.admin-topbar{height:64px;border-bottom:1px solid #e8e8e8;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 32px 0 18px;position:sticky;top:0;z-index:80}.admin-top-left,.admin-top-right{display:flex;align-items:center;gap:16px}.admin-logo-lockup{display:flex;align-items:center;gap:16px;color:#27231f;text-decoration:none;font-size:18px;font-weight:750}.de-logo{width:34px;height:34px;display:grid;place-items:center;background:#1f1518;color:#fff;border-radius:2px;font-size:11px;font-weight:900;letter-spacing:-.08em}.upgrade-link{height:36px;display:inline-flex;align-items:center;justify-content:center;padding:0 18px;border-radius:4px;background:#fff0f4;color:#f13f67;font-size:12px;font-weight:800;text-decoration:none}.user-menu{position:relative}.user-menu summary{list-style:none;height:36px;display:flex;align-items:center;gap:9px;border:1px solid #e2e2e2;border-radius:4px;padding:0 12px;cursor:pointer;background:#fff;font-weight:700}.user-menu summary::-webkit-details-marker{display:none}.avatar-mini{width:22px;height:22px;border-radius:999px;background:#f3f4f6;display:grid;place-items:center;font-size:11px}.user-menu-panel{position:absolute;right:0;top:43px;width:190px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 20px 60px rgba(15,23,42,.12);padding:8px;display:grid;gap:4px}.user-menu-panel a{color:#27231f;text-decoration:none;padding:10px 11px;border-radius:6px;font-size:12px;font-weight:750}.user-menu-panel a:hover{background:#f7f7f7}.admin-layout{display:grid;grid-template-columns:64px minmax(0,1fr);min-height:calc(100vh - 64px)}.admin-sidebar{background:#f7f7f8;border-right:1px solid #e6e6e8;padding:10px 8px;position:sticky;top:64px;height:calc(100vh - 64px);overflow:visible;z-index:60}.admin-nav{display:grid;gap:6px}.admin-nav-link,.admin-nav-parent{display:flex;align-items:center;justify-content:center;width:48px;height:42px;color:#4a4a4a;text-decoration:none;border-radius:7px;padding:0;background:transparent;border:0;cursor:pointer;position:relative}.admin-nav-link i,.admin-nav-parent i{font-style:normal;font-size:17px}.admin-nav-link em,.admin-nav-parent em,.admin-nav-parent b{display:none}.admin-nav-link:hover,.admin-nav-link.active,.admin-nav-parent:hover,.admin-nav-parent.active{background:#ffe8ef;color:#f13f67}.admin-nav-group{position:relative}.admin-subnav{display:none;position:absolute;left:58px;top:0;min-width:172px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 18px 55px rgba(15,23,42,.12);padding:8px;z-index:90}.admin-nav-group.is-open .admin-subnav{display:grid;gap:4px}.admin-subnav-link{display:block;color:#383838;text-decoration:none;border-radius:7px;padding:10px 12px;font-size:12px;font-weight:750}.admin-subnav-link:hover,.admin-subnav-link.active{background:#fff0f4;color:#f13f67}.sidebar-footer{position:absolute;bottom:14px;left:0;right:0;display:grid;place-items:center}.sidebar-footer span{font-size:10px;background:#e5e5e5;color:#333;padding:6px 8px;border-radius:3px;font-weight:900}.admin-main{min-width:0;padding:30px clamp(24px,5vw,70px) 60px;background:#fff}.admin-hero{display:flex;align-items:end;justify-content:space-between;gap:18px;padding:0 0 22px}.kicker{color:#f13f67;font-size:10px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}h1{font-size:24px;line-height:1.1;letter-spacing:-.035em;margin:8px 0 8px;font-weight:800}.muted{color:#60646c;line-height:1.58;font-size:13px;max-width:720px}.card,.panel{background:#fff;border:1px solid #dedfe3;border-radius:4px;padding:20px;box-shadow:none}.grid{display:grid;gap:16px}.admin-menu-button{display:none;border:0;background:transparent;font-size:18px;cursor:pointer}.admin-overlay{display:none}@media(max-width:900px){.admin-topbar{padding:0 14px}.admin-menu-button{display:inline-grid}.admin-logo-lockup span{display:none}.upgrade-link{display:none}.user-menu summary span:last-child{display:none}.admin-layout{display:block}.admin-sidebar{position:fixed;top:64px;left:0;width:260px;height:calc(100vh - 64px);transform:translateX(-105%);transition:transform .22s ease;box-shadow:32px 0 80px rgba(0,0,0,.18);overflow:auto}.admin-sidebar.is-open{transform:translateX(0)}.admin-nav-link,.admin-nav-parent{width:100%;justify-content:flex-start;padding:0 14px;gap:12px}.admin-nav-link em,.admin-nav-parent em{display:inline;font-style:normal}.admin-nav-parent b{display:block;margin-left:auto}.admin-subnav{position:static;box-shadow:none;margin:4px 0 6px 34px}.admin-overlay{position:fixed;inset:64px 0 0;z-index:55;background:rgba(7,16,30,.45)}.admin-overlay.is-open{display:block}.admin-main{padding:24px 16px 44px}.admin-hero{display:block}}
CSS;
}

function admin_shell_js(): string
{
    return "(function(){var b=document.querySelector('[data-admin-menu]'),s=document.querySelector('[data-admin-sidebar]'),o=document.querySelector('[data-admin-overlay]');function t(){s&&s.classList.toggle('is-open');o&&o.classList.toggle('is-open')}if(b)b.addEventListener('click',t);if(o)o.addEventListener('click',t);document.querySelectorAll('[data-nav-accordion]').forEach(function(btn){btn.addEventListener('click',function(){var g=btn.closest('[data-nav-group]');if(g)g.classList.toggle('is-open');});});})();";
}
CSS;
}
