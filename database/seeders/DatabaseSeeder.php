<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@qubah.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);

        // 2. Create Parent and Student
        $parent = User::create([
            'name' => 'John Parent',
            'email' => 'parent@qubah.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Parent_->value,
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Timmy Student',
            'email' => 'student@qubah.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Student->value,
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        // Link parent to student
        $parent->children()->attach($student->id);

        // 3. Create 7-Level Hierarchy (Arabic Data)
        
        // Level 1: Educational Stage
        $stagePrimary = \App\Models\EducationalStage::create([
            'title' => 'ابتدائي',
            'description' => 'المرحلة الابتدائية',
            'order' => 1,
        ]);

        // Level 2: Grade
        $grade1 = \App\Models\Grade::create([
            'educational_stage_id' => $stagePrimary->id,
            'title' => 'الصف الأول',
            'description' => 'الصف الأول الابتدائي',
            'order' => 1,
        ]);

        // Level 3: Section
        $sectionA = \App\Models\Section::create([
            'grade_id' => $grade1->id,
            'title' => 'فصل أ',
            'description' => 'الفصل الدراسي الأول',
            'order' => 1,
        ]);

        // Level 4: Subject
        $subjectMath = \App\Models\Subject::create([
            'section_id' => $sectionA->id,
            'title' => 'الرياضيات',
            'description' => 'أساسيات الرياضيات',
            'order' => 1,
        ]);

        // Level 5: Unit
        $unit1 = \App\Models\Unit::create([
            'subject_id' => $subjectMath->id,
            'title' => 'الوحدة الأولى',
            'description' => 'الجمع والطرح',
            'order' => 1,
        ]);

        // Level 6: Lesson
        $lesson1 = \App\Models\Lesson::create([
            'unit_id' => $unit1->id,
            'title' => 'الدرس الأول: الجمع',
            'description' => 'مقدمة في عملية الجمع',
            'order' => 1,
        ]);

        // Level 7: Lesson File
        $fileVideo = \App\Models\LessonFile::create([
            'lesson_id' => $lesson1->id,
            'title' => 'فيديو شرح الجمع',
            'type' => 'video',
            'file_path' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'order' => 1,
        ]);

        $filePdf = \App\Models\LessonFile::create([
            'lesson_id' => $lesson1->id,
            'title' => 'تدريبات الجمع',
            'type' => 'pdf',
            'file_path' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'order' => 2,
        ]);

        // 4. Create some progress for the student
        /*
        UserProgress::create([
            'user_id' => $student->id,
            'lesson_file_id' => $fileVideo->id,
            'status' => ProgressStatus::Completed->value,
            'time_spent' => 300,
        ]);

        UserProgress::create([
            'user_id' => $student->id,
            'lesson_file_id' => $filePdf->id,
            'status' => ProgressStatus::Started->value,
            'time_spent' => 120,
        ]);
        */

        // 5. Seed Settings
        Setting::setValue('app_name', 'قبة المعرفة');
        Setting::setValue('contact_email', 'support@qubah.com');
        Setting::setValue('contact_phone', '+966500000000');
        Setting::setValue('maintenance_mode', false);
        Setting::setValue('social_links', [
            'facebook' => 'https://facebook.com/qubah',
            'twitter' => 'https://twitter.com/qubah',
            'instagram' => 'https://instagram.com/qubah',
            'youtube' => 'https://youtube.com/qubah',
        ]);

        // 6. Seed Plans
        /*
        $planMonthly = Plan::create([
            'name' => 'الباقة الشهرية',
            'price' => 50.00,
            'duration_months' => 1,
            'features' => ['الوصول لجميع المواد', 'تحميل الكتب الدراسية', 'دعم فني على مدار الساعة'],
            'is_active' => true,
        ]);

        $planYearly = Plan::create([
            'name' => 'الباقة السنوية',
            'price' => 450.00,
            'duration_months' => 12,
            'features' => ['خصم ٢٥٪ مقارنة بالشهري', 'الوصول لجميع المواد والدروس', 'تحميل الكتب الدراسية بصيغة PDF', 'دعم فني ممتاز'],
            'is_active' => true,
        ]);

        // 7. Seed Subscriptions
        Subscription::create([
            'user_id' => $student->id,
            'user_name' => $student->name,
            'plan_name' => $planYearly->name,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'amount' => 450.00,
        ]);

        // 8. Seed Notifications
        Notification::create([
            'title' => 'مرحباً بك في قبة المعرفة',
            'body' => 'تم تفعيل حسابك بنجاح. نتمنى لك رحلة تعليمية ممتعة!',
            'target_type' => 'all',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Notification::create([
            'title' => 'تحديث هام للمرحلة الابتدائية',
            'body' => 'تمت إضافة دروس جديدة لمادة الرياضيات للصف الأول الابتدائي.',
            'target_type' => 'grade',
            'target_id' => $grade1->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        */
    }
}
