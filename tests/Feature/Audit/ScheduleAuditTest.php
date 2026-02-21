<?php

namespace Tests\Feature\Audit;

use App\Livewire\Schedule\CreateSchedule;
use App\Livewire\Schedule\EditSchedule;
use App\Livewire\Schedule\ScheduleList;
use App\Livewire\Schedule\MySchedule;
use App\Livewire\Schedule\ScheduleCalendar;
use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * Schedule Module Audit Tests
 *
 * Tests schedule CRUD operations, viewing, and calendar functionality.
 * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7, 5.8
 */
class ScheduleAuditTest extends AuditTestCase
{
    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create a draft schedule for testing.
     */
    protected function createDraftSchedule(array $overrides = []): Schedule
    {
        $weekStart = now()->startOfWeek();
        $defaults = [
            'week_start_date' => $weekStart,
            'week_end_date' => $weekStart->copy()->addDays(3),
            'status' => 'draft',
            'generated_by' => $this->ketua->id,
            'total_slots' => 12,
            'filled_slots' => 0,
            'coverage_rate' => 0,
            'notes' => 'Test draft schedule',
        ];

        return Schedule::create(array_merge($defaults, $overrides));
    }

    /**
     * Create a published schedule for testing.
     */
    protected function createPublishedSchedule(array $overrides = []): Schedule
    {
        $weekStart = now()->startOfWeek();
        $defaults = [
            'week_start_date' => $weekStart,
            'week_end_date' => $weekStart->copy()->addDays(3),
            'status' => 'published',
            'generated_by' => $this->ketua->id,
            'published_by' => $this->ketua->id,
            'published_at' => now(),
            'total_slots' => 12,
            'filled_slots' => 6,
            'coverage_rate' => 50,
            'notes' => 'Test published schedule',
        ];

        return Schedule::create(array_merge($defaults, $overrides));
    }

    /**
     * Create a schedule with assignments for a user.
     */
    protected function createScheduleWithAssignments(User $user, int $assignmentCount = 3): Schedule
    {
        $schedule = $this->createPublishedSchedule();
        $weekStart = Carbon::parse($schedule->week_start_date);

        for ($i = 0; $i < $assignmentCount; $i++) {
            $date = $weekStart->copy()->addDays($i % 4);
            $session = ($i % 3) + 1;

            ScheduleAssignment::create([
                'schedule_id' => $schedule->id,
                'user_id' => $user->id,
                'day' => strtolower($date->englishDayOfWeek),
                'session' => $session,
                'date' => $date->format('Y-m-d'),
                'time_start' => $this->getSessionStartTime($session),
                'time_end' => $this->getSessionEndTime($session),
                'status' => 'scheduled',
            ]);
        }

        $schedule->update([
            'filled_slots' => $assignmentCount,
            'coverage_rate' => ($assignmentCount / 12) * 100,
        ]);

        return $schedule;
    }

    /**
     * Get session start time.
     */
    protected function getSessionStartTime(int $session): string
    {
        return ['07:30:00', '10:20:00', '13:30:00'][$session - 1] ?? '07:30:00';
    }

    /**
     * Get session end time.
     */
    protected function getSessionEndTime(int $session): string
    {
        return ['10:00:00', '12:50:00', '16:00:00'][$session - 1] ?? '10:00:00';
    }

    // ==========================================
    // 7.1 Schedule CRUD Tests
    // Requirements: 5.1, 5.2, 5.3, 5.4, 5.8
    // ==========================================

    /**
     * Test schedule index displays schedules.
     * Requirement 5.1: WHEN an authorized user accesses schedule index
     * THEN the System SHALL display list of schedules with correct status indicators
     */
    public function test_schedule_index_displays_schedules(): void
    {
        // Create test schedules
        $draftSchedule = $this->createDraftSchedule();
        $publishedSchedule = $this->createPublishedSchedule([
            'week_start_date' => now()->addWeek()->startOfWeek(),
            'week_end_date' => now()->addWeek()->startOfWeek()->addDays(3),
        ]);

        $component = Livewire::actingAs($this->ketua)
            ->test(Index::class);

        // Verify schedules are displayed
        $component->assertStatus(200);

        // Verify status indicators are present
        $component->assertSee('draft');
        $component->assertSee('published');
    }

    /**
     * Test schedule index is accessible to authorized users.
     */
    public function test_schedule_index_accessible_to_authorized_users(): void
    {
        $response = $this->actingAs($this->ketua)
            ->get('/admin/schedule');

        $response->assertStatus(200);
    }

