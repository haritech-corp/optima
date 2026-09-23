-- Defense-in-depth policies for future direct Supabase API access.
-- The current Laravel implementation uses server-side scoped queries.
alter table public.user_profiles enable row level security;
alter table public.leads enable row level security;
alter table public.clients enable row level security;
alter table public.interactions enable row level security;
alter table public.follow_ups enable row level security;

create policy "users read own profile" on public.user_profiles
for select to authenticated using (auth_user_id = auth.uid());

create policy "users read assigned leads" on public.leads
for select to authenticated using (
  owner_id in (select id from public.user_profiles where auth_user_id = auth.uid())
);

-- Add organization-wide manager policies only after the final role matrix is approved.
