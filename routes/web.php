<?php

use App\Http\Controllers\Admin\AcademicProgressController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\MarketingManagementController;
use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ElearningAccessController;
use App\Http\Controllers\Admin\ClientManagementController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\PackageManagementController;
use App\Http\Controllers\Admin\StudyUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\CuaninController;
use App\Http\Controllers\StudyTrackerController;
use App\Http\Middleware\WalletMember;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureOnboardingCompleted;
use Illuminate\Support\Facades\Route;

// Public Landing Routes
Route::middleware('guest')->group(function () {
    Route::get('/simulasi/daftar', [\App\Http\Controllers\ExamController::class, 'registerForm'])->name('exams.register');
    Route::post('/simulasi/daftar', [\App\Http\Controllers\ExamController::class, 'register'])->middleware('throttle:8,1');
    Route::get('/simulasi/masuk', [\App\Http\Controllers\ExamController::class, 'loginForm'])->name('exams.login');
    Route::post('/simulasi/masuk', [\App\Http\Controllers\ExamController::class, 'login'])->middleware('throttle:8,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/simulasi/paket-saya', [\App\Http\Controllers\ExamController::class, 'mine'])->name('exams.mine');
    Route::post('/simulasi/paket/{id}/beli', [\App\Http\Controllers\ExamController::class, 'buy'])->middleware('throttle:10,1')->name('exams.buy');
    Route::post('/simulasi/paket/{id}/buka', [\App\Http\Controllers\ExamController::class, 'open'])->middleware('throttle:20,1')->name('exams.open');
    Route::post('/simulasi/tutup', [\App\Http\Controllers\ExamController::class, 'close'])->name('exams.close');
    Route::post('/simulasi/paket/{id}/heartbeat', [\App\Http\Controllers\ExamController::class, 'heartbeat'])->middleware('throttle:6,1')->name('exams.heartbeat');
    Route::post('/simulasi/ujian/{id}/buka', [\App\Http\Controllers\ExamController::class, 'reopen'])->middleware('throttle:20,1')->name('exams.reopen');
    Route::get('/simulasi/paket/{id}/belajar/{page?}', [\App\Http\Controllers\ExamController::class, 'learn'])->whereNumber(['id','page'])->name('exams.learn');
    Route::get('/simulasi/paket/{id}/halaman/{page}', [\App\Http\Controllers\ExamController::class, 'pageImage'])->whereNumber(['id','page'])->middleware('throttle:80,1')->name('exams.page');
    Route::post('/simulasi/paket/{id}/mulai', [\App\Http\Controllers\ExamController::class, 'start'])->middleware('throttle:10,1')->name('exams.start');
    Route::get('/simulasi/ujian/{id}', [\App\Http\Controllers\ExamController::class, 'attempt'])->whereNumber('id')->name('exams.attempt');
    Route::post('/simulasi/ujian/{id}', [\App\Http\Controllers\ExamController::class, 'answer'])->whereNumber('id')->middleware('throttle:80,1')->name('exams.answer');
    Route::get('/simulasi/ujian/{id}/soal/{index}', [\App\Http\Controllers\ExamController::class, 'questionImage'])->whereNumber(['id','index'])->middleware('throttle:80,1')->name('exams.question');
});
Route::prefix('admin/simulasi')->name('admin.exams.')->middleware(['auth', AdminMiddleware::class])->group(function () {
    Route::put('/paket', [\App\Http\Controllers\Admin\ExamManagementController::class, 'updateAll'])->name('update-all');
    Route::get('/paket', [\App\Http\Controllers\Admin\ExamManagementController::class, 'index'])->name('index');
    Route::post('/import', [\App\Http\Controllers\Admin\ExamManagementController::class, 'import'])->middleware('throttle:3,1')->name('import');
    Route::put('/paket/{id}', [\App\Http\Controllers\Admin\ExamManagementController::class, 'update'])->name('update');
    Route::get('/clients', [\App\Http\Controllers\Admin\ExamManagementController::class, 'clients'])->name('clients');
    Route::get('/paket/{id}/soal', [\App\Http\Controllers\Admin\ExamManagementController::class, 'questions'])->name('questions');
    Route::put('/paket/{id}/soal/{index}', [\App\Http\Controllers\Admin\ExamManagementController::class, 'saveQuestion'])->name('questions.save');
    Route::get('/paket/{id}/gambar/{index}', [\App\Http\Controllers\Admin\ExamManagementController::class, 'questionImage'])->name('questions.image');
    Route::get('/preview/{id}/{page?}', [\App\Http\Controllers\Admin\ExamManagementController::class, 'preview'])->whereNumber(['id','page'])->name('preview');
});
Route::view('/syarat-ketentuan', 'legal.terms')->name('terms');
Route::get('/study-tracker', [StudyTrackerController::class, 'landing'])->name('study.landing');
Route::middleware('guest')->group(function () {
    Route::get('/study-tracker/register', [StudyTrackerController::class, 'registerForm'])->name('study.register');
    Route::post('/study-tracker/register', [StudyTrackerController::class, 'register'])->middleware('throttle:8,1,study-register:');
    Route::get('/study-tracker/login', [StudyTrackerController::class, 'loginForm'])->name('study.login');
    Route::post('/study-tracker/login', [StudyTrackerController::class, 'login'])->middleware('throttle:10,1,study-login:');
});
Route::middleware('auth')->group(function () {
    Route::get('/study-tracker/dashboard', [StudyTrackerController::class, 'dashboard'])->name('study.dashboard');
    Route::post('/study-tracker/courses', [StudyTrackerController::class, 'storeCourse'])->middleware('throttle:20,1,study-course:')->name('study.courses.store');
    Route::get('/study-tracker/courses/{course}', [StudyTrackerController::class, 'showCourse'])->name('study.courses.show');
    Route::put('/study-tracker/courses/{course}', [StudyTrackerController::class, 'updateCourse'])->name('study.courses.update');
    Route::put('/study-tracker/courses/{course}/sessions/{session}', [StudyTrackerController::class, 'updateSession'])->name('study.sessions.update');
    Route::delete('/study-tracker/courses/{course}', [StudyTrackerController::class, 'destroyCourse'])->middleware('throttle:12,1,study-delete:')->name('study.courses.destroy');
});
Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');
Route::middleware('guest')->group(function () {
    Route::get('/marketing/register', [MarketingController::class, 'registerForm'])->name('marketing.register');
    Route::post('/marketing/register', [MarketingController::class, 'register'])->middleware('throttle:6,1,marketing-register:');
    Route::get('/marketing/login', [MarketingController::class, 'loginForm'])->name('marketing.login');
    Route::post('/marketing/login', [MarketingController::class, 'login'])->middleware('throttle:10,1,marketing-login:');
});
Route::middleware(['auth', WalletMember::class])->group(function () {
    Route::get('/cuanin', [CuaninController::class, 'index'])->name('cuanin.index');
    Route::post('/cuanin/activate', [CuaninController::class, 'activate'])->middleware('throttle:5,1,cuanin-activate:')->name('cuanin.activate');
    Route::put('/cuanin/bank', [CuaninController::class, 'bank'])->middleware('throttle:6,1,cuanin-bank:')->name('cuanin.bank');
    Route::post('/cuanin/withdrawals', [CuaninController::class, 'withdraw'])->middleware('throttle:6,1,cuanin-withdraw:')->name('cuanin.withdraw');
    Route::get('/cuanin/withdrawals/{id}', [CuaninController::class, 'withdrawal'])->name('cuanin.withdrawal');
    Route::get('/marketing/clients', [MarketingController::class, 'clients'])->name('marketing.clients');
});
Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/pricelist', [LandingController::class, 'pricelist'])->name('landing.pricelist');
Route::get('/testimoni', [LandingController::class, 'testimoni'])->name('landing.testimoni');
Route::get('/kalkulator', [LandingController::class, 'kalkulator'])->name('landing.kalkulator');
Route::get('/simulasi', [\App\Http\Controllers\ExamController::class, 'index'])->name('landing.simulasi');
Route::redirect('/checkout', '/simulasi')->name('landing.checkout');
Route::redirect('/akses', '/simulasi/paket-saya')->name('landing.akses');
Route::redirect('/paket-soal', '/simulasi/paket-saya')->name('landing.paket-soal');

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordController::class, 'forgot'])->name('password.request');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:6,1')->name('password.store');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1,login:');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Payment Page (Accessible by client or admin)
Route::middleware('auth')->group(function () {
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/payment/{order}', [PaymentController::class, 'showPaymentPage'])->name('payment.show');
    Route::get('/payment/{order}/proof', [PaymentController::class, 'proof'])->name('payment.proof');
    Route::get('/payment-receipts/{id}/proof', [PaymentController::class, 'receiptProof'])->name('payment.receipt-proof');
    Route::post('/payment/{order}/upload', [PaymentController::class, 'uploadProof'])->name('payment.upload');
});