    /**
     * Test schedule index requires authentication.
     */
    public function test_schedule_index_requires_authentication(): void
    {
        $response = $this->get('/admin/jadwal');

        $response->assertRedirect('/admin/masuk');
    }

    /**
     * Test schedule creation page is accessible.
     * Requirement 5.2: WHEN an admin creates a new schedule
     * THEN the System SHALL validate required fields and create the schedule with draft status
     */
    public function test_schedule_creation_page_accessible(): void
    {
        $response = $this->actingAs($this->ketua)
            ->get('/admin/jadwal/buat');

        $response->assertStatus(200);
    }

    /**
     * Test schedule creation with validation.
     * Requirement 5.2: WHEN an admin creates a new schedule
     * THEN the System SHALL validate required fields and create the schedule with draft status
     */
    public function test_schedule_creation_with_validation(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(CreateSchedule::class);

        // Verify default dates are set
        $this->assertNotNull($component->get('weekStartDate'));
        $this->assertNotNull($component->get('weekEndDate'));

        // Verify the component initializes with empty assignments grid
        $assignments = $component->get('assignments');
        $this->assertIsArray($assignments);
    }

    /**
     * Test schedule can be saved as draft.
     * Requirement 5.2: Schedule should be created with draft status
     */
    public function test_schedule_can_be_saved_as_draft(): void
    {
        // Create a user to assign
        $assignUser = $this->createUserWithRole('Anggota', [
            'nim' => '55555555',
            'name' => 'Test Assign User',
            'email' => 'assign@test.com',
        ]);

        $component = Livewire::actingAs($this->ketua)
            ->test(CreateSchedule::class);

        // Get the first date from assignments
        $assignments = $component->get('assignments');
        $firstDate = array_key_first($assignments);

        // Add a user to a slot
        $component->call('selectCell', $firstDate, 1);
        $component->call('assignUser', $assignUser->id);

        // Save as draft
        $component->call('saveDraft');

        // Verify schedule was created with draft status
        $this->assertDatabaseHas('schedules', [
            'status' => 'draft',
        ]);
    }

    /**
     * Test schedule editing preserves data.
     * Requirement 5.3: WHEN an admin edits a schedule
     * THEN the System SHALL load existing data and save changes without data loss
     */
    public function test_schedule_editing_preserves_data(): void
    {
        $schedule = $this->createScheduleWithAssignments($this->anggota, 3);

        // Use superAdmin as the edit policy requires 'Super Admin' or 'Admin' role
        $component = Livewire::actingAs($this->superAdmin)
            ->test(EditSchedule::class, ['schedule' => $schedule]);

        // Verify schedule data is loaded
        $this->assertEquals($schedule->id, $component->get('schedule')->id);

        // Verify assignments are loaded
        $assignments = $component->get('assignments');
        $this->assertNotEmpty($assignments);
    }

    /**
     * Test schedule publishing changes status.
     * Requirement 5.4: WHEN an admin publishes a schedule
     * THEN the System SHALL change status to published and make it visible to assigned members
     */
    public function test_schedule_publishing_changes_status(): void
    {
        $schedule = $this->createDraftSchedule();

        // Add some assignments to meet minimum coverage
        $weekStart = Carbon::parse($schedule->week_start_date);
        for ($i = 0; $i < 6; $i++) {
            $date = $weekStart->copy()->addDays($i % 4);
            $session = ($i % 3) + 1;

            ScheduleAssignment::create([
                'schedule_id' => $schedule->id,
                'user_id' => $this->anggota->id,
                'day' => strtolower($date->englishDayOfWeek),
                'session' => $session,
                'date' => $date->format('Y-m-d'),
                'time_start' => $this->getSessionStartTime($session),
                'time_end' => $this->getSessionEndTime($session),
                'status' => 'scheduled',
            ]);
        }

        $schedule->update([
            'filled_slots' => 6,
            'coverage_rate' => 50,
        ]);

        $component = Livewire::actingAs($this->ketua)
            ->test(Index::class);

        // Publish the schedule
        $component->call('publish', $schedule->id);

        // Verify status changed to published
        $schedule->refresh();
        $this->assertEquals('published', $schedule->status);
    }

