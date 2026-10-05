<?php

use App\Http\Controllers\AcademicReportController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AnnouncementController;
// =====================================================
// CONTROLLERS
// =====================================================

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookBorrowingController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\ClassPerformanceReportController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\ClassSubjectController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamSubjectController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\LibraryFineController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ResultPublicationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentFeeController;
use App\Http\Controllers\StudentPortalAccountController;
use App\Http\Controllers\StudentResultController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\TermController;
use App\Models\Announcement;
use Illuminate\Support\Facades\Route;

// =====================================================
// AUTHENTICATION
// =====================================================

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::get('/reset-password/{token}', [PasswordResetController::class, 'create'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

// =====================================================
// DASHBOARD
// =====================================================

Route::get('/dashboard', function () {
    $user = auth()->user();
    $recentAnnouncements = Announcement::query()
        ->visible()
        ->forUser($user)
        ->withCount([
            'reads as is_read' => fn ($query) => $query->where('user_id', $user->id),
        ])
        ->orderByDesc('is_pinned')
        ->orderByDesc('published_at')
        ->limit(10)
        ->get(['id', 'title', 'content', 'category', 'is_pinned', 'published_at']);

    return view('dashboard', compact('recentAnnouncements'));
})
    ->middleware('auth')
    ->name('dashboard');

// =====================================================
// STUDENTS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('students.index');

    Route::get('/students/create', [StudentController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('students.create');

    Route::post('/students', [StudentController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('students.store');

    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])
        ->middleware('permission:students.update')
        ->name('students.edit');

    Route::put('/students/{student}', [StudentController::class, 'update'])
        ->middleware('permission:students.update')
        ->name('students.update');

    Route::post('/students/{student}/portal-account', [StudentPortalAccountController::class, 'store'])
        ->middleware('permission:students.portal_accounts.manage')
        ->name('students.portal-account.store');

    Route::delete('/students/{student}', [StudentController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('students.destroy');

});

// =====================================================
// LIBRARY BOOKS
// =====================================================

Route::middleware('auth')->group(function () {
    Route::resource('announcements', AnnouncementController::class);

    Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])
        ->name('announcements.publish');

    Route::post('announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])
        ->name('announcements.archive');
});

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('book-copies', BookCopyController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('book-borrowings', BookBorrowingController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('book-borrowings/{bookBorrowing}/return', [BookBorrowingController::class, 'returnBook'])
        ->name('book-borrowings.return');

    Route::post('book-borrowings/{bookBorrowing}/renew', [BookBorrowingController::class, 'renew'])
        ->name('book-borrowings.renew');
});

Route::middleware('auth')->group(function () {
    Route::resource('library-fines', LibraryFineController::class)
        ->only(['index', 'show']);

    Route::post('library-fines/{libraryFine}/pay', [LibraryFineController::class, 'pay'])
        ->name('library-fines.pay');

    Route::post('library-fines/{libraryFine}/waive', [LibraryFineController::class, 'waive'])
        ->name('library-fines.waive');
});

Route::middleware('auth')->group(function () {
    Route::resource('fee-structures', FeeStructureController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('student-fees/generate', [StudentFeeController::class, 'generateForm'])
        ->name('student-fees.generate-form');

    Route::post('student-fees/generate', [StudentFeeController::class, 'generate'])
        ->name('student-fees.generate');

    Route::resource('student-fees', StudentFeeController::class)
        ->only(['index', 'show', 'destroy']);
});

Route::middleware('auth')->group(function () {
    Route::resource('payments', PaymentController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])
        ->name('payments.reverse');
});

Route::middleware('auth')->group(function () {
    Route::get('receipts/{payment}', [ReceiptController::class, 'show'])
        ->name('receipts.show');

    Route::get('receipts/{payment}/pdf', [ReceiptController::class, 'pdf'])
        ->name('receipts.pdf');

    Route::get('receipts/{payment}/print', [ReceiptController::class, 'print'])
        ->name('receipts.print');
});

Route::middleware('auth')->prefix('reports/finance')->name('reports.finance.')->group(function () {
    Route::get('collection-summary', [FinanceReportController::class, 'collectionSummary'])
        ->name('collection-summary');

    Route::get('outstanding', [FinanceReportController::class, 'outstanding'])
        ->name('outstanding');

    Route::get('daily-collection', [FinanceReportController::class, 'dailyCollection'])
        ->name('daily-collection');

    Route::get('class-collection', [FinanceReportController::class, 'classCollection'])
        ->name('class-collection');

    Route::get('payment-methods', [FinanceReportController::class, 'paymentMethods'])
        ->name('payment-methods');

    Route::get('student-statement/{student}', [FinanceReportController::class, 'studentStatement'])
        ->name('student-statement');
});

// =====================================================
// TEACHERS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/teachers', [TeacherController::class, 'index'])
        ->middleware('permission:teachers.view')
        ->name('teachers.index');

    Route::get('/teachers/create', [TeacherController::class, 'create'])
        ->middleware('permission:teachers.create')
        ->name('teachers.create');

    Route::post('/teachers', [TeacherController::class, 'store'])
        ->middleware('permission:teachers.create')
        ->name('teachers.store');

    Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])
        ->middleware('permission:teachers.update')
        ->name('teachers.edit');

    Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])
        ->middleware('permission:teachers.update')
        ->name('teachers.update');

    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])
        ->middleware('permission:teachers.delete')
        ->name('teachers.destroy');

});

