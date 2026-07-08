<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceTokenModel extends Model
{
    protected $table         = 'device_tokens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'token', 'platform'];

    /** Insert or refresh a device token (unique by token). */
    public function upsertToken(?int $userId, string $token, string $platform = 'android'): void
    {
        $existing = $this->where('token', $token)->first();
        if ($existing) {
            $this->update($existing['id'], ['user_id' => $userId, 'platform' => $platform]);
        } else {
            $this->insert(['user_id' => $userId, 'token' => $token, 'platform' => $platform]);
        }
    }

    /** @return list<string> tokens for a user */
    public function tokensFor(int $userId): array
    {
        return array_column($this->where('user_id', $userId)->findAll(), 'token');
    }
}
