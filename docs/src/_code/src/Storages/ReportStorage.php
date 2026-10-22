<?php
#region snippet
// file: /app/src/Storages/ReportStorage.php
namespace App\Storages;

final class ReportStorage
{
    private int $permission = 0777;

    public function __construct(
        public readonly string $dir
    ) {}

    public function setPermission(int $permission): void
    {
        $this->permission = $permission;
    }

    public function getPermission(): int
    {
        return $this->permission;
    }
}
#endregion snippet