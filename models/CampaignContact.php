<?php

declare(strict_types=1);

final class CampaignContact extends Model
{
    protected static string $table = 'campaign_contacts';

    public static function add(int $campaignId, int $contactId): void
    {
        db()->prepare('INSERT IGNORE INTO campaign_contacts (campaign_id, contact_id) VALUES (?, ?)')
            ->execute([$campaignId, $contactId]);
    }

    public static function setStatus(int $campaignId, int $contactId, string $status): void
    {
        db()->prepare('UPDATE campaign_contacts SET status = ? WHERE campaign_id = ? AND contact_id = ?')
            ->execute([$status, $campaignId, $contactId]);
    }
}
