<?php

namespace App\Domain\Access;

/**
 * Computes effective permissions (RN-15, RN-16): denied by default, user denial first,
 * then user allowance, then roles; a permission holds only if all its dependencies hold.
 */
final class PermissionResolver
{
    public function __construct(private readonly PermissionCatalog $catalog) {}

    /**
     * @return array<string, Decision>
     */
    public function explain(Grants $grants): array
    {
        $decisions = [];

        foreach ($this->catalog->keys() as $key) {
            $decisions[$key] = $this->direct($key, $grants);
        }

        do {
            $changed = false;

            foreach ($decisions as $key => $decision) {
                if (! $decision->allowed) {
                    continue;
                }

                foreach ($this->catalog->get($key)->dependsOn as $dependency) {
                    if (! $decisions[$dependency]->allowed) {
                        $decisions[$key] = new Decision(false, Reason::MissingDependency);
                        $changed = true;
                        break;
                    }
                }
            }
        } while ($changed);

        return $decisions;
    }

    /**
     * @return list<string>
     */
    public function allowedKeys(Grants $grants): array
    {
        return array_keys(array_filter($this->explain($grants), fn (Decision $decision): bool => $decision->allowed));
    }

    private function direct(string $key, Grants $grants): Decision
    {
        return match (true) {
            ! $grants->active => new Decision(false, Reason::Inactive),
            $grants->isAdmin => new Decision(true, Reason::Administrator),
            ($grants->overrides[$key] ?? null) === Effect::Deny => new Decision(false, Reason::DeniedForUser),
            ($grants->overrides[$key] ?? null) === Effect::Allow => new Decision(true, Reason::AllowedForUser),
            isset($grants->rolePermissions[$key]) => new Decision(true, Reason::Role, $grants->rolePermissions[$key]),
            default => new Decision(false, Reason::NotGranted),
        };
    }
}
