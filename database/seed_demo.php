<?php
/**
 * Demo data seeder (optional, for local trials).
 *
 *   php database/seed_demo.php
 *
 * Populates the admin account with a list, contacts, a template, a disabled
 * demo SMTP account, a draft campaign and 14 days of fake activity so the
 * dashboard and reports have something to show. Safe to re-run.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$admin = User::findByEmail('admin@eventogen.com');
if (!$admin) {
    fwrite(STDERR, "Admin user not found. Import schema first.\n");
    exit(1);
}
$uid = (int) $admin['id'];

// Ensure a known admin password for the demo.
User::updatePassword($uid, 'Admin@123');

$pdo = db();
// Clean prior demo rows (idempotent-ish) without touching the schema.
foreach (['email_logs', 'opens', 'clicks'] as $t) {
    $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$uid]);
}

// ---- contact list + contacts ----
$listId = (int) (ContactList::insert([
    'user_id' => $uid, 'name' => 'Demo Leads', 'description' => 'Sample imported contacts',
]));

$people = [
    ['jane.doe@example.com', 'Jane', 'Doe', 'Acme Inc'],
    ['bob.lee@example.com', 'Bob', 'Lee', 'Globex'],
    ['carla.ng@example.com', 'Carla', 'Ng', 'Initech'],
    ['dmitri.k@example.com', 'Dmitri', 'Kov', 'Umbrella'],
    ['emma.s@example.com', 'Emma', 'Stone', 'Soylent'],
    ['frank.m@example.com', 'Frank', 'Moore', 'Hooli'],
    ['grace.h@example.com', 'Grace', 'Hopper', 'Pied Piper'],
    ['hassan.a@example.com', 'Hassan', 'Ali', 'Vehement'],
];
$contactIds = [];
foreach ($people as [$email, $fn, $ln, $co]) {
    $res = Contact::upsert($uid, ['email' => $email, 'first_name' => $fn, 'last_name' => $ln, 'company' => $co, 'list_id' => $listId]);
    $contactIds[] = $res['id'];
}

// ---- a template ----
$tplId = (int) Template::insert([
    'user_id' => $uid,
    'name'    => 'Welcome email',
    'subject' => 'Welcome aboard, {{first_name}}!',
    'body_html' => '<html><body style="font-family:Arial,sans-serif;color:#333">'
        . '<h2 style="color:#4f46e5">Hi {{first_name}},</h2>'
        . '<p>Thanks for joining us at {{company}}. We are excited to have you.</p>'
        . '<p><a href="https://example.com/getstarted" style="background:#4f46e5;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">Get started</a></p>'
        . '<p>Cheers,<br>The Team</p></body></html>',
]);

// ---- a disabled demo SMTP account (so nothing actually sends) ----
$smtpId = (int) SmtpAccount::insert([
    'user_id' => $uid, 'label' => 'Demo Gmail (disabled)', 'provider' => 'gmail',
    'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls',
    'username' => 'demo@example.com', 'password' => Crypto::encrypt('app-password-here'),
    'from_email' => 'demo@example.com', 'from_name' => 'Demo Sender',
    'priority' => 10, 'daily_limit' => 300, 'is_enabled' => 0, 'last_status' => 'unknown',
]);

// ---- a draft campaign ----
$campId = (int) Campaign::insert([
    'user_id' => $uid, 'name' => 'Spring Newsletter', 'list_id' => $listId, 'smtp_id' => $smtpId,
    'template_id' => $tplId, 'subject' => 'Spring updates for {{first_name}}',
    'body_html' => '<html><body style="font-family:Arial"><h2>Spring news</h2><p>Hi {{first_name}}, here is what is new...</p>'
        . '<p><a href="https://example.com/read">Read more</a></p></body></html>',
    'status' => 'draft', 'track_opens' => 1, 'track_clicks' => 1, 'total_recipients' => count($contactIds),
]);

// ---- 14 days of fake activity for charts ----
$logStmt = $pdo->prepare(
    "INSERT INTO email_logs (user_id, campaign_id, contact_id, smtp_id, email, event, message, created_at)
     VALUES (?, ?, ?, ?, ?, 'sent', 'Demo send', ?)"
);
$openStmt = $pdo->prepare(
    "INSERT INTO opens (user_id, campaign_id, contact_id, tracking_id, device, browser, created_at)
     VALUES (?, ?, ?, ?, 'Desktop', 'Chrome', ?)"
);
$clickStmt = $pdo->prepare(
    "INSERT INTO clicks (user_id, campaign_id, contact_id, tracking_id, url, device, browser, created_at)
     VALUES (?, ?, ?, ?, 'https://example.com/read', 'Mobile', 'Safari', ?)"
);

for ($d = 13; $d >= 0; $d--) {
    $date = date('Y-m-d H:i:s', strtotime("-$d day"));
    $sends = random_int(6, 24);
    for ($i = 0; $i < $sends; $i++) {
        $cid = $contactIds[array_rand($contactIds)];
        $email = $people[array_search($cid, $contactIds, true)][0] ?? 'demo@example.com';
        $logStmt->execute([$uid, $campId, $cid, $smtpId, $email, $date]);
        if (random_int(0, 100) < 55) {
            $openStmt->execute([$uid, $campId, $cid, bin2hex(random_bytes(8)), $date]);
            if (random_int(0, 100) < 35) {
                $clickStmt->execute([$uid, $campId, $cid, bin2hex(random_bytes(8)), $date]);
            }
        }
    }
}

// Reflect the demo sends on the SMTP + campaign counters.
$pdo->prepare("UPDATE smtp_accounts SET sent_total = (SELECT COUNT(*) FROM email_logs WHERE smtp_id = ?) WHERE id = ?")->execute([$smtpId, $smtpId]);
$pdo->prepare("UPDATE campaigns SET sent_count = (SELECT COUNT(*) FROM email_logs WHERE campaign_id = ?) WHERE id = ?")->execute([$campId, $campId]);

echo "Demo data seeded:\n";
echo "  - List 'Demo Leads' with " . count($contactIds) . " contacts\n";
echo "  - Template 'Welcome email'\n";
echo "  - SMTP 'Demo Gmail (disabled)'\n";
echo "  - Campaign 'Spring Newsletter' (draft) + 14 days of activity\n";
echo "\nLogin: admin@eventogen.com / Admin@123\n";
