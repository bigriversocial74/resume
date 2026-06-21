<?php
declare(strict_types=1);

function admin_nav_items(): array
{
    return [
        ['label' => 'Requests', 'href' => '/admin/dashboard.php'],
        ['label' => 'Chats', 'href' => '/admin/chat.php'],
        ['label' => 'Analytics', 'href' => '/admin/analytics.php'],
        ['label' => 'Knowledge', 'href' => '/admin/knowledge.php'],
        [
            'label' => 'Tavus',
            'href' => '/admin/tavus.php',
            'children' => [
                ['label' => 'Settings', 'href' => '/admin/tavus.php#settings'],
                ['label' => 'Build Avatar', 'href' => '/admin/tavus.php#build'],
                ['label' => 'Saved Profiles', 'href' => '/admin/tavus.php#profiles'],
            ],
        ],
        ['label' => 'Customers', 'href' => '/admin/customers.php'],
        ['label' => 'Account', 'href' => '/admin/account.php'],
        ['label' => 'Logout', 'href' => '/admin/logout.php'],
    ];
}

function admin_nav_href(string $href): string
{
    if (str_starts_with($href, '/')) {
        return app_url($href);
    }
    return $href;
}

function admin_nav_html(): string
{
    $current = current_path();
    $html = '';
    foreach (admin_nav_items() as $item) {
        $children = $item['children'] ?? [];
        $href = (string)($item['href'] ?? '#');
        $isActive = $current === strtok($href, '#');
        if ($children) {
            foreach ($children as $child) {
                if ($current === strtok((string)$child['href'], '#')) {
                    $isActive = true;
                }
            }
            $open = $isActive ? ' is-open' : '';
            $active = $isActive ? ' active' : '';
            $html .= '<div class="admin-nav-group' . $open . '" data-nav-group>';
            $html .= '<button class="admin-nav-parent' . $active . '" type="button" data-nav-accordion><span>' . e((string)$item['label']) . '</span><b>⌄</b></button>';
            $html .= '<div class="admin-subnav">';
            foreach ($children as $child) {
                $childHref = (string)$child['href'];
                $childActive = $current === strtok($childHref, '#') ? ' active' : '';
                $html .= '<a class="admin-subnav-link' . $childActive . '" href="' . e(admin_nav_href($childHref)) . '">' . e((string)$child['label']) . '</a>';
            }
            $html .= '</div></div>';
        } else {
            $active = $isActive ? ' active' : '';
            $html .= '<a class="admin-nav-link' . $active . '" href="' . e(admin_nav_href($href)) . '">' . e((string)$item['label']) . '</a>';
        }
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
*{box-sizing:border-box}body{margin:0;background:#f8faff;font-family:Inter,Arial,sans-serif;color:#111827}.admin-layout{display:grid;grid-template-columns:20% 80%;min-height:100vh;width:100%}.admin-sidebar{background:#07101e;color:#fff;padding:28px 24px;position:sticky;top:0;height:100vh;overflow:auto}.admin-brand{font-weight:900;font-size:19px;margin-bottom:34px}.admin-nav{display:grid;gap:10px}.admin-nav-link,.admin-nav-parent{display:flex;align-items:center;justify-content:space-between;width:100%;color:#dbeafe;text-decoration:none;border-radius:14px;padding:13px 14px;font-size:14px;font-weight:800;background:transparent;border:0;text-align:left;cursor:pointer}.admin-nav-parent b{font-size:14px;transition:transform .2s ease}.admin-nav-link:hover,.admin-nav-link.active,.admin-nav-parent:hover,.admin-nav-parent.active{background:rgba(255,255,255,.1);color:#fff}.admin-nav-group.is-open .admin-nav-parent b{transform:rotate(180deg)}.admin-subnav{display:none;margin:7px 0 0 12px;padding-left:12px;border-left:1px solid rgba(255,255,255,.14)}.admin-nav-group.is-open .admin-subnav{display:grid;gap:6px}.admin-subnav-link{display:block;color:#a8c7ff;text-decoration:none;border-radius:10px;padding:10px 12px;font-size:12px;font-weight:800}.admin-subnav-link:hover,.admin-subnav-link.active{background:rgba(47,104,255,.18);color:#fff}.admin-main{min-width:0;padding:46px clamp(24px,4vw,64px)}.admin-hero{padding:20px 0 42px}.kicker{color:#2f68ff;font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}h1{font-size:clamp(34px,4vw,56px);letter-spacing:-.06em;line-height:.95;margin:12px 0 16px}.muted{color:#667085;line-height:1.65}.card,.panel{background:#fff;border:1px solid #edf0f6;border-radius:16px;padding:22px;box-shadow:0 18px 48px rgba(18,30,57,.055)}.grid{display:grid;gap:16px}.admin-mobile-bar{display:none}.admin-overlay{display:none}@media(max-width:900px){.admin-layout{display:block}.admin-mobile-bar{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;background:#07101e;color:#fff;padding:16px 18px}.admin-menu-button{border:0;border-radius:999px;background:#2f68ff;color:#fff;font-weight:900;padding:10px 14px}.admin-sidebar{position:fixed;z-index:70;left:0;top:0;width:min(320px,86vw);height:100vh;transform:translateX(-105%);transition:transform .22s ease;box-shadow:32px 0 80px rgba(0,0,0,.25)}.admin-sidebar.is-open{transform:translateX(0)}.admin-overlay{position:fixed;inset:0;z-index:60;background:rgba(7,16,30,.55)}.admin-overlay.is-open{display:block}.admin-main{padding:28px 18px 48px}}
CSS;
}

function admin_shell_js(): string
{
    return "(function(){var b=document.querySelector('[data-admin-menu]'),s=document.querySelector('[data-admin-sidebar]'),o=document.querySelector('[data-admin-overlay]');function t(){s&&s.classList.toggle('is-open');o&&o.classList.toggle('is-open')}if(b)b.addEventListener('click',t);if(o)o.addEventListener('click',t);document.querySelectorAll('[data-nav-accordion]').forEach(function(btn){btn.addEventListener('click',function(){var g=btn.closest('[data-nav-group]');if(g)g.classList.toggle('is-open');});});})();";
}
