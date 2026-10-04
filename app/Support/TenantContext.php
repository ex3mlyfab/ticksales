<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function __get(string $name)
    {
        if ($name === 'tenant') {
            return $this->tenant;
        }

        throw new \RuntimeException(sprintf('Undefined property: %s', $name));
    }
}