    /**
     * Test schedule deletion handles assignments.
     * Requirement 5.8: WHEN a schedule is deleted
     * THEN the System SHALL handle related assignments appropriately
     */
    public function test_schedule_deletion_handles_assignments(): void
    {
        $schedule = $this->createScheduleWithAssignments($this->anggota, 3);
        $scheduleId = $schedule->id;

        // Verify assignments exist
        $this->assertDatabaseHas('schedule_assignments', [
            'schedule_id' => $scheduleId,
        ]);

        $component = Livewire::actingAs($this->ketua)
            ->test(Index::class);

        // Delete the schedule
        $component->call('delete', $scheduleId);

        // Verify schedule was deleted
        $this->assertDatabaseMissing('schedules', [
            'id' => $scheduleId,
        ]);

        // Verify assignments were also deleted
        $this->assertDatabaseMissing('schedule_assignments', [
            'schedule_id' => $scheduleId,
        ]);
    }

    /**
     * Test schedule index filtering by status.
     */
    public function test_schedule_index_filtering_by_status(): void
    {
        $draftSchedule = $this->createDraftSchedule();
        $publishedSchedule = $this->createPublishedSchedule([
            'week_start_date' => now()->addWeek()->startOfWeek(),
            'week_end_date' => now()->addWeek()->startOfWeek()->addDays(3),
        ]);

        $component = Livewire::actingAs($this->ketua)
            ->test(Index::class)
            ->set('filterStatus', 'draft');

        // Should only show draft schedules
        $component->assertSee('draft');
    }

    // ==========================================
    // 7.2 Schedule View Tests
    // Requirements: 5.5, 5.7
    // ==========================================

    /**
     * Test my-schedule shows only user's assignments.
     * Requirement 5.5: WHEN a member views their schedule (my-schedule)
     * THEN the System SHALL display only their assigned shifts
     */
    public function test_my_schedule_shows_only_users_assignments(): void
    {
        // Create schedule with assignments for anggota
        $schedule = $this->createScheduleWithAssignments($this->anggota, 2);

        // Create another user with different assignments
        $otherUser = $this->createUserWithRole('Anggota', [
            'nim' => '66666666',
            'name' => 'Other User',
            'email' => 'other@test.com',
        ]);

        $weekStart = Carbon::parse($schedule->week_start_date);
        ScheduleAssignment::create([
            'schedule_id' => $schedule->id,
            'user_id' => $otherUser->id,
            'day' => strtolower($weekStart->englishDayOfWeek),
            'session' => 3,
            'date' => $weekStart->format('Y-m-d'),
            'time_start' => $this->getSessionStartTime(3),
            'time_end' => $this->getSessionEndTime(3),
            'status' => 'scheduled',
        ]);

        $component = Livewire::actingAs($this->anggota)
            ->test(MySchedule::class);

        // Verify component loads successfully
        $component->assertStatus(200);

        // Get the schedules from the component
        $mySchedules = $component->viewData('mySchedules');

        // Verify only anggota's assignments are shown
        foreach ($mySchedules as $dateSchedules) {
            foreach ($dateSchedules as $assignment) {
                $this->assertEquals($this->anggota->id, $assignment->user_id);
            }
        }
    }

    /**
     * Test my-schedule page is accessible.
     */
    public function test_my_schedule_page_accessible(): void
    {
        $response = $this->actingAs($this->anggota)
            ->get('/admin/schedule/my-schedule');

        $response->assertStatus(200);
    }

    /**
     * Test my-schedule requires authentication.
     */
    public function test_my_schedule_requires_authentication(): void
    {
        $response = $this->get('/admin/jadwal/jadwal-saya');

        $response->assertRedirect('/admin/masuk');
    }

    /**
     * Test my-schedule week navigation.
     */
    public function test_my_schedule_week_navigation(): void
    {
        $component = Livewire::actingAs($this->anggota)
            ->test(MySchedule::class);

        // Initial week offset should be 0
        $this->assertEquals(0, $component->get('weekOffset'));

        // Navigate to next week
        $component->call('nextWeek');
        $this->assertEquals(1, $component->get('weekOffset'));

        // Navigate to previous week
        $component->call('previousWeek');
        $this->assertEquals(0, $component->get('weekOffset'));

        // Navigate to previous week again
        $component->call('previousWeek');
        $this->assertEquals(-1, $component->get('weekOffset'));

        // Return to current week
        $component->call('currentWeek');
        $this->assertEquals(0, $component->get('weekOffset'));
    }

