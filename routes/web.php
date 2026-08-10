<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth routes
Auth::routes();
Route::redirect('/', '/login');

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

/*------------------------------------------
--------------------------------------------
All Normal Users Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:user'])->prefix('user')->name('user.')->group(function () {

    // === Dashboard ===
    Route::get('/dashboard', [UserController::class, 'userDashboard'])->name('dashboard');

    // === Profile & Password ===
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::get('/change-password', [UserController::class, 'changePassword'])->name('changePassword');
    Route::post('/change-password', [UserController::class, 'updatePassword'])->name('updatePassword');

    // === Pelaksanaan Tugas (Karyawan) ===
    Route::get('/tasks', [UserController::class, 'tasksIndex'])->name('tasks.index'); // Halaman daftar tugas
    Route::post('/tasks/submit', [UserController::class, 'submitTask'])->name('tasks.submit'); // Proses submit file tugas
    Route::post('/tasks/update-status', [UserController::class, 'updateTaskStatus'])->name('tasks.updateStatus'); //Ubah status dari pending ke on progress ke completed
    // === Pelaksanaan Tugas (Karyawan) ===
    Route::get('/tasks', [UserController::class, 'tasksIndex'])->name('tasks.index'); // Halaman daftar tugas
    Route::get('/tasks/{id}', [UserController::class, 'taskDetail'])->name('task.detail'); // Halaman detail tugas
    Route::post('/tasks/submit', [UserController::class, 'submitTask'])->name('tasks.submit'); // Proses submit file tugas
    Route::post('/task/update-status', [UserController::class, 'updateTaskStatus'])->name('task.updateStatus');

    Route::get('/projects/{project}/tasks', [UserController::class, 'projectTasks'])->name('projects.tasks');

    // === Tugas ===
    Route::get('/tasks/{task}', [UserController::class, 'taskDetail'])->name('tasks.detail');
    Route::post('/tasks/submit-notes', [UserController::class, 'submitTaskNotes'])->name('tasks.submitNotes');
});

/*------------------------------------------
--------------------------------------------
All Admin Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:admin'])->prefix('admin')->name('admin.')->group(function () {

    // === Dashboard ===
    Route::get('/home', [AdminController::class, 'adminHome'])->name('home');

    // === User Management ===
    Route::get('/user', [AdminController::class, 'adminUser'])->name('user');
    Route::post('/user/add', [AdminController::class, 'addUser'])->name('addUser');
    Route::post('/user/update', [AdminController::class, 'updateUser'])->name('updateUser');
    Route::post('/user/reset-password', [AdminController::class, 'resetUserPassword'])->name('resetUserPassword');
    Route::delete('/user/delete', [AdminController::class, 'deleteUser'])->name('deleteUser');

    // === Branch ===
    Route::get('/branch', [AdminController::class, 'adminBranch'])->name('branch');
    Route::get('/branch/manage-branch', [AdminController::class, 'manageBranch'])->name('manage.branch');
    Route::post('/branch/manage-branch/edit', [AdminController::class, 'editBranch'])->name('edit.branch');
    Route::post('/branch/manage-branch/delete', [AdminController::class, 'deleteBranch'])->name('delete.branch');
    Route::post('/branch/manage-branch/store', [AdminController::class, 'storeBranch'])->name('store.branch');

    // === Hierarchy ===
    Route::get('/hierarchy', [AdminController::class, 'manageHierarchy'])->name('manage.hierarchy');
    Route::post('/hierarchy/store', [AdminController::class, 'storeHierarchy'])->name('hierarchy.store');
    Route::post('/hierarchy/update', [AdminController::class, 'updateHierarchy'])->name('hierarchy.update');
    Route::post('/hierarchy/unassign', [AdminController::class, 'unassignHierarchy'])->name('hierarchy.unassign');

    // === Manajemen Proyek ===
    Route::get('/projects', [AdminController::class, 'manageProject'])->name('manage.project');
    Route::post('/projects/store', [AdminController::class, 'storeProject'])->name('projects.store');
    Route::post('/projects/update', [AdminController::class, 'updateProject'])->name('projects.update');
    Route::post('/projects/delete', [AdminController::class, 'deleteProject'])->name('projects.delete');

    // Halaman kelola tugas spesifik untuk satu proyek
    Route::get('/projects/{project_id}/tasks', [AdminController::class, 'manageTasks'])->name('tasks.manage');

    // CRUD Tugas
    Route::post('/tasks/store', [AdminController::class, 'storeTask'])->name('tasks.store');
    Route::post('/tasks/update', [AdminController::class, 'updateTask'])->name('tasks.update');
    Route::post('/tasks/delete', [AdminController::class, 'deleteTask'])->name('tasks.delete');

    // === Review Submission ===
    Route::get('/projects/{project_id}/tasks/{task_id}/review', [AdminController::class, 'taskReview'])->name('tasks.review');
    Route::post('/submissions/review', [AdminController::class, 'reviewSubmission'])->name('submissions.review');

    Route::post('submissions/review-feedback', [AdminController::class, 'addReviewFeedback'])->name('submissions.review-feedback');
    Route::post('tasks/update-status', [AdminController::class, 'updateTaskStatus'])->name('tasks.update-status');

    Route::get('/calendar', [AdminController::class, 'calendar'])->name('calendar');
    Route::get('/calendar/data', [AdminController::class, 'calendarData'])->name('calendar.data');

    // === Profile ===
    Route::get('/profile', [AdminController::class, 'adminProfile'])->name('profile');

    // === Change Password ===
    Route::get('change-password', [AdminController::class, 'adminChangePassword'])->name('changePassword');
    Route::post('change-password', [AdminController::class, 'adminUpdatePassword'])->name('updatePassword');
});

/*------------------------------------------
--------------------------------------------
All Manager Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:manager'])->prefix('manager')->name('manager.')->group(function () {

    // === Dashboard ===
    Route::get('/dashboard', [ManagerController::class, 'managerDashboard'])->name('dashboard');

    // === Profile & Password ===
    Route::get('/profile', [ManagerController::class, 'managerProfile'])->name('profile');
    Route::get('/change-password', [ManagerController::class, 'managerChangePassword'])->name('changePassword');
    Route::post('/change-password', [ManagerController::class, 'managerUpdatePassword'])->name('updatePassword');

    // === Hierarchy ===
    Route::get('/hierarchy', [ManagerController::class, 'manageHierarchy'])->name('manage.hierarchy');
    Route::post('/hierarchy/store', [ManagerController::class, 'storeHierarchy'])->name('hierarchy.store');
    Route::post('/hierarchy/update', [ManagerController::class, 'updateHierarchy'])->name('hierarchy.update');
    Route::post('/hierarchy/unassign', [ManagerController::class, 'unassignHierarchy'])->name('hierarchy.unassign');

    // === Manajemen Proyek ===
    Route::get('/projects', [ManagerController::class, 'manageProject'])->name('manage.project');
    Route::post('/projects/store', [ManagerController::class, 'storeProject'])->name('projects.store');
    Route::post('/projects/update', [ManagerController::class, 'updateProject'])->name('projects.update');
    Route::post('/projects/delete', [ManagerController::class, 'deleteProject'])->name('projects.delete');

    // === Manajemen Tugas ===
    // Halaman kelola tugas spesifik untuk satu proyek
    Route::get('/projects/{project_id}/tasks', [ManagerController::class, 'manageTasks'])->name('tasks.manage');

    // CRUD Tugas
    Route::post('/tasks/store', [ManagerController::class, 'storeTask'])->name('tasks.store');
    Route::post('/tasks/update', [ManagerController::class, 'updateTask'])->name('tasks.update');
    Route::post('/tasks/delete', [ManagerController::class, 'deleteTask'])->name('tasks.delete');
    Route::post('/tasks/update-status', [UserController::class, 'updateTaskStatus'])->name('tasks.updateStatus');

    // === Review & Progress ===
    Route::get('/submissions', [ManagerController::class, 'submissionsIndex'])->name('submissions.index'); // Halaman daftar hasil kerja bawahan
    Route::post('/reviews/store', [ManagerController::class, 'storeReview'])->name('reviews.store'); // Proses manager kasih nilai/review

    // === Review Submission ===
    Route::get('/projects/{project_id}/tasks/{task_id}/review', [ManagerController::class, 'taskReview'])->name('tasks.review');
    Route::post('/submissions/review', [ManagerController::class, 'reviewSubmission'])->name('submissions.review');

    // Route::post('tasks/update-status', [ManagerController::class, 'updateTaskStatus'])->name('tasks.update-status');
    Route::post('reviews/update', [ManagerController::class, 'updateReview'])->name('reviews.update');

    Route::get('tasks/{project_id}/review/{task_id}', [ManagerController::class, 'taskReview'])->name('tasks.review');
    Route::post('submissions/review-feedback', [ManagerController::class, 'addReviewFeedback'])->name('submissions.review-feedback'); //Ini yang dipake

    // ✅ Route untuk ubah status tugas (terpisah)
    Route::post('tasks/update-status', [ManagerController::class, 'updateTaskStatus'])->name('tasks.update-status');

    Route::get('/calendar', [ManagerController::class, 'calendar'])->name('calendar');
    Route::get('/calendar/data', [ManagerController::class, 'calendarData'])->name('calendar.data');
});
