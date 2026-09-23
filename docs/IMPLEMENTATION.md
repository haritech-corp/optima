# Implementation Notes

## MVP complete in this branch

- Supabase email/password authentication and Google OAuth callback bridge.
- Automatic application profile provisioning.
- Dashboard metrics and personal follow-up queue.
- Lead CRUD, filter, duplicate warning, archive, and conversion.
- Client list/detail, interaction timeline, and follow-up center.
- PostgreSQL schema, seed data, RLS starter policy, and CI workflow.

## Before production

1. Change automatic provisioning to an invitation or domain allowlist policy.
2. Set the first user role to `super_admin` directly in `user_profiles`.
3. Finalize the permission matrix and enforce it with Laravel Policies.
4. Configure Google OAuth and all redirect URLs.
5. Select the Laravel runtime and configure worker, scheduler, HTTPS, logs, and backups.
6. Run UAT for each role and test database restore.

## Recommended next increment

- Admin user and role management UI.
- Contact entity and multiple contacts per client.
- CSV import preview and error report.
- Audit event observers and reminder notification jobs.
- Saved filters and management charts.