    /**
     * Test calendar renders assignments correctly.
     * Requirement 5.7: WHEN the schedule calendar is accessed
     * THEN the System SHALL render all scheduled assignments in correct date positions
     */
    public function test_calendar_renders_assignments_correctly(): void
    {
        $schedule = $this->createScheduleWithAssignments($this->anggota, 3);

        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class);

        // Verify component loads successfully
        $component->assertStatus(200);

        // Verify calendar days are generated
        $calendarDays = $component->viewData('calendarDays');
        $this->assertNotEmpty($calendarDays);

        // Verify each day has the expected structure
        foreach ($calendarDays as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('day', $day);
            $this->assertArrayHasKey('is_current_month', $day);
            $this->assertArrayHasKey('is_today', $day);
            $this->assertArrayHasKey('assignments', $day);
        }
    }

    /**
     * Test calendar page is accessible.
     */
    public function test_calendar_page_accessible(): void
    {
        $response = $this->actingAs($this->ketua)
            ->get('/admin/schedule/calendar');

        $response->assertStatus(200);
    }

    /**
     * Test calendar month navigation.
     */
    public function test_calendar_month_navigation(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class);

        $initialMonth = $component->get('currentMonth');
        $initialYear = $component->get('currentYear');

        // Navigate to next month
        $component->call('nextMonth');

        $newMonth = $component->get('currentMonth');
        $newYear = $component->get('currentYear');

        // Verify month changed
        if ($initialMonth == '12') {
            $this->assertEquals('01', $newMonth);
            $this->assertEquals((string) ((int) $initialYear + 1), $newYear);
        } else {
            $expectedMonth = str_pad((int) $initialMonth + 1, 2, '0', STR_PAD_LEFT);
            $this->assertEquals($expectedMonth, $newMonth);
        }

        // Navigate back to previous month
        $component->call('previousMonth');
        $this->assertEquals($initialMonth, $component->get('currentMonth'));
    }

    /**
     * Test calendar go to today functionality.
     */
    public function test_calendar_go_to_today(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class);

        // Navigate away from current month
        $component->call('nextMonth');
        $component->call('nextMonth');

        // Go back to today
        $component->call('goToToday');

        // Verify we're back to current month
        $this->assertEquals(now()->format('m'), $component->get('currentMonth'));
        $this->assertEquals(now()->format('Y'), $component->get('currentYear'));
        $this->assertEquals(now()->format('Y-m-d'), $component->get('selectedDate'));
    }

    /**
     * Test calendar date selection.
     */
    public function test_calendar_date_selection(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class);

        $testDate = now()->format('Y-m-d');

        // Select a date
        $component->call('selectDate', $testDate);

        // Verify date is selected and details are shown
        $this->assertEquals($testDate, $component->get('selectedDate'));
        $this->assertTrue($component->get('showDetails'));
    }

    /**
     * Test calendar filtering by user.
     */
    public function test_calendar_filtering_by_user(): void
    {
        $schedule = $this->createScheduleWithAssignments($this->anggota, 3);

        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class)
            ->set('filterUser', $this->anggota->id);

        // Verify filter is applied
        $this->assertEquals($this->anggota->id, $component->get('filterUser'));
    }

    /**
     * Test calendar filtering by session.
     */
    public function test_calendar_filtering_by_session(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class)
            ->set('filterSession', 1);

        // Verify filter is applied
        $this->assertEquals(1, $component->get('filterSession'));
    }

    /**
     * Test calendar reset filters.
     */
    public function test_calendar_reset_filters(): void
    {
        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class)
            ->set('filterUser', $this->anggota->id)
            ->set('filterSession', 1)
            ->set('search', 'test');

        // Reset filters
        $component->call('resetFilters');

        // Verify filters are cleared
        $this->assertEquals('', $component->get('filterUser'));
        $this->assertEquals('', $component->get('filterSession'));
        $this->assertEquals('', $component->get('search'));
    }

    /**
     * Test calendar month statistics.
     */
    public function test_calendar_month_statistics(): void
    {
        $schedule = $this->createScheduleWithAssignments($this->anggota, 3);

        $component = Livewire::actingAs($this->ketua)
            ->test(ScheduleCalendar::class);

        $monthStats = $component->viewData('monthStats');

        // Verify statistics structure
        $this->assertArrayHasKey('total_assignments', $monthStats);
        $this->assertArrayHasKey('unique_users', $monthStats);
        $this->assertArrayHasKey('total_hours', $monthStats);
        $this->assertArrayHasKey('coverage_days', $monthStats);
    }
}
