<?php

declare(strict_types=1);

/**
 * Quick search backing the Ctrl/Cmd-K command palette — finds the user's own
 * contacts, campaigns and templates by name. Tenant-scoped.
 */
final class SearchController extends BaseController
{
    public function quick(): void
    {
        $this->requireAuth();
        $q = trim(str_input('q'));
        if (mb_strlen($q) < 2) {
            json_response(['ok' => true, 'results' => []]);
        }
        $uid  = $this->uid();
        $like = '%' . $q . '%';
        $results = [];

        // Contacts
        $st = db()->prepare(
            'SELECT id, email, first_name, last_name FROM contacts
             WHERE user_id = ? AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR company LIKE ?)
             ORDER BY created_at DESC LIMIT 5'
        );
        $st->execute([$uid, $like, $like, $like, $like]);
        foreach ($st->fetchAll() as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            $results[] = [
                'g' => 'Contacts', 'icon' => 'person-circle',
                'label' => $name !== '' ? $name : $r['email'],
                'sub' => $name !== '' ? $r['email'] : '',
                'url' => url('contacts?q=' . rawurlencode($r['email'])),
            ];
        }

        // Campaigns
        $st = db()->prepare('SELECT id, name, status FROM campaigns WHERE user_id = ? AND name LIKE ? ORDER BY created_at DESC LIMIT 5');
        $st->execute([$uid, $like]);
        foreach ($st->fetchAll() as $r) {
            $results[] = ['g' => 'Campaigns', 'icon' => 'send', 'label' => $r['name'], 'sub' => ucfirst((string) $r['status']), 'url' => url('campaigns/show?id=' . (int) $r['id'])];
        }

        // Templates
        $st = db()->prepare('SELECT id, name FROM templates WHERE user_id = ? AND name LIKE ? ORDER BY updated_at DESC LIMIT 5');
        $st->execute([$uid, $like]);
        foreach ($st->fetchAll() as $r) {
            $results[] = ['g' => 'Templates', 'icon' => 'file-richtext', 'label' => $r['name'], 'sub' => '', 'url' => url('templates/edit?id=' . (int) $r['id'])];
        }

        json_response(['ok' => true, 'results' => $results]);
    }
}
