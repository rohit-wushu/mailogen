<?php
/**
 * Seed ready-made, well-designed sales email templates (with images) for the
 * admin user. Upserts by name, so re-running refreshes the designs + covers.
 *
 *   php database/seed_templates.php
 *
 * Images use picsum.photos (stable, real photos via absolute URLs) so they
 * render in any inbox. Swap for your own hosted/brand images in production.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$admin = User::findByEmail('admin@eventogen.com');
if (!$admin) {
    fwrite(STDERR, "Admin user not found.\n");
    exit(1);
}
$uid = (int) $admin['id'];

$BRAND = '#6d5efc';
$img = static fn (string $seed, int $w, int $h): string => "https://picsum.photos/seed/{$seed}/{$w}/{$h}";

$wrap = static function (string $inner, string $preheader = ''): string {
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f4f5fb;">'
        . ($preheader ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . $preheader . '</div>' : '')
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5fb;padding:24px 0;font-family:Arial,Helvetica,sans-serif">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(20,16,80,.08)">'
        . $inner
        . '<tr><td style="padding:22px 32px;background:#0e1530;color:#97a3c4;font-size:12px;text-align:center">'
        . 'You\'re receiving this because you subscribed.<br>'
        . '<a href="#" style="color:#97a3c4;text-decoration:underline">Unsubscribe</a> &nbsp;·&nbsp; {{company}}'
        . '</td></tr></table></td></tr></table></body></html>';
};

$btn = static function (string $label, string $url = 'https://example.com') use ($BRAND): string {
    return '<table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="background:' . $BRAND . ';border-radius:8px">'
        . '<a href="' . $url . '" style="display:inline-block;padding:14px 34px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none">' . $label . '</a>'
        . '</td></tr></table>';
};

$hero = static fn (string $url, string $alt = ''): string =>
    '<tr><td style="padding:0"><img src="' . $url . '" alt="' . $alt . '" width="600" style="width:100%;display:block;border:0"></td></tr>';

$templates = [];

// 1. Sales promotion / discount — hero image + offer
$templates[] = [
    'name'    => 'Sales Promo — Limited Offer',
    'subject' => '🔥 {{first_name}}, your exclusive 30% off ends tonight',
    'thumb'   => $img('promo', 600, 360),
    'html'    => $wrap(
        '<tr><td style="padding:26px 32px 0;text-align:center"><div style="font-size:22px;font-weight:bold;color:#0e1530">Eventogen</div></td></tr>'
        . $hero($img('promo', 600, 280), 'Sale')
        . '<tr><td style="background:linear-gradient(135deg,#6d5efc,#8b5cf6);padding:30px 32px;text-align:center">'
        . '<div style="color:#fff;font-size:13px;letter-spacing:2px;text-transform:uppercase;opacity:.85">Limited Time</div>'
        . '<div style="color:#fff;font-size:42px;font-weight:800;margin:6px 0">30% OFF</div>'
        . '<div style="color:#ffffff;font-size:15px;opacity:.9">Everything in your plan — today only</div></td></tr>'
        . '<tr><td style="padding:30px 32px;color:#3a4256;font-size:16px;line-height:1.7">'
        . 'Hi {{first_name}},<br><br>As a valued customer at {{company}}, you get early access to our biggest sale of the year. '
        . 'Use code <b style="color:#6d5efc">SAVE30</b> at checkout — but hurry, it expires at midnight.'
        . '<div style="text-align:center;margin:28px 0">' . $btn('Claim 30% Off', 'https://example.com/offer') . '</div>'
        . '<div style="text-align:center;color:#98a0bd;font-size:13px">Offer ends 11:59 PM tonight</div></td></tr>',
        'Your exclusive 30% discount expires tonight'
    ),
];

// 2. Cold outreach — small avatar/office image
$templates[] = [
    'name'    => 'Cold Outreach — B2B Pitch',
    'subject' => 'Quick idea for {{company}}, {{first_name}}',
    'thumb'   => $img('office', 600, 360),
    'html'    => $wrap(
        $hero($img('office', 600, 220), 'Team')
        . '<tr><td style="padding:34px 36px 8px"><div style="font-size:18px;font-weight:bold;color:#0e1530;margin-bottom:18px">Eventogen</div>'
        . '<div style="color:#3a4256;font-size:16px;line-height:1.75">Hi {{first_name}},<br><br>'
        . 'I noticed {{company}} is growing fast — congrats. Most teams your size lose hours every week on manual email follow-ups.<br><br>'
        . 'We help companies like yours send personalised campaigns through your own inbox, with automated follow-ups that '
        . 'stop the moment someone replies. Teams typically see <b>2–3× more replies</b>.<br><br>'
        . 'Worth a quick 15-minute chat this week?'
        . '<div style="margin:26px 0">' . $btn('Book a 15-min call', 'https://example.com/book') . '</div>'
        . 'Best,<br>The Eventogen Team</div></td></tr><tr><td style="height:22px"></td></tr>',
        'A quick idea to get more replies'
    ),
];

// 3. Product launch — hero product shot + feature grid w/ icons
$templates[] = [
    'name'    => 'Product Launch — Feature Announcement',
    'subject' => 'Introducing something new for you, {{first_name}} 🚀',
    'thumb'   => $img('product', 600, 360),
    'html'    => $wrap(
        '<tr><td style="padding:26px 32px;text-align:center;border-bottom:1px solid #eef0f6"><div style="font-size:20px;font-weight:bold;color:#0e1530">Eventogen</div></td></tr>'
        . $hero($img('product', 600, 300), 'Product')
        . '<tr><td style="padding:34px 32px 14px;text-align:center">'
        . '<div style="display:inline-block;background:#eef0ff;color:#6d5efc;font-size:12px;font-weight:bold;padding:6px 14px;border-radius:999px;text-transform:uppercase;letter-spacing:1px">New Feature</div>'
        . '<h1 style="color:#0e1530;font-size:28px;margin:16px 0 10px">Automated Follow-ups</h1>'
        . '<p style="color:#3a4256;font-size:16px;line-height:1.7;margin:0 auto;max-width:440px">Hi {{first_name}}, set it once and let it run. '
        . 'Send an email, wait, follow up — automatically, and stop instantly when a contact replies.</p>'
        . '<div style="margin:26px 0">' . $btn('See how it works', 'https://example.com/feature') . '</div></td></tr>'
        . '<tr><td style="padding:0 32px 38px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td width="50%" style="padding:14px;vertical-align:top"><div style="font-size:24px">⚡</div><div style="font-weight:bold;color:#0e1530;margin:6px 0">Save hours</div><div style="color:#98a0bd;font-size:14px">No more manual follow-ups.</div></td>'
        . '<td width="50%" style="padding:14px;vertical-align:top"><div style="font-size:24px">🎯</div><div style="font-weight:bold;color:#0e1530;margin:6px 0">More replies</div><div style="color:#98a0bd;font-size:14px">Smart timing, real results.</div></td>'
        . '</tr></table></td></tr>',
        'Meet our newest feature'
    ),
];

// 4. Event invitation — full-bleed event image
$templates[] = [
    'name'    => 'Event Invitation',
    'subject' => "You're invited, {{first_name}} — don't miss it",
    'thumb'   => $img('event', 600, 360),
    'html'    => $wrap(
        $hero($img('event', 600, 260), 'Event')
        . '<tr><td style="background:#0e1530;padding:34px 32px;text-align:center">'
        . '<div style="color:#8b5cf6;font-size:13px;letter-spacing:3px;text-transform:uppercase">You\'re Invited</div>'
        . '<h1 style="color:#fff;font-size:30px;margin:12px 0 6px">Eventogen Summit 2026</h1>'
        . '<div style="color:#cbd5e1;font-size:15px">📅 12 March 2026 &nbsp;·&nbsp; 🕒 6:00 PM &nbsp;·&nbsp; 📍 Online</div></td></tr>'
        . '<tr><td style="padding:32px;color:#3a4256;font-size:16px;line-height:1.75">Hi {{first_name}},<br><br>'
        . 'Join us for an evening of insights, networking and product reveals. Seats are limited — reserve yours now.'
        . '<div style="text-align:center;margin:28px 0">' . $btn('Reserve My Seat', 'https://example.com/rsvp') . '</div>'
        . '<div style="text-align:center;color:#98a0bd;font-size:13px">Can\'t make it? Reply and we\'ll send the recording.</div></td></tr>',
        'Reserve your seat for the Eventogen Summit'
    ),
];

// 5. Newsletter — header image + article thumbnails
$templates[] = [
    'name'    => 'Monthly Newsletter',
    'subject' => '{{first_name}}, here\'s what\'s new this month',
    'thumb'   => $img('news', 600, 360),
    'html'    => $wrap(
        $hero($img('news', 600, 200), 'Newsletter')
        . '<tr><td style="padding:26px 32px 0;border-bottom:1px solid #eef0f6"><div style="font-size:20px;font-weight:bold;color:#0e1530">Eventogen Monthly</div>'
        . '<div style="color:#98a0bd;font-size:13px;padding-bottom:16px">Your roundup for this month</div></td></tr>'
        . '<tr><td style="padding:28px 32px;color:#3a4256;font-size:16px;line-height:1.75">Hi {{first_name}},<br><br>Here are this month\'s highlights:'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0"><tr>'
        . '<td width="90" style="vertical-align:top"><img src="' . $img('article1', 80, 80) . '" width="80" style="border-radius:8px;display:block"></td>'
        . '<td style="padding-left:14px;vertical-align:top"><div style="font-weight:bold;color:#0e1530">📈 New analytics dashboard</div>'
        . '<div style="color:#6b7280;font-size:14px;margin-top:4px">Track opens, clicks and SMTP health at a glance.</div></td></tr></table>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 18px"><tr>'
        . '<td width="90" style="vertical-align:top"><img src="' . $img('article2', 80, 80) . '" width="80" style="border-radius:8px;display:block"></td>'
        . '<td style="padding-left:14px;vertical-align:top"><div style="font-weight:bold;color:#0e1530">🔁 Multi-SMTP rotation</div>'
        . '<div style="color:#6b7280;font-size:14px;margin-top:4px">Send across multiple accounts with auto-failover.</div></td></tr></table>'
        . '<div style="text-align:center;margin:24px 0">' . $btn('Read the full update', 'https://example.com/blog') . '</div></td></tr>',
        'This month\'s product highlights'
    ),
];

$created = 0; $updated = 0;
foreach ($templates as $t) {
    $stmt = db()->prepare('SELECT id FROM templates WHERE user_id = ? AND name = ? LIMIT 1');
    $stmt->execute([$uid, $t['name']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        Template::update((int) $id, ['subject' => $t['subject'], 'body_html' => $t['html'], 'thumbnail' => $t['thumb']]);
        $updated++;
        echo "  ~ {$t['name']} (updated)\n";
    } else {
        Template::insert(['user_id' => $uid, 'name' => $t['name'], 'subject' => $t['subject'], 'body_html' => $t['html'], 'thumbnail' => $t['thumb']]);
        $created++;
        echo "  + {$t['name']} (new)\n";
    }
}

echo "Done. {$created} created, {$updated} updated — all now include images + cover thumbnails.\n";
