<?php

declare(strict_types=1);

final class CampaignSchedule extends Model
{
    protected static string $table = 'campaign_schedules';

    public static function forCampaign(int $campaignId): array
    {
        $stmt = db()->prepare('SELECT * FROM campaign_schedules WHERE campaign_id = ? ORDER BY step ASC');
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public static function activeForCampaign(int $campaignId): array
    {
        $stmt = db()->prepare('SELECT * FROM campaign_schedules WHERE campaign_id = ? AND is_active = 1 ORDER BY step ASC');
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public static function deleteForCampaign(int $campaignId): void
    {
        db()->prepare('DELETE FROM campaign_schedules WHERE campaign_id = ?')->execute([$campaignId]);
    }
}
