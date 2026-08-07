<?php

declare(strict_types=1);

final class ReplyId extends Model
{
    protected static string $table = 'reply_ids';

    public static function clearDefault(int $userId): void
    {
        db()->prepare('UPDATE reply_ids SET is_default = 0 WHERE user_id = ?')->execute([$userId]);
    }
}