// Client Portal Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->middleware('throttle:10,1,onboarding:')->name('onboarding.store');

    Route::middleware([EnsureOnboardingCompleted::class])->group(function () {
        Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    });
});

// Admin Backoffice Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', AdminMiddleware::class])->group(function () {
    Route::get('/cash/setup', [\App\Http\Controllers\Admin\CashController::class, 'setup'])->name('cash.setup');
    Route::get('/cash/opening', [\App\Http\Controllers\Admin\CashController::class, 'opening'])->name('cash.opening');
    Route::post('/cash/prepare', [\App\Http\Controllers\Admin\CashController::class, 'prepare'])->middleware('throttle:6,1,cash:')->name('cash.prepare');
    Route::post('/cash/preview', [\App\Http\Controllers\Admin\CashController::class, 'preview'])->middleware('throttle:10,1,cash-preview:')->name('cash.preview');
    Route::get('/cash/preview', [\App\Http\Controllers\Admin\CashController::class, 'review'])->name('cash.review');
    Route::post('/cash/commit', [\App\Http\Controllers\Admin\CashController::class, 'commit'])->middleware('throttle:6,1,cash:')->name('cash.commit');
    Route::post('/cash/note', [\App\Http\Controllers\Admin\CashController::class, 'note'])->name('cash.note');
    Route::post('/orders/{id}/receipts', [\App\Http\Controllers\Admin\CashController::class, 'receipt'])->middleware('throttle:10,1,cash-receipt:')->name('orders.receipts');
    Route::post('/orders/{id}/correction', [\App\Http\Controllers\Admin\CashController::class, 'correctOrder'])->middleware('throttle:6,1,cash-correction:')->name('orders.correction');
    Route::post('/orders/{id}/split/preview', [\App\Http\Controllers\Admin\CashSplitController::class, 'preview'])->middleware('throttle:10,1,split-preview:')->name('orders.split.preview');
    Route::get('/orders/{id}/split/preview', [\App\Http\Controllers\Admin\CashSplitController::class, 'review'])->name('orders.split.review');
    Route::post('/orders/{id}/split', [\App\Http\Controllers\Admin\CashSplitController::class, 'commit'])->middleware('throttle:6,1,split-commit:')->name('orders.split.commit');
    Route::get('/accounts/{id}/reset-password', [\App\Http\Controllers\Admin\AccountRecoveryController::class, 'show'])->name('accounts.reset');
    Route::post('/accounts/{id}/reset-password', [\App\Http\Controllers\Admin\AccountRecoveryController::class, 'issue'])->middleware('throttle:6,1,account-recovery:')->name('accounts.reset.issue');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/cuanin/requests', [CuaninController::class, 'requests'])->name('cuanin.requests');
    Route::post('/cuanin/requests/{id}', [CuaninController::class, 'process'])->middleware('throttle:10,1,cuanin-process:')->name('cuanin.process');
    Route::post('/elearning/activate', [ElearningAccessController::class, 'activate'])->middleware('throttle:6,1,elearning-activate:')->name('elearning.activate');
    Route::get('/clients/{id}/elearning', [ElearningAccessController::class, 'show'])->name('elearning.show');
    Route::post('/clients/{id}/elearning', [ElearningAccessController::class, 'reveal'])->middleware('throttle:5,1,elearning-reveal:')->name('elearning.reveal');
    Route::put('/clients/{id}/elearning', [ElearningAccessController::class, 'update'])->middleware('throttle:5,1,elearning-update:')->name('elearning.update');
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [AdminProfileController::class, 'update'])->middleware('throttle:10,1,admin-profile:')->name('profile.update');
    Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/activate', [AdminChatController::class, 'activate'])->middleware('throttle:6,1,admin-chat-activate:')->name('chat.activate');
    Route::get('/chat/messages', [AdminChatController::class, 'messages'])->middleware('throttle:120,1,admin-chat-read:')->name('chat.messages');
    Route::post('/chat/messages', [AdminChatController::class, 'store'])->middleware('throttle:30,1,admin-chat-send:')->name('chat.store');
    Route::get('/admins', [AdminManagementController::class, 'index'])->name('admins.index');
    Route::get('/marketing', [MarketingManagementController::class, 'index'])->name('marketing.index');
    Route::get('/marketing/{id}/delete', [MarketingManagementController::class, 'confirmDelete'])->name('marketing.delete');
    Route::delete('/marketing/{id}', [MarketingManagementController::class, 'destroy'])->middleware('throttle:6,1,marketing-delete:')->name('marketing.destroy');
    Route::get('/admins/create', [AdminManagementController::class, 'create'])->name('admins.create');
    Route::post('/admins', [AdminManagementController::class, 'store'])->middleware('throttle:6,1')->name('admins.store');
    Route::get('/study-users', [StudyUserController::class, 'index'])->name('study-users.index');
    Route::get('/study-users/{id}', [StudyUserController::class, 'show'])->name('study-users.show');
    Route::put('/study-users/{id}/status', [StudyUserController::class, 'updateStatus'])->middleware('throttle:10,1,study-user-status:')->name('study-users.status');
    Route::delete('/study-users/{id}', [StudyUserController::class, 'destroy'])->middleware('throttle:6,1,study-user-delete:')->name('study-users.destroy');

    // Clients Management
    Route::get('/clients', [ClientManagementController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [ClientManagementController::class, 'show'])->name('clients.show');
    Route::get('/clients/{id}/delete', [ClientManagementController::class, 'confirmDelete'])->name('clients.delete');
    Route::delete('/clients/{id}', [ClientManagementController::class, 'destroy'])->middleware('throttle:6,1')->name('clients.destroy');
    Route::post('/clients/{id}/reset-password', [ClientManagementController::class, 'resetPassword'])->middleware('throttle:6,1')->name('clients.reset-password');

    // Orders & Payments Management
    Route::get('/orders', [OrderPaymentController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderPaymentController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/approve', [OrderPaymentController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{id}/reject', [OrderPaymentController::class, 'reject'])->name('orders.reject');

    // Packages Management
    Route::get('/packages', [PackageManagementController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageManagementController::class, 'store'])->name('packages.store');
    Route::put('/packages/{id}', [PackageManagementController::class, 'update'])->name('packages.update');
    Route::delete('/packages/{id}', [PackageManagementController::class, 'destroy'])->name('packages.destroy');

    // Academic Progress Management
    Route::get('/academic', [AcademicProgressController::class, 'index'])->name('academic.index');
    Route::get('/academic/client/{user_id}', [AcademicProgressController::class, 'showClientAcademic'])->name('academic.show');
    Route::post('/academic/client/{user_id}/claim', [AcademicProgressController::class, 'claimClient'])->middleware('throttle:20,1,academic-claim:')->name('academic.claim');
    Route::delete('/academic/client/{user_id}/claim', [AcademicProgressController::class, 'releaseClient'])->middleware('throttle:20,1,academic-release:')->name('academic.release');
    Route::post('/academic/client/{user_id}/courses', [AcademicProgressController::class, 'addCourse'])->name('academic.courses.store');
    Route::post('/academic/client/{user_id}/courses/bulk', [AcademicProgressController::class, 'addCourses'])->middleware('throttle:20,1')->name('academic.courses.bulk');
    Route::put('/academic/client/{user_id}/session', [AcademicProgressController::class, 'updateSession'])->middleware('throttle:20,1,academic-session:')->name('academic.session.update');
    Route::put('/academic/progress/{progress_id}', [AcademicProgressController::class, 'updateProgress'])->name('academic.progress.update');
    Route::delete('/academic/courses/{course_id}', [AcademicProgressController::class, 'destroyCourse'])->name('academic.courses.destroy');
});
