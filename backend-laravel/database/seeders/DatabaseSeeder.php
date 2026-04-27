<?php

namespace Database\Seeders;

use App\Models\EmailDraft;
use App\Models\Integration;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\TeamInvite;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create Sarah Jenkins (main user from the UI) ───────────────────
        $sarah = User::create([
            'first_name'    => 'Sarah',
            'last_name'     => 'Jenkins',
            'email'         => 'sarah@meetlyai.ai',
            'password'      => Hash::make('Password1'),
            'display_name'  => 'Sarah J.',
            'biography'     => 'Product Lead focused on turning meeting chaos into actionable clarity. Based in San Francisco.',
            'role'          => 'admin',
            'plan'          => 'pro',
            'department'    => 'Product',
            'job_title'     => 'Product Lead',
            'storage_used'  => 446500000, // ~85% of 500MB
            'storage_limit' => 524288000,
        ]);

        UserSetting::create([
            'user_id'                        => $sarah->id,
            'auto_send_summary_emails'       => true,
            'create_tasks_from_action_items' => true,
            'weekly_digest_report'           => false,
            'email_notifications'            => true,
            'push_notifications'             => true,
        ]);

        // ── Team members ────────────────────────────────────────────────────
        $marcus = User::create([
            'first_name'   => 'Marcus',
            'last_name'    => 'Thorne',
            'email'        => 'marcus@meetlyai.ai',
            'password'     => Hash::make('Password1'),
            'display_name' => 'Marcus T.',
            'role'         => 'member',
            'plan'         => 'pro',
            'department'   => 'Engineering',
            'job_title'    => 'Backend Engineer',
        ]);

        $alex = User::create([
            'first_name'   => 'Alex',
            'last_name'    => 'Rivera',
            'email'        => 'alex@meetlyai.ai',
            'password'     => Hash::make('Password1'),
            'display_name' => 'Alex R.',
            'role'         => 'member',
            'plan'         => 'pro',
            'department'   => 'Sales',
            'job_title'    => 'Business Development',
        ]);

        $lisa = User::create([
            'first_name'   => 'Lisa',
            'last_name'    => 'Park',
            'email'        => 'lisa@meetlyai.ai',
            'password'     => Hash::make('Password1'),
            'display_name' => 'Lisa P.',
            'role'         => 'member',
            'plan'         => 'pro',
            'department'   => 'Design',
            'job_title'    => 'Design Lead',
        ]);

        foreach ([$marcus, $alex, $lisa] as $member) {
            UserSetting::create(['user_id' => $member->id]);
        }

        // ── Integrations for Sarah ───────────────────────────────────────────
        Integration::create([
            'user_id'      => $sarah->id,
            'provider'     => 'slack',
            'status'       => 'connected',
            'meta'         => ['channel' => '#meetings', 'workspace' => 'MeetlyAI HQ'],
        ]);

        Integration::create([
            'user_id'  => $sarah->id,
            'provider' => 'zoom',
            'status'   => 'disconnected',
        ]);

        // ── Meetings ──────────────────────────────────────────────────────────
        $q3Meeting = Meeting::create([
            'owner_id'         => $sarah->id,
            'title'            => 'Q4 Roadmap Alignment & Technical Debt Strategy',
            'analysis_profile' => 'executive_summary',
            'transcript'       => "Sarah Jenkins: Alright everyone, let's kick things off. Today we're finalizing the Q4 roadmap and I want us to leave with clear owners on each workstream.\n\nMarcus Thorne: I've been looking at the analytics dashboard spec. We need to nail down the API contract first before we can start building out the frontend visualization layer.\n\nAlex Rivera: Agreed on the API work. On the enterprise side, I can have the pricing deck ready for finance by end of week — that one's close to done.",
            'ai_summary'       => "The Q4 planning session focused on three pillars: shipping the new analytics dashboard by November 15th, reducing legacy API technical debt through a dedicated two-sprint initiative, and finalizing the enterprise pricing tier. The team aligned on deprioritizing the mobile app redesign to Q1 2025.\n\nKey risks identified include the dependency on the third-party data pipeline for the analytics feature and the availability of the design team for the enterprise onboarding flow review.",
            'duration_seconds' => 2700,
            'word_count'       => 3412,
            'sentiment'        => 'productive',
            'status'           => 'ready',
            'tags'             => ['q4', 'roadmap', 'technical-debt'],
            'created_at'       => now()->subDay(),
        ]);

        $q3Meeting->attendees()->attach([
            $sarah->id  => ['is_host' => true,  'talk_time_seconds' => 900],
            $marcus->id => ['is_host' => false, 'talk_time_seconds' => 720],
            $alex->id   => ['is_host' => false, 'talk_time_seconds' => 600],
            $lisa->id   => ['is_host' => false, 'talk_time_seconds' => 480],
        ]);

        $standup = Meeting::create([
            'owner_id'         => $sarah->id,
            'title'            => 'Weekly Engineering Standup',
            'analysis_profile' => 'action_oriented',
            'ai_summary'       => 'Quick sync covering sprint progress. API endpoints at 80% completion. Deployment pipeline blocking issue resolved.',
            'duration_seconds' => 900,
            'word_count'       => 842,
            'sentiment'        => 'productive',
            'status'           => 'ready',
            'tags'             => ['standup', 'engineering'],
            'created_at'       => now()->setHour(9)->setMinute(30),
        ]);

        $standup->attendees()->attach([
            $sarah->id  => ['is_host' => true,  'talk_time_seconds' => 240],
            $marcus->id => ['is_host' => false, 'talk_time_seconds' => 420],
        ]);

        $branding = Meeting::create([
            'owner_id'         => $sarah->id,
            'title'            => 'Branding Workshop - Session 1',
            'analysis_profile' => 'verbatim_archive',
            'ai_summary'       => 'Explored three brand directions for the rebrand. Team aligned on "Minimal Precision" as the primary direction.',
            'duration_seconds' => 4800,
            'word_count'       => 6200,
            'sentiment'        => 'decision_focused',
            'status'           => 'ready',
            'tags'             => ['branding', 'design', 'workshop'],
            'created_at'       => now()->subDays(2),
        ]);

        $branding->attendees()->attach([
            $sarah->id => ['is_host' => true, 'talk_time_seconds' => 1200],
            $lisa->id  => ['is_host' => false, 'talk_time_seconds' => 2400],
        ]);

        // ── Tasks ─────────────────────────────────────────────────────────────
        Task::create([
            'meeting_id'  => $q3Meeting->id,
            'created_by'  => $sarah->id,
            'assigned_to' => $marcus->id,
            'title'       => 'Create detailed technical spec for analytics dashboard API endpoints',
            'status'      => 'in_progress',
            'priority'    => 'high',
            'department'  => 'Engineering',
            'due_date'    => now()->addDays(5),
            'progress'    => 65,
        ]);

        Task::create([
            'meeting_id'  => $q3Meeting->id,
            'created_by'  => $sarah->id,
            'assigned_to' => $sarah->id,
            'title'       => 'Schedule technical debt audit with backend team leads',
            'status'      => 'in_progress',
            'priority'    => 'medium',
            'department'  => 'Engineering',
            'due_date'    => now()->addDays(4),
            'progress'    => 30,
        ]);

        Task::create([
            'meeting_id'  => $q3Meeting->id,
            'created_by'  => $sarah->id,
            'assigned_to' => $alex->id,
            'title'       => 'Share enterprise pricing deck with finance team',
            'status'      => 'done',
            'priority'    => 'medium',
            'department'  => 'Sales',
            'due_date'    => now()->subDays(3),
            'progress'    => 100,
        ]);

        Task::create([
            'meeting_id'  => $q3Meeting->id,
            'created_by'  => $sarah->id,
            'assigned_to' => $alex->id,
            'title'       => 'Enterprise pricing model revision',
            'status'      => 'in_progress',
            'priority'    => 'low',
            'department'  => 'Sales',
            'due_date'    => now()->addDays(9),
            'progress'    => 80,
        ]);

        Task::create([
            'created_by'  => $sarah->id,
            'assigned_to' => $lisa->id,
            'title'       => 'Redesign onboarding flow',
            'status'      => 'backlog',
            'priority'    => 'medium',
            'department'  => 'Design',
        ]);

        Task::create([
            'created_by'  => $sarah->id,
            'assigned_to' => $marcus->id,
            'title'       => 'Write API documentation for v3 endpoints',
            'status'      => 'backlog',
            'priority'    => 'medium',
            'department'  => 'Engineering',
        ]);

        Task::create([
            'created_by'  => $sarah->id,
            'assigned_to' => $sarah->id,
            'title'       => 'User interview synthesis Q4',
            'status'      => 'backlog',
            'priority'    => 'low',
            'department'  => 'Product',
        ]);

        Task::create([
            'meeting_id'  => $branding->id,
            'created_by'  => $sarah->id,
            'assigned_to' => $lisa->id,
            'title'       => 'Brand identity refresh proposal',
            'status'      => 'in_review',
            'priority'    => 'medium',
            'department'  => 'Design',
            'progress'    => 90,
        ]);

        Task::create([
            'created_by'  => $sarah->id,
            'assigned_to' => $sarah->id,
            'title'       => 'Q4 OKR alignment document',
            'status'      => 'in_review',
            'priority'    => 'high',
            'department'  => 'Product',
            'progress'    => 85,
        ]);

        Task::create([
            'created_by'  => $sarah->id,
            'assigned_to' => $marcus->id,
            'title'       => 'Set up CI/CD pipeline for staging',
            'status'      => 'done',
            'priority'    => 'high',
            'department'  => 'Engineering',
            'progress'    => 100,
        ]);

        // ── Email Draft ────────────────────────────────────────────────────────
        EmailDraft::create([
            'meeting_id'  => $q3Meeting->id,
            'created_by'  => $sarah->id,
            'subject'     => '[MeetlyAI] Q4 Roadmap Alignment & Technical Debt Strategy — Action Items',
            'body'        => "Hi team,\n\nThanks for joining today's Q4 planning session. Here's a quick summary of what we aligned on and the action items coming out of our discussion.\n\n📋 MEETING SUMMARY\nWe finalized three key priorities for Q4: (1) shipping the analytics dashboard by November 15th, (2) a dedicated two-sprint technical debt reduction initiative, and (3) closing the enterprise pricing tier. The mobile app redesign has been moved to Q1 2025.\n\n✅ ACTION ITEMS\n\n→ Marcus Thorne\n   Create detailed technical spec for analytics dashboard API endpoints\n   Due: November 1st | Priority: High\n\n→ Sarah Jenkins\n   Schedule technical debt audit with backend team leads\n   Due: October 30th | Priority: Medium\n\n→ Alex Rivera\n   Enterprise pricing deck — COMPLETED ✓\n\n⚠️ KEY RISKS\n• Third-party data pipeline dependency for the analytics feature\n• Design team availability for enterprise onboarding flow review\n\nLet me know if I've missed anything or if any of the action items need clarification.\n\nBest,\nSarah Jenkins\nProduct Lead, MeetlyAI",
            'recipients'  => [$marcus->id, $alex->id, $lisa->id],
            'status'      => 'draft',
        ]);

        // ── Pending team invite ────────────────────────────────────────────────
        TeamInvite::create([
            'invited_by' => $sarah->id,
            'email'      => 'jordan.kim@meetlyai.ai',
            'token'      => TeamInvite::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }
}