// =====================================================
// STAFF
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/staff', [StaffController::class, 'index'])
        ->middleware('permission:teachers.view')
        ->name('staff.index');

    Route::get('/staff/create', [StaffController::class, 'create'])
        ->middleware('permission:teachers.create')
        ->name('staff.create');

    Route::post('/staff', [StaffController::class, 'store'])
        ->middleware('permission:teachers.create')
        ->name('staff.store');

    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])
        ->middleware('permission:teachers.update')
        ->name('staff.update');

    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])
        ->middleware('permission:teachers.delete')
        ->name('staff.destroy');

});

// =====================================================
// ACADEMIC YEARS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/academic-years', [AcademicYearController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('academic-years.index');

    Route::get('/academic-years/create', [AcademicYearController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('academic-years.create');

    Route::post('/academic-years', [AcademicYearController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('academic-years.store');

    Route::get('/academic-years/{academicYear}/edit', [AcademicYearController::class, 'edit'])
        ->middleware('permission:academic_structure.update')
        ->name('academic-years.edit');

    Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update'])
        ->middleware('permission:academic_structure.update')
        ->name('academic-years.update');

    Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('academic-years.destroy');

});

// =====================================================
// TERMS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/terms', [TermController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('terms.index');

    Route::get('/terms/create', [TermController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('terms.create');

    Route::post('/terms', [TermController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('terms.store');

    Route::get('/terms/{term}/edit', [TermController::class, 'edit'])
        ->middleware('permission:academic_structure.update')
        ->name('terms.edit');

    Route::put('/terms/{term}', [TermController::class, 'update'])
        ->middleware('permission:academic_structure.update')
        ->name('terms.update');

    Route::delete('/terms/{term}', [TermController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('terms.destroy');

});

// =====================================================
// CLASSES
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/classes', [ClassRoomController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('classes.index');

    Route::get('/classes/create', [ClassRoomController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('classes.create');

    Route::post('/classes', [ClassRoomController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('classes.store');

    Route::get('/classes/{class}/edit', [ClassRoomController::class, 'edit'])
        ->middleware('permission:academic_structure.update')
        ->name('classes.edit');

    Route::put('/classes/{class}', [ClassRoomController::class, 'update'])
        ->middleware('permission:academic_structure.update')
        ->name('classes.update');

    Route::delete('/classes/{class}', [ClassRoomController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('classes.destroy');

});

// =====================================================
// STREAMS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/streams', [StreamController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('streams.index');

    Route::get('/streams/create', [StreamController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('streams.create');

    Route::post('/streams', [StreamController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('streams.store');

    Route::get('/streams/{stream}/edit', [StreamController::class, 'edit'])
        ->middleware('permission:academic_structure.update')
        ->name('streams.edit');

    Route::put('/streams/{stream}', [StreamController::class, 'update'])
        ->middleware('permission:academic_structure.update')
        ->name('streams.update');

    Route::delete('/streams/{stream}', [StreamController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('streams.destroy');

});

// =====================================================
// SUBJECTS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/subjects', [SubjectController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('subjects.index');

    Route::get('/subjects/create', [SubjectController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('subjects.create');

    Route::post('/subjects', [SubjectController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('subjects.store');

    Route::get('/subjects/{subject}/edit', [SubjectController::class, 'edit'])
        ->middleware('permission:academic_structure.update')
        ->name('subjects.edit');

    Route::put('/subjects/{subject}', [SubjectController::class, 'update'])
        ->middleware('permission:academic_structure.update')
        ->name('subjects.update');

    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('subjects.destroy');

});

// =====================================================
// TEACHER ↔ SUBJECT
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/teacher-subjects', [TeacherSubjectController::class, 'index'])
        ->middleware('permission:teachers.view')
        ->name('teacher-subjects.index');

    Route::get('/teacher-subjects/create', [TeacherSubjectController::class, 'create'])
        ->middleware('permission:teachers.create')
        ->name('teacher-subjects.create');

    Route::post('/teacher-subjects', [TeacherSubjectController::class, 'store'])
        ->middleware('permission:teachers.create')
        ->name('teacher-subjects.store');

    Route::delete(
        '/teacher-subjects/{teacher}/{subject}',
        [TeacherSubjectController::class, 'destroy']
    )
        ->middleware('permission:teachers.delete')
        ->name('teacher-subjects.destroy');

});

// =====================================================
// CLASS ↔ SUBJECT
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/class-subjects', [ClassSubjectController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('class-subjects.index');

    Route::get('/class-subjects/create', [ClassSubjectController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('class-subjects.create');

    Route::post('/class-subjects', [ClassSubjectController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('class-subjects.store');

    Route::delete(
        '/class-subjects/{class}/{subject}',
        [ClassSubjectController::class, 'destroy']
    )
        ->middleware('permission:students.delete')
        ->name('class-subjects.destroy');

});

// =====================================================
// STUDENT ENROLLMENT
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/enrollments', [StudentEnrollmentController::class, 'index'])
        ->middleware('permission:students.view')
        ->name('enrollments.index');

    Route::get('/enrollments/create', [StudentEnrollmentController::class, 'create'])
        ->middleware('permission:students.create')
        ->name('enrollments.create');

    Route::post('/enrollments', [StudentEnrollmentController::class, 'store'])
        ->middleware('permission:students.create')
        ->name('enrollments.store');

    Route::get('/enrollments/{enrollment}/edit', [StudentEnrollmentController::class, 'edit'])
        ->middleware('permission:enrollments.update')
        ->name('enrollments.edit');

    Route::put('/enrollments/{enrollment}', [StudentEnrollmentController::class, 'update'])
        ->middleware('permission:enrollments.update')
        ->name('enrollments.update');

    Route::delete('/enrollments/{enrollment}', [StudentEnrollmentController::class, 'destroy'])
        ->middleware('permission:students.delete')
        ->name('enrollments.destroy');

});

// =====================================================
// ATTENDANCE
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->middleware('permission:attendance.view')
        ->name('attendance.index');

    Route::get('/attendance/create', [AttendanceController::class, 'create'])
        ->middleware('permission:attendance.create')
        ->name('attendance.create');

    Route::post('/attendance', [AttendanceController::class, 'store'])
        ->middleware('permission:attendance.create')
        ->name('attendance.store');

    Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
        ->middleware('permission:attendance.update')
        ->name('attendance.edit');

    Route::match(['put', 'patch'], '/attendance/{attendance}', [AttendanceController::class, 'update'])
        ->middleware('permission:attendance.update')
        ->name('attendance.update');

    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])
        ->middleware('permission:attendance.update')
        ->name('attendance.destroy');

});

// =====================================================
// TEACHER ASSIGNMENTS
// =====================================================

Route::middleware('auth')->group(function () {

    Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index'])
        ->middleware('permission:teachers.view')
        ->name('teacher-assignments.index');

    Route::get('/teacher-assignments/create', [TeacherAssignmentController::class, 'create'])
        ->middleware('permission:teachers.create')
        ->name('teacher-assignments.create');

    Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store'])
        ->middleware('permission:teachers.create')
        ->name('teacher-assignments.store');

    Route::get('/teacher-assignments/{teacherAssignment}/edit', [TeacherAssignmentController::class, 'edit'])
        ->middleware('permission:teachers.update')
        ->name('teacher-assignments.edit');

    Route::put('/teacher-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'update'])
        ->middleware('permission:teachers.update')
        ->name('teacher-assignments.update');

    Route::delete('/teacher-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'destroy'])
        ->middleware('permission:teachers.delete')
        ->name('teacher-assignments.destroy');

});

// =====================================================
// FUTURE MODULES
// =====================================================

// Academic Calendar
// Timetable
// Exams
// Marks
// Results
// Library
// Finance
// Reports
// Users
// Roles
// Permissions
// Parent Portal
// Student Portal
// Communication
// Audit Trail

// Exams
Route::middleware('auth')->group(function () {

    Route::get('/exams', [ExamController::class, 'index'])
        ->middleware('permission:marks.view')
        ->name('exams.index');

    Route::get('/exams/create', [ExamController::class, 'create'])
        ->middleware('permission:marks.create')
        ->name('exams.create');

    Route::post('/exams', [ExamController::class, 'store'])
        ->middleware('permission:marks.create')
        ->name('exams.store');

    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])
        ->middleware('permission:marks.update')
        ->name('exams.edit');

    Route::put('/exams/{exam}', [ExamController::class, 'update'])
        ->middleware('permission:marks.update')
        ->name('exams.update');

    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])
        ->middleware('permission:marks.delete')
        ->name('exams.destroy');
    // Exam Subjects

    Route::get('/exam-subjects', [ExamSubjectController::class, 'index'])
        ->middleware('permission:marks.view')
        ->name('exam-subjects.index');

    Route::get('/exam-subjects/create', [ExamSubjectController::class, 'create'])
        ->middleware('permission:marks.create')
        ->name('exam-subjects.create');

    Route::post('/exam-subjects', [ExamSubjectController::class, 'store'])
        ->middleware('permission:marks.create')
        ->name('exam-subjects.store');

    Route::delete('/exam-subjects/{examSubject}', [ExamSubjectController::class, 'destroy'])
        ->middleware('permission:marks.delete')
        ->name('exam-subjects.destroy');
    // Marks

    Route::get('/marks', [MarkController::class, 'index'])
        ->middleware('permission:marks.view')
        ->name('marks.index');

    Route::get('/marks/create', [MarkController::class, 'create'])
        ->middleware('permission:marks.create')
        ->name('marks.create');

    Route::post('/marks', [MarkController::class, 'store'])
        ->middleware('permission:marks.create')
        ->name('marks.store');
    Route::post('/marks/{mark}/approve', [MarkController::class, 'approve'])
        ->middleware('permission:marks.approve')
        ->name('marks.approve');
    Route::post('/marks/{mark}/approve', [MarkController::class, 'approve'])
        ->middleware('permission:marks.approve')
        ->name('marks.approve');
    Route::get('/result-publications', [ResultPublicationController::class, 'index'])
        ->middleware('permission:marks.view')
        ->name('result-publications.index');

    Route::get('/result-publications/create', [ResultPublicationController::class, 'create'])
        ->middleware('permission:results.publish')
        ->name('result-publications.create');

    Route::post('/result-publications', [ResultPublicationController::class, 'store'])
        ->middleware('permission:results.publish')
        ->name('result-publications.store');

    Route::get('/student-results', [StudentResultController::class, 'index'])
        ->middleware('auth')
        ->name('student-results.index');

    Route::get('/student-results/{student}/print', [StudentResultController::class, 'printResult'])
        ->middleware('auth')
        ->name('student-results.print');

    Route::get('/academic-reports/{student}', [AcademicReportController::class, 'show'])
        ->middleware('permission:reports.academic.view')
        ->name('academic-reports.show');

    Route::get('/academic-reports/{student}/print', [AcademicReportController::class, 'print'])
        ->middleware('permission:reports.academic.export')
        ->name('academic-reports.print');

    Route::get('/academic-reports/{student}/pdf', [AcademicReportController::class, 'pdf'])
        ->middleware('permission:reports.academic.export')
        ->name('academic-reports.pdf');

    Route::prefix('reports/academic')->name('reports.academic.')->group(function () {
        Route::get('students/{student}', [AcademicReportController::class, 'show'])
            ->middleware('permission:reports.academic.view')
            ->name('student');

        Route::get('students/{student}/pdf', [AcademicReportController::class, 'pdf'])
            ->middleware('permission:reports.academic.export')
            ->name('student.pdf');

        Route::get('students/{student}/print', [AcademicReportController::class, 'print'])
            ->middleware('permission:reports.academic.export')
            ->name('student.print');

        Route::get('class-performance/{class}', [ClassPerformanceReportController::class, 'classPerformance'])
            ->middleware('permission:reports.academic.view')
            ->name('class-performance');

        Route::get('class-performance/{class}/pdf', [ClassPerformanceReportController::class, 'classPerformancePdf'])
            ->middleware('permission:reports.academic.export')
            ->name('class-performance.pdf');

        Route::get('class-performance/{class}/print', [ClassPerformanceReportController::class, 'classPerformancePrint'])
            ->middleware('permission:reports.academic.export')
            ->name('class-performance.print');
    });
});
