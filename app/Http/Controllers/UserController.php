<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ReportSetting;
use Illuminate\Support\Facades\Storage;
use App\Models\TaskSubmission;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class UserController extends Controller
{

    /*------------------------------------------
    Dashboard User (menampilkan daftar proyek)
    --------------------------------------------*/
    public function userDashboard(Request $request)
    {
        $userId = Auth::id();

        // Ambil semua proyek yang memiliki tugas yang ditugaskan ke user ini
        $projects = Project::whereHas('tasks', function ($query) use ($userId) {
            $query->where('assigned_to', $userId);
        })->with(['manager','category', 'tasks' => function ($query) use ($userId) {
            $query->where('assigned_to', $userId);
        }])->get();

        // Statistik sederhana untuk setiap proyek (opsional)
        foreach ($projects as $project) {
            $tasks = $project->tasks;
            $project->total_tasks = $tasks->count();
            $project->completed_tasks = $tasks->where('status', 'Completed')->count();
            $project->pending_tasks = $tasks->where('status', 'Pending')->count();
            $project->on_progress_tasks = $tasks->where('status', 'On Progress')->count();
            $project->progress_percentage = $project->total_tasks > 0
                ? round(($project->completed_tasks / $project->total_tasks) * 100)
                : 0;
        }

        // ===== GLOBAL STATISTICS FOR CHARTS =====
        // Get all tasks assigned to this user
        $allTasks = Task::where('assigned_to', $userId)->get();

        // Status distribution (for pie chart)
        $statusStats = [
            'Pending' => $allTasks->where('status', 'Pending')->count(),
            'On Progress' => $allTasks->where('status', 'On Progress')->count(),
            'Review' => $allTasks->where('status', 'Review')->count(),
            'Completed' => $allTasks->where('status', 'Completed')->count(),
            'Rejected' => $allTasks->where('status', 'Rejected')->count(),
        ];

        // Project progress for chart
        $projectChartData = $projects->map(function($project) {
            return [
                'name' => $project->title,
                'total' => $project->total_tasks,
                'completed' => $project->completed_tasks,
                'progress' => $project->progress_percentage
            ];
        });

        return view('pages.user.dashboard.userHome', compact(
            'projects',
            'statusStats',
            'projectChartData',

        ));
    }

    /*------------------------------------------
    --------------------------------------------
    Task Detail & Submission
    --------------------------------------------*/
    public function taskDetail($taskId)
    {
        $user = Auth::user();

        $task = Task::with([
            'project',
            'assignee',
            'creator',
            'submissions' => function($query) use ($user) {
                $query->where('employee_id', $user->id)
                      ->with(['files', 'reviews.reviewer']) // ✅ tambahkan files
                      ->orderBy('created_at', 'asc');
            }
        ])
        ->where('id', $taskId)
        ->where('assigned_to', $user->id)
        ->firstOrFail();

        $submission = $task->submissions->last();

        // ============================================================
        // BUAT KOLEKSI PESAN TERURUT (Submission + Review)
        // ============================================================
        $messages = collect();

        foreach ($task->submissions as $sub) {
            // Simpan teks submission untuk referensi reply
            $submissionText = $sub->notes ?? 'Tidak ada catatan';

            // Tambahkan submission (pesan dari karyawan)
            $messages->push((object) [
                'id' => $sub->id,
                'type' => 'submission',
                'user_name' => $user->name,
                'message' => $submissionText,
                'created_at' => $sub->created_at,
                'is_employee' => true,
                'status' => $sub->status,
                'reply_to' => null, // tidak ada reply
                'files' => $sub->files, // tambahkan files ke object
                'is_deleted' => $sub->is_deleted,
                'review_status' => $sub->review_status,
            ]);

            // Tambahkan review (feedback dari atasan)
            foreach ($sub->reviews as $review) {
                $messages->push((object) [
                    'type' => 'review',
                    'user_name' => $review->reviewer->name ?? 'Atasan',
                    'message' => $review->feedback_notes ?? 'Tidak ada feedback',
                    'created_at' => $review->created_at,
                    'is_employee' => false,
                    'status' => $review->status,
                    'reviewer' => $review->reviewer,
                    'reply_to' => $submissionText, // ✅ Isi dengan teks submission yang direspon
                ]);
            }
        }

        // Urutkan berdasarkan waktu (dari yang paling tua ke terbaru)
        $messages = $messages->sortBy('created_at')->values();

        return view('pages.user.tasks.detail', compact('task', 'submission', 'messages'));
    }

    // public function submitTaskNotes(Request $request)
    // {
    //     $request->validate([
    //         'task_id' => 'required|exists:tasks,id',
    //         'notes' => 'nullable|string|max:1000',
    //     ]);

    //     $task = Task::findOrFail($request->task_id);

    //     // Cek apakah task ini milik user yang login
    //     if ($task->assigned_to != Auth::id()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Anda tidak memiliki akses untuk tugas ini.'
    //         ], 403);
    //     }

    //     // Buat submission baru
    //     $submission = \App\Models\TaskSubmission::create([
    //         'task_id' => $task->id,
    //         'employee_id' => Auth::id(),
    //         'notes' => $request->notes,
    //         'status' => 'pending'
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Catatan berhasil dikirim!',
    //         'data' => $submission
    //     ]);
    // }



    public function tasksIndex()
    {
        $user = Auth::user();
        $tasks = Task::with(['project', 'assignee'])
            ->where('assigned_to', $user->id)
            ->orderBy('deadline', 'asc')
            ->get();

        return view('pages.user.tasks.index', compact('tasks'));
    }

    // public function updateTaskStatus(Request $request)
    // {
    //     $request->validate([
    //         'task_id' => 'required|exists:tasks,id',
    //         'status' => 'required|in:Pending,On Progress,Completed'
    //     ]);

    //     $task = Task::findOrFail($request->task_id);

    //     // Cek apakah tugas ini milik user yang login
    //     if ($task->assigned_to != Auth::id()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Anda tidak memiliki akses untuk mengubah tugas ini.'
    //         ], 403);
    //     }

    //     $task->status = $request->status;
    //     $task->save();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Status tugas berhasil diupdate.'
    //     ]);
    // }

    /**
     * Update status tugas (Pending -> On Progress -> Completed)
     */
    // public function updateTaskStatus(Request $request)
    // {
    //     $request->validate([
    //         'task_id' => 'required|exists:tasks,id',
    //         'status' => 'required|in:Pending,On Progress,Completed'
    //     ]);

    //     $userId = Auth::id();
    //     $task = Task::findOrFail($request->task_id);

    //     // ✅ Validasi: hanya user yang ditugaskan yang bisa update status
    //     if ($task->assigned_to != $userId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Anda tidak memiliki akses untuk mengubah status tugas ini!'
    //         ], 403);
    //     }

    //     // ✅ Validasi: hanya bisa maju, tidak bisa mundur
    //     $statusOrder = ['Pending' => 0, 'On Progress' => 1, 'Completed' => 2];
    //     $currentStatus = $statusOrder[$task->status] ?? 0;
    //     $newStatus = $statusOrder[$request->status] ?? 0;

    //     if ($newStatus < $currentStatus) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Tidak bisa mengubah status ke status sebelumnya!'
    //         ], 422);
    //     }

    //     // Update status
    //     $task->update([
    //         'status' => $request->status
    //     ]);

    //     // ✅ Update progress project secara otomatis
    //     // $this->updateProjectProgress($task->project_id);
    //     // gk pake progress ini, dinamik langsung aja dengan (x) selesai / (x)total tugas

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Status tugas berhasil diupdate menjadi ' . $request->status . '!',
    //         'new_status' => $request->status
    //     ]);
    // }

    /**
     * Update status tugas (Pending -> On Progress) untuk user biasa
     * (Hanya bisa dari Pending ke On Progress)
     */
    public function updateTaskStatus(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:On Progress,Review,Completed,Rejected'
        ]);

        $userId = Auth::id();
        $task = Task::findOrFail($request->task_id);

        // Pastiin user yang ditugaskan yang bisa update status
        if ($task->assigned_to != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah status tugas ini!'
            ], 403);
        }

        // Validasi transisi status yang diizinkan untuk user
        $allowedTransitions = [
            'Pending' => ['On Progress'],
            'On Progress' => ['Review'],
            'Review' => [], // User tidak bisa mengubah dari Review
            'Rejected' => ['On Progress'],
            'Completed' => [], // User tidak bisa mengubah dari Completed
        ];

        $currentStatus = $task->status;
        $newStatus = $request->status;

        // Cek apakah transisi diizinkan
        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            $allowed = !empty($allowedTransitions[$currentStatus])
                ? implode(', ', $allowedTransitions[$currentStatus])
                : 'tidak ada';

            return response()->json([
                'success' => false,
                'message' => "Status saat ini adalah '{$currentStatus}'. Anda hanya dapat mengubah ke: {$allowed}."
            ], 403);
        }

        // Jika status 'Review', kirim notifikasi ke manager
        // if ($newStatus == 'Review') {
        //     Kirim notifikasi ke manager
        //     Contoh: event(new TaskReviewed($task));
        //     Atau simpan di tabel notifikasi
        //     Notification::send($task->project->manager, new TaskReviewNotification($task));
        // }

        $task->update([
            'status' => $newStatus
        ]);



        // Batasin dari Pending ke On Progress untuk user biasa
        // if ($task->status == 'Pending' && $request->status == 'On Progress') {
        // } else {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Anda hanya dapat mengubah status dari Pending ke On Progress. Untuk menyelesaikan tugas, hubungi manajer Anda.'
        //     ], 403);
        // }

        // Jika status 'Review', kirim notifikasi ke manager
        // if ($request->status == 'Review') {
        //     Kirim notifikasi ke manager (bisa via email, database, atau event)
        //     Contoh: event(new TaskReviewed($task));
        //     Atau simpan di tabel notifikasi
        // }

        // $task->update([
        //     'status' => $request->status
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diupdate menjadi ' . $newStatus . '!',
            'new_status' => $request->status
        ]);
    }

    public function projectsIndex()
    {
        $user = Auth::user();

        // Ambil semua proyek yang memiliki tugas yang ditugaskan ke user ini
        $projects = Project::whereHas('tasks', function($query) use ($user) {
            $query->where('assigned_to', $user->id);
        })
        ->with(['tasks' => function($query) use ($user) {
            $query->where('assigned_to', $user->id);
        }, 'manager', 'category'])
        ->get()
        ->map(function($project) use ($user) {
            // Hitung statistik tugas untuk proyek ini
            $userTasks = $project->tasks->where('assigned_to', $user->id);
            $project->total_tasks = $userTasks->count();
            $project->pending_tasks = $userTasks->where('status', 'Pending')->count();
            $project->on_progress_tasks = $userTasks->where('status', 'On Progress')->count();
            $project->completed_tasks = $userTasks->where('status', 'Completed')->count();

            // Hitung progress user di proyek ini (berdasarkan bobot)
            $totalWeight = $userTasks->sum('weight');
            $completedWeight = $userTasks->where('status', 'Completed')->sum('weight');
            $project->user_progress = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100) : 0;

            return $project;
        });

        return view('pages.user.projects.index', compact('projects'));
    }

    /*------------------------------------------
    Daftar Tugas dalam Proyek (filter user)
    --------------------------------------------*/
    public function projectTasks($projectId)
    {
        $userId = Auth::id();

        $project = Project::with(['manager'])
            ->whereHas('tasks', function ($query) use ($userId) {
                $query->where('assigned_to', $userId);
            })
            ->findOrFail($projectId);

        // Ambil tugas user dalam proyek ini
        $tasks = Task::with(['assignee', 'creator'])
            ->where('project_id', $projectId)
            ->where('assigned_to', $userId)
            ->orderBy('deadline', 'asc')
            ->get();

        return view('pages.user.tasks.index', compact('project', 'tasks'));
    }

    // Upload file bukti / submission files
    public function submitTaskNotes(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'notes' => 'nullable|string|max:1000',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120',
        ]);

        $task = Task::findOrFail($request->task_id);

        if ($task->assigned_to != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk tugas ini.'
            ], 403);
        }

        // Buat submission
        $submission = TaskSubmission::create([
            'task_id' => $task->id,
            'employee_id' => Auth::id(),
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        // Upload file jika ada
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Simpan di storage/public/submissions
                $path = $file->store('submissions', 'public');

                SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil dikirim!',
            'data' => $submission
        ]);
    }

    // Hapus task_submission
    public function deleteSubmission(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:task_submissions,id'
        ]);

        $submission = TaskSubmission::findOrFail($request->submission_id);

        // Cek kepemilikan
        if ($submission->employee_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak menghapus pesan ini.'], 403);
        }

        // Cek apakah sudah direview (gunakan aksesoris review_status)
        if ($submission->review_status != 'pending') {
            return response()->json(['success' => false, 'message' => 'Pesan sudah direview, tidak dapat dihapus.'], 422);
        }

        // Cek apakah sudah dihapus
        if ($submission->is_deleted) {
            return response()->json(['success' => false, 'message' => 'Pesan sudah dihapus.'], 422);
        }

        // Hapus file-file terkait
        foreach ($submission->files as $file) {
            // Hapus file fisik dari storage
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            // Hapus record
            $file->delete();
        }

        // Update submission
        $submission->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'notes' => null // opsional, karena accessor akan mengembalikan "Pesan dihapus"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dihapus.'
        ]);
    }


    /*------------------------------------------
    --------------------------------------------
    profile & password
    --------------------------------------------*/
    public function profile()
    {
        // Ambil user dengan relasi yang diperlukan
        $user = User::with(['branch'])->find(Auth::id());

        return view('pages.user.profile', compact('user'));
    }
    public function changePassword()
    {
        return view('pages.user.change_password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Retrieve users based on the ID that is currently logged in
        $user = User::find(Auth::id());

        if (!$user) {
            return redirect()->route('user.changePassword')->with('error', 'User tidak ditemukan');
        }

        // Check if the old password matches
        if (Hash::check($request->old_password, $user->password)) {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Logout user after successful password change
            Auth::logout();

            // Invalidate the session
            $request->session()->invalidate();

            // Regenerate CSRF token
            $request->session()->regenerateToken();

            // Redirect to login page with success message
            return redirect()->route('login')->with('success', 'Kata sandi berhasil diubah. Silakan login kembali dengan kata sandi baru.');
        }

        return redirect()->route('user.changePassword')->with('error', 'Kata sandi lama salah.');
    }
}